<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit();
}

// Get admin information from database
require_once 'connect.php'; // Make sure you have this file for database connection

$admin_username = $_SESSION['admin'];
$stmt = $conn->prepare("SELECT fullname, profile_picture FROM admin WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();



if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $admin_fullname = $row['fullname'];
  $admin_profile = $row['profile_picture'];
}
$stmt->close();

// Function to load and parse XML
function loadTransactions() {
    $xml = simplexml_load_file('transactions.xml');
    if ($xml === false) {
        die('Error loading XML file');
    }
    return $xml;
}

// Function to get metrics grouped by date - ONLY for approved transactions
function getMetricsByDate($transactions) {
    $metricsByDate = [];
    
    foreach ($transactions->transaction as $transaction) {
        // ONLY process approved transactions
        if ((string)$transaction->status !== 'approved') {
            continue; // Skip transactions that are not approved
        }
        
        $date = date('Y-m-d', strtotime((string)$transaction['date']));
        
        if (!isset($metricsByDate[$date])) {
            $metricsByDate[$date] = [
                'transactions' => 0,
                'sales' => 0,
                'users' => [],
                'products' => 0
            ];
        }
        
        // Increment transaction count
        $metricsByDate[$date]['transactions']++;
        
        // Add sales amount
        $metricsByDate[$date]['sales'] += (float)$transaction->total_amount;
        
        // Track unique users
        $userId = (string)$transaction['user_id'];
        if (!in_array($userId, $metricsByDate[$date]['users'])) {
            $metricsByDate[$date]['users'][] = $userId;
        }
        
        // Count products sold
        foreach ($transaction->items->item as $item) {
            $metricsByDate[$date]['products'] += (int)$item->quantity;
        }
    }
    
    // Convert users arrays to counts
    foreach ($metricsByDate as $date => $metrics) {
        $metricsByDate[$date]['users'] = count($metrics['users']);
    }
    
    return $metricsByDate;
}

// Check if this is an AJAX refresh request
if (isset($_GET['refresh']) && $_GET['refresh'] === 'true') {
    header('Content-Type: application/json');
    
    // Load transactions and calculate metrics
    $transactions = loadTransactions();
    $metricsByDate = getMetricsByDate($transactions);
    
    // Sort dates chronologically
    ksort($metricsByDate);
    
    // Limit to last 7 days
    $dates = array_keys($metricsByDate);
    $recentDates = count($dates) > 7 ? array_slice($dates, -7) : $dates;
    
    // Filter metrics to just those dates
    $recentMetrics = [];
    foreach ($recentDates as $date) {
        $recentMetrics[$date] = $metricsByDate[$date];
    }
    
    // Get total metrics - ONLY for approved transactions
    $totalTransactions = 0;
    $totalSales = 0;
    $uniqueUsers = [];
    $productsSold = 0;

    foreach ($transactions->transaction as $transaction) {
        // Skip transactions that are not approved
        if ((string)$transaction->status !== 'approved') {
            continue;
        }
        
        $totalTransactions++;
        $totalSales += (float)$transaction->total_amount;
        
        $userId = (string)$transaction['user_id'];
        if (!in_array($userId, $uniqueUsers)) {
            $uniqueUsers[] = $userId;
        }
        
        foreach ($transaction->items->item as $item) {
            $productsSold += (int)$item->quantity;
        }
    }
    
    $uniqueUsersCount = count($uniqueUsers);
    
    // Format data for charts
    $chartDates = array_keys($recentMetrics);
    $chartTransactions = array_column($recentMetrics, 'transactions');
    $chartSales = array_column($recentMetrics, 'sales');
    $chartUsers = array_column($recentMetrics, 'users');
    $chartProducts = array_column($recentMetrics, 'products');
    
    // Prepare response data
    $responseData = [
        'totalTransactions' => $totalTransactions,
        'totalSales' => $totalSales,
        'uniqueUsersCount' => $uniqueUsersCount,
        'productsSold' => $productsSold,
        'dates' => $chartDates,
        'transactions' => $chartTransactions,
        'sales' => $chartSales,
        'users' => $chartUsers,
        'products' => $chartProducts,
        'dailyTotals' => $recentMetrics
    ];
    
    echo json_encode($responseData);
    exit();
}

// Load transactions from XML
$transactions = loadTransactions();

// Get metrics by date - ONLY for approved transactions
$metricsByDate = getMetricsByDate($transactions);

// Sort dates chronologically
ksort($metricsByDate);

// Limit to last 7 days or fewer if we don't have that much data
$dates = array_keys($metricsByDate);
$recentDates = count($dates) > 7 ? array_slice($dates, -7) : $dates;

// Filter metrics to just those dates
$recentMetrics = [];
foreach ($recentDates as $date) {
    $recentMetrics[$date] = $metricsByDate[$date];
}

// Get total metrics - ONLY for approved transactions
$totalTransactions = 0;
$totalSales = 0;
$uniqueUsers = [];
$productsSold = 0;

foreach ($transactions->transaction as $transaction) {
    // Skip transactions that are not approved
    if ((string)$transaction->status !== 'approved') {
        continue;
    }
    
    $totalTransactions++;
    $totalSales += (float)$transaction->total_amount;
    
    $userId = (string)$transaction['user_id'];
    if (!in_array($userId, $uniqueUsers)) {
        $uniqueUsers[] = $userId;
    }
    
    foreach ($transaction->items->item as $item) {
        $productsSold += (int)$item->quantity;
    }
}

$uniqueUsersCount = count($uniqueUsers);

// Format data for charts
$chartDates = array_keys($recentMetrics);
$chartTransactions = array_column($recentMetrics, 'transactions');
$chartSales = array_column($recentMetrics, 'sales');
$chartUsers = array_column($recentMetrics, 'users');
$chartProducts = array_column($recentMetrics, 'products');

// Calculate daily totals for display under charts
$dailyTotals = [];
foreach ($recentMetrics as $date => $metrics) {
    $dailyTotals[$date] = [
        'transactions' => $metrics['transactions'],
        'sales' => $metrics['sales'],
        'users' => $metrics['users'],
        'products' => $metrics['products']
    ];
}

// Convert data to JSON for JavaScript
$datesJson = json_encode($chartDates);
$transactionsJson = json_encode($chartTransactions);
$salesJson = json_encode($chartSales);
$usersJson = json_encode($chartUsers);
$productsJson = json_encode($chartProducts);
$dailyTotalsJson = json_encode($dailyTotals);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>La Croissanterie Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .charts-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-top: 30px;
}

@media (max-width: 1200px) {
  .charts-row {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chart-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.chart-header {
  padding: 15px 20px;
  color: white;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chart-header h5 {
  margin: 0;
  font-size: 18px;
  letter-spacing: 0.5px;
}

.chart-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.chart-container {
  height: 200px;
  margin-bottom: 15px;
  position: relative;
}

.daily-totals {
  margin-top: 10px;
  padding-top: 15px;
  border-top: 1px solid #eaeaea;
}

.daily-totals h6 {
  margin: 0 0 10px 0;
  font-size: 15px;
  color: #555;
  font-weight: 600;
}

.daily-total-item {
  display: inline-block;
  margin-right: 15px;
  margin-bottom: 8px;
  font-size: 13px;
  background-color: #f8f9fa;
  padding: 5px 10px;
  border-radius: 15px;
  color: #444;
}

/* Colors for chart headers */
.bg-primary {
  background-color: rgba(13, 110, 253, 0.9);
}

.bg-success {
  background-color: rgba(25, 135, 84, 0.9);
}

.bg-info {
  background-color: rgba(13, 202, 240, 0.9);
}

.bg-warning {
  background-color: rgba(255, 193, 7, 0.9);
}

/* Refresh button styling */
.refresh-btn {
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 8px 16px;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s ease;
  margin-bottom: 15px;
  align-self: flex-end;
}

.refresh-btn:hover {
  background-color: #5c636a;
}

.refresh-btn i {
  font-size: 14px;
}

/* Dashboard metrics styling */
.metrics-container {
  display: flex;
  flex-direction: column;
}

.d-flex {
  display: flex;
}

.justify-content-end {
  justify-content: flex-end;
}

.mb-3 {
  margin-bottom: 1rem;
}

/* Metrics summary cards */
.metrics-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 20px;
}

@media (max-width: 992px) {
  .metrics-summary {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 576px) {
  .metrics-summary {
    grid-template-columns: 1fr;
  }
}

.metric-card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  padding: 20px;
  display: flex;
  align-items: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.metric-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  flex-shrink: 0;
}

.metric-icon i {
  color: white;
  font-size: 20px;
}

.metric-info h3 {
  margin: 0;
  font-size: 14px;
  color: #6c757d;
  font-weight: 600;
}

.metric-info h2 {
  margin: 5px 0;
  font-size: 24px;
  font-weight: 700;
  color: #343a40;
}

.metric-info p {
  margin: 0;
  font-size: 12px;
  color: #6c757d;
}
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    <div class="sidebar-menu">
      <a href="admindashboard.php" class="menu-item active">
        <i class="fas fa-tachometer-alt"></i>
        <span class="menu-text">Dashboard</span>
      </a>
      <a href="product.php" class="menu-item">
        <i class="fas fa-box"></i>
        <span class="menu-text">Products</span>
      </a>
      <a href="user_list.php" class="menu-item">
        <i class="fas fa-users"></i>
        <span class="menu-text">Users</span>
      </a>
      <a href="adminorder.php" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-text">Orders</span>
      </a>
      <a href="adminTransaction.php" class="menu-item">
        <i class="fas fa-money-bill-wave"></i>
        <span class="menu-text">Transactions</span>
      </a>
      <a href="adminSetting.php" class="menu-item">
        <i class="fas fa-cog"></i>
        <span class="menu-text">Settings</span>
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h1 class="page-title">Dashboard</h1>
      <div class="user-info">
        <?php if(!empty($admin_profile)): ?>
          <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Profile" class="profile-pic">
        <?php endif; ?>
        
        <a href="#" onclick="confirmLogout()" class="logout-btn">Logout</a>

        <script>
        function confirmLogout() {
          if (confirm("Are you sure you want to logout?")) {
            window.location.href = "logout.php";
          }
        }
        </script>
      </div>
    </div>

    <div class="dashboard-content">
      <div class="welcome-message">
        <h2>Welcome, <?php echo htmlspecialchars($admin_username); ?>!</h2>
        <p>Here's your daily overview of La Croissanterie's performance.</p>
      </div>
      
      <!-- Dashboard Metrics -->
      <div class="metrics-container">
        <div class="d-flex justify-content-end mb-3">
          <button id="refreshData" class="refresh-btn">
            <i class="fas fa-sync-alt"></i> Refresh Data
          </button>
        </div>
        
        <!-- Summary Cards -->
        <div class="metrics-summary">
          <div class="metric-card">
            <div class="metric-icon bg-primary">
              <i class="fas fa-receipt"></i>
            </div>
            <div class="metric-info">
              <h3>Transactions</h3>
              <h2 id="totalTransactionsValue"><?= $totalTransactions ?></h2>
              <p>Total transactions</p>
            </div>
          </div>
          
          <div class="metric-card">
            <div class="metric-icon bg-success">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="metric-info">
              <h3>Sales</h3>
              <h2 id="totalSalesValue">₱<?= number_format($totalSales, 2) ?></h2>
              <p>Total revenue</p>
            </div>
          </div>
          
          <div class="metric-card">
            <div class="metric-icon bg-info">
              <i class="fas fa-users"></i>
            </div>
            <div class="metric-info">
              <h3> Customer</h3>
              <h2 id="uniqueUsersValue"><?= $uniqueUsersCount ?></h2>
              <p>Unique customers</p>
            </div>
          </div>
          
          <div class="metric-card">
            <div class="metric-icon bg-warning">
              <i class="fas fa-box"></i>
            </div>
            <div class="metric-info">
              <h3>Products</h3>
              <h2 id="productsSoldValue"><?= $productsSold ?></h2>
              <p>Items sold</p>
            </div>
          </div>
        </div>
        
        <!-- Charts Row -->
        <div class="charts-row">
          <div class="chart-card">
            <div class="chart-header bg-primary">
              <h5>Transactions</h5>
            </div>
            <div class="chart-body">
              <div class="chart-container">
                <canvas id="transactionsChart"></canvas>
              </div>
              <div class="daily-totals" id="transactionsTotals">
                <h6>Daily Transactions:</h6>
                <div id="dailyTransactionsList"></div>
              </div>
            </div>
          </div>
          
          <div class="chart-card">
            <div class="chart-header bg-success">
              <h5>Sales</h5>
            </div>
            <div class="chart-body">
              <div class="chart-container">
                <canvas id="salesChart"></canvas>
              </div>
              <div class="daily-totals" id="salesTotals">
                <h6>Daily Sales:</h6>
                <div id="dailySalesList"></div>
              </div>
            </div>
          </div>
          
          <div class="chart-card">
            <div class="chart-header bg-info">
              <h5>Users</h5>
            </div>
            <div class="chart-body">
              <div class="chart-container">
                <canvas id="usersChart"></canvas>
              </div>
              <div class="daily-totals" id="usersTotals">
                <h6>Daily Active Users:</h6>
                <div id="dailyUsersList"></div>
              </div>
            </div>
          </div>
          
          <div class="chart-card">
            <div class="chart-header bg-warning">
              <h5>Products</h5>
            </div>
            <div class="chart-body">
              <div class="chart-container">
                <canvas id="productsChart"></canvas>
              </div>
              <div class="daily-totals" id="productsTotals">
                <h6>Daily Products Sold:</h6>
                <div id="dailyProductsList"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Chart configuration function
    function createChart(elementId, label, data, dates, borderColor, backgroundColor) {
        const ctx = document.getElementById(elementId).getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: borderColor,
                    backgroundColor: backgroundColor,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Format dates for display
    function formatDisplayDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    // Function to populate daily totals
    function populateDailyTotals(dailyTotals, dates) {
        const transactionsList = document.getElementById('dailyTransactionsList');
        const salesList = document.getElementById('dailySalesList');
        const usersList = document.getElementById('dailyUsersList');
        const productsList = document.getElementById('dailyProductsList');
        
        transactionsList.innerHTML = '';
        salesList.innerHTML = '';
        usersList.innerHTML = '';
        productsList.innerHTML = '';
        
        dates.forEach(date => {
            const displayDate = formatDisplayDate(date);
            const metrics = dailyTotals[date];
            
            // Add transaction count
            const transactionItem = document.createElement('span');
            transactionItem.className = 'daily-total-item';
            transactionItem.textContent = `${displayDate}: ${metrics.transactions}`;
            transactionsList.appendChild(transactionItem);
            
            // Add sales
            const salesItem = document.createElement('span');
            salesItem.className = 'daily-total-item';
            salesItem.textContent = `${displayDate}: ₱${metrics.sales.toFixed(2)}`;
            salesList.appendChild(salesItem);
            
            // Add users
            const usersItem = document.createElement('span');
            usersItem.className = 'daily-total-item';
            usersItem.textContent = `${displayDate}: ${metrics.users}`;
            usersList.appendChild(usersItem);
            
            // Add products
            const productsItem = document.createElement('span');
            productsItem.className = 'daily-total-item';
            productsItem.textContent = `${displayDate}: ${metrics.products}`;
            productsList.appendChild(productsItem);
        });
    }

    // Create charts
    const dates = <?= $datesJson ?>;
    const displayDates = dates.map(formatDisplayDate);
    const dailyTotals = <?= $dailyTotalsJson ?>;
    
    const transactionsChart = createChart(
        'transactionsChart', 
        'Total Transactions', 
        <?= $transactionsJson ?>, 
        displayDates, 
        'rgba(13, 110, 253, 1)', 
        'rgba(13, 110, 253, 0.1)'
    );
    
    const salesChart = createChart(
        'salesChart', 
        'Total Sales', 
        <?= $salesJson ?>, 
        displayDates, 
        'rgba(25, 135, 84, 1)', 
        'rgba(25, 135, 84, 0.1)'
    );
    
    const usersChart = createChart(
        'usersChart', 
        'Unique Users', 
        <?= $usersJson ?>, 
        displayDates, 
        'rgba(13, 202, 240, 1)', 
        'rgba(13, 202, 240, 0.1)'
    );
    
    const productsChart = createChart(
        'productsChart', 
        'Products Sold', 
        <?= $productsJson ?>, 
        displayDates, 
        'rgba(255, 193, 7, 1)', 
        'rgba(255, 193, 7, 0.1)'
    );
    
    // Populate daily totals
    populateDailyTotals(dailyTotals, dates);
    
    // Function to refresh data from the server
    function refreshData() {
        // Show loading icon
        const refreshBtn = document.getElementById('refreshData');
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        refreshBtn.disabled = true;
        
        // AJAX request to fetch updated data
        fetch('admindashboard.php?refresh=true')
            .then(response => response.json())
            .then(data => {
                // Update the displayed values
                document.getElementById('totalTransactionsValue').textContent = data.totalTransactions;
                document.getElementById('totalSalesValue').textContent = '₱' + data.totalSales.toFixed(2);
                document.getElementById('uniqueUsersValue').textContent = data.uniqueUsersCount;
                document.getElementById('productsSoldValue').textContent = data.productsSold;
                
                // Update charts
                const displayDates = data.dates.map(formatDisplayDate);
                
                transactionsChart.data.labels = displayDates;
                transactionsChart.data.datasets[0].data = data.transactions;
                transactionsChart.update();
                
                salesChart.data.labels = displayDates;
                salesChart.data.datasets[0].data = data.sales;
                salesChart.update();
                
                usersChart.data.labels = displayDates;
                usersChart.data.datasets[0].data = data.users;
                usersChart.update();
                
                productsChart.data.labels = displayDates;
                productsChart.data.datasets[0].data = data.products;
                productsChart.update();
                
                // Update daily totals
                populateDailyTotals(data.dailyTotals, data.dates);
                
                // Reset the button
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                refreshBtn.disabled = false;
                
                // Show a success message
                alert('Dashboard data has been refreshed successfully!');
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
                alert('Failed to refresh dashboard data. Please try again.');
                
                // Reset the button
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Data';
                refreshBtn.disabled = false;
            });
    }
    
    // Add event listener to refresh button
    document.getElementById('refreshData').addEventListener('click', refreshData);
  </script>
</body>
</html>
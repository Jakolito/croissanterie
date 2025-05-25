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

$admin_fullname = "Admin User"; // Default value
$admin_profile = ""; // Default empty profile picture path

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $admin_fullname = $row['fullname'];
  $admin_profile = $row['profile_picture'];
}
$stmt->close();

// Function to load and parse XML
function loadTransactions() {
    // In a real application, you might load from a database instead
    $xml = simplexml_load_file('transactions.xml');
    if ($xml === false) {
        die('Error loading XML file');
    }
    return $xml;
}

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('M d, Y h:i A');
}

// Function to get user count by product (only for approved transactions)
function getUsersByProduct($transactions) {
    $productUsers = [];
    
    foreach ($transactions->transaction as $transaction) {
        // Skip if not approved
        if ((string)$transaction->status !== 'approved') {
            continue;
        }
        
        foreach ($transaction->items->item as $item) {
            $productId = (string)$item['product_id'];
            $userId = (string)$transaction['user_id'];
            
            if (!isset($productUsers[$productId])) {
                $productUsers[$productId] = [];
            }
            
            if (!in_array($userId, $productUsers[$productId])) {
                $productUsers[$productId][] = $userId;
            }
        }
    }
    
    $results = [];
    foreach ($productUsers as $productId => $users) {
        $results[$productId] = count($users);
    }
    
    return $results;
}

// Function to get daily sales data
function getDailySales($transactions) {
    $dailySales = [];
    
    foreach ($transactions->transaction as $transaction) {
        if ((string)$transaction->status === 'approved') {
            $date = date('Y-m-d', strtotime((string)$transaction['date']));
            
            if (!isset($dailySales[$date])) {
                $dailySales[$date] = [
                    'date' => $date,
                    'sales' => 0,
                    'transactions' => 0
                ];
            }
            
            $dailySales[$date]['sales'] += (float)$transaction->total_amount;
            $dailySales[$date]['transactions']++;
        }
    }
    
    // Sort by date
    ksort($dailySales);
    return array_values($dailySales);
}

// Function to get monthly sales data
function getMonthlySales($transactions) {
    $monthlySales = [];
    
    foreach ($transactions->transaction as $transaction) {
        if ((string)$transaction->status === 'approved') {
            $month = date('Y-m', strtotime((string)$transaction['date']));
            
            if (!isset($monthlySales[$month])) {
                $monthlySales[$month] = [
                    'month' => $month,
                    'sales' => 0,
                    'transactions' => 0
                ];
            }
            
            $monthlySales[$month]['sales'] += (float)$transaction->total_amount;
            $monthlySales[$month]['transactions']++;
        }
    }
    
    // Sort by month
    ksort($monthlySales);
    return array_values($monthlySales);
}

// Load transactions
$transactions = loadTransactions();

// Count only approved transactions
$approvedTransactionsCount = 0;

// Get total sales (only from approved transactions)
$totalSales = 0;
foreach ($transactions->transaction as $transaction) {
    if ((string)$transaction->status === 'approved') {
        $totalSales += (float)$transaction->total_amount;
        $approvedTransactionsCount++;
    }
}

// Get product popularity (only from approved transactions)
$productSales = [];
$productUsers = getUsersByProduct($transactions);

foreach ($transactions->transaction as $transaction) {
    // Skip if not approved
    if ((string)$transaction->status !== 'approved') {
        continue;
    }
    
    foreach ($transaction->items->item as $item) {
        $productId = (string)$item['product_id'];
        $productName = (string)$item->name;
        
        if (!isset($productSales[$productId])) {
            $productSales[$productId] = [
                'name' => $productName,
                'quantity' => 0,
                'revenue' => 0
            ];
        }
        
        $productSales[$productId]['quantity'] += (int)$item->quantity;
        $productSales[$productId]['revenue'] += (float)$item->total;
    }
}

// Get user activity (only from approved transactions)
$userOrders = [];
foreach ($transactions->transaction as $transaction) {
    // Skip if not approved
    if ((string)$transaction->status !== 'approved') {
        continue;
    }
    
    $userId = (string)$transaction['user_id'];
    
    if (!isset($userOrders[$userId])) {
        $userOrders[$userId] = [
            'count' => 0,
            'total' => 0
        ];
    }
    
    $userOrders[$userId]['count']++;
    $userOrders[$userId]['total'] += (float)$transaction->total_amount;
}

// Sort by most active users
arsort($userOrders);

// Get report data
$dailySales = getDailySales($transactions);
$monthlySales = getMonthlySales($transactions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Croissanterie Admin Transactions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .dashboard-card {
            transition: transform 0.3s;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card-header {
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 400;
            color: var(--primary-color);
            margin: 0;
        }
        .bg-primary {
            background-color: #4e73df;
            color: white;
        }
        .bg-success {
            background-color: #1cc88a;
            color: white;
        }
        .bg-info {
            background-color: #36b9cc;
            color: white;
        }
        .bg-warning {
            background-color: #f6c23e;
            color: #2e2f37;
        }
        .bg-dark {
            background-color: var(--dark-color);
            color: white;
        }
        .card-body h5 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .card-body h2 {
            font-size: 26px;
            margin: 0;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: var(--light-color);
            font-weight: 400;
            color: var(--primary-color);
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: normal;
        }
        .bg-success {
            background-color: #1cc88a;
        }
        .bg-warning {
            background-color: #f6c23e;
        }
        .btn-info {
            background-color: #36b9cc;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-secondary {
            background-color: #858796;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .btn-primary:hover {
            background-color: #3b5bdb;
        }
        .modal-header, .modal-footer {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
        }
        .modal-body {
            padding: 15px;
        }
        strong {
            font-weight: 600;
        }
        .btn-close {
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Report Modal Styles */
        .report-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .report-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 0;
            border: none;
            width: 90%;
            max-width: 1200px;
            border-radius: 8px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .report-section {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background:  rgba(85, 39, 12, 0.83);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .report-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
            background: #f8f9fc;
            border-radius: 8px;
            border-left: 4px solid #4e73df;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4e73df;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .report-table {
            margin-top: 20px;
        }
        
        .btn-download {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .btn-download:hover {
            background-color: #c0392b;
        }
        
        .filter-section {
            background: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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
      <a href="admindashboard.php" class="menu-item">
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
      <a href="adminTransaction.php" class="menu-item active">
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
      <h1 class="page-title">Transaction Management</h1>
      <div class="user-info">
        <?php if(!empty($admin_profile)): ?>
          <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Profile" class="profile-pic">
        <?php endif; ?>
        <a href="#" onclick="confirmLogout()" class="logout-btn">Logout</a>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Summary Cards -->
      <div class="dashboard-cards">
        <div class="dashboard-card bg-primary">
            <div class="card-body">
                <h5 class="card-title">Approved Transactions</h5>
                <h2><?= $approvedTransactionsCount ?></h2>
            </div>
        </div>
        <div class="dashboard-card bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Sales</h5>
                <h2>₱<?= number_format($totalSales, 2) ?></h2>
            </div>
        </div>
        <div class="dashboard-card bg-warning">
            <div class="card-body">
                <h5 class="card-title">Products Sold</h5>
                <h2><?= array_sum(array_column($productSales, 'quantity')) ?></h2>
            </div>
        </div>
      </div>
      
      <!-- Transactions Table -->
      <div class="table-container">
        <div class="table-header">
            <h5 class="table-title">All Transactions</h5>
            <div class="search-container">
                <input type="text" placeholder="Search..." class="search-input" id="transactionSearch">
                <button class="search-btn"><i class="fas fa-search"></i></button>
                <button class="btn-primary" onclick="showReportModal()">
                    <i class="fas fa-chart-line"></i> Generate Report
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Products</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions->transaction as $transaction): ?>
                    <tr>
                        <td><?= $transaction['id'] ?></td>
                        <td><?= $transaction['order_id'] ?></td>
                        <td><?= $transaction['user_id'] ?></td>
                        <td><?= formatDate($transaction['date']) ?></td>
                        <td><?= ucfirst($transaction->payment_method) ?></td>
                        <td>
                            <?php foreach ($transaction->items->item as $item): ?>
                                <div class="mb-1">
                                    <?= $item->name ?> (x<?= $item->quantity ?>)
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td>₱<?= number_format((float)$transaction->total_amount, 2) ?></td>
                        <td>
                            <span class="badge bg-<?= ((string)$transaction->status === 'approved') ? 'success' : 'warning' ?>">
                                <?= ucfirst($transaction->status) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-info" onclick="viewDetails('<?= $transaction['id'] ?>')">Details</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
      </div>

  <!-- Modal for Transaction Details -->
  <div class="modal" id="transactionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Transaction Details</h5>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="transactionDetails">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
  </div>

  <!-- Report Modal -->
  <div class="report-modal" id="reportModal">
    <div class="report-modal-content">
        <div class="report-header">
            <h2>La Croissanterie - Transaction Report</h2>
            <p>Generated on <?= date('F d, Y h:i A') ?></p>
        </div>
        
        <div class="modal-body">
            
            <div class="filter-section">
                <h5>Report Filters</h5>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Date From:</label>
                        <input type="date" id="dateFrom" onchange="applyFilters()">
                    </div>
                    <div class="filter-group">
                        <label>Date To:</label>
                        <input type="date" id="dateTo" onchange="applyFilters()">
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="statusFilter" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Payment Method:</label>
                        <select id="paymentFilter" onchange="applyFilters()">
                            <option value="">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div id="reportContent">
                <!-- Summary Statistics -->
                <div class="report-section">
                    <h4>Summary Statistics</h4>
                    <div class="report-stats">
                        <div class="stat-card">
                            <div class="stat-value" id="totalTransactions"><?= $approvedTransactionsCount ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="totalRevenue">₱<?= number_format($totalSales, 2) ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="avgTransaction">₱<?= $approvedTransactionsCount > 0 ? number_format($totalSales / $approvedTransactionsCount, 2) : '0.00' ?></div>
                            <div class="stat-label">Average Transaction</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="totalProducts"><?= array_sum(array_column($productSales, 'quantity')) ?></div>
                            <div class="stat-label">Products Sold</div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="report-section">
                    <h4>Daily Sales Trend</h4>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="report-section">
                    <h4>Top Selling Products</h4>
                    <div class="report-table">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="topProductsTable">
                                <?php 
                                // Sort products by quantity sold
                                uasort($productSales, function($a, $b) {
                                    return $b['quantity'] - $a['quantity'];
                                });
                                
                                $count = 0;
                                foreach ($productSales as $product): 
                                    if ($count >= 10) break; // Show top 10
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= $product['quantity'] ?></td>
                                    <td>₱<?= number_format($product['revenue'], 2) ?></td>
                                </tr>
                                <?php 
                                    $count++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                

                <!-- Transaction Details -->
                <div class="report-section">
                    <h4>Transaction Details</h4>
                    <div class="report-table">
                        <table class="table" id="reportTransactionTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction ID</th>
                                    <th>User</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions->transaction as $transaction): ?>
                                <tr data-date="<?= $transaction['date'] ?>" data-status="<?= $transaction->status ?>" data-payment="<?= $transaction->payment_method ?>">
                                    <td><?= formatDate($transaction['date']) ?></td>
                                    <td><?= $transaction['id'] ?></td>
                                    <td><?= $transaction['user_id'] ?></td>
                                    <td><?= ucfirst($transaction->payment_method) ?></td>
                                    <td>₱<?= number_format((float)$transaction->total_amount, 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= ((string)$transaction->status === 'approved') ? 'success' : 'warning' ?>">
                                            <?= ucfirst($transaction->status) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn-download" onclick="downloadPDF()">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
            <button type="button" class="btn-secondary" onclick="closeReportModal()">Close</button>
        </div>
    </div>
  </div>

  <script>
    // Transaction data for charts
    const dailySalesData = <?= json_encode($dailySales) ?>;
    const transactionsData = <?= json_encode(json_decode(json_encode($transactions), true)) ?>;
    
    let salesChart, paymentChart;

    function confirmLogout() {
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
      }
    }
    
    function viewDetails(transactionId) {
        // In a real application, you would fetch details via AJAX
        const modal = document.getElementById('transactionModal');
        
        // Find transaction in our XML data
        const transactions = <?= json_encode(simplexml_load_file('transactions.xml')->asXML()) ?>;
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(transactions, "text/xml");
        
        const transaction = Array.from(xmlDoc.getElementsByTagName('transaction'))
            .find(t => t.getAttribute('id') === transactionId);
        
        if (transaction) {
            let items = '';
            Array.from(transaction.getElementsByTagName('item')).forEach(item => {
                const name = item.getElementsByTagName('name')[0].textContent;
                const price = item.getElementsByTagName('price')[0].textContent;
                const quantity = item.getElementsByTagName('quantity')[0].textContent;
                // Continue from where the code was cut off
                const total = item.getElementsByTagName('total')[0].textContent;
                items += `
                    <tr>
                        <td>${name}</td>
                        <td>₱${parseFloat(price).toFixed(2)}</td>
                        <td>${quantity}</td>
                        <td>₱${parseFloat(total).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            const content = `
                <div>
                    <p><strong>Transaction ID:</strong> ${transaction.getAttribute('id')}</p>
                    <p><strong>Order ID:</strong> ${transaction.getAttribute('order_id')}</p>
                    <p><strong>User ID:</strong> ${transaction.getAttribute('user_id')}</p>
                    <p><strong>Date:</strong> ${new Date(transaction.getAttribute('date')).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</p>
                    <p><strong>Payment Method:</strong> ${transaction.getElementsByTagName('payment_method')[0].textContent}</p>
                    <p><strong>Status:</strong> <span class="badge bg-${transaction.getElementsByTagName('status')[0].textContent === 'approved' ? 'success' : 'warning'}">${transaction.getElementsByTagName('status')[0].textContent}</span></p>
                    <p><strong>Total Amount:</strong> ₱${parseFloat(transaction.getElementsByTagName('total_amount')[0].textContent).toFixed(2)}</p>
                    
                    <h6><strong>Items:</strong></h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items}
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('transactionDetails').innerHTML = content;
        }
        
        modal.style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }
    
    function showReportModal() {
        document.getElementById('reportModal').style.display = 'block';
        
        // Initialize charts
        setTimeout(() => {
            initializeCharts();
        }, 100);
    }
    
    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
        
        // Destroy existing charts to prevent memory leaks
        if (salesChart) {
            salesChart.destroy();
            salesChart = null;
        }
        if (paymentChart) {
            paymentChart.destroy();
            paymentChart = null;
        }
    }
    
    function initializeCharts() {
        // Initialize Sales Chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx && !salesChart) {
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: dailySalesData.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Daily Sales',
                        data: dailySalesData.map(item => item.sales),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize Payment Methods Chart
        const paymentCtx = document.getElementById('paymentChart');
        if (paymentCtx && !paymentChart) {
            // Calculate payment method distribution
            const paymentMethods = {};
            transactionsData.transaction.forEach(transaction => {
                if (transaction.status === 'approved') {
                    const method = transaction.payment_method;
                    paymentMethods[method] = (paymentMethods[method] || 0) + 1;
                }
            });
            
            paymentChart = new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(paymentMethods).map(method => method.charAt(0).toUpperCase() + method.slice(1)),
                    datasets: [{
                        data: Object.values(paymentMethods),
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e',
                            '#e74c3c'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    function applyFilters() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const statusFilter = document.getElementById('statusFilter').value;
        const paymentFilter = document.getElementById('paymentFilter').value;
        
        // Filter transaction table
        const tableRows = document.querySelectorAll('#reportTransactionTable tbody tr');
        let totalTransactions = 0;
        let totalRevenue = 0;
        let totalProducts = 0;
        
        tableRows.forEach(row => {
            const rowDate = new Date(row.dataset.date);
            const rowStatus = row.dataset.status;
            const rowPayment = row.dataset.payment;
            
            let showRow = true;
            
            // Date filter
            if (dateFrom && rowDate < new Date(dateFrom)) showRow = false;
            if (dateTo && rowDate > new Date(dateTo)) showRow = false;
            
            // Status filter
            if (statusFilter && rowStatus !== statusFilter) showRow = false;
            
            // Payment method filter
            if (paymentFilter && rowPayment !== paymentFilter) showRow = false;
            
            if (showRow) {
                row.style.display = '';
                totalTransactions++;
                
                // Calculate totals for visible rows
                const amountText = row.cells[4].textContent.replace('₱', '').replace(',', '');
                totalRevenue += parseFloat(amountText);
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update summary statistics
        document.getElementById('totalTransactions').textContent = totalTransactions;
        document.getElementById('totalRevenue').textContent = '₱' + totalRevenue.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('avgTransaction').textContent = totalTransactions > 0 ? '₱' + (totalRevenue / totalTransactions).toLocaleString(undefined, {minimumFractionDigits: 2}) : '₱0.00';
    }
    
    function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        
        // Add header
        doc.setFontSize(20);
        doc.text('La Croissanterie - Transaction Report', 20, 20);
        
        doc.setFontSize(12);
        doc.text('Generated on: ' + new Date().toLocaleDateString(), 20, 30);
        
        // Add summary statistics
        doc.setFontSize(16);
        doc.text('Summary Statistics', 20, 45);
        
        doc.setFontSize(12);
        const totalTransactions = document.getElementById('totalTransactions').textContent;
        const totalRevenue = document.getElementById('totalRevenue').textContent;
        const avgTransaction = document.getElementById('avgTransaction').textContent;
        
        doc.text(`Total Transactions: ${totalTransactions}`, 20, 55);
        doc.text(`Total Revenue: ${totalRevenue}`, 20, 65);
        doc.text(`Average Transaction: ${avgTransaction}`, 20, 75);
        
        // Add transaction table
        doc.setFontSize(16);
        doc.text('Transaction Details', 20, 90);
        
        // Get visible table rows
        const visibleRows = Array.from(document.querySelectorAll('#reportTransactionTable tbody tr'))
            .filter(row => row.style.display !== 'none')
            .slice(0, 20) // First 20 transactions to fit in PDF
            .map(row => [
                row.cells[0].textContent, // Date
                row.cells[1].textContent, // Transaction ID
                row.cells[2].textContent, // User
                row.cells[3].textContent, // Payment Method
                row.cells[4].textContent, // Amount
                row.cells[5].textContent.trim() // Status
            ]);
        
        // Simple table implementation
        let yPosition = 100;
        doc.setFontSize(10);
        
        // Table headers
        doc.text('Date', 20, yPosition);
        doc.text('Trans ID', 60, yPosition);
        doc.text('User', 90, yPosition);
        doc.text('Payment', 120, yPosition);
        doc.text('Amount', 150, yPosition);
        doc.text('Status', 180, yPosition);
        
        yPosition += 10;
        
        // Table rows
        visibleRows.forEach(row => {
            if (yPosition > 280) { // Start new page if needed
                doc.addPage();
                yPosition = 20;
            }
            
            doc.text(row[0].substring(0, 12), 20, yPosition); // Truncate date
            doc.text(row[1].substring(0, 8), 60, yPosition);   // Truncate ID
            doc.text(row[2].substring(0, 8), 90, yPosition);   // Truncate user
            doc.text(row[3], 120, yPosition);
            doc.text(row[4], 150, yPosition);
            doc.text(row[5], 180, yPosition);
            
            yPosition += 8;
        });
        
        // Save the PDF
        doc.save('transaction-report.pdf');
    }
    
    // Search functionality for transactions table
    document.getElementById('transactionSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('.table tbody tr');
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        const transactionModal = document.getElementById('transactionModal');
        const reportModal = document.getElementById('reportModal');
        
        if (event.target === transactionModal) {
            closeModal();
        }
        if (event.target === reportModal) {
            closeReportModal();
        }
    }
    
    // Initialize date filters with current month
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('dateFrom').value = firstDay.toISOString().split('T')[0];
        document.getElementById('dateTo').value = today.toISOString().split('T')[0];
    });
    </script>
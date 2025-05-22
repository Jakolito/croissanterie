<?php
// Function to load and parse XML
function loadTransactions() {
    $xml = simplexml_load_file('transactions.xml');
    if ($xml === false) {
        die('Error loading XML file');
    }
    return $xml;
}

// Function to get metrics grouped by date
function getMetricsByDate($transactions) {
    $metricsByDate = [];
    
    foreach ($transactions->transaction as $transaction) {
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

// Load transactions from XML
$transactions = loadTransactions();

// Get metrics by date
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

// Get total metrics
$totalTransactions = 0;
$totalSales = 0;
$uniqueUsers = [];
$productsSold = 0;

foreach ($transactions->transaction as $transaction) {
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Metrics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            transition: transform 0.3s;
            height: 100%;
            margin-bottom: 30px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
            margin-bottom: 15px;
        }
        .daily-totals {
            font-size: 0.9rem;
            margin-top: 10px;
        }
        .daily-total-item {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .refresh-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row my-4">
            <div class="col-12">
                <h1 class="text-center mb-4">Admin Dashboard Metrics</h1>
                <div class="d-flex justify-content-end mb-3">
                    <button id="refreshData" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                        Refresh Data
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Line Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Transactions</h5>
                    </div>
                    <div class="card-body">
                        <h2 id="totalTransactionsValue"><?= $totalTransactions ?> total</h2>
                        <div class="chart-container">
                            <canvas id="transactionsChart"></canvas>
                        </div>
                        <div class="daily-totals" id="transactionsTotals">
                            <h6>Daily Transactions:</h6>
                            <div id="dailyTransactionsList"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Sales</h5>
                    </div>
                    <div class="card-body">
                        <h2 id="totalSalesValue">₱<?= number_format($totalSales, 2) ?> total</h2>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                        <div class="daily-totals" id="salesTotals">
                            <h6>Daily Sales:</h6>
                            <div id="dailySalesList"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Users</h5>
                    </div>
                    <div class="card-body">
                        <h2 id="uniqueUsersValue"><?= $uniqueUsersCount ?> total</h2>
                        <div class="chart-container">
                            <canvas id="usersChart"></canvas>
                        </div>
                        <div class="daily-totals" id="usersTotals">
                            <h6>Daily Active Users:</h6>
                            <div id="dailyUsersList"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">Products</h5>
                    </div>
                    <div class="card-body">
                        <h2 id="productsSoldValue"><?= $productsSold ?> total</h2>
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
            // Show loading spinner
            const refreshBtn = document.getElementById('refreshData');
            refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            refreshBtn.disabled = true;
            
            // In a real application, this would make an AJAX request to fetch updated data
            fetch('admindashboard.php?refresh=true')
                .then(response => response.json())
                .then(data => {
                    // Update the displayed values
                    document.getElementById('totalTransactionsValue').textContent = data.totalTransactions + ' total';
                    document.getElementById('totalSalesValue').textContent = '₱' + data.totalSales.toFixed(2) + ' total';
                    document.getElementById('uniqueUsersValue').textContent = data.uniqueUsersCount + ' total';
                    document.getElementById('productsSoldValue').textContent = data.productsSold + ' total';
                    
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
                    refreshBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> Refresh Data';
                    refreshBtn.disabled = false;
                    
                    // Show a success message
                    alert('Dashboard data has been refreshed successfully!');
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                    alert('Failed to refresh dashboard data. Please try again.');
                    
                    // Reset the button
                    refreshBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg> Refresh Data';
                    refreshBtn.disabled = false;
                });
        }
        
        // Add event listener to refresh button
        document.getElementById('refreshData').addEventListener('click', refreshData);
    </script>
</body>
</html>
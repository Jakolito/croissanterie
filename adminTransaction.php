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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Croissanterie Admin Transactions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
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
        <span class="user-name"><?php echo htmlspecialchars($admin_fullname); ?></span>
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

  <script>
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
                const total = item.getElementsByTagName('total')[0].textContent;
                
                items += `
                    <tr>
                        <td>${name}</td>
                        <td>₱${price}</td>
                        <td>${quantity}</td>
                        <td>₱${total}</td>
                    </tr>
                `;
            });
            
            document.getElementById('transactionDetails').innerHTML = `
                <div class="row">
                    <div style="width: 50%; float: left;">
                        <p><strong>Transaction ID:</strong> ${transaction.getAttribute('id')}</p>
                        <p><strong>Order ID:</strong> ${transaction.getAttribute('order_id')}</p>
                        <p><strong>User:</strong> ${transaction.getAttribute('user_id')}</p>
                        <p><strong>Date:</strong> ${transaction.getAttribute('date')}</p>
                    </div>
                    <div style="width: 50%; float: left;">
                        <p><strong>Payment Method:</strong> ${transaction.getElementsByTagName('payment_method')[0].textContent}</p>
                        <p><strong>Total Amount:</strong> ₱${transaction.getElementsByTagName('total_amount')[0].textContent}</p>
                        <p><strong>Status:</strong> ${transaction.getElementsByTagName('status')[0].textContent}</p>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                <h6 style="margin-top: 15px; margin-bottom: 10px;">Items</h6>
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
            `;
        } else {
            document.getElementById('transactionDetails').innerHTML = '<p>Transaction details not found</p>';
        }
        
        modal.style.display = "block";
    }
    
    // Close the modal
    function closeModal() {
        document.getElementById('transactionModal').style.display = "none";
    }
    
    // Close the modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Transaction search functionality
    document.getElementById('transactionSearch').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('table tbody tr');
        
        tableRows.forEach(row => {
            let found = false;
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    found = true;
                }
            });
            
            if (found) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
  </script>
</body>
</html>
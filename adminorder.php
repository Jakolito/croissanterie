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

  // Path to the transactions XML file
  $transactionsXmlPath = 'transactions.xml';

  // Function to format XML with proper indentation
  function formatXML($xml) {
      $dom = new DOMDocument('1.0');
      $dom->preserveWhiteSpace = false;
      $dom->formatOutput = true;
      $dom->loadXML($xml->asXML());
      return $dom->saveXML();
  }

  // Load transactions from XML
  function loadTransactions() {
      global $transactionsXmlPath;
      if (file_exists($transactionsXmlPath)) {
          $xml = simplexml_load_file($transactionsXmlPath);
          if ($xml === false) {
              die('Error loading XML file');
          }
          return $xml;
      } else {
          // Create empty transactions file if it doesn't exist
          $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
          file_put_contents($transactionsXmlPath, formatXML($xml));
          return $xml;
      }
  }

  // Process order status updates if form is submitted
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['transaction_id'])) {
      $action = $_POST['action'];
      $transaction_id = $_POST['transaction_id'];
      
      // Load transactions
      $transactions = loadTransactions();
      
      // Find the transaction by ID
      $updated = false;
      foreach ($transactions->transaction as $transaction) {
          if ((string)$transaction['id'] === $transaction_id) {
              // Update status based on action
              if ($action === 'approve') {
                  $transaction->status = 'approved';
                  $updated = true;
              } elseif ($action === 'reject') {
                  $transaction->status = 'rejected';
                  $updated = true;
              }
              break;
          }
      }
      
      // Save updated XML if changes were made
      if ($updated) {
          file_put_contents($transactionsXmlPath, formatXML($transactions));
          
          // Set a success message in session
          $_SESSION['order_status_message'] = "Order #" . $transaction_id . " has been " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
      }
      
      // Redirect to avoid form resubmission
      header("Location: adminorder.php");
      exit();
  }

  // Filter orders by status if filter is set
  $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
  $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

  // Load all transactions
  $allTransactions = loadTransactions();

  // Prepare filtered transactions array
  $filteredTransactions = [];

  // Apply filters
  foreach ($allTransactions->transaction as $transaction) {
      $status = (string)$transaction->status;
      $transactionId = (string)$transaction['id'];
      $orderId = (string)$transaction['order_id'];
      $customerName = (string)$transaction->customer->name;
      
      // Apply status filter
      $statusMatch = ($filter === 'all') || ($filter === $status);
      
      // Apply search filter if search term is provided
      $searchMatch = empty($searchTerm) || 
                    stripos($transactionId, $searchTerm) !== false ||
                    stripos($orderId, $searchTerm) !== false ||
                    stripos($customerName, $searchTerm) !== false;
      
      if ($statusMatch && $searchMatch) {
          $filteredTransactions[] = $transaction;
      }
  }

  // Sort transactions by date (newest first)
  usort($filteredTransactions, function($a, $b) {
      $dateA = strtotime((string)$a['date']);
      $dateB = strtotime((string)$b['date']);
      return $dateB - $dateA;
  });

  // Pagination
  $itemsPerPage = 10;
  $totalItems = count($filteredTransactions);
  $totalPages = ceil($totalItems / $itemsPerPage);
  $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
  $offset = ($currentPage - 1) * $itemsPerPage;

  // Get current page items
  $currentPageItems = array_slice($filteredTransactions, $offset, $itemsPerPage);

  // Helper function to format date
  function formatDate($dateStr) {
      $date = new DateTime($dateStr);
      return $date->format('M d, Y h:i A');
  }

  // Function to get status badge class
  function getStatusBadgeClass($status) {
      switch ($status) {
          case 'pending':
              return 'status-pending';
          case 'approved':
              return 'status-approved';
          case 'rejected':
              return 'status-rejected';
          default:
              return 'status-pending';
      }
  }
  ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Management - La Croissanterie Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
  <style>
    /* Order management specific styles */
    .orders-container {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-top: 20px;
      overflow: hidden;
    }
    
    .orders-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #e8e8e8;
    }
    
    .filter-container {
      display: flex;
      gap: 15px;
    }
    
    .filter-dropdown {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: white;
      font-size: 14px;
    }
    
    .search-container {
      display: flex;
      gap: 5px;
    }
    
    .search-input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      width: 200px;
    }
    
    .search-btn {
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 8px 12px;
      cursor: pointer;
    }
    
    .search-btn:hover {
      background-color: #0069d9;
    }
    
    .orders-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .orders-table th,
    .orders-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #e8e8e8;
    }
    
    .orders-table th {
      background-color: #f8f9fa;
      font-weight: 600;
      color: #495057;
      border-top: 1px solid #e8e8e8;
    }
    
    .orders-table tr:hover {
      background-color: #f8f9fa;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-rejected {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .action-buttons {
      display: flex;
      gap: 5px;
    }
    
    .btn-view {
      background-color: #17a2b8;
    }
    
    .btn-approve {
      background-color: #28a745;
    }
    
    .btn-reject {
      background-color: #dc3545;
    }
    
    .action-btn {
      color: white;
      border: none;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 12px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    
    .action-btn:hover {
      opacity: 0.9;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    
    .pagination a,
    .pagination span {
      display: inline-block;
      padding: 8px 12px;
      margin: 0 5px;
      border-radius: 4px;
      text-decoration: none;
      color: #007bff;
      background-color: #fff;
      border: 1px solid #dee2e6;
    }
    
    .pagination a:hover {
      background-color: #e9ecef;
    }
    
    .pagination .active {
      background-color: #007bff;
      color: white;
      border-color: #007bff;
    }
    
    .no-orders {
      padding: 40px;
      text-align: center;
    }
    
    .no-orders h3 {
      color: #6c757d;
    }
    
    .no-orders p {
      color: #adb5bd;
      margin-top: 10px;
    }
    
    /* Alert message styling */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      border: 1px solid transparent;
    }
    
    .alert-success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }
    
    /* Modal Styling */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      width: 70%;
      max-width: 800px;
      max-height: 80vh;
      overflow-y: auto;
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #e8e8e8;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }
    
    .modal-title {
      font-size: 20px;
      font-weight: 600;
      margin: 0;
    }
    
    .close-modal {
      font-size: 24px;
      cursor: pointer;
      color: #adb5bd;
    }
    
    .close-modal:hover {
      color: #212529;
    }
    
    .modal-body {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    
    .order-details-section {
      padding: 15px;
      background-color: #f8f9fa;
      border-radius: 8px;
    }
    
    .order-details-section h3 {
      margin-top: 0;
      font-size: 16px;
      color: #495057;
      margin-bottom: 12px;
      border-bottom: 1px solid #e8e8e8;
      padding-bottom: 8px;
    }
    
    .detail-row {
      display: flex;
      margin-bottom: 8px;
    }
    
    .detail-label {
      font-weight: 600;
      width: 40%;
      color: #6c757d;
    }
    
    .detail-value {
      width: 60%;
    }
    
    .order-items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    
    .order-items-table th,
    .order-items-table td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #e8e8e8;
    }
    
    .order-items-table th {
      background-color: #e9ecef;
      font-weight: 600;
      color: #495057;
    }
    
    .item-image {
      width: 50px;
      height: 50px;
      border-radius: 4px;
      object-fit: cover;
    }
    
    .item-details {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .modal-footer {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid #e8e8e8;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    
    /* Make modal responsive */
    @media (max-width: 768px) {
      .modal-body {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        width: 90%;
      }
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
      <a href="adminorder.php" class="menu-item active">
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
      <h1 class="page-title">Order Management</h1>
      <div class="user-info">
        <?php if(!empty($admin_profile)): ?>
          <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Profile" class="profile-pic">
        <?php endif; ?>
        <span class="user-name"><?php echo htmlspecialchars($admin_fullname); ?></span>
        <a href="#" onclick="confirmLogout()" class="logout-btn">Logout</a>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Success Alert Message -->
      <?php if(isset($_SESSION['order_status_message'])): ?>
        <div class="alert alert-success">
          <?php 
            echo $_SESSION['order_status_message'];
            unset($_SESSION['order_status_message']); // Clear the message after displaying
          ?>
        </div>
      <?php endif; ?>
      
      <div class="welcome-message">
        <h2>Order Management</h2>
        <p>View and manage customer orders from this dashboard.</p>
      </div>

      <!-- Orders Container -->
      <div class="orders-container">
        <div class="orders-header">
          <div class="filter-container">
            <form action="adminorder.php" method="get" id="filterForm">
              <select name="filter" class="filter-dropdown" onchange="this.form.submit()">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
              <!-- Preserve current page and search when changing filter -->
              <?php if(!empty($searchTerm)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
              <?php endif; ?>
            </form>
          </div>
          
          <div class="search-container">
            <form action="adminorder.php" method="get">
              <input type="text" name="search" class="search-input" placeholder="Search orders..." value="<?php echo htmlspecialchars($searchTerm); ?>">
              <!-- Preserve current filter when searching -->
              <?php if($filter !== 'all'): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
              <?php endif; ?>
              <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
              </button>
            </form>
          </div>
        </div>
        
        <?php if(empty($currentPageItems)): ?>
          <!-- No orders found message -->
          <div class="no-orders">
            <h3>No orders found</h3>
            <p>There are no orders matching your current filter or search criteria.</p>
          </div>
        <?php else: ?>
          <!-- Orders Table -->
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($currentPageItems as $transaction): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$transaction['order_id']); ?></td>
                  <td><?php echo htmlspecialchars((string)$transaction->customer->name); ?></td>
                  <td><?php echo formatDate((string)$transaction['date']); ?></td>
                  <td>₱<?php echo number_format((float)$transaction->total_amount, 2); ?></td>
                  <td><?php echo htmlspecialchars(ucfirst((string)$transaction->payment_method)); ?></td>
                  <td>
                    <span class="status-badge <?php echo getStatusBadgeClass((string)$transaction->status); ?>">
                      <?php echo ucfirst((string)$transaction->status); ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="action-btn btn-view" 
                              onclick="viewOrderDetails('<?php echo htmlspecialchars((string)$transaction['id']); ?>')">
                        <i class="fas fa-eye"></i> View
                      </button>
                      
                      <?php if((string)$transaction->status === 'pending'): ?>
                        <form action="adminorder.php" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to approve this order?');">
                          <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars((string)$transaction['id']); ?>">
                          <input type="hidden" name="action" value="approve">
                          <button type="submit" class="action-btn btn-approve">
                            <i class="fas fa-check"></i> Approve
                          </button>
                        </form>
                        
                        <form action="adminorder.php" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this order?');">
                          <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars((string)$transaction['id']); ?>">
                          <input type="hidden" name="action" value="reject">
                          <button type="submit" class="action-btn btn-reject">
                            <i class="fas fa-times"></i> Reject
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          
          <!-- Pagination -->
          <?php if($totalPages > 1): ?>
            <div class="pagination">
              <?php if($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>&filter=<?php echo htmlspecialchars($filter); ?>&search=<?php echo htmlspecialchars($searchTerm); ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php endif; ?>
              
              <?php
              // Show limited page numbers with ellipsis
              $startPage = max(1, $currentPage - 2);
              $endPage = min($totalPages, $currentPage + 2);
              
              // Show first page if not in range
              if($startPage > 1) {
                echo '<a href="?page=1&filter=' . htmlspecialchars($filter) . '&search=' . htmlspecialchars($searchTerm) . '">1</a>';
                if($startPage > 2) {
                  echo '<span>...</span>';
                }
              }
              
              // Display page numbers
              for($i = $startPage; $i <= $endPage; $i++) {
                if($i == $currentPage) {
                  echo '<span class="active">' . $i . '</span>';
                } else {
                  echo '<a href="?page=' . $i . '&filter=' . htmlspecialchars($filter) . '&search=' . htmlspecialchars($searchTerm) . '">' . $i . '</a>';
                }
              }
              
              // Show last page if not in range
              if($endPage < $totalPages) {
                if($endPage < $totalPages - 1) {
                  echo '<span>...</span>';
                }
                echo '<a href="?page=' . $totalPages . '&filter=' . htmlspecialchars($filter) . '&search=' . htmlspecialchars($searchTerm) . '">' . $totalPages . '</a>';
              }
              ?>
              
              <?php if($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>&filter=<?php echo htmlspecialchars($filter); ?>&search=<?php echo htmlspecialchars($searchTerm); ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Order Details Modal -->
  <div id="orderDetailsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Order Details</h2>
        <span class="close-modal" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <!-- Content will be loaded dynamically via JavaScript -->
      </div>
      <div class="modal-footer" id="orderModalFooter">
        <!-- Action buttons will be added dynamically if needed -->
      </div>
    </div>
  </div>

  <script>
    // Function to handle logout confirmation
    function confirmLogout() {
      if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
      }
    }
    
    // Function to view order details
    function viewOrderDetails(transactionId) {
      // Load all transactions data from the current page for simplicity
      const transactions = <?php echo json_encode($filteredTransactions); ?>;
      
      // Find the transaction by ID
      const transaction = transactions.find(t => t['@attributes'].id === transactionId);
      
      if (!transaction) {
        alert('Order details not found');
        return;
      }
      
      // Format the modal content
      let modalContent = `
        <div class="order-details-section">
          <h3>Order Information</h3>
          <div class="detail-row">
            <div class="detail-label">Order ID:</div>
            <div class="detail-value">${transaction['@attributes'].order_id}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Transaction ID:</div>
            <div class="detail-value">${transaction['@attributes'].id}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Date:</div>
            <div class="detail-value">${formatDateForDisplay(transaction['@attributes'].date)}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Total Amount:</div>
            <div class="detail-value">₱${parseFloat(transaction.total_amount).toFixed(2)}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Payment Method:</div>
            <div class="detail-value">${transaction.payment_method.charAt(0).toUpperCase() + transaction.payment_method.slice(1)}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value">
              <span class="status-badge ${getStatusBadgeClassJS(transaction.status)}">
                ${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}
              </span>
            </div>
          </div>
        </div>
        
        <div class="order-details-section">
          <h3>Customer Information</h3>
          <div class="detail-row">
            <div class="detail-label">Name:</div>
            <div class="detail-value">${transaction.customer.name}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Email:</div>
            <div class="detail-value">${transaction.customer.email}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Phone:</div>
            <div class="detail-value">${transaction.customer.phone}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Address:</div>
            <div class="detail-value">${transaction.customer.address}</div>
          </div>
          <div class="detail-row">
            <div class="detail-label">Delivery Notes:</div>
            <div class="detail-value">${transaction.customer.delivery_notes || 'No additional notes'}</div>
          </div>
        </div>
      `;
      
      // Add order items section (spans full width)
      modalContent += `
        <div class="order-details-section" style="grid-column: span 2;">
          <h3>Order Items</h3>
          <table class="order-items-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
      `;
      
      // Add order items if they exist
      if (transaction.items && transaction.items.item) {
        // Ensure items is treated as an array even if only one item
        const items = Array.isArray(transaction.items.item) ? transaction.items.item : [transaction.items.item];
        
        for (const item of items) {
          modalContent += `
            <tr>
              <td class="item-details">
                <img src="${item.image || 'placeholder.jpg'}" alt="${item.name}" class="item-image">
                <div>
                  <div><strong>${item.name}</strong></div>
                  <div class="item-code">${item.product_id || 'N/A'}</div>
                </div>
              </td>
              <td>₱${parseFloat(item.price).toFixed(2)}</td>
              <td>${item.quantity}</td>
              <td>₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</td>
            </tr>
          `;
        }
      } else {
        modalContent += `
          <tr>
            <td colspan="4" style="text-align: center;">No items found for this order.</td>
          </tr>
        `;
      }
      
      modalContent += `
            </tbody>
          </table>
        </div>
      `;
      
      // Set modal content
      document.getElementById('orderDetailsContent').innerHTML = modalContent;
      
      // Add action buttons in footer if status is pending
      const footerEl = document.getElementById('orderModalFooter');
      if (transaction.status === 'pending') {
        footerEl.innerHTML = `
          <form action="adminorder.php" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to approve this order?');">
            <input type="hidden" name="transaction_id" value="${transactionId}">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="action-btn btn-approve">
              <i class="fas fa-check"></i> Approve Order
            </button>
          </form>
          
          <form action="adminorder.php" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this order?');">
            <input type="hidden" name="transaction_id" value="${transactionId}">
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="action-btn btn-reject">
              <i class="fas fa-times"></i> Reject Order
            </button>
          </form>
        `;
      } else {
        footerEl.innerHTML = ''; // Clear footer if no actions needed
      }
      
      // Show the modal
      document.getElementById('orderDetailsModal').style.display = 'block';
    }
    
    // Function to format date for display in modal
    function formatDateForDisplay(dateStr) {
      const date = new Date(dateStr);
      const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      };
      return date.toLocaleDateString('en-US', options);
    }
    
    // Function to get status badge class for JS
    function getStatusBadgeClassJS(status) {
      switch (status) {
        case 'pending':
          return 'status-pending';
        case 'approved':
          return 'status-approved';
        case 'rejected':
          return 'status-rejected';
        default:
          return 'status-pending';
      }
    }
    
    // Close the modal
    function closeModal() {
      document.getElementById('orderDetailsModal').style.display = 'none';
    }
    
    // Close modal if clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('orderDetailsModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
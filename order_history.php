<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user's ID and name
$user_id = $_SESSION['user'];
$user_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];

// Path to the transactions XML file
$transactionsXmlPath = 'transactions.xml';

// Load transaction data
$transactions = [];
$totalTransactions = 0;

if (file_exists($transactionsXmlPath)) {
    $transactionsXml = simplexml_load_file($transactionsXmlPath);
    
    // Check if the file loaded correctly
    if ($transactionsXml !== false) {
        foreach ($transactionsXml->transaction as $transaction) {
            // Check if this transaction belongs to the current user
            if ((string)$transaction['user_id'] === $user_id) {
                $transactions[] = $transaction;
                $totalTransactions++;
            }
        }
    }
}

// Sort transactions by date (newest first)
usort($transactions, function($a, $b) {
    $dateA = strtotime((string)$a['date']);
    $dateB = strtotime((string)$b['date']);
    return $dateB - $dateA;
});

// Count items in cart for badge display
$cartXmlPath = 'carts.xml';
$cartItemCount = 0;

if (file_exists($cartXmlPath)) {
    $cartsXml = simplexml_load_file($cartXmlPath);
    
    if ($cartsXml !== false) {
        foreach ($cartsXml->cart as $cart) {
            if ((string)$cart['user_id'] === $user_id) {
                foreach ($cart->item as $item) {
                    $cartItemCount += (int)$item->quantity;
                }
                break;
            }
        }
    }
}

// Handle transaction filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Filter transactions based on selection
$filteredTransactions = [];
foreach ($transactions as $transaction) {
    $transactionDate = strtotime((string)$transaction['date']);
    $now = time();
    
    // Apply filter
    if ($filter === 'all' || 
        ($filter === 'recent' && ($now - $transactionDate) < 7 * 24 * 60 * 60) || // last week
        ($filter === 'month' && ($now - $transactionDate) < 30 * 24 * 60 * 60) || // last month
        ($filter === 'older' && ($now - $transactionDate) >= 30 * 24 * 60 * 60)) { // older than a month
        
        $filteredTransactions[] = $transaction;
    }
}

// Pagination
$itemsPerPage = 5;
$totalPages = ceil(count($filteredTransactions) / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Get current page items
$currentPageTransactions = array_slice($filteredTransactions, $offset, $itemsPerPage);

// Function to format date
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('F j, Y - g:i A');
}

// Function to get transaction status class
function getStatusClass($status) {
    switch (strtolower($status)) {
        case 'completed':
            return 'status-completed';
        case 'processing':
            return 'status-processing';
        case 'delivered':
            return 'status-delivered';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
    <style>
        .order-history-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .filter-button {
            background: none;
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .filter-button:hover {
            background-color: #f5f5f5;
        }
        
        .filter-button.active {
            background-color: #4a6fa1;
            color: white;
            border-color: #4a6fa1;
        }
        
        .order-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: 600;
            color: #333;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-processing {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .order-item-price {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-item-quantity {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-item-total {
            font-weight: 600;
            font-size: 1.1rem;
            align-self: center;
            padding-left: 15px;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        
        .order-total {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .view-details-btn {
            background-color: transparent;
            color: #4a6fa1;
            border: 1px solid #4a6fa1;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .view-details-btn:hover {
            background-color: #f0f5ff;
        }
        
        .reorder-btn {
            background-color: #4a6fa1;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .reorder-btn:hover {
            background-color: #3a5a8f;
        }
        
        .order-summary {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-row.total {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 10px;
        }
        
        .empty-history {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .empty-icon {
            margin-bottom: 20px;
        }
        
        .empty-icon svg {
            width: 64px;
            height: 64px;
            color: #999;
        }
        
        .empty-history h2 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-history p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            padding: 0 10px;
        }
        
        .pagination-item:hover {
            background-color: #f5f5f5;
        }
        
        .pagination-item.active {
            background-color: #4a6fa1;
            color: white;
            border-color: #4a6fa1;
        }
        
        .pagination-item.disabled {
            color: #ccc;
            pointer-events: none;
        }
        
        /* Responsive order items */
        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
                padding: 15px 0;
            }
            
            .order-item-image {
                margin-bottom: 10px;
            }
            
            .order-item-total {
                align-self: flex-start;
                padding-left: 0;
                margin-top: 10px;
            }
            
            .order-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-options {
                width: 100%;
                overflow-x: auto;
                padding-bottom: 5px;
            }
        }
        
        /* Order details modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow-y: auto;
        }
        
        .modal.show {
            display: block;
        }
        
        .modal-content {
            background-color: white;
            margin: 60px auto;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: 0;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .delivery-info {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .delivery-info-title {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        /* Cart badge */
        .cart-badge {
          background-color: var(--accent-color);
          color: white;
          font-size: 12px;
          width: 20px;
          height: 20px;
          border-radius: 50%;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          margin-left: 5px;
        }
        
        /* Order details section */
        .order-detail-section {
            margin-bottom: 20px;
        }
        
        .order-detail-section-title {
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            flex: 0 0 140px;
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
        }
        
        /* Collapsible order items */
        .items-toggle {
            background: none;
            border: none;
            color: #4a6fa1;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0;
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .items-toggle svg {
            margin-right: 5px;
            transition: transform 0.3s;
        }
        
        .items-toggle.open svg {
            transform: rotate(90deg);
        }
        
        .order-items-list {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }
        
        .order-items-list.open {
            max-height: 1000px;
        }
    </style>
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    
    <nav>
      <ul class="main-nav">
        <li><a href="menu2.php">Menu</a></li>
        <li><a href="cart.php">Cart <span class="cart-badge" id="cartCount"><?php echo $cartItemCount; ?></span></a></li>
        <li><a href="order_history.php" class="active">Your Orders</a></li>
      </ul>
    </nav>
    
    <div class="profile-dropdown">
      <div class="dropdown-toggle" id="profileDropdown">
        <div class="profile-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <span class="profile-name"><?php echo htmlspecialchars($_SESSION['fname']); ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
      <div class="dropdown-menu" id="profileMenu">
        <a href="profile.php">My Profile</a>
        <div class="dropdown-divider"></div>
        <a href="homepage.php" id="logoutBtn">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="container">
    <h1 class="page-title">Order History</h1>
    
    <div class="order-history-container">
        <?php if ($totalTransactions > 0): ?>
            <div class="filter-bar">
                <div class="order-count">
                    <strong><?php echo count($filteredTransactions); ?></strong> orders found
                </div>
                <div class="filter-options">
                    <a href="?filter=all" class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>">All Orders</a>
                    <a href="?filter=recent" class="filter-button <?php echo $filter === 'recent' ? 'active' : ''; ?>">Recent (7 days)</a>
                    <a href="?filter=month" class="filter-button <?php echo $filter === 'month' ? 'active' : ''; ?>">Last 30 Days</a>
                    <a href="?filter=older" class="filter-button <?php echo $filter === 'older' ? 'active' : ''; ?>">Older</a>
                </div>
            </div>
            
            <?php if (count($currentPageTransactions) > 0): ?>
                <?php foreach ($currentPageTransactions as $transaction): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo htmlspecialchars((string)$transaction['order_id']); ?></div>
                                <div class="order-date"><?php echo formatDate((string)$transaction['date']); ?></div>
                            </div>
                            <div>
                                <span class="order-status <?php echo getStatusClass((string)$transaction->status); ?>">
                                    <?php echo ucfirst(htmlspecialchars((string)$transaction->status)); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-items">
                                <?php
                                $itemCount = 0;
                                $displayLimit = 2; // Display only first 2 items
                                ?>
                                
                                <?php foreach ($transaction->items->item as $item): ?>
                                    <?php if ($itemCount < $displayLimit): ?>
                                        <div class="order-item">
                                            <img src="<?php echo htmlspecialchars((string)$item->image); ?>" alt="<?php echo htmlspecialchars((string)$item->name); ?>" class="order-item-image">
                                            <div class="order-item-details">
                                                <h3 class="order-item-name"><?php echo htmlspecialchars((string)$item->name); ?></h3>
                                                <div class="order-item-price">₱<?php echo number_format((float)$item->price, 2); ?></div>
                                                <div class="order-item-quantity">Quantity: <?php echo (int)$item->quantity; ?></div>
                                            </div>
                                            <div class="order-item-total">₱<?php echo number_format((float)$item->total, 2); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php $itemCount++; ?>
                                <?php endforeach; ?>
                                
                                <?php
                                $remainingItems = $itemCount - $displayLimit;
                                if ($remainingItems > 0):
                                ?>
                                <div class="more-items">
                                    <button class="items-toggle" data-order-id="<?php echo htmlspecialchars((string)$transaction['id']); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                        <?php echo $remainingItems; ?> more item<?php echo $remainingItems > 1 ? 's' : ''; ?>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-total">
                                Total: ₱<?php echo number_format((float)$transaction->total_amount, 2); ?>
                            </div>
                            <div class="order-actions">
                                <button class="view-details-btn" data-transaction-id="<?php echo htmlspecialchars((string)$transaction['id']); ?>">View Details</button>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Previous button
                        $prevPage = $currentPage - 1;
                        $prevDisabled = $prevPage < 1 ? 'disabled' : '';
                        $prevLink = "?filter=$filter&page=$prevPage";
                        ?>
                        
                        <a href="<?php echo $prevDisabled ? '#' : $prevLink; ?>" class="pagination-item <?php echo $prevDisabled; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        
                        <?php
                        // Calculate start and end pages
                        $maxVisiblePages = 5;
                        $halfVisible = floor($maxVisiblePages / 2);
                        
                        if ($totalPages <= $maxVisiblePages) {
                            $startPage = 1;
                            $endPage = $totalPages;
                        } else {
                            if ($currentPage <= $halfVisible + 1) {
                                $startPage = 1;
                                $endPage = $maxVisiblePages;
                            } elseif ($currentPage >= $totalPages - $halfVisible) {
                                $startPage = $totalPages - $maxVisiblePages + 1;
                                $endPage = $totalPages;
                            } else {
                                $startPage = $currentPage - $halfVisible;
                                $endPage = $currentPage + $halfVisible;
                            }
                        }
                        
                        // Page buttons
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $isActive = $i === $currentPage ? 'active' : '';
                            $pageLink = "?filter=$filter&page=$i";
                            ?>
                            <a href="<?php echo $pageLink; ?>" class="pagination-item <?php echo $isActive; ?>"><?php echo $i; ?></a>
                            <?php
                        }
                        
                        // Next button
                        $nextPage = $currentPage + 1;
                        $nextDisabled = $nextPage > $totalPages ? 'disabled' : '';
                        $nextLink = "?filter=$filter&page=$nextPage";
                        ?>
                        
                        <a href="<?php echo $nextDisabled ? '#' : $nextLink; ?>" class="pagination-item <?php echo $nextDisabled; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-history">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 8v4"></path>
                            <path d="M12 16h.01"></path>
                        </svg>
                    </div>
                    <h2>No orders found</h2>
                    <p>No orders match your selected filter. Try changing your filter or place a new order.</p>
                    <a href="menu2.php" class="btn primary-btn">Browse Products</a>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-history">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <h2>No Order History</h2>
                <p>You haven't placed any orders yet. Browse our products and place your first order!</p>
                <a href="menu2.php" class="btn primary-btn">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal" id="orderDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Order Details</h2>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body" id="orderDetailsContent">
            <!-- Content will be loaded dynamically -->
            <div id="orderDetailsSkeleton">
                <div class="order-detail-section">
                    <h3 class="order-detail-section-title">Order Information</h3>
                    <div class="detail-row">
                        <div class="detail-label">Order ID:</div>
                        <div class="detail-value" id="modalOrderId"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Order Date:</div>
                        <div class="detail-value" id="modalOrderDate"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value" id="modalOrderStatus"></div>
                    </div>
                </div>
                
                <div class="order-detail-section">
                    <h3 class="order-detail-section-title">Items</h3>
                    <div id="modalOrderItems">
                        <!-- Items will be inserted here dynamically -->
                    </div>
                </div>
                
                <div class="delivery-info">
                    <div class="delivery-info-title">Delivery Information</div>
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value" id="modalDeliveryAddress"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value" id="modalDeliveryContact"></div>
                    </div>
                </div>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <div>Subtotal:</div>
                        <div id="modalSubtotal"></div>
                    </div>
                    <div class="summary-row">
                        <div>Delivery Fee:</div>
                        <div id="modalDeliveryFee"></div>
                    </div>
                    <div class="summary-row total">
                        <div>Total:</div>
                        <div id="modalTotal"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="view-details-btn" id="closeModalBtn">Close</button>
            <button class="reorder-btn" id="modalReorderBtn">Reorder</button>
        </div>
    </div>
</div>

<script>
    // Profile dropdown toggle
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    profileDropdown.addEventListener('click', () => {
        profileMenu.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
        if (!profileDropdown.contains(event.target)) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Logout button functionality
    document.getElementById('logoutBtn').addEventListener('click', (e) => {
        e.preventDefault();
        // Implement AJAX logout or redirect to logout page
        window.location.href = 'logout.php';
    });
    
    // Modal functionality
    const modal = document.getElementById('orderDetailsModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const closeModalX = document.getElementById('closeModal');
    
    // Close modal with buttons
    closeModalBtn.addEventListener('click', () => {
        modal.classList.remove('show');
    });
    
    closeModalX.addEventListener('click', () => {
        modal.classList.remove('show');
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
    
    // View order details functionality
    const viewDetailsBtns = document.querySelectorAll('.view-details-btn');
    viewDetailsBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const transactionId = btn.getAttribute('data-transaction-id');
            loadOrderDetails(transactionId);
            modal.classList.add('show');
        });
    });
    
    // Reorder functionality
    const reorderBtns = document.querySelectorAll('.reorder-btn');
    reorderBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const transactionId = btn.getAttribute('data-transaction-id');
            reorderItems(transactionId);
        });
    });
    
    // Toggle order items display
    const itemsToggles = document.querySelectorAll('.items-toggle');
    itemsToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('open');
            const orderId = toggle.getAttribute('data-order-id');
            const itemsList = document.querySelector(`.order-items-list[data-order-id="${orderId}"]`);
            if (itemsList) {
                itemsList.classList.toggle('open');
            }
        });
    });
    
    // Function to load order details
    function loadOrderDetails(transactionId) {
        // In a real application, this would be an AJAX request to fetch order details
        // For now, we'll simulate loading with static data based on the DOM
        
        // Find the order card that was clicked
        const orderCard = document.querySelector(`.view-details-btn[data-transaction-id="${transactionId}"]`).closest('.order-card');
        
        // Extract information from the order card
        const orderId = orderCard.querySelector('.order-id').textContent.replace('Order #', '');
        const orderDate = orderCard.querySelector('.order-date').textContent;
        const orderStatus = orderCard.querySelector('.order-status').textContent.trim();
        const orderTotal = orderCard.querySelector('.order-total').textContent.replace('Total: ', '');
        
        // Update modal with extracted information
        document.getElementById('modalOrderId').textContent = orderId;
        document.getElementById('modalOrderDate').textContent = orderDate;
        document.getElementById('modalOrderStatus').textContent = orderStatus;
        document.getElementById('modalTotal').textContent = orderTotal;
        
        // Set placeholder values for other fields
        document.getElementById('modalDeliveryAddress').textContent = "123 Sample St., City";
        document.getElementById('modalDeliveryContact').textContent = "09123456789";
        document.getElementById('modalSubtotal').textContent = orderTotal.replace('₱', '₱');
        document.getElementById('modalDeliveryFee').textContent = "₱50.00";
        
        // Clone order items from order card to modal
        const itemsContainer = document.getElementById('modalOrderItems');
        itemsContainer.innerHTML = '';
        
        // Get all items from the order card (visible and hidden)
        const orderItems = orderCard.querySelectorAll('.order-item');
        orderItems.forEach(item => {
            const clonedItem = item.cloneNode(true);
            itemsContainer.appendChild(clonedItem);
        });
    }
    
    // Function to handle reordering
    function reorderItems(transactionId) {
        // In a real application, this would be an AJAX request to add items to cart
        alert('Items from order have been added to your cart!');
        
        // Optionally redirect to cart page
        // window.location.href = 'cart.php';
    }
</script>
</body>
</html>
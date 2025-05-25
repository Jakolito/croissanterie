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
        case 'delivered':
            return 'status-completed';
        case 'processing':
        case 'approved':
        case 'shipping':
            return 'status-processing';
        case 'pending':
            return 'status-pending';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}

// Function to check if order can be tracked
function canTrackOrder($status) {
    $trackableStatuses = ['approved', 'shipping', 'processing'];
    return in_array(strtolower($status), $trackableStatuses);
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
        
        .status-pending {
            background-color: #fff3e0;
            color: #f57c00;
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
        
        .track-order-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .track-order-btn:hover {
            background-color: #218838;
        }
        
        .pending-status {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: not-allowed;
            opacity: 0.7;
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

        /* Tracking progress bar */
        .tracking-progress {
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .progress-title {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .progress-bar {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 10px;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
            z-index: 2;
        }
        
        .step-icon.active {
            background-color: #28a745;
            color: white;
        }
        
        .step-icon.completed {
            background-color: #28a745;
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            text-align: center;
            color: #666;
        }
        
        .step-label.active {
            color: #28a745;
            font-weight: 600;
        }
        
        .progress-line {
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        
        .progress-line-fill {
            height: 100%;
            background-color: #28a745;
            transition: width 0.3s ease;
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
                                
                                <?php if (canTrackOrder((string)$transaction->status)): ?>
                                    <button class="track-order-btn" data-transaction-id="<?php echo htmlspecialchars((string)$transaction['id']); ?>" data-order-id="<?php echo htmlspecialchars((string)$transaction['order_id']); ?>">Track Order</button>
                                <?php elseif (strtolower((string)$transaction->status) === 'pending'): ?>
                                    <button class="pending-status" disabled>Awaiting Approval</button>
                                <?php endif; ?>
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
                            <a href="<?php echo $pageLink; ?>" class="pagination-item <?php echo $isActive; ?>"
                            <?php echo $i; ?></a>
                        <?php } ?>
                        
                        <?php
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
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h2>No orders found for this filter</h2>
                    <p>Try adjusting your filter criteria or browse our menu to place your first order.</p>
                    <a href="menu2.php" class="view-details-btn" style="display: inline-block; text-decoration: none; margin-top: 10px;">Browse Menu</a>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-history">
                <div class="empty-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61l1.14-8H6"></path>
                    </svg>
                </div>
                <h2>No orders yet</h2>
                <p>You haven't placed any orders yet. Start by browsing our delicious menu!</p>
                <a href="menu2.php" class="view-details-btn" style="display: inline-block; text-decoration: none; margin-top: 10px;">Browse Menu</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Order Details</h2>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Order details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="view-details-btn" id="closeModalBtn">Close</button>
        </div>
    </div>
</div>

<!-- Order Tracking Modal -->
<div id="trackingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Track Your Order</h2>
            <button class="modal-close" id="closeTrackingModal">&times;</button>
        </div>
        <div class="modal-body" id="trackingModalBody">
            <!-- Tracking details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="view-details-btn" id="closeTrackingBtn">Close</button>
        </div>
    </div>
</div>

<script>
// Profile dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    profileDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        profileMenu.classList.toggle('show');
    });
    
    document.addEventListener('click', function() {
        profileMenu.classList.remove('show');
    });
    
    profileMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

// Modal functionality
const orderModal = document.getElementById('orderModal');
const trackingModal = document.getElementById('trackingModal');
const modalBody = document.getElementById('modalBody');
const trackingModalBody = document.getElementById('trackingModalBody');

// Close modal functions
function closeModal() {
    orderModal.classList.remove('show');
    trackingModal.classList.remove('show');
}

document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('closeModalBtn').addEventListener('click', closeModal);
document.getElementById('closeTrackingModal').addEventListener('click', closeModal);
document.getElementById('closeTrackingBtn').addEventListener('click', closeModal);

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target === orderModal || event.target === trackingModal) {
        closeModal();
    }
});

// View order details
document.querySelectorAll('.view-details-btn').forEach(button => {
    button.addEventListener('click', function() {
        const transactionId = this.getAttribute('data-transaction-id');
        if (!transactionId) return;
        
        // Find the transaction data
        const transactions = <?php echo json_encode($transactions); ?>;
        const transaction = transactions.find(t => t['@attributes'].id === transactionId);
        
        if (transaction) {
            showOrderDetails(transaction);
        }
    });
});

// Track order
document.querySelectorAll('.track-order-btn').forEach(button => {
    button.addEventListener('click', function() {
        const transactionId = this.getAttribute('data-transaction-id');
        const orderId = this.getAttribute('data-order-id');
        
        if (!transactionId || !orderId) return;
        
        // Find the transaction data
        const transactions = <?php echo json_encode($transactions); ?>;
        const transaction = transactions.find(t => t['@attributes'].id === transactionId);
        
        if (transaction) {
            showOrderTracking(transaction, orderId);
        }
    });
});

// Show order details in modal
function showOrderDetails(transaction) {
    const orderDate = new Date(transaction['@attributes'].date);
    const formattedDate = orderDate.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
    
    let itemsHtml = '';
    if (Array.isArray(transaction.items.item)) {
        transaction.items.item.forEach(item => {
            itemsHtml += `
                <div class="order-item">
                    <img src="${item.image}" alt="${item.name}" class="order-item-image">
                    <div class="order-item-details">
                        <h3 class="order-item-name">${item.name}</h3>
                        <div class="order-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                        <div class="order-item-quantity">Quantity: ${item.quantity}</div>
                    </div>
                    <div class="order-item-total">₱${parseFloat(item.total).toFixed(2)}</div>
                </div>
            `;
        });
    } else {
        const item = transaction.items.item;
        itemsHtml = `
            <div class="order-item">
                <img src="${item.image}" alt="${item.name}" class="order-item-image">
                <div class="order-item-details">
                    <h3 class="order-item-name">${item.name}</h3>
                    <div class="order-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                    <div class="order-item-quantity">Quantity: ${item.quantity}</div>
                </div>
                <div class="order-item-total">₱${parseFloat(item.total).toFixed(2)}</div>
            </div>
        `;
    }
    
    modalBody.innerHTML = `
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Order Information</h3>
            <div class="detail-row">
                <span class="detail-label">Order ID:</span>
                <span class="detail-value">#${transaction['@attributes'].order_id}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date Ordered:</span>
                <span class="detail-value">${formattedDate}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value">
                    <span class="order-status ${getStatusClass(transaction.status)}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span>
                </span>
            </div>
        </div>
        
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Items Ordered</h3>
            <div class="order-items">
                ${itemsHtml}
            </div>
        </div>
        
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Order Summary</h3>
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱${parseFloat(transaction.subtotal || transaction.total_amount).toFixed(2)}</span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>₱${parseFloat(transaction.delivery_fee || 0).toFixed(2)}</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>₱${parseFloat(transaction.total_amount).toFixed(2)}</span>
                </div>
            </div>
        </div>
        
        ${transaction.delivery_address ? `
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Delivery Information</h3>
            <div class="delivery-info">
                <div class="delivery-info-title">Delivery Address</div>
                <div>${transaction.delivery_address}</div>
                ${transaction.delivery_notes ? `<div style="margin-top: 10px;"><strong>Notes:</strong> ${transaction.delivery_notes}</div>` : ''}
            </div>
        </div>
        ` : ''}
    `;
    
    orderModal.classList.add('show');
}

// Show order tracking
function showOrderTracking(transaction, orderId) {
    const status = transaction.status.toLowerCase();
    
    // Define tracking steps
    const steps = [
        { id: 'pending', label: 'Order Received', icon: '📝' },
        { id: 'approved', label: 'Order Approved', icon: '✅' },
        { id: 'processing', label: 'Preparing', icon: '👨‍🍳' },
        { id: 'shipping', label: 'Out for Delivery', icon: '🚚' },
        { id: 'delivered', label: 'Delivered', icon: '📦' }
    ];
    
    // Calculate progress
    let currentStepIndex = steps.findIndex(step => step.id === status);
    if (currentStepIndex === -1 && status === 'completed') {
        currentStepIndex = steps.length - 1; // Treat completed as delivered
    }
    
    const progressPercentage = currentStepIndex >= 0 ? ((currentStepIndex + 1) / steps.length) * 100 : 0;
    
    let stepsHtml = '';
    steps.forEach((step, index) => {
        const isCompleted = index < currentStepIndex;
        const isActive = index === currentStepIndex;
        const stepClass = isCompleted ? 'completed' : (isActive ? 'active' : '');
        const labelClass = isCompleted || isActive ? 'active' : '';
        
        stepsHtml += `
            <div class="progress-step">
                <div class="step-icon ${stepClass}">${step.icon}</div>
                <div class="step-label ${labelClass}">${step.label}</div>
            </div>
        `;
    });
    
    trackingModalBody.innerHTML = `
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Order #${orderId}</h3>
            <div class="detail-row">
                <span class="detail-label">Current Status:</span>
                <span class="detail-value">
                    <span class="order-status ${getStatusClass(transaction.status)}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span>
                </span>
            </div>
        </div>
        
        <div class="tracking-progress">
            <div class="progress-title">Order Progress</div>
            <div class="progress-bar">
                <div class="progress-line">
                    <div class="progress-line-fill" style="width: ${progressPercentage}%"></div>
                </div>
                ${stepsHtml}
            </div>
        </div>
        
        <div class="order-detail-section">
            <h3 class="order-detail-section-title">Estimated Delivery</h3>
            <div class="delivery-info">
                <div class="delivery-info-title">Expected Delivery Time</div>
                <div>${getEstimatedDelivery(status)}</div>
            </div>
        </div>
    `;
    
    trackingModal.classList.add('show');
}

// Helper function to get status class
function getStatusClass(status) {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'delivered':
            return 'status-completed';
        case 'processing':
        case 'approved':
        case 'shipping':
            return 'status-processing';
        case 'pending':
            return 'status-pending';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return 'status-default';
    }
}

// Helper function to get estimated delivery time
function getEstimatedDelivery(status) {
    const now = new Date();
    const estimatedTime = new Date(now.getTime() + (30 * 60000)); // Add 30 minutes
    
    switch (status.toLowerCase()) {
        case 'pending':
            return 'Waiting for approval (usually within 10-15 minutes)';
        case 'approved':
            return 'Preparation will begin shortly (1-2 hours)';
        case 'processing':
            return 'Currently being prepared (15-20 minutes remaining)';
        case 'shipping':
            return `Expected delivery: ${estimatedTime.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`;
        case 'delivered':
        case 'completed':
            return 'Order has been delivered!';
        default:
            return 'Estimated delivery time will be updated soon';
    }
}

// Items toggle functionality
document.querySelectorAll('.items-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const orderId = this.getAttribute('data-order-id');
        const itemsList = this.closest('.order-card').querySelector('.order-items-list');
        
        if (itemsList) {
            itemsList.classList.toggle('open');
            this.classList.toggle('open');
            
            const isOpen = itemsList.classList.contains('open');
            const itemCount = this.textContent.match(/\d+/)[0];
            this.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="${isOpen ? '6 9 12 15 18 9' : '9 18 15 12 9 6'}"></polyline>
                </svg>
                ${isOpen ? 'Show less' : `${itemCount} more item${itemCount > 1 ? 's' : ''}`}
            `;
        }
    });
});
</script>

</body>
</html>
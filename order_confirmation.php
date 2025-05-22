<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user's ID
$user_id = $_SESSION['user'];

// Check if this is a successful order completion
$success = isset($_GET['success']) && $_GET['success'] == 1;

// If we have no order success parameter, redirect to menu
if (!$success) {
    header("Location: menu2.php");
    exit();
}

// Generate a random order ID for display purposes
$order_id = strtoupper(substr(md5(uniqid()), 0, 8));

// Store it in session for reference (could also be stored in a database in a real app)
$_SESSION['last_order_id'] = $order_id;

// Get the order date (current time)
$order_date = date('Y-m-d H:i:s');

// If we have order details in session, we can display them
$order_total = isset($_SESSION['order_total']) ? $_SESSION['order_total'] : 0;
$shipping_address = isset($_SESSION['shipping_address']) ? $_SESSION['shipping_address'] : '';

// Clear the order details from session after displaying
// In a real application, you might want to keep these for a history feature
unset($_SESSION['order_total']);
unset($_SESSION['shipping_address']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
    <style>
        /* Additional CSS for order confirmation page */
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .confirmation-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background-color: #48BB78;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .confirmation-title {
            font-size: 2rem;
            color: #2D3748;
            margin-bottom: 10px;
        }
        
        .confirmation-message {
            color: #4A5568;
            margin-bottom: 30px;
        }
        
        .confirmation-details {
            margin: 30px 0;
            text-align: left;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #E2E8F0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #718096;
            font-weight: 500;
        }
        
        .detail-value {
            color: #2D3748;
            font-weight: 600;
        }
        
        .next-steps {
            margin-top: 30px;
            padding: 20px;
            background-color: #EBF8FF;
            border-radius: 8px;
            color: #2C5282;
        }
        
        .button-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .primary-btn {
            padding: 12px 24px;
        }
        
        .secondary-btn {
            background-color: #E2E8F0;
            color: #4A5568;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .secondary-btn:hover {
            background-color: #CBD5E0;
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
        <li><a href="cart.php">Cart</a></li>
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
    <div class="confirmation-container">
        <div class="confirmation-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        
        <h1 class="confirmation-title">Order Confirmed!</h1>
        <p class="confirmation-message">Thank you for your order. We've received your purchase and are getting it ready for delivery.</p>
        
        <div class="confirmation-details">
            <div class="detail-row">
                <span class="detail-label">Order Number:</span>
                <span class="detail-value">#<?php echo $order_id; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?php echo date('F j, Y', strtotime($order_date)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">
                    <?php 
                        if (isset($_SESSION['payment_method'])) {
                            switch ($_SESSION['payment_method']) {
                                case 'cod':
                                    echo 'Cash on Delivery';
                                    break;
                                case 'card':
                                    echo 'Credit/Debit Card';
                                    break;
                                case 'gcash':
                                    echo 'GCash';
                                    break;
                                default:
                                    echo 'Not specified';
                            }
                        } else {
                            echo 'Not specified';
                        }
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shipping Address:</span>
                <span class="detail-value"><?php echo htmlspecialchars($shipping_address); ?></span>
            </div>
        </div>
        
        <div class="next-steps">
            <h3>What happens next?</h3>
            <p>You will receive an email confirmation shortly. We will notify you when your order has been shipped. Thank you for shopping with La Croissanterie!</p>
        </div>
        
        <div class="button-container">
            <a href="menu2.php" class="primary-btn">Continue Shopping</a>
            <a href="#" class="secondary-btn">Track Order</a>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-content logout-modal-content">
    <span class="modal-close" id="closeLogoutModal">&times;</span>
    <div class="logout-modal-body">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <div class="logout-modal-buttons">
        <button class="cancel-btn" id="cancelLogout">Cancel</button>
        <button class="confirm-btn" id="confirmLogout">Logout</button>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>La Croissanterie</h3>
      <p>Quality baked goods made with love and care. Bringing Japanese-inspired treats to your neighborhood.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">Products</a></li>
        <li><a href="#">Franchising</a></li>
        <li><a href="#">Contact Us</a></li>
      </ul>
    </div>
    <div class="footer-section">
      <h3>Contact</h3>
      <ul>
        <li>Email: info@pogishop.com</li>
        <li>Phone: +63 123 456 7890</li>
        <li>Address: 123 Bakery Street, Manila</li>
      </ul>
    </div>
  </div>
  <div class="copyright">
    &copy; 2025 Pogi Shop. All rights reserved.
  </div>
</footer>

<script>
    // Initialize profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    profileDropdown.addEventListener('click', () => {
        profileMenu.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    window.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-dropdown')) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Initialize logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const closeLogoutModal = document.getElementById('closeLogoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');
    
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        logoutModal.classList.add('show');
    });
    
    closeLogoutModal.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    cancelLogout.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    confirmLogout.addEventListener('click', () => {
        window.location.href = 'homepage.php';
    });
    
    // Close modal when clicking outside
    logoutModal.addEventListener('click', (e) => {
        if (e.target === logoutModal) {
            logoutModal.classList.remove('show');
        }
    });
</script>
</body>
</html>
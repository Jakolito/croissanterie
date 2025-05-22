<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Check if the required session variables are set
if (!isset($_SESSION['order_id']) || !isset($_SESSION['total_amount']) || !isset($_SESSION['transaction_id'])) {
    header("Location: cart.php");
    exit();
}

// Get order details from session
$order_id = $_SESSION['order_id'];
$total_amount = $_SESSION['total_amount'];
$transaction_id = $_SESSION['transaction_id'];

// Path to the transactions XML file
$transactionsXmlPath = 'transactions.xml';

// Load the transactions XML to get more details
$transactionsXml = simplexml_load_file($transactionsXmlPath);
$transaction = null;

// Find the specific transaction
foreach ($transactionsXml->transaction as $trans) {
    if ((string)$trans['id'] === $transaction_id) {
        $transaction = $trans;
        break;
    }
}

// If transaction not found, redirect to cart
if ($transaction === null) {
    header("Location: cart.php");
    exit();
}

// Get the current user's ID and name
$user_id = $_SESSION['user'];
$user_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];

// Handle payment form submission
$payment_completed = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complete_payment'])) {
        // In a real system, this would verify the payment with GCash API
        // For this example, we're simulating a successful payment
            
        // Update transaction status in XML
        if ($transaction) {
            // This is where you would update the transaction status
            // $transaction->status = 'paid';
            
            $transactionsXml->asXML($transactionsXmlPath);
        }
        
        $payment_completed = true;
        
        // Redirect to confirmation page after short delay
        header("refresh:3;url=order_confirmation.php?order_id=" . $order_id);
    }
}

// Generate a GCash reference number (simulated)
$gcash_reference = 'GC' . strtoupper(substr($transaction_id, -8));

// Generate QR code data
// In a real implementation, this would be the actual GCash payment QR code data
// For this example, we're creating a placeholder with the transaction details
$qr_code_data = "gcash://pay?merchant=LaCroissanterie&amount=" . $total_amount . "&reference=" . $gcash_reference;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
    <style>
        .gcash-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .gcash-box {
            background-color: #007EF2;
            border-radius: 12px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 126, 242, 0.3);
        }
        
        .gcash-logo {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .gcash-logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            background-color: white;
            border-radius: 8px;
            padding: 5px;
        }
        
        .gcash-details {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
        }
        
        .payment-form {
            background-color: white;
            border-radius: 10px;
            padding: 40px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .qr-code-container {
            margin: 20px auto;
            max-width: 250px;
            position: relative;
        }
        
        .qr-code {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background-color: white;
        }
        
        .qr-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-overlay img {
            width: 30px;
            height: 30px;
        }
        
        .qr-instructions {
            margin: 20px 0;
            font-size: 0.9rem;
            color: #666;
            text-align: center;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .gcash-btn {
            background-color: #007EF2;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            max-width: 250px;
            margin: 0 auto;
            transition: background-color 0.2s;
        }
        
        .gcash-btn:hover {
            background-color: #0069c5;
        }
        
        .cancel-payment {
            text-align: center;
            margin-top: 15px;
        }
        
        .cancel-payment a {
            color: #666;
            text-decoration: none;
        }
        
        .cancel-payment a:hover {
            text-decoration: underline;
        }
        
        /* Loader animation */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007EF2;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        <li><a href="cart.php">Cart </a></li>
        <li><a href="order_history.php">Your Orders</a></li>
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
    <h1 class="page-title">GCash Payment</h1>
    
    <div class="gcash-container">
        <?php if ($payment_completed): ?>
        <div class="success-message">
            <h2>Payment Successful!</h2>
            <p>Your payment has been processed successfully.</p>
            <div class="loader"></div>
            <p>Redirecting to order confirmation...</p>
        </div>
        <?php else: ?>
        <div class="gcash-box">
            <div class="gcash-logo">
                <img src="gcash_logo.png" alt="GCash" onerror="this.src='https://placeholder.pics/svg/40x40/FFFFFF/007EF2/G'"> GCash Payment
            </div>
            
            <div class="gcash-details">
                <div class="detail-row">
                    <div class="detail-label">Merchant</div>
                    <div class="detail-value">La Croissanterie</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Order ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($order_id); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Reference</div>
                    <div class="detail-value"><?php echo $gcash_reference; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Amount</div>
                    <div class="detail-value">₱<?php echo number_format($total_amount, 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="payment-form">
            <div class="form-title">Scan QR Code to Pay with GCash</div>
            
            <div class="qr-code-container">
                <!-- QR Code Image -->
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($qr_code_data); ?>" class="qr-code" alt="GCash Payment QR Code">
                <div class="qr-overlay">
                    <img src="gcash_logo.png" alt="GCash" onerror="this.src='https://placeholder.pics/svg/30x30/007EF2-007EF2/FFFFFF/G'">
                </div>
            </div>
            
            <div class="qr-instructions">
                <p>1. Open your GCash app</p>
                <p>2. Tap on "Scan QR" and scan this code</p>
                <p>3. Confirm payment in your GCash app</p>
                <p>4. Click "Complete Payment" below once done</p>
            </div>
            
            <form action="gcash_payment.php" method="post">
                <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <button type="submit" name="complete_payment" class="gcash-btn">Complete Payment</button>
            </form>
        </div>
        <?php endif; ?>
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
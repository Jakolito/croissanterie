<?php
session_start();

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user's ID and name
$user_id = $_SESSION['user'];
$user_name = $_SESSION['fname'] . ' ' . $_SESSION['lname'];

// Path to the XML cart file
$cartXmlPath = 'carts.xml';

// Path to the products XML file
$xmlPath = 'pastry.xml';

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

// Create or load the transactions XML file with better error handling
if (!file_exists($transactionsXmlPath)) {
    $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
    
    // Check if file is writable
    if (!is_writable(dirname($transactionsXmlPath))) {
        die("Error: Directory is not writable. Please check permissions for: " . dirname($transactionsXmlPath));
    }
    
    $result = file_put_contents($transactionsXmlPath, formatXML($transactionsXml));
    if ($result === false) {
        die("Error: Could not create transactions.xml file. Check permissions.");
    }
} else {
    // Try to load the file, if it fails, create a new one
    $transactionsXml = @simplexml_load_file($transactionsXmlPath);
    if ($transactionsXml === false) {
        // File exists but is invalid, recreate it
        $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
        $result = file_put_contents($transactionsXmlPath, formatXML($transactionsXml));
        if ($result === false) {
            die("Error: Could not recreate transactions.xml file. Check permissions.");
        }
    }
}

// Load product data from XML file
$pastries = [];
if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    if ($file === false) {
        die("Error: Could not load pastry.xml file.");
    }
    foreach ($file->pastry as $row) {
        // Add ID if it doesn't exist
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[(string)$row['id']] = $row;
    }
} else {
    die("Error: pastry.xml file not found.");
}

// Load cart data
if (!file_exists($cartXmlPath)) {
    die("Error: carts.xml file not found.");
}

$cartsXml = simplexml_load_file($cartXmlPath);
if ($cartsXml === false) {
    die("Error: Could not load carts.xml file.");
}

$userCart = null;
foreach ($cartsXml->cart as $cart) {
    if ((string)$cart['user_id'] === $user_id) {
        $userCart = $cart;
        break;
    }
}

// Check if items were selected from cart page
$selectedItems = [];
if (isset($_SESSION['selected_items']) && !empty($_SESSION['selected_items'])) {
    $selectedItems = $_SESSION['selected_items'];
    $isSelectedCheckout = true;
} else {
    $isSelectedCheckout = false;
}

// Create checkout items array for display
$checkoutItems = [];
$totalItems = 0;
$subtotal = 0;

// If user has a cart, process items
if ($userCart !== null) {
    foreach ($userCart->item as $item) {
        $product_id = (string)$item['product_id'];
        
        // Skip if product doesn't exist in products XML
        if (!isset($pastries[$product_id])) {
            continue;
        }
        
        // Skip if not in selected items (for selective checkout)
        if ($isSelectedCheckout && !in_array($product_id, $selectedItems)) {
            continue;
        }
        
        $quantity = (int)$item->quantity;
        $product = $pastries[$product_id];
        $price = (float)$product->price;
        $itemTotal = $price * $quantity;
        
        $checkoutItems[] = [
            'id' => $product_id,
            'name' => (string)$product->name,
            'price' => $price,
            'quantity' => $quantity,
            'image' => (string)$product->image,
            'total' => $itemTotal
        ];
        
        $totalItems += $quantity;
        $subtotal += $itemTotal;
    }
}

// Calculate tax and total
$tax = $subtotal * 0.12; // 12% tax
$total = $subtotal + $tax;

// Generate a unique order ID
$order_id = 'order_' . bin2hex(random_bytes(5)) . 'aabe';

// Check if we have user details from profile
$defaultAddress = isset($_SESSION['address']) ? $_SESSION['address'] : '';
$defaultPhone = isset($_SESSION['phone']) ? $_SESSION['phone'] : '';
$defaultEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';

// Handle payment process
$paymentSuccess = false;
$transaction_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_payment'])) {
        echo "<!-- DEBUG: Processing payment -->\n";
        
        $payment_method = $_POST['payment_method'];
        
        // Get customer details from form
        $customer_name = $_POST['customer_name'];
        $customer_address = $_POST['customer_address'];
        $customer_phone = $_POST['customer_phone'];
        $customer_email = $_POST['customer_email'];
        $delivery_notes = $_POST['delivery_notes'];
        
        // Validate required fields
        if (empty($customer_name) || empty($customer_address) || empty($customer_phone) || empty($customer_email)) {
            die("Error: All required fields must be filled.");
        }
        
        // Generate transaction ID
        $transaction_id = 'TRANS' . time() . rand(1000, 9999);
        
        echo "<!-- DEBUG: Creating transaction with ID: $transaction_id -->\n";
        
        try {
            // Create new transaction record in XML
            $transaction = $transactionsXml->addChild('transaction');
            $transaction->addAttribute('id', $transaction_id);
            $transaction->addAttribute('order_id', $order_id);
            $transaction->addAttribute('user_id', $user_id);
            $transaction->addAttribute('date', date('Y-m-d H:i:s'));
            
            // Add transaction details
            $transaction->addChild('payment_method', htmlspecialchars($payment_method));
            $transaction->addChild('total_amount', $total);
            $transaction->addChild('status', 'pending');
            $transaction->addChild('payment_status', 'paid');
            
            // Add customer details to transaction
            $customer = $transaction->addChild('customer');
            $customer->addChild('name', htmlspecialchars($customer_name));
            $customer->addChild('address', htmlspecialchars($customer_address));
            $customer->addChild('phone', htmlspecialchars($customer_phone));
            $customer->addChild('email', htmlspecialchars($customer_email));
            $customer->addChild('delivery_notes', htmlspecialchars($delivery_notes));
            
            // Add items to transaction
            $items = $transaction->addChild('items');
            foreach ($checkoutItems as $item) {
                $transItem = $items->addChild('item');
                $transItem->addAttribute('product_id', $item['id']);
                
                // Add complete item details including name, price, quantity, total and image
                $transItem->addChild('name', htmlspecialchars($item['name']));
                $transItem->addChild('price', $item['price']);
                $transItem->addChild('quantity', $item['quantity']);
                $transItem->addChild('total', $item['total']);
                $transItem->addChild('image', htmlspecialchars($item['image']));
            }
            
            echo "<!-- DEBUG: Transaction XML created, attempting to save -->\n";
            
            // Save transaction to XML file with proper formatting
            $xmlContent = formatXML($transactionsXml);
            $result = file_put_contents($transactionsXmlPath, $xmlContent);
            
            if ($result === false) {
                throw new Exception("Failed to save transaction to XML file. Check file permissions.");
            }
            
            echo "<!-- DEBUG: Transaction saved successfully. Bytes written: $result -->\n";
            
            // Remove purchased items from cart
            if ($userCart !== null) {
                // Create a copy of items to iterate through
                $itemsToRemove = [];
                
                foreach ($userCart->item as $index => $item) {
                    $product_id = (string)$item['product_id'];
                    
                    // Skip if product doesn't exist in products XML
                    if (!isset($pastries[$product_id])) {
                        continue;
                    }
                    
                    // Check if item should be removed
                    $shouldRemove = false;
                    
                    if ($isSelectedCheckout) {
                        // If doing selected checkout, only remove items that were selected
                        if (in_array($product_id, $selectedItems)) {
                            $shouldRemove = true;
                        }
                    } else {
                        // If doing "checkout all", remove all items that are in checkout
                        foreach ($checkoutItems as $checkoutItem) {
                            if ($checkoutItem['id'] === $product_id) {
                                $shouldRemove = true;
                                break;
                            }
                        }
                    }
                    
                    if ($shouldRemove) {
                        $itemsToRemove[] = $item;
                    }
                }
                
                // Now remove the items
                foreach ($itemsToRemove as $itemToRemove) {
                    $dom = dom_import_simplexml($itemToRemove);
                    $dom->parentNode->removeChild($dom);
                }
                
                // Save changes to cart XML file with proper formatting
                $cartResult = file_put_contents($cartXmlPath, formatXML($cartsXml));
                if ($cartResult === false) {
                    echo "<!-- DEBUG: Warning - Could not update cart XML -->\n";
                }
            }
            
            // Clear selected items session
            if (isset($_SESSION['selected_items'])) {
                unset($_SESSION['selected_items']);
            }
            
            // Set payment success flag
            $paymentSuccess = true;
            
            echo "<!-- DEBUG: Payment processing completed successfully -->\n";
            
            // Redirect to confirmation page or display confirmation
            if ($payment_method === 'gcash') {
                // Show GCash payment screen
                $_SESSION['order_id'] = $order_id;
                $_SESSION['total_amount'] = $total;
                $_SESSION['transaction_id'] = $transaction_id;
                header("Location: gcash_payment.php");
                exit();
            } else {
                // Redirect to confirmation page
                header("Location: order_confirmation.php?order_id=" . $order_id);
                exit();
            }
            
        } catch (Exception $e) {
            die("Error processing payment: " . $e->getMessage());
        }
    }
}

// Debug: Check file permissions
echo "<!-- DEBUG INFO:\n";
echo "Transactions XML Path: $transactionsXmlPath\n";
echo "File exists: " . (file_exists($transactionsXmlPath) ? 'Yes' : 'No') . "\n";
echo "File readable: " . (is_readable($transactionsXmlPath) ? 'Yes' : 'No') . "\n";
echo "File writable: " . (is_writable($transactionsXmlPath) ? 'Yes' : 'No') . "\n";
echo "Directory writable: " . (is_writable(dirname($transactionsXmlPath)) ? 'Yes' : 'No') . "\n";
echo "Current user: " . get_current_user() . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "-->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
     <style>
        .checkout-container {
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            gap: 30px;
        }
        
        @media (min-width: 992px) {
            .checkout-container {
                flex-direction: row;
                align-items: flex-start;
            }
            
            .checkout-items {
                flex: 3;
            }
            
            .checkout-form {
                flex: 2;
            }
        }
        
        .checkout-items {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .checkout-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 70px;
            position: sticky;
            top: 20px;
        }
        
        .order-items {
            margin-bottom: 20px;
        }
        
        .order-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 80px;
            height: 80px;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-check {
            margin-bottom: 10px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        .payment-methods {
            margin-top: 20px;
        }
        
        .payment-method {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-method:hover {
            background-color: #f9f9f9;
        }
        
        .payment-method.selected {
            border-color: #4a6fa1;
            background-color: #f0f5ff;
        }
        
        .payment-method input {
            margin-right: 10px;
        }
        
        .payment-method-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 15px;
        }
        
        .payment-method-details {
            flex: 1;
        }
        
        .payment-method-name {
            font-weight: 600;
        }
        
        .payment-method-description {
            font-size: 0.9rem;
            color: #666;
        }
        
        .empty-checkout {
            text-align: center;
            padding: 40px;
        }
        
        .empty-checkout-icon {
            margin-bottom: 20px;
        }
        
        .empty-checkout-icon svg {
            width: 64px;
            height: 64px;
            color: #999;
        }
        
        .empty-checkout h2 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-checkout p {
            color: #666;
            margin-bottom: 20px;
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
        
        /* Checkout form sections */
        .checkout-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .checkout-section:last-child {
            border-bottom: none;
        }
        
        .checkout-section-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
        }
        
        .checkout-section-title svg {
            margin-right: 10px;
            color: #4a6fa1;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            .form-col {
                flex-basis: 100%;
                margin-bottom: 15px;
            }
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        .required-field::after {
            content: "*";
            color: #e74c3c;
            margin-left: 4px;
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
        <li><a href="cart.php">Cart <span class="cart-badge" id="cartCount"><?php echo $totalItems; ?></span></a></li>
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
    <h1 class="page-title">Checkout</h1>
    
    <?php if (empty($checkoutItems)): ?>
    <div class="empty-checkout">
        <div class="empty-checkout-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        </div>
        <h2>No items to checkout</h2>
        <p>Your cart is empty or no items were selected for checkout.</p>
        <a href="cart.php" class="btn secondary-btn">Return to Cart</a>
        <a href="menu2.php" class="btn primary-btn">Continue Shopping</a>
    </div>
    <?php else: ?>
    
    <div class="checkout-container">
        <div class="checkout-items">
            <h2>Order Items</h2>
            <div class="order-items">
                <?php foreach ($checkoutItems as $item): ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-image">
                    <div class="order-item-details">
                        <h3 class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="order-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                        <div class="order-item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    <div class="order-item-total">₱<?php echo number_format($item['total'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary" style="margin-top: 20px;">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (12%)</span>
                    <span>₱<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="checkout-form">
            <form action="checkout.php" method="post" id="checkoutForm">
                <!-- Step 1: Customer Information Section -->
                <div id="step1" class="checkout-step active">
                    <!-- Customer Information Section -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Customer Information
                        </h2>
                        
                        <div class="form-group">
                            <label for="customerName" class="required-field">Full Name</label>
                            <input type="text" class="form-control" id="customerName" name="customer_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="customerEmail" class="required-field">Email Address</label>
                                    <input type="email" class="form-control" id="customerEmail" name="customer_email" value="<?php echo htmlspecialchars($defaultEmail); ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="customerPhone" class="required-field">Phone Number</label>
                                    <input type="tel" class="form-control" id="customerPhone" name="customer_phone" value="<?php echo htmlspecialchars($defaultPhone); ?>" placeholder="e.g. 09XX-XXX-XXXX" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Information Section -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Delivery Information
                        </h2>
                        
                        <div class="form-group">
                            <label for="customerAddress" class="required-field">Complete Address</label>
                            <textarea class="form-control" id="customerAddress" name="customer_address" rows="3" placeholder="House/Unit No., Street, Barangay, City, Province, Postal Code" required><?php echo htmlspecialchars($defaultAddress); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="deliveryNotes">Delivery Notes (Optional)</label>
                            <textarea class="form-control" id="deliveryNotes" name="delivery_notes" rows="2" placeholder="Additional instructions for delivery (e.g., landmark, gate code, etc.)"></textarea>
                        </div>
                    </div>
                    
                    <button type="button" class="btn primary-btn next-step" onclick="nextStep()" style="width: 100%; padding: 12px; margin-top: 20px;">Continue to Payment</button>
                    <a href="cart.php" class="btn text-btn" style="display: block; text-align: center; margin-top: 10px;">Return to Cart</a>
                </div>
                
                <!-- Step 2: Payment & Summary -->
                <div id="step2" class="checkout-step">
                    <!-- Payment Information Section -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Payment Method
                        </h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method" data-method="gcash">
                                <input type="radio" name="payment_method" id="gcash" value="gcash" checked>
                                <img src="gcash_logo.png" alt="GCash" class="payment-method-logo" onerror="this.src='https://placeholder.pics/svg/40x40/3498DB/FFFFFF/GCash'">
                                <div class="payment-method-details">
                                    <div class="payment-method-name">GCash</div>
                                    <div class="payment-method-description">Pay securely with your GCash account</div>
                                </div>
                            </div>
                            
                            
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            Order Summary
                        </h2>
                        
                        <div class="cart-summary" style="margin-top: 20px;">
                            <div class="summary-row">
                                <span>Items (<?php echo $totalItems; ?>)</span>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (12%)</span>
                                <span>₱<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <button type="submit" name="process_payment" class="btn primary-btn" style="width: 100%; padding: 12px; margin-top: 20px;">Place Order</button>
                    <button type="button" class="btn secondary-btn prev-step" onclick="prevStep()" style="width: 100%; padding: 12px; margin-top: 10px;">Back to Information</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function nextStep() {
        document.getElementById('step1').classList.remove('active');
        document.getElementById('step2').classList.add('active');
    }
    
    function prevStep() {
        document.getElementById('step2').classList.remove('active');
        document.getElementById('step1').classList.add('active');
    }
    </script>
    
    <style>
    .checkout-step {
        display: none;
    }
    
    .checkout-step.active {
        display: block;
    }
    </style>
    
    <?php endif; ?>
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
    // Initialize payment method selection
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => m.classList.remove('selected'));
            
            // Add selected class to clicked method
            this.classList.add('selected');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
    
    // Initially select the first payment method
    paymentMethods[0].classList.add('selected');
    
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
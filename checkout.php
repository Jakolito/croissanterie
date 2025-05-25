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

// Function to safely save XML with error handling
function saveXMLSafely($filePath, $xmlContent) {
    try {
        // Create backup of existing file
        if (file_exists($filePath)) {
            copy($filePath, $filePath . '.backup');
        }
        
        // Attempt to save
        $result = file_put_contents($filePath, $xmlContent, LOCK_EX);
        
        if ($result === false) {
            error_log("Failed to save XML to: " . $filePath);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving XML: " . $e->getMessage());
        return false;
    }
}

// Create or load the transactions XML file
if (!file_exists($transactionsXmlPath)) {
    $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
    if (!saveXMLSafely($transactionsXmlPath, formatXML($transactionsXml))) {
        die("Error: Cannot create transactions file. Please check file permissions.");
    }
} else {
    // Try to load the file, if it fails, create a new one
    $transactionsXml = @simplexml_load_file($transactionsXmlPath);
    if ($transactionsXml === false) {
        // File exists but is invalid, recreate it
        $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
        if (!saveXMLSafely($transactionsXmlPath, formatXML($transactionsXml))) {
            die("Error: Cannot recreate transactions file. Please check file permissions.");
        }
    }
}

// Load product data from XML file
$pastries = [];
if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    if ($file !== false) {
        foreach ($file->pastry as $row) {
            // Add ID if it doesn't exist
            if (!isset($row['id'])) {
                $row->addAttribute('id', uniqid());
            }
            $pastries[(string)$row['id']] = $row;
        }
    }
}

// Load cart data
$cartsXml = @simplexml_load_file($cartXmlPath);
if ($cartsXml === false) {
    die("Error: Cannot load cart file.");
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
        // Debug: Log the POST data
        error_log("Processing payment with data: " . print_r($_POST, true));
        
        $payment_method = $_POST['payment_method'];
        
        // Get customer details from form
        $customer_name = htmlspecialchars(trim($_POST['customer_name']));
        $customer_address = htmlspecialchars(trim($_POST['customer_address']));
        $customer_phone = htmlspecialchars(trim($_POST['customer_phone']));
        $customer_email = htmlspecialchars(trim($_POST['customer_email']));
        $delivery_notes = htmlspecialchars(trim($_POST['delivery_notes']));
        
        // Validate required fields
        if (empty($customer_name) || empty($customer_address) || empty($customer_phone) || empty($customer_email)) {
            die("Error: All required fields must be filled.");
        }
        
        // Check if checkout items exist
        if (empty($checkoutItems)) {
            die("Error: No items to checkout.");
        }
        
        // Generate transaction ID
        $transaction_id = 'TRANS' . time() . rand(1000, 9999);
        
        try {
            // Reload transactions XML to ensure we have the latest version
            $transactionsXml = @simplexml_load_file($transactionsXmlPath);
            if ($transactionsXml === false) {
                $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
            }
            
            // Create new transaction record in XML
            $transaction = $transactionsXml->addChild('transaction');
            $transaction->addAttribute('id', $transaction_id);
            $transaction->addAttribute('order_id', $order_id);
            $transaction->addAttribute('user_id', $user_id);
            $transaction->addAttribute('date', date('Y-m-d H:i:s'));
            
            // Add transaction details
            $transaction->addChild('payment_method', $payment_method);
            $transaction->addChild('total_amount', number_format($total, 2, '.', ''));
            $transaction->addChild('status', 'pending');
            $transaction->addChild('payment_status', 'paid');
            
            // Add customer details to transaction  
            $customer = $transaction->addChild('customer');
            $customer->addChild('name', $customer_name);
            $customer->addChild('address', $customer_address);
            $customer->addChild('phone', $customer_phone);
            $customer->addChild('email', $customer_email);
            $customer->addChild('delivery_notes', $delivery_notes);
            
            // Add items to transaction
            $items = $transaction->addChild('items');
            foreach ($checkoutItems as $item) {
                $transItem = $items->addChild('item');
                $transItem->addAttribute('product_id', $item['id']);
                
                // Add complete item details
                $transItem->addChild('name', $item['name']);
                $transItem->addChild('price', number_format($item['price'], 2, '.', ''));
                $transItem->addChild('quantity', $item['quantity']);
                $transItem->addChild('total', number_format($item['total'], 2, '.', ''));
                $transItem->addChild('image', $item['image']);
            }
            
            // Save transaction to XML file with proper formatting
            $formattedXML = formatXML($transactionsXml);
            if (!saveXMLSafely($transactionsXmlPath, $formattedXML)) {
                throw new Exception("Failed to save transaction to file");
            }
            
            // Debug: Log successful save
            error_log("Transaction saved successfully: " . $transaction_id);
            
            // Remove purchased items from cart
            if ($userCart !== null) {
                // Reload cart XML to ensure we have the latest version
                $cartsXml = @simplexml_load_file($cartXmlPath);
                if ($cartsXml !== false) {
                    // Find user cart again
                    $userCart = null;
                    foreach ($cartsXml->cart as $cart) {
                        if ((string)$cart['user_id'] === $user_id) {
                            $userCart = $cart;
                            break;
                        }
                    }
                    
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
                        if (!saveXMLSafely($cartXmlPath, formatXML($cartsXml))) {
                            error_log("Warning: Failed to update cart after checkout");
                        }
                    }
                }
            }
            
            // Clear selected items session
            if (isset($_SESSION['selected_items'])) {
                unset($_SESSION['selected_items']);
            }
            
            // Set payment success flag
            $paymentSuccess = true;
            
            // Store order details in session for confirmation page
            $_SESSION['order_id'] = $order_id;
            $_SESSION['total_amount'] = $total;
            $_SESSION['transaction_id'] = $transaction_id;
            
            // Redirect based on payment method
            if ($payment_method === 'gcash') {
                // Show GCash payment screen
                header("Location: gcash_payment.php");
                exit();
            } else {
                // Redirect to confirmation page
                header("Location: order_confirmation.php?order_id=" . $order_id);
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Error processing checkout: " . $e->getMessage());
            die("Error processing your order. Please try again. Error: " . $e->getMessage());
        }
    }
}
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

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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
                    <!-- Payment Method Section -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            Payment Method
                        </h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method" onclick="selectPayment('cod')">
                                <input type="radio" name="payment_method" value="cod" id="cod" required>
                                <div class="payment-method-logo">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="1" x2="12" y2="23"></line>
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                    </svg>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-name">Cash on Delivery</div>
                                    <div class="payment-method-description">Pay when your order arrives</div>
                                </div>
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment('gcash')">
                                <input type="radio" name="payment_method" value="gcash" id="gcash" required>
                                <div class="payment-method-logo">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 12c0 1.66-1.34 3-3 3H6c-1.66 0-3-1.34-3-3s1.34-3 3-3h12c1.66 0 3 1.34 3 3z"></path>
                                        <path d="M7 12h10"></path>
                                    </svg>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-name">GCash</div>
                                    <div class="payment-method-description">Pay securely with GCash</div>
                                </div>
                            </div>
                            
                            <div class="payment-method" onclick="selectPayment('card')">
                                <input type="radio" name="payment_method" value="card" id="card" required>
                                <div class="payment-method-logo">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                </div>
                                <div class="payment-method-details">
                                    <div class="payment-method-name">Credit/Debit Card</div>
                                    <div class="payment-method-description">Visa, Mastercard, and more</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary Section -->
                    <div class="checkout-section">
                        <h2 class="checkout-section-title">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                                <path d="M9 11V7a3 3 0 0 1 6 0v4"></path>
                            </svg>
                            Order Summary
                        </h2>
                        
                        <div class="order-summary-details">
                            <div class="summary-row">
                                <span>Order ID:</span>
                                <span><?php echo $order_id; ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Items:</span>
                                <span><?php echo $totalItems; ?> item(s)</span>
                            </div>
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax (12%):</span>
                                <span>₱<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span><strong>Total Amount:</strong></span>
                                <span><strong>₱<?php echo number_format($total, 2); ?></strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn secondary-btn prev-step" onclick="prevStep()" style="width: 48%; margin-right: 4%;">Back</button>
                        <button type="submit" name="process_payment" class="btn primary-btn" style="width: 48%;">Place Order</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileDropdown) {
        profileDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            profileMenu.classList.toggle('active');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (profileMenu && !profileDropdown.contains(e.target)) {
            profileMenu.classList.remove('active');
        }
    });
    
    // Form validation
    const form = document.getElementById('checkoutForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Check if payment method is selected
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
        });
    }
});

// Multi-step checkout functions
function nextStep() {
    // Validate step 1 fields
    const step1Fields = document.querySelectorAll('#step1 [required]');
    let isValid = true;
    
    step1Fields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = '#e74c3c';
            field.focus();
        } else {
            field.style.borderColor = '#ddd';
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields before proceeding.');
        return;
    }
    
    // Email validation
    const email = document.getElementById('customerEmail');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value)) {
        alert('Please enter a valid email address.');
        email.style.borderColor = '#e74c3c';
        email.focus();
        return;
    }
    
    // Phone validation (basic)
    const phone = document.getElementById('customerPhone');
    const phoneRegex = /^(\+63|0)[0-9]{10}$/;
    if (!phoneRegex.test(phone.value.replace(/[-\s]/g, ''))) {
        alert('Please enter a valid Philippine phone number.');
        phone.style.borderColor = '#e74c3c';
        phone.focus();
        return;
    }
    
    // Hide step 1, show step 2
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
}

function prevStep() {
    // Hide step 2, show step 1
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}

function selectPayment(method) {
    // Remove selected class from all payment methods
    document.querySelectorAll('.payment-method').forEach(pm => {
        pm.classList.remove('selected');
    });
    
    // Add selected class to clicked method
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById(method).checked = true;
}

// Phone number formatting
document.getElementById('customerPhone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.startsWith('63')) {
        value = '+' + value;
    } else if (value.startsWith('0')) {
        // Keep as is for local format
    }
    e.target.value = value;
});
</script>

<style>
.checkout-step {
    display: none;
}

.checkout-step.active,
#step1 {
    display: block;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.order-summary-details {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-top: 15px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    font-size: 1.1rem;
    font-weight: 600;
    color: #4a6fa1;
    border-top: 2px solid #4a6fa1;
    padding-top: 15px;
    margin-top: 10px;
}

.payment-method input[type="radio"] {
    margin: 0;
}

.payment-method.selected {
    border-color: #4a6fa1 !important;
    background-color: #f0f5ff !important;
}

.btn.text-btn {
    background: none;
    border: none;
    color: #666;
    text-decoration: underline;
    padding: 0;
}

.btn.text-btn:hover {
    color: #4a6fa1;
}

@media (max-width: 768px) {
    .form-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .form-actions .btn {
        width: 100% !important;
        margin: 0 !important;
    }
    
    .checkout-form {
        padding: 20px;
    }
}
</style>

</body>
</html>
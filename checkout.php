<?php
session_start();

// Enable error reporting for debugging
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

// Path to the XML files
$cartXmlPath = 'carts.xml';
$xmlPath = 'pastry.xml';
$transactionsXmlPath = 'transactions.xml';

// Debug function to log errors
function debugLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents('checkout_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Function to format XML with proper indentation
function formatXML($xml) {
    try {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        return $dom->saveXML();
    } catch (Exception $e) {
        debugLog("Error formatting XML: " . $e->getMessage());
        return $xml->asXML();
    }
}

// Create or load the transactions XML file with better error handling
function initializeTransactionsXML($transactionsXmlPath) {
    try {
        if (!file_exists($transactionsXmlPath)) {
            debugLog("Transactions XML file doesn't exist, creating new one");
            $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
            
            // Ensure directory exists
            $dir = dirname($transactionsXmlPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Check if file is writable
            if (!is_writable($dir)) {
                debugLog("Directory $dir is not writable");
                throw new Exception("Directory is not writable");
            }
            
            $result = file_put_contents($transactionsXmlPath, formatXML($transactionsXml));
            if ($result === false) {
                debugLog("Failed to create transactions XML file");
                throw new Exception("Could not create transactions file");
            }
            debugLog("Successfully created transactions XML file");
        } else {
            debugLog("Loading existing transactions XML file");
            $transactionsXml = @simplexml_load_file($transactionsXmlPath);
            if ($transactionsXml === false) {
                debugLog("Failed to load existing XML, recreating");
                $transactionsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
                file_put_contents($transactionsXmlPath, formatXML($transactionsXml));
            }
        }
        return $transactionsXml;
    } catch (Exception $e) {
        debugLog("Error initializing transactions XML: " . $e->getMessage());
        throw $e;
    }
}

// Initialize transactions XML
try {
    $transactionsXml = initializeTransactionsXML($transactionsXmlPath);
} catch (Exception $e) {
    die("Error: Could not initialize transactions file. Please check file permissions.");
}

// Load product data from XML file
$pastries = [];
if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    if ($file !== false) {
        foreach ($file->pastry as $row) {
            if (!isset($row['id'])) {
                $row->addAttribute('id', uniqid());
            }
            $pastries[(string)$row['id']] = $row;
        }
    }
}

// Load cart data
$userCart = null;
if (file_exists($cartXmlPath)) {
    $cartsXml = simplexml_load_file($cartXmlPath);
    if ($cartsXml !== false) {
        foreach ($cartsXml->cart as $cart) {
            if ((string)$cart['user_id'] === $user_id) {
                $userCart = $cart;
                break;
            }
        }
    }
}

// Check if items were selected from cart page
$selectedItems = [];
$isSelectedCheckout = false;
if (isset($_SESSION['selected_items']) && !empty($_SESSION['selected_items'])) {
    $selectedItems = $_SESSION['selected_items'];
    $isSelectedCheckout = true;
    debugLog("Selected checkout with items: " . implode(', ', $selectedItems));
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
            debugLog("Product $product_id not found in pastries XML");
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
    debugLog("POST request received");
    
    if (isset($_POST['process_payment'])) {
        debugLog("Processing payment");
        
        try {
            // Validate form data
            $required_fields = ['payment_method', 'customer_name', 'customer_address', 'customer_phone', 'customer_email'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Required field '$field' is missing");
                }
            }
            
            $payment_method = $_POST['payment_method'];
            $customer_name = $_POST['customer_name'];
            $customer_address = $_POST['customer_address'];
            $customer_phone = $_POST['customer_phone'];
            $customer_email = $_POST['customer_email'];
            $delivery_notes = isset($_POST['delivery_notes']) ? $_POST['delivery_notes'] : '';
            
            debugLog("Form data validated successfully");
            
            // Generate transaction ID
            $transaction_id = 'TRANS' . time() . rand(1000, 9999);
            debugLog("Generated transaction ID: $transaction_id");
            
            // Reload transactions XML to ensure we have the latest version
            $transactionsXml = initializeTransactionsXML($transactionsXmlPath);
            
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
            
            debugLog("Added basic transaction details");
            
            // Add customer details to transaction
            $customer = $transaction->addChild('customer');
            $customer->addChild('name', htmlspecialchars($customer_name));
            $customer->addChild('address', htmlspecialchars($customer_address));
            $customer->addChild('phone', htmlspecialchars($customer_phone));
            $customer->addChild('email', htmlspecialchars($customer_email));
            $customer->addChild('delivery_notes', htmlspecialchars($delivery_notes));
            
            debugLog("Added customer details");
            
            // Add items to transaction
            $items = $transaction->addChild('items');
            foreach ($checkoutItems as $item) {
                $transItem = $items->addChild('item');
                $transItem->addAttribute('product_id', $item['id']);
                
                $transItem->addChild('name', htmlspecialchars($item['name']));
                $transItem->addChild('price', $item['price']);
                $transItem->addChild('quantity', $item['quantity']);
                $transItem->addChild('total', $item['total']);
                $transItem->addChild('image', htmlspecialchars($item['image']));
            }
            
            debugLog("Added " . count($checkoutItems) . " items to transaction");
            
            // Save transaction to XML file with proper formatting
            $xmlContent = formatXML($transactionsXml);
            $result = file_put_contents($transactionsXmlPath, $xmlContent, LOCK_EX);
            
            if ($result === false) {
                throw new Exception("Failed to save transaction to XML file");
            }
            
            debugLog("Successfully saved transaction to XML file (bytes written: $result)");
            
            // Verify the file was written correctly
            if (file_exists($transactionsXmlPath)) {
                $fileSize = filesize($transactionsXmlPath);
                debugLog("Transaction file exists, size: $fileSize bytes");
                
                // Try to reload and verify
                $verifyXML = @simplexml_load_file($transactionsXmlPath);
                if ($verifyXML !== false) {
                    $transactionCount = count($verifyXML->transaction);
                    debugLog("Verification successful: $transactionCount transactions in file");
                } else {
                    debugLog("Warning: Could not verify saved XML file");
                }
            }
            
            // Remove purchased items from cart
            if ($userCart !== null && file_exists($cartXmlPath)) {
                $cartsXml = simplexml_load_file($cartXmlPath);
                
                // Find user cart again (reload from file)
                $userCart = null;
                foreach ($cartsXml->cart as $cart) {
                    if ((string)$cart['user_id'] === $user_id) {
                        $userCart = $cart;
                        break;
                    }
                }
                
                if ($userCart !== null) {
                    $itemsToRemove = [];
                    
                    foreach ($userCart->item as $item) {
                        $product_id = (string)$item['product_id'];
                        
                        if (!isset($pastries[$product_id])) {
                            continue;
                        }
                        
                        $shouldRemove = false;
                        
                        if ($isSelectedCheckout) {
                            if (in_array($product_id, $selectedItems)) {
                                $shouldRemove = true;
                            }
                        } else {
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
                    
                    // Remove the items
                    foreach ($itemsToRemove as $itemToRemove) {
                        $dom = dom_import_simplexml($itemToRemove);
                        $dom->parentNode->removeChild($dom);
                    }
                    
                    // Save changes to cart XML file
                    $cartResult = file_put_contents($cartXmlPath, formatXML($cartsXml), LOCK_EX);
                    debugLog("Updated cart file (bytes written: $cartResult)");
                }
            }
            
            // Clear selected items session
            if (isset($_SESSION['selected_items'])) {
                unset($_SESSION['selected_items']);
            }
            
            // Set payment success flag
            $paymentSuccess = true;
            
            debugLog("Payment processing completed successfully");
            
            // Redirect based on payment method
            if ($payment_method === 'gcash') {
                $_SESSION['order_id'] = $order_id;
                $_SESSION['total_amount'] = $total;
                $_SESSION['transaction_id'] = $transaction_id;
                debugLog("Redirecting to GCash payment");
                header("Location: gcash_payment.php");
                exit();
            } else {
                debugLog("Redirecting to order confirmation");
                header("Location: order_confirmation.php?order_id=" . $order_id);
                exit();
            }
            
        } catch (Exception $e) {
            debugLog("Error processing payment: " . $e->getMessage());
            $error_message = "Error processing your order: " . $e->getMessage();
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
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .debug-info h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
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
    
    <?php if (isset($error_message)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>
    
    <!-- Debug Information (remove in production) -->
    <div class="debug-info">
        <h4>Debug Information:</h4>
        <p><strong>Transactions XML Path:</strong> <?php echo $transactionsXmlPath; ?></p>
        <p><strong>File Exists:</strong> <?php echo file_exists($transactionsXmlPath) ? 'Yes' : 'No'; ?></p>
        <p><strong>File Writable:</strong> <?php echo is_writable(dirname($transactionsXmlPath)) ? 'Yes' : 'No'; ?></p>
        <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
        <p><strong>Checkout Items Count:</strong> <?php echo count($checkoutItems); ?></p>
        <p><strong>Is Selected Checkout:</strong> <?php echo $isSelectedCheckout ? 'Yes' : 'No'; ?></p>
        <?php if (file_exists($transactionsXmlPath)): ?>
            <p><strong>File Size:</strong> <?php echo filesize($transactionsXmlPath); ?> bytes</p>
            <?php 
            $testXML = @simplexml_load_file($transactionsXmlPath);
            if ($testXML !== false): ?>
                <p><strong>Current Transaction Count:</strong> <?php echo count($testXML->transaction); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
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
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<footer>
    <div class="footer-container">
        <div class="footer-section">
            <h3>La Croissanterie</h3>
            <p>Freshly baked pastries made with love and the finest ingredients.</p>
        </div>
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="menu2.php">Menu</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="order_history.php">Order History</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h4>Contact</h4>
            <p>Email: info@lacroissanterie.com</p>
            <p>Phone: (02) 8123-4567</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 La Croissanterie. All rights reserved.</p>
    </div>
</footer>

<script>
// Form validation and checkout flow
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileDropdown && profileMenu) {
        profileDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            profileMenu.classList.remove('show');
        });
    }
    
    // Form validation
    const form = document.getElementById('checkoutForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Processing...';
            }
        });
    }
    
    // Phone number formatting
    const phoneInput = document.getElementById('customerPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            if (value.length >= 4) {
                value = value.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
            }
            e.target.value = value;
        });
    }
    
    // Email validation
    const emailInput = document.getElementById('customerEmail');
    if (emailInput) {
        emailInput.addEventListener('blur', function(e) {
            const email = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                e.target.setCustomValidity('Please enter a valid email address');
                e.target.classList.add('error');
            } else {
                e.target.setCustomValidity('');
                e.target.classList.remove('error');
            }
        });
    }
});

function nextStep() {
    if (validateStep1()) {
        document.getElementById('step1').classList.remove('active');
        document.getElementById('step2').classList.add('active');
        
        // Update summary with customer info
        updateOrderSummary();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function prevStep() {
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step1').classList.add('active');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep1() {
    const requiredFields = [
        'customer_name',
        'customer_email', 
        'customer_phone',
        'customer_address'
    ];
    
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const value = field.value.trim();
            
            if (!value) {
                field.classList.add('error');
                showFieldError(field, 'This field is required');
                isValid = false;
            } else {
                field.classList.remove('error');
                hideFieldError(field);
                
                // Additional validation
                if (fieldName === 'customer_email' && !isValidEmail(value)) {
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a valid email address');
                    isValid = false;
                }
                
                if (fieldName === 'customer_phone' && !isValidPhone(value)) {
                    field.classList.add('error');
                    showFieldError(field, 'Please enter a valid phone number (11 digits)');
                    isValid = false;
                }
            }
        }
    });
    
    return isValid;
}

function validateForm() {
    return validateStep1();
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const cleaned = phone.replace(/\D/g, '');
    return cleaned.length === 11 && cleaned.startsWith('09');
}

function showFieldError(field, message) {
    hideFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function hideFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function updateOrderSummary() {
    // This could populate a summary preview in step 2
    // For now, the summary is already displayed
}

// Payment method selection
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove active class from all methods
            paymentMethods.forEach(m => m.classList.remove('active'));
            
            // Add active class to clicked method
            this.classList.add('active');
            
            // Check the radio button
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });
    
    // Set initial active state
    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
    if (checkedRadio) {
        const paymentMethod = checkedRadio.closest('.payment-method');
        if (paymentMethod) {
            paymentMethod.classList.add('active');
        }
    }
});
</script>

<style>
/* Additional checkout-specific styles */
.checkout-step {
    display: none;
}

.checkout-step.active {
    display: block;
}

.checkout-section {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.checkout-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2em;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f8d7a1;
}

.checkout-section-title svg {
    color: #d4841c;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.required-field::after {
    content: " *";
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #d4841c;
    box-shadow: 0 0 0 3px rgba(212, 132, 28, 0.1);
}

.form-control.error {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.field-error {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
    display: block;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.payment-method {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.payment-method:hover {
    border-color: #d4841c;
    background: #fef9f0;
}

.payment-method.active {
    border-color: #d4841c;
    background: #fef9f0;
    box-shadow: 0 2px 10px rgba(212, 132, 28, 0.1);
}

.payment-method input[type="radio"] {
    margin-right: 15px;
    transform: scale(1.2);
}

.payment-method-logo {
    width: 40px;
    height: 40px;
    margin-right: 15px;
    object-fit: contain;
}

.payment-method-details {
    flex: 1;
}

.payment-method-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.payment-method-description {
    color: #666;
    font-size: 14px;
}

.order-items {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
}

.order-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
}

.order-item-details {
    flex: 1;
}

.order-item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.order-item-price {
    color: #d4841c;
    font-weight: 500;
    margin-bottom: 3px;
}

.order-item-quantity {
    color: #666;
    font-size: 14px;
}

.order-item-total {
    font-weight: 600;
    color: #333;
    font-size: 18px;
}

.empty-checkout {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-checkout-icon {
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-checkout h2 {
    color: #333;
    margin-bottom: 10px;
}

.empty-checkout p {
    color: #666;
    margin-bottom: 30px;
}

.empty-checkout .btn {
    margin: 0 10px;
    display: inline-block;
}

/* Responsive design */
@media (max-width: 768px) {
    .checkout-container {
        flex-direction: column;
    }
    
    .checkout-items,
    .checkout-form {
        width: 100%;
        margin-bottom: 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .order-item-image {
        margin: 0 0 10px 0;
    }
    
    .order-item-total {
        margin-top: 10px;
    }
}

/* Step navigation buttons */
.next-step,
.prev-step {
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.next-step:hover,
.prev-step:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Loading state */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}
</style>

</body>
</html>
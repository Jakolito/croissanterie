<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user'];
$cartXmlPath = 'carts.xml';
$xmlPath = 'pastry.xml';

// Create or load the cart XML file
if (!file_exists($cartXmlPath)) {
    $cartsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><carts></carts>');
    $cartsXml->asXML($cartXmlPath);
} else {
    $cartsXml = simplexml_load_file($cartXmlPath);
}

// Find this user's cart
$userCart = null;
foreach ($cartsXml->cart as $cart) {
    if ((string)$cart['user_id'] === $user_id) {
        $userCart = $cart;
        break;
    }
}

// Load product data from XML file
$pastries = [];
if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    foreach ($file->pastry as $row) {
        // Add ID if it doesn't exist
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[(string)$row['id']] = $row;
    }
}

// Handle cart updates (quantity changes and removals)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart']) && $userCart !== null) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            // Find the item in the cart
            foreach ($userCart->item as $item) {
                if ((string)$item['product_id'] === $product_id) {
                    if ($quantity > 0) {
                        // Update quantity
                        $item->quantity = (int)$quantity;
                    } else {
                        // Remove item from cart
                        $dom = dom_import_simplexml($item);
                        $dom->parentNode->removeChild($dom);
                    }
                    break;
                }
            }
        }
        // Save changes to XML file
        $cartsXml->asXML($cartXmlPath);
        header("Location: cart.php?updated=1");
        exit();
    }
    
    // Handle removing individual items
    if (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        
        if ($userCart !== null) {
            foreach ($userCart->item as $item) {
                if ((string)$item['product_id'] === $product_id) {
                    // Remove the item
                    $dom = dom_import_simplexml($item);
                    $dom->parentNode->removeChild($dom);
                    break;
                }
            }
            // Save changes to XML file
            $cartsXml->asXML($cartXmlPath);
            header("Location: cart.php?removed=1");
            exit();
        }
    }
    
    // Handle clearing cart
    if (isset($_POST['clear_cart']) && $userCart !== null) {
        // Remove all items
        foreach ($userCart->item as $item) {
            $dom = dom_import_simplexml($item);
            $dom->parentNode->removeChild($dom);
        }
        // Save changes to XML file
        $cartsXml->asXML($cartXmlPath);
        header("Location: cart.php?cleared=1");
        exit();
    }
    
    // Handle selective checkout
    if (isset($_POST['checkout_selected'])) {
        if (!empty($_POST['selected_items'])) {
            // Save selected items to session for checkout page
            $_SESSION['selected_items'] = $_POST['selected_items'];
            header("Location: checkout.php");
            exit();
        } else {
            // No items selected
            header("Location: cart.php?error=no_selection");
            exit();
        }
    }
}

// Create cart items array for display
$cartItems = [];
$totalItems = 0;
$subtotal = 0;

if ($userCart !== null) {
    foreach ($userCart->item as $item) {
        $product_id = (string)$item['product_id'];
        $quantity = (int)$item->quantity;
        
        // Skip if product doesn't exist in products XML
        if (!isset($pastries[$product_id])) {
            continue;
        }
        
        $product = $pastries[$product_id];
        $price = (float)$product->price;
        $itemTotal = $price * $quantity;
        
        $cartItems[] = [
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

// Initialize selected items summary variables
$selectedSubtotal = 0;
$selectedTax = 0;
$selectedTotal = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
    <style>
      
        /* Additional CSS for item selection */
        .cart-item-select {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
        }
        
        .cart-header-select {
            width: 40px;
            text-align: center;
        }
        
        .item-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        #selectAllContainer {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 8px;
        }
        
        #selectAll {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .selected-summary {
            border-top: 1px solid #e0e0e0;
            margin-top: 15px;
            padding-top: 15px;
        }
        
        .selected-summary h3 {
            margin-bottom: 10px;
            color: #4a5568;
        }
        
        .checkout-selected-btn {
            margin-top: 15px;
        }
        
        .checkout-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .alert-error {
            background-color: #fed7d7;
            color: #9b2c2c;
            border-color: #f56565;
        }
        .logout-modal-content {
  padding: 2rem;
  text-align: center;
}

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--secondary-text);
}

.logout-modal-buttons {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.cancel-btn {
  background-color: transparent;
  color: var(--secondary-text);
  border: 1px solid var(--secondary-text);
  padding: 0.5rem 1.5rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: var(--transition);
}

.cancel-btn:hover {
  background-color: #f1f1f1;
}

.confirm-btn {
  background-color: var(--primary-color);
  color: #fff;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: var(--transition);
}

.confirm-btn:hover {
  background-color: var(--primary-color);
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
    <h1 class="page-title">Your Cart</h1>
    
    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">
        Your cart has been updated!
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['removed'])): ?>
    <div class="alert alert-success">
        Item has been removed from your cart.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['cleared'])): ?>
    <div class="alert alert-success">
        Your cart has been cleared.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] == 'no_selection'): ?>
    <div class="alert alert-error">
        Please select at least one item to checkout.
    </div>
    <?php endif; ?>
    
    <?php if (empty($cartItems)): ?>
    <div class="empty-cart">
        <div class="empty-cart-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
        </div>
        <h2>Your cart is empty</h2>
        <p>Looks like you haven't added any items to your cart yet.</p>
        <a href="menu2.php" class="btn primary-btn">Continue Shopping</a>
    </div>
    <?php else: ?>
    
    <div class="cart-container">
        <div class="cart-items">
            <form action="cart.php" method="post" id="cartForm">
                <div id="selectAllContainer">
                    <input type="checkbox" id="selectAll" title="Select All Items">
                    <label for="selectAll">Select All Items</label>
                </div>
                
                <div class="cart-header">
                    <div class="cart-header-select"></div>
                    <div class="cart-header-product">Product</div>
                    <div class="cart-header-price">Price</div>
                    <div class="cart-header-quantity">Quantity</div>
                    <div class="cart-header-total">Total</div>
                    <div class="cart-header-actions">Actions</div>
                </div>
                
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-select">
                        <input type="checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>" class="item-checkbox" data-price="<?php echo $item['price']; ?>" data-quantity="<?php echo $item['quantity']; ?>">
                    </div>
                    <div class="cart-item-product">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                        <div class="cart-item-details">
                            <h3 class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        </div>
                    </div>
                    <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                    <div class="cart-item-quantity">
                        <div class="quantity-control cart-quantity">
                            <button type="button" class="quantity-btn decrease">-</button>
                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0" class="quantity-input">
                            <button type="button" class="quantity-btn increase">+</button>
                        </div>
                    </div>
                    <div class="cart-item-total">₱<?php echo number_format($item['total'], 2); ?></div>
                    <div class="cart-item-actions">
                        <button type="submit" name="remove_item" class="remove-item" formaction="cart.php">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="cart-actions">
                    <button type="submit" name="update_cart" class="btn secondary-btn update-cart">Update Cart</button>
                    <button type="submit" name="clear_cart" class="btn danger-btn clear-cart">Clear Cart</button>
                </div>
            </form>
        </div>
        
        <div class="cart-summary">
            <h2 class="summary-title">Order Summary</h2>
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
            
            <!-- Selected Items Summary -->
            <div class="selected-summary" id="selectedSummary" style="display: none;">
                <h3>Selected Items Summary</h3>
                <div class="summary-row">
                    <span>Selected Subtotal</span>
                    <span id="selectedSubtotal">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Selected Tax (12%)</span>
                    <span id="selectedTax">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Selected Total</span>
                    <span id="selectedTotal">₱0.00</span>
                </div>
            </div>
            
            <div class="checkout-options">
                <button type="submit" form="cartForm" name="checkout_selected" class="btn primary-btn checkout-selected-btn" id="checkoutSelectedBtn">Checkout Selected Items</button>
                <a href="checkout.php" class="btn secondary-btn checkout-btn">Checkout All Items</a>
                <a href="menu2.php" class="btn text-btn continue-shopping">Continue Shopping</a>
            </div>
        </div>
    </div>
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
    // Initialize quantity button functionality
    const decreaseButtons = document.querySelectorAll('.quantity-btn.decrease');
    const increaseButtons = document.querySelectorAll('.quantity-btn.increase');
    
    decreaseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            const value = parseInt(input.value);
            if (value > 0) {
                input.value = value - 1;
            }
        });
    });
    
    increaseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            input.value = parseInt(input.value) + 1;
        });
    });
    
    // Remove item functionality
    const removeButtons = document.querySelectorAll('.remove-item');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to remove this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Clear cart confirmation
    const clearCartBtn = document.querySelector('.clear-cart');
    
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to clear your entire cart?')) {
                e.preventDefault();
            }
        });
    }
    
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
    
    // Auto-hide alert after 3 seconds
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 500);
        }, 3000);
    }
    
    // Item selection functionality
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectedSummary = document.getElementById('selectedSummary');
    const selectedSubtotalElem = document.getElementById('selectedSubtotal');
    const selectedTaxElem = document.getElementById('selectedTax');
    const selectedTotalElem = document.getElementById('selectedTotal');
    const checkoutSelectedBtn = document.getElementById('checkoutSelectedBtn');
    
    // Function to update selected items summary
    function updateSelectedSummary() {
        let selectedSubtotal = 0;
        let hasSelectedItems = false;
        
        itemCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                hasSelectedItems = true;
                const price = parseFloat(checkbox.getAttribute('data-price'));
                const quantity = parseInt(checkbox.getAttribute('data-quantity'));
                selectedSubtotal += price * quantity;
            }
        });
        
        if (hasSelectedItems) {
            selectedSummary.style.display = 'block';
            const selectedTax = selectedSubtotal * 0.12;
            const selectedTotal = selectedSubtotal + selectedTax;
            
            selectedSubtotalElem.textContent = '₱' + selectedSubtotal.toFixed(2);
            selectedTaxElem.textContent = '₱' + selectedTax.toFixed(2);
            selectedTotalElem.textContent = '₱' + selectedTotal.toFixed(2);
            
            checkoutSelectedBtn.disabled = false;
        } else {
            selectedSummary.style.display = 'none';
            checkoutSelectedBtn.disabled = true;
        }
    }
    
    // Add event listeners to checkboxes
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedSummary);
    });
    
    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        
        updateSelectedSummary();
    });
    
    // Initialize summary on page load
    updateSelectedSummary();
    
    // Disable checkout selected button if no items are selected
    checkoutSelectedBtn.addEventListener('click', function(e) {
        let hasSelectedItems = false;
        
        itemCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                hasSelectedItems = true;
            }
        });
        
        if (!hasSelectedItems) {
            e.preventDefault();
            alert('Please select at least one item to checkout.');
        }
    });
</script>
</body>
</html>
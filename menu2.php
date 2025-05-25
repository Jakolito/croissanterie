<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user's ID
$user_id = $_SESSION['user'];

// Path to the XML cart file
$cartXmlPath = 'carts.xml';

// Create or load the cart XML file
if (!file_exists($cartXmlPath)) {
    $cartsXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><carts></carts>');
    $cartsXml->asXML($cartXmlPath);
} else {
    $cartsXml = simplexml_load_file($cartXmlPath);
}

// Find or create this user's cart
$userCart = null;
foreach ($cartsXml->cart as $cart) {
    if ((string)$cart['user_id'] === $user_id) {
        $userCart = $cart;
        break;
    }
}

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Ensure quantity is at least 1
    $quantity = max(1, $quantity);
    
    // Load product data to get the image
    $xmlPath = 'pastry.xml';
    $productImage = '';
    $productName = '';
    $productPrice = 0;
    
    if (file_exists($xmlPath)) {
        $file = simplexml_load_file($xmlPath);
        foreach ($file->pastry as $pastry) {
            if ((string)$pastry['id'] === $product_id) {
                $productImage = (string)$pastry->image;
                $productName = (string)$pastry->name;
                $productPrice = (float)$pastry->price;
                break;
            }
        }
    }
    
    // If user cart doesn't exist yet, create it
    if ($userCart === null) {
        $userCart = $cartsXml->addChild('cart');
        $userCart->addAttribute('user_id', $user_id);
    }
    
    // Check if product already exists in cart
    $itemFound = false;
    foreach ($userCart->item as $item) {
        if ((string)$item['product_id'] === $product_id) {
            // Update quantity
            $item->quantity = (int)$item->quantity + $quantity;
            $itemFound = true;
            break;
        }
    }
    
    // If product not found in cart, add new item
    if (!$itemFound) {
        $newItem = $userCart->addChild('item');
        $newItem->addAttribute('product_id', $product_id);
        $newItem->addChild('quantity', $quantity);
        $newItem->addChild('image', $productImage);
        $newItem->addChild('name', $productName);
        $newItem->addChild('price', $productPrice);
    }
    
    // Save changes to XML file
    $cartsXml->asXML($cartXmlPath);
    
    // Redirect to prevent form resubmission
    header("Location: menu2.php?added=1");
    exit();
}

// Load data from XML file
$xmlPath = 'pastry.xml';
$pastries = [];

if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    foreach ($file->pastry as $row) {
        // Add ID if it doesn't exist
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[] = $row;
    }
}

// MODIFIED: Load categories from categories.xml instead of extracting from pastry.xml
$categoriesXmlPath = 'categories.xml';
$categories = [];

if (file_exists($categoriesXmlPath)) {
    $categoriesXml = simplexml_load_file($categoriesXmlPath);
    foreach ($categoriesXml->category as $category) {
        $categories[] = trim((string)$category);
    }
} else {
    // Fallback to extracting from pastry.xml if categories.xml doesn't exist
    $categories = array_unique(array_map(function($p) { 
        return (string)$p->producttype; 
    }, $pastries));
}

// Calculate total items in cart for this specific user
$totalItems = 0;
if ($userCart !== null) {
    foreach ($userCart->item as $item) {
        $totalItems += (int)$item->quantity;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Menu - La Croissanterie</title>
    <link rel="stylesheet" href="style.css">
    <style>
        
        :root {
          --primary-color: #513826;
          --accent-color: #a67c52;
          --light-color: #f5f1eb;
          --dark-color: #362517;
          --text-color: #333;
          --font-main: 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
          margin: 0;
          font-family: var(--font-main);
          background-color: var(--light-color);
          color: var(--text-color);
          line-height: 1.6;
        }
        
        /* Header styles */
        .header-container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px 50px;
          max-width: 1200px;
          margin: 0 auto;
          border-bottom: 1px solid #ddd;
        }

        header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 20px 50px;
          max-width: 1200px;
          margin: 0 auto;
        }

        .logo {
          display: flex;
          align-items: center;
        }

        .logo-text {
          font-size: 20px;
          font-weight: 300;
          letter-spacing: 1px;
          text-transform: uppercase;
          margin-left: 10px;
        }

        nav {
          display: flex;
          align-items: center;
        }

        .main-nav {
          display: flex;
          list-style: none;
          padding: 0;
          margin: 0;
        }

        .main-nav li {
          margin: 0 15px;
        }

        .main-nav a {
          text-decoration: none;
          color: var(--text-color);
          font-weight: 400;
          transition: color 0.3s;
          padding-bottom: 5px;
          position: relative;
        }

        .main-nav a:hover::after {
          content: '';
          position: absolute;
          width: 100%;
          height: 1px;
          background-color: var(--text-color);
          bottom: 0;
          left: 0;
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

        /* Profile dropdown styles */
        .profile-dropdown {
          position: relative;
          display: inline-block;
        }

        .dropdown-toggle {
          display: flex;
          align-items: center;
          cursor: pointer;
          padding: 5px 10px;
          border-radius: 20px;
          transition: background-color 0.3s;
        }

        .dropdown-toggle:hover {
          background-color: rgba(166, 124, 82, 0.1);
        }

        .dropdown-toggle span {
          margin-right: 5px;
        }

        .dropdown-menu {
          position: absolute;
          right: 0;
          top: 100%;
          background-color: white;
          min-width: 180px;
          box-shadow: 0 8px 16px rgba(0,0,0,0.1);
          border-radius: 8px;
          padding: 10px 0;
          z-index: 1000;
          display: none;
        }

        .dropdown-menu.show {
          display: block;
        }

        .dropdown-menu a {
          display: block;
          padding: 8px 20px;
          color: var(--text-color);
          text-decoration: none;
          transition: background-color 0.2s;
        }

        .dropdown-menu a:hover {
          background-color: rgba(166, 124, 82, 0.1);
        }

        .dropdown-divider {
          border-top: 1px solid #eee;
          margin: 5px 0;
        }

        /* Modal styles */
        .modal {
          display: none;
          position: fixed;
          z-index: 999;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          overflow: auto;
          background-color: rgba(0,0,0,0.7);
          opacity: 0;
          transition: opacity 0.3s;
        }

        .modal.show {
          display: flex;
          opacity: 1;
          align-items: center;
          justify-content: center;
        }

        .modal-content {
          background-color: white;
          margin: auto;
          border-radius: 10px;
          box-shadow: 0 5px 15px rgba(0,0,0,0.3);
          position: relative;
          transform: translateY(-50px);
          transition: transform 0.4s;
          overflow: hidden;
          max-width: 90%;
        }

        .modal.show .modal-content {
          transform: translateY(0);
        }

        .modal-close {
          position: absolute;
          right: 15px;
          top: 15px;
          font-size: 24px;
          font-weight: bold;
          color: #777;
          cursor: pointer;
          z-index: 2;
        }

        .logout-modal-content {
          max-width: 400px;
          padding: 20px;
        }

        .logout-modal-body {
          text-align: center;
        }

        .logout-modal-body h3 {
          color: var(--primary-color);
          margin-bottom: 15px;
        }

        .logout-modal-buttons {
          display: flex;
          justify-content: center;
          gap: 15px;
          margin-top: 25px;
        }

        .cancel-btn {
          padding: 10px 20px;
          background-color: #f5f5f5;
          border: 1px solid #ddd;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.3s;
        }

        .cancel-btn:hover {
          background-color: #e5e5e5;
        }

        .confirm-btn {
          padding: 10px 20px;
          background-color: var(--primary-color);
          color: white;
          border: none;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.3s;
        }

        .confirm-btn:hover {
          background-color: var(--dark-color);
        }
        
        /* Container styles */
        .container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 30px 20px;
        }

        .page-title {
          font-size: 32px;
          margin-bottom: 30px;
          color: var(--primary-color);
          text-align: center;
          position: relative;
        }

        .page-title:after {
          content: '';
          display: block;
          width: 80px;
          height: 3px;
          background-color: var(--accent-color);
          margin: 15px auto 0;
        }
        
        /* Alert message */
        .alert {
          padding: 15px;
          margin-bottom: 20px;
          border-radius: 5px;
          text-align: center;
        }

        .alert-success {
          background-color: #d4edda;
          color: #155724;
          border: 1px solid #c3e6cb;
        }

        /* Category menu */
        .category-menu {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          gap: 10px;
          margin-bottom: 30px;
        }

        .category-btn {
          padding: 8px 16px;
          background-color: white;
          border: 1px solid #ddd;
          border-radius: 20px;
          cursor: pointer;
          transition: all 0.3s;
          font-weight: 500;
        }

        .category-btn:hover {
          background-color: rgba(166, 124, 82, 0.1);
        }

        .category-btn.active {
          background-color: var(--accent-color);
          color: white;
          border-color: var(--accent-color);
        }

        /* Controls section */
        .controls {
          display: flex;
          justify-content: center;
          margin-bottom: 30px;
        }

        .search-box {
          position: relative;
          width: 300px;
        }

        .search-icon {
          position: absolute;
          left: 10px;
          top: 50%;
          transform: translateY(-50%);
          color: #777;
        }

        .search-input {
          width: 100%;
          padding: 10px 10px 10px 35px;
          border: 1px solid #ddd;
          border-radius: 20px;
          font-size: 14px;
        }

        /* Product grid */
        .product-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
          gap: 30px;
          margin-bottom: 40px;
        }

        .product-card {
          background-color: white;
          border-radius: 10px;
          overflow: hidden;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          transition: transform 0.3s, box-shadow 0.3s;
        }

        .product-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .product-image {
          width: 100%;
          height: 380px;
          object-fit: cover;
          cursor: pointer;
        }

        .product-info {
          padding: 20px;
        }

        .product-name {
          margin: 0 0 10px;
          font-size: 18px;
          color: var(--primary-color);
          cursor: pointer;
        }

        .product-price {
          color: var(--accent-color);
          font-weight: 600;
          font-size: 18px;
          margin-bottom: 10px;
        }

        .product-tag {
          display: inline-block;
          background-color: rgba(166, 124, 82, 0.1);
          color: var(--accent-color);
          font-size: 12px;
          padding: 3px 8px;
          border-radius: 20px;
          margin-bottom: 10px;
        }

        .product-description {
          margin-bottom: 15px;
          color: #666;
          font-size: 14px;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
          cursor: pointer;
        }

        .add-to-cart {
          width: 100%;
          padding: 10px 0;
          background-color: var(--primary-color);
          color: white;
          border: none;
          border-radius: 5px;
          cursor: pointer;
          transition: background-color 0.3s;
          font-size: 15px;
        }

        .add-to-cart:hover {
          background-color: var(--dark-color);
        }

        /* Pagination */
        .pagination {
          display: flex;
          justify-content: center;
          gap: 5px;
          margin-top: 30px;
        }

        .page-btn {
          min-width: 35px;
          height: 35px;
          background-color: white;
          border: 1px solid #ddd;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.3s;
          font-weight: 500;
        }

        .page-btn:hover:not(:disabled) {
          background-color: rgba(166, 124, 82, 0.1);
        }

        .page-btn.active {
          background-color: var(--accent-color);
          color: white;
          border-color: var(--accent-color);
        }

        .page-btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }
         .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .modal.show {
            display: flex;
            opacity: 1;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            margin: auto;
            max-width: 800px;
            width: 90%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            transform: translateY(-50px);
            transition: transform 0.4s;
            overflow: hidden;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #777;
            cursor: pointer;
            z-index: 2;
            background-color: white;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
        }
        
        .modal-close:hover {
            color: var(--primary-color);
        }
        
        .modal-body {
            display: flex;
            flex-direction: column;
        }
        
        .modal-image-container {
            width: 100%;
            height: 300px;
            overflow: hidden;
        }
        
        .modal-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .modal-details {
            padding: 25px;
        }
        
        .modal-product-name {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .modal-product-price {
            font-size: 20px;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 20px;
            display: inline-block;
            background-color: rgba(166, 124, 82, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .modal-product-description {
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .modal-product-tag {
            display: inline-block;
            background-color: #f5f5f5;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .modal-add-to-cart {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 25px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            flex: 1;
        }
        
        .modal-add-to-cart:hover {
            background-color: var(--dark-color);
        }
        
        @media (min-width: 768px) {
            .modal-body {
                flex-direction: row;
            }
            
            .modal-image-container {
                width: 50%;
                height: auto;
            }
            
            .modal-details {
                width: 50%;
            }
        }

        /* No results */
        .no-results {
          text-align: center;
          padding: 40px 0;
          grid-column: 1 / -1;
          color: #666;
          font-size: 18px;
        }

        /* Footer styles */
        .footer {
          background-color: white;
          padding: 40px 0 20px;
          margin-top: 60px;
          border-top: 1px solid #eee;
        }

        .footer-container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 20px;
          display: flex;
          flex-wrap: wrap;
          justify-content: space-between;
        }

        .footer-section {
          flex: 1;
          min-width: 200px;
          margin-bottom: 30px;
          padding-right: 20px;
        }

        .footer-title {
          font-size: 18px;
          color: var(--primary-color);
          margin-bottom: 15px;
          position: relative;
          padding-bottom: 10px;
        }

        .footer-title:after {
          content: '';
          position: absolute;
          left: 0;
          bottom: 0;
          width: 40px;
          height: 2px;
          background-color: var(--accent-color);
        }

        .footer-links {
          list-style: none;
          padding-left: 0;
        }

        .footer-links li {
          margin-bottom: 10px;
        }

        .footer-links a {
          color: #666;
          text-decoration: none;
          transition: color 0.3s;
        }

        .footer-links a:hover {
          color: var(--accent-color);
        }

        .copyright {
          text-align: center;
          padding-top: 20px;
          margin-top: 20px;
          border-top: 1px solid #eee;
          font-size: 14px;
          color: #777;
          width: 100%;
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
    <h1 class="page-title">Our Menu</h1>
    
    <?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">
        Product has been added to your cart!
    </div>
    <?php endif; ?>
    
    <!-- Category Menu - Dynamic display based on categories from XML -->
    <div class="category-menu" id="categoryMenu">
        <button class="category-btn active" data-category="all">All Products</button>
        <?php foreach ($categories as $category): ?>
            <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <div class="controls">
        <div class="search-box">
            <span class="search-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" class="search-input" id="tagSearch" placeholder="Search by tag...">
        </div>
    </div>
    
    <!-- Product Grid -->
    <div class="product-grid" id="productGrid">
        <!-- Products will be loaded by JavaScript -->
    </div>
    
    <!-- Pagination -->
    <div class="pagination" id="pagination">
        <!-- Pagination buttons will be added by JavaScript -->
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal" id="productModal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div class="modal-body">
            <div class="modal-image-container">
                <img src="" alt="" class="modal-image" id="modalImage">
            </div>
            <div class="modal-details">
                <h2 class="modal-product-name" id="modalName"></h2>
                <div class="modal-product-price" id="modalPrice"></div>
                <div class="modal-product-tag" id="modalTag"></div>
                <p class="modal-product-description" id="modalDescription"></p>
                <form action="menu2.php" method="post" class="modal-form">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <div class="quantity-control">
                        <label for="modalQuantity">Quantity:</label>
                        <input type="number" name="quantity" id="modalQuantity" value="1" min="1" class="quantity-input">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
                    </div>
                </form>
            </div>
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

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3 class="footer-title">La Croissanterie</h3>
            <p>Authentic French pastries baked fresh daily with premium ingredients.</p>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Quick Links</h3>
            <ul class="footer-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="menu2.php">Menu</a></li>
                <li><a href="cart.php">Cart</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Contact Us</h3>
            <ul class="footer-links">
                <li>123 Bakery Street, Manila</li>
                <li>Phone: (02) 8123-4567</li>
                <li>Email: info@lacroissanterie.com</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Opening Hours</h3>
            <ul class="footer-links">
                <li>Monday - Friday: 7am - 8pm</li>
                <li>Saturday: 8am - 9pm</li>
                <li>Sunday: 8am - 7pm</li>
            </ul>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> La Croissanterie. All rights reserved.
        </div>
    </div>
</footer>

<script>
    // Convert PHP array to JavaScript array
    const products = [
        <?php foreach ($pastries as $item): ?>
        {
            id: "<?php echo $item['id']; ?>",
            name: "<?php echo htmlspecialchars($item->name); ?>",
            price: <?php echo floatval($item->price); ?>,
            description: "<?php echo htmlspecialchars($item->description ?? ''); ?>",
            image: "<?php echo htmlspecialchars($item->image); ?>",
            category: "<?php echo htmlspecialchars($item->producttype); ?>",
            tag: "<?php echo htmlspecialchars($item->producttag ?? ''); ?>"
        },
        <?php endforeach; ?>
    ];
    
    // DOM elements
    const productGrid = document.getElementById('productGrid');
    const pagination = document.getElementById('pagination');
    const categoryButtons = document.querySelectorAll('.category-btn');
    const tagSearch = document.getElementById('tagSearch');
    const productModal = document.getElementById('productModal');
    const modalClose = document.querySelector('.modal-close');
    const modalImage = document.getElementById('modalImage');
    const modalName = document.getElementById('modalName');
    const modalPrice = document.getElementById('modalPrice');
    const modalTag = document.getElementById('modalTag');
    const modalDescription = document.getElementById('modalDescription');
    const modalProductId = document.getElementById('modalProductId');
    
    // Pagination settings
    const productsPerPage = 6;
    let currentPage = 1;
    let filteredProducts = [...products];
    
    // Add event listeners to category buttons
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
            
            filterProducts();
        });
    });
    
    // Add event listener to search input
    tagSearch.addEventListener('input', filterProducts);
    
    // Close modal when clicking on the X button
    modalClose.addEventListener('click', () => {
        productModal.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    // Close modal when clicking outside the modal content
    productModal.addEventListener('click', (e) => {
        if (e.target === productModal) {
            productModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Open modal with product details
    function openProductModal(product) {
        modalImage.src = product.image;
        modalImage.alt = product.name;
        modalName.textContent = product.name;
        modalPrice.textContent = `₱${product.price.toFixed(2)}`;
        modalTag.textContent = product.tag;
        modalDescription.textContent = product.description;
        modalProductId.value = product.id;
        
        productModal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }
    
    // Filter products based on selected category and search term
    function filterProducts() {
        const activeCategory = document.querySelector('.category-btn.active').dataset.category;
        const searchTerm = tagSearch.value.toLowerCase();
        
        filteredProducts = products.filter(product => {
            const matchesCategory = activeCategory === 'all' || product.category === activeCategory;
            const matchesSearch = product.tag.toLowerCase().includes(searchTerm) || 
                                  product.name.toLowerCase().includes(searchTerm);
            return matchesCategory && matchesSearch;
        });
        
        // Reset to first page and render
        currentPage = 1;
        renderProducts();
    }
    
    // Render products for current page
    function renderProducts() {
        // Calculate start and end indices
        const startIndex = (currentPage - 1) * productsPerPage;
        const endIndex = Math.min(startIndex + productsPerPage, filteredProducts.length);
        const currentProducts = filteredProducts.slice(startIndex, endIndex);
        
        // Clear the product grid
        productGrid.innerHTML = '';
        
        // If no products found
        if (currentProducts.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.textContent = 'No products found matching your criteria.';
            productGrid.appendChild(noResults);
            
            // Clear pagination
            pagination.innerHTML = '';
            return;
        }
        
        // Add products to the grid
        currentProducts.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            
            card.innerHTML = `
                <img src="${product.image}" alt="${product.name}" class="product-image">
<div class="product-info">
    <h3 class="product-name">${product.name}</h3>
    <div class="product-price">₱${product.price.toFixed(2)}</div>
    <div class="product-tag">${product.tag}</div>
    <p class="product-description">${product.description}</p>
    <form action="menu2.php" method="post">
        <input type="hidden" name="product_id" value="${product.id}">
        <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
    </form>
</div>
            `;
            
            // Add event listeners for opening the product modal
            const productImage = card.querySelector('.product-image');
            const productName = card.querySelector('.product-name');
            const productDesc = card.querySelector('.product-description');
            
            productImage.addEventListener('click', () => openProductModal(product));
            productName.addEventListener('click', () => openProductModal(product));
            productDesc.addEventListener('click', () => openProductModal(product));
            
            productGrid.appendChild(card);
        });
        
        // Render pagination
        renderPagination();
    }
    
    // Render pagination controls
    function renderPagination() {
        const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
        
        // Clear pagination
        pagination.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '&laquo;';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderProducts();
            }
        });
        pagination.appendChild(prevBtn);
        
        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'page-btn';
            if (i === currentPage) {
                pageBtn.classList.add('active');
            }
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                renderProducts();
            });
            pagination.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = '&raquo;';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderProducts();
            }
        });
        pagination.appendChild(nextBtn);
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
    
    // Initial rendering
    renderProducts();
    
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
</script>
</body>
</html>
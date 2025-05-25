
<?php
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

// Load categories from categories.xml - this will contain all admin-added categories
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

// This ensures our categories are unique and sorted
$categories = array_unique($categories);
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Menu</title>
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
    
    .header-container {
  display: flex;
  justify-content: space-between; /* logo sa kaliwa, nav sa kanan */
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
      align-items: 500px;
    }
    .nav-wrapper {
  flex: 1;
  display: flex;
  justify-content: center; /* center align ang nav */
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

    .right-nav {
      display: flex;
      align-items: center;
    }

    .right-nav a {
      margin-left: 20px;
      text-decoration: none;
      color: var(--text-color);
    }

    .hero {
      display: flex;
      height: 500px;
      position: relative;
    }

    .hero-text {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding-left: 50px;
      max-width: 50%;
    }

    .specialty {
      font-size: 16px;
      font-weight: 300;
      margin-bottom: 10px;
      color: #777;
    }

    .hero-title {
      font-size: 4rem;
      font-weight: 500;
      margin-bottom: 30px;
    }

    .cta-button {
      background-color: var(--accent-color);
      border: none;
      color: var(--text-color);
      padding: 12px 30px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
      width: fit-content;
    }

    .cta-button:hover {
      background-color: #dbc8b0;
    }

    .login a {
      text-decoration: none;
      color: var(--text-color);
      font-weight: 400;
      transition: color 0.3s;
    }

    .login a:hover {
      color: var(--accent-color);
    }
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
        
        /* Category Menu Styles */
        .category-menu {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 10px 18px;
            background-color: white;
            border: 2px solid var(--accent-color);
            border-radius: 25px;
            margin: 5px;
            cursor: pointer;
            font-weight: 500;
            color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .category-btn:hover, .category-btn.active {
            background-color: var(--accent-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex-grow: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(166, 124, 82, 0.2);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Product Grid Styles */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-image {
            height: 380px;
            width: 100%;
            object-fit: cover;
        }
        
        /* Modal Styles */
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
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .product-price {
            color: var(--accent-color);
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .product-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .product-tag {
            display: inline-block;
            background-color: rgba(166, 124, 82, 0.1);
            color: var(--accent-color);
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 15px;
        }
        
        .add-to-cart {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .add-to-cart:hover {
            background-color: var(--dark-color);
        }
        
        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }
        
        .page-btn {
            margin: 0 5px;
            padding: 8px 15px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover, .page-btn.active {
            background-color: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
        
        /* No results message */
        .no-results {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
            grid-column: 1 / -1;
        }
        
        /* Footer Styles */
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
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
            }
            
            .main-nav {
                margin-top: 15px;
                justify-content: center;
            }
            
            .main-nav li {
                margin: 0 10px;
            }
            
            .controls {
                flex-direction: column;
                align-items: center;
            }
            
            .search-box {
                margin-bottom: 15px;
                width: 100%;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
 
  <div class="header-container">
  <header>
    <div class="logo">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    
    <nav>
      <ul class="main-nav">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="login.php">Login</a></li>
      </ul>
    </nav>
    </div>
</header>
    
    <div class="container">
        <h1 class="page-title">Our Menu</h1>
        
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
                    <div class="modal-buttons">
                        <button class="modal-add-to-cart">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3 class="footer-title">About Us</h3>
                <p>We offer delicious pastries made with the finest ingredients. Our bakers create delightful treats every day.</p>
            </div>
            
            <div class="footer-section">
                <h3 class="footer-title">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">About</a></li>
                    <li><a href="#">Menu</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3 class="footer-title">Contact Info</h3>
                <ul class="footer-links">
                    <li>123 Bakery Street</li>
                    <li>Phone: +63 123 456 7890</li>
                    <li>Email: info@pastryshop.com</li>
                </ul>
            </div>
            
            <div class="copyright">
                &copy; 2025 Pastry Shop. All rights reserved.
            </div>
        </div>
    </footer>
    
    <script>
        // Convert PHP array to JavaScript array
        const products = [
            <?php foreach ($pastries as $item): ?>
            {
                id: "<?= $item['id'] ?>",
                name: "<?= htmlspecialchars($item->name) ?>",
                price: <?= floatval($item->price) ?>,
                description: "<?= htmlspecialchars($item->description ?? '') ?>",
                image: "<?= htmlspecialchars($item->image) ?>",
                category: "<?= htmlspecialchars($item->producttype) ?>",
                tag: "<?= htmlspecialchars($item->producttag ?? '') ?>"
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
            
            productModal.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        }
        
        // Filter products based on selected category and search term
        function filterProducts() {
            const activeCategory = document.querySelector('.category-btn.active').dataset.category;
            const searchTerm = tagSearch.value.toLowerCase();
            
            filteredProducts = products.filter(product => {
                const matchesCategory = activeCategory === 'all' || product.category === activeCategory;
                const matchesSearch = product.tag.toLowerCase().includes(searchTerm);
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
                        <button class="add-to-cart">Add to Cart</button>
                    </div>
                `;
                
                // Add click event to open modal for the card without the button
                card.querySelector('.product-image').addEventListener('click', () => openProductModal(product));
                card.querySelector('.product-name').addEventListener('click', () => openProductModal(product));
                card.querySelector('.product-description').addEventListener('click', () => openProductModal(product));
                
                // Add click event for Add to Cart button to redirect to login
                card.querySelector('.add-to-cart').addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent modal from opening
                    window.location.href = 'login.php';
                });
                
                productGrid.appendChild(card);
                
                // Add click event to open modal
                card.addEventListener('click', () => openProductModal(product));
                
                productGrid.appendChild(card);
            });
            
            // Update pagination
            renderPagination();
        }
        
        // Render pagination buttons
        function renderPagination() {
            const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
            pagination.innerHTML = '';
            
            // Previous button
            if (totalPages > 1) {
                const prevButton = document.createElement('button');
                prevButton.className = 'page-btn prev';
                prevButton.innerHTML = '&laquo;';
                prevButton.disabled = currentPage === 1;
                prevButton.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        renderProducts();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
                pagination.appendChild(prevButton);
            }
            
            // Page buttons
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement('button');
                pageButton.className = `page-btn ${i === currentPage ? 'active' : ''}`;
                pageButton.textContent = i;
                pageButton.addEventListener('click', () => {
                    currentPage = i;
                    renderProducts();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                pagination.appendChild(pageButton);
            }
            
            // Next button
            if (totalPages > 1) {
                const nextButton = document.createElement('button');
                nextButton.className = 'page-btn next';
                nextButton.innerHTML = '&raquo;';
                nextButton.disabled = currentPage === totalPages;
                nextButton.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderProducts();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
                pagination.appendChild(nextButton);
            }
        }
        
        // Handle keyboard events for modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && productModal.classList.contains('show')) {
                productModal.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Initial render
        renderProducts();
    </script>
</body>
</html>
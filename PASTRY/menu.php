<?php 
session_start(); 

error_reporting(E_ALL);
ini_set('display_errors', 1);

$xmlPath = 'C:/xampp/htdocs/PASTRY/PASTRY/pastry.xml';
$pastries = [];



if (file_exists($xmlPath)) {
    libxml_use_internal_errors(true);
    $file = simplexml_load_file($xmlPath);
    if ($file === false) {
        echo "Failed loading XML:";
        foreach(libxml_get_errors() as $error) {
            echo "<br>", htmlspecialchars($error->message);
        }
        exit();
    }
    foreach ($file->pastry as $row) {
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[] = $row;
    }
} else {
    echo "XML file does not exist.";
    exit();
}

$categories = array_unique(array_map(fn($p) => (string)$p->producttype, $pastries));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>La Croissanterie - Menu</title>
  <link rel="stylesheet" href="style.css" /> 
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

        /* Menu Page Specific Styles */
        .page-title {
            text-align: center;
            margin: 40px 0;
            color: var(--primary-color);
            font-weight: 300;
            letter-spacing: 2px;
            font-size: 28px;
            position: relative;
        }
        
        .page-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 1px;
            background-color: var(--accent-color);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .header-controls select, .header-controls input {
            width: 200px;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background-color: #fff;
            color: var(--text-color);
            font-family: var(--font-main);
        }
        
        .header-controls select:focus, .header-controls input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .product-container {
            margin-top: 30px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        
        .pagination {
            display: flex;
            gap: 10px;
        }
        
        .page-button {
            padding: 8px 15px;
            border: 1px solid var(--accent-color);
            background-color: white;
            color: var(--accent-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-button:hover {
            background-color: #f0e8e0;
        }
        
        .page-button.active {
            background-color: var(--accent-color);
            color: white;
        }
        
        .product-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 15px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.12);
        }
        
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .product-name {
            font-weight: 500;
            font-size: 17px;
            margin-bottom: 8px;
            color: var(--primary-color);
        }
        
        .product-price {
            color: var(--accent-color);
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .product-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
            text-align: left;
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .add-cart-btn {
            border: 1px solid var(--primary-color);
            background: transparent;
            border-radius: 4px;
            padding: 8px 20px;
            font-weight: 400;
            color: var(--primary-color);
            transition: all 0.3s;
            cursor: pointer;
            text-transform: lowercase;
            letter-spacing: 0.5px;
            margin-top: auto;
        }
        
        .add-cart-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Footer Styles from Homepage */
        footer {
            background-color: white;
            color: var(--text-color);
            text-align: center;
            padding: 40px 20px;
            margin-top: 60px;
            border-top: 1px solid #eee;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin: 10px;
            text-align: left;
        }
        
        .footer-section h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            font-weight: 400;
            letter-spacing: 1px;
            font-size: 16px;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section li {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .footer-section a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: var(--accent-color);
        }
        
        .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 20px;
            }
            
            .header-controls {
                flex-direction: column;
                gap: 10px;
            }
            
            .header-controls select, .header-controls input {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
  <div class="header-container">
    <header>
      <div class="logo">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"/><path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"/></svg>
        <span class="logo-text">La Croissanterie</span>
      </div>
      <nav>
        <ul class="main-nav">	
		
          <li><a href="homepage.php">Home</a></li>
          <li><a href="about.php">About</a></li>
          <li><a href="menu.php">Menu</a></li>
          <li><a href="login.php">Login</a></li>
		  <div class="right-nav">
  <a href="cart.php" style="position: relative;">
    🛒 Cart <span id="cartItemCount" style="background: var(--accent-color); color: white; font-size: 12px; padding: 2px 6px; border-radius: 50%; position: absolute; top: -8px; right: -15px;">0</span>
  </a>
</div>
       
        </ul>
      </nav>
    </header>
  </div>

  <div class="container">
    <h1 class="page-title">Our Pastry Menu</h1>

    <div class="header-controls">
      <select id="categoryFilter">
        <option value="All">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>

      <select id="priceSort">
        <option value="default">Sort by Price</option>
        <option value="asc">Lowest to Highest</option>
        <option value="desc">Highest to Lowest</option>
      </select>

      <input type="text" id="tagSearch" placeholder="Search by Tag...">
    </div>

    <div class="product-grid product-container" id="productGrid">
      <!-- JS renders products here -->
    </div>

    <div class="pagination-container">
      <div class="pagination" id="pagination"></div>
    </div>
  </div>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h3>La Croissanterie</h3>
        <p>Quality baked goods made with love and care.</p>
      </div>
      <div class="footer-section">
        <h3>Quick Links</h3>
        <ul>
          <li><a href="homepage.php">Home</a></li>
          <li><a href="menu.php">Products</a></li>
          <li><a href="#">Franchising</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h3>Contact</h3>
        <ul>
          <li>Email: info@lacroissanterie.com</li>
          <li>Phone: +63 123 456 7890</li>
          <li>Address: 123 Bakery Street, Manila</li>
        </ul>
      </div>
    </div>
    <div class="copyright">
      &copy; 2025 La Croissanterie. All rights reserved.
    </div>
  </footer>

  <script>
  const productData = [
    <?php foreach ($pastries as $item): ?>
    {
      id: "<?= $item['id'] ?>",
      name: "<?= htmlspecialchars($item->name, ENT_QUOTES) ?>",
      price: <?= (float)$item->price ?>,
      image: "<?= htmlspecialchars($item->image, ENT_QUOTES) ?>",
      category: "<?= htmlspecialchars($item->producttype, ENT_QUOTES) ?>",
      tag: "<?= htmlspecialchars($item->producttag, ENT_QUOTES) ?>",
      description: "<?= htmlspecialchars($item->description ?? '', ENT_QUOTES) ?>"
    },
    <?php endforeach; ?>
  ];

  const categoryFilter = document.getElementById('categoryFilter');
  const priceSort = document.getElementById('priceSort');
  const tagSearch = document.getElementById('tagSearch');
  const productGrid = document.getElementById('productGrid');
  const paginationContainer = document.getElementById('pagination');
  const productsPerPage = 6;
  let currentPage = 1;
  let filteredProducts = [...productData];

  let cart = JSON.parse(localStorage.getItem('cart')) || {};

  function updateCartCount() {
    const count = Object.values(cart).reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cartItemCount').textContent = count;
  }

  function addToCart(product) {
    if (cart[product.id]) {
      cart[product.id].quantity += 1;
    } else {
      cart[product.id] = {
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        quantity: 1
      };
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
    alert(`${product.name} has been added to your cart!`);
  }

  function filterAndSort() {
    const selectedCategory = categoryFilter.value;
    const sortOrder = priceSort.value;
    const tag = tagSearch.value.toLowerCase();

    filteredProducts = productData.filter(p => {
      const matchCat = selectedCategory === 'All' || p.category === selectedCategory;
      const matchTag = p.tag.toLowerCase().includes(tag);
      return matchCat && matchTag;
    });

    if (sortOrder === 'asc') filteredProducts.sort((a, b) => a.price - b.price);
    else if (sortOrder === 'desc') filteredProducts.sort((a, b) => b.price - a.price);

    currentPage = 1;
    renderProducts();
  }

  function renderProducts() {
    const start = (currentPage - 1) * productsPerPage;
    const end = start + productsPerPage;
    const currentItems = filteredProducts.slice(start, end);

    productGrid.innerHTML = currentItems.map(p => `
      <div class="product-card">
        <img src="${p.image}" alt="${p.name}">
        <div class="product-name">${p.name}</div>
        <div class="product-price">₱${p.price.toFixed(2)}</div>
        <div class="product-description">${p.description}</div>
        <button class="add-cart-btn" onclick='addToCart(${JSON.stringify(p)})'>Add to Cart</button>
      </div>
    `).join('');

    renderPagination();
  }

  function renderPagination() {
    const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
    paginationContainer.innerHTML = '';

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.className = 'page-button' + (i === currentPage ? ' active' : '');
      btn.textContent = i;
      btn.addEventListener('click', () => {
        currentPage = i;
        renderProducts();
      });
      paginationContainer.appendChild(btn);
    }
  }

  categoryFilter.addEventListener('change', filterAndSort);
  priceSort.addEventListener('change', filterAndSort);
  tagSearch.addEventListener('input', filterAndSort);

  // Initial render
  updateCartCount();
  renderProducts();
</script>

</body>
</html>

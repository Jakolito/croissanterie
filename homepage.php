<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>La Croissanterie Shop</title>
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

    /* Hero Section */
    .hero {
      height: 100vh;
      display: flex;
      align-items: center;
      position: relative;
      background: linear-gradient(135deg, var(--light-color) 0%, #f0ebe3 100%);
      margin-top: 70px;
    }

    .hero-content {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 30px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .hero-text {
      animation: fadeInLeft 1s ease-out;
    }

    .specialty {
      font-size: 18px;
      font-weight: 300;
      margin-bottom: 15px;
      color: var(--accent-color);
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .hero-title {
      font-size: 4.5rem;
      font-weight: 300;
      margin-bottom: 25px;
      color: var(--primary-color);
      line-height: 1.1;
      letter-spacing: -2px;
    }

    .hero-subtitle {
      font-size: 1.2rem;
      color: #666;
      margin-bottom: 40px;
      line-height: 1.8;
      max-width: 450px;
    }

    .cta-button {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 16px 40px;
      font-size: 16px;
      font-weight: 400;
      cursor: pointer;
      transition: all 0.3s ease;
      border-radius: 30px;
      letter-spacing: 1px;
      text-transform: uppercase;
      box-shadow: 0 4px 15px rgba(81, 56, 38, 0.3);
    }

    .cta-button:hover {
      background: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(81, 56, 38, 0.4);
    }

    .hero-visual {
      position: relative;
      animation: fadeInRight 1s ease-out;
    }

    /* Slideshow Styles */
    .slideshow-container {
      position: relative;
      height: 500px;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    
    .slide {
      display: none;
      position: relative;
      height: 100%;
    }
    
    .slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    
    .slide:hover img {
      transform: scale(1.05);
    }
    
    .active {
      display: block;
    }

    .caption {
      position: absolute;
      bottom: 30px;
      left: 30px;
      right: 30px;
      color: white;
      font-size: 24px;
      font-weight: 300;
      background: rgba(0, 0, 0, 0.6);
      padding: 20px 30px;
      border-radius: 15px;
      backdrop-filter: blur(10px);
      text-align: center;
      letter-spacing: 1px;
    }

    .dots {
      position: absolute;
      bottom: -50px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 12px;
    }
    
    .dot {
      cursor: pointer;
      height: 12px;
      width: 12px;
      background-color: rgba(166, 124, 82, 0.3);
      border-radius: 50%;
      transition: all 0.3s ease;
      border: 2px solid transparent;
    }
    
    .dot.active {
      background-color: var(--accent-color);
      transform: scale(1.2);
      border-color: var(--primary-color);
    }

    .dot:hover {
      background-color: var(--accent-color);
      transform: scale(1.1);
    }

    /* Products Section */
    .products-section {
      padding: 100px 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .section-header {
      text-align: center;
      margin-bottom: 80px;
    }
    
    .section-title {
      font-size: 3rem;
      margin-bottom: 20px;
      color: var(--primary-color);
      font-weight: 300;
      letter-spacing: -1px;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.8;
    }
    
    .products-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 40px;
    }
    
    .product-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.4s ease;
      position: relative;
    }
    
    .product-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }
    
    .product-image {
      width: 100%;
      height: 250px;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .product-card:hover .product-image {
      transform: scale(1.1);
    }
    
    .product-info {
      padding: 30px;
    }
    
    .product-name {
      font-weight: 500;
      font-size: 1.3rem;
      margin-bottom: 12px;
      color: var(--primary-color);
    }
    
    .product-price {
      font-size: 1.2rem;
      color: var(--accent-color);
      font-weight: 600;
      margin-bottom: 15px;
    }
    
    .product-description {
      font-size: 14px;
      color: #777;
      margin-bottom: 25px;
      line-height: 1.6;
    }
    
    .buy-button {
      width: 100%;
      background: var(--light-color);
      color: var(--primary-color);
      border: 2px solid var(--primary-color);
      padding: 12px 20px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 14px;
    }
    
    .buy-button:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-2px);
    }

    /* Footer */
    footer {
      background: var(--primary-color);
      color: white;
      padding: 60px 30px 30px;
      margin-top: 50px;
    }
    
    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .footer-section h3 {
      margin-bottom: 25px;
      color: var(--light-color);
      font-weight: 400;
      font-size: 1.2rem;
      letter-spacing: 1px;
    }
    
    .footer-section ul {
      list-style: none;
    }
    
    .footer-section li {
      margin-bottom: 12px;
      font-size: 14px;
      opacity: 0.9;
    }
    
    .footer-section a {
      color: white;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    
    .footer-section a:hover {
      color: var(--accent-color);
    }
    
    .copyright {
      margin-top: 40px;
      padding-top: 30px;
      border-top: 1px solid rgba(255,255,255,0.2);
      text-align: center;
      font-size: 14px;
      opacity: 0.8;
    }
    
    /* Loading Animation */
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 200px;
    }
    
    .loading::after {
      content: "";
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 3px solid var(--accent-color);
      border-color: var(--accent-color) transparent var(--accent-color) transparent;
      animation: loading 1.2s linear infinite;
    }
    
    @keyframes loading {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @keyframes fadeInLeft {
      from {
        opacity: 0;
        transform: translateX(-50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes fadeInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-content {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
      }

      .hero-title {
        font-size: 3rem;
      }

      .main-nav {
        gap: 20px;
      }

      .products-container {
        grid-template-columns: 1fr;
      }

      .slideshow-container {
        height: 300px;
      }
    }
  </style>
</head>
<body>

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

<!-- Hero Section -->
<section class="hero" id="home">
  <div class="hero-content">
    <div class="hero-text">
      <p class="specialty">Artisan Bakery</p>
      <h1 class="hero-title">Fresh Baked<br>Daily</h1>
      <p class="hero-subtitle">Experience the finest selection of handcrafted pastries, croissants, and desserts made with premium ingredients and traditional techniques.</p>
      <button class="cta-button" onclick="scrollToProducts()">Explore Menu</button>
    </div>
    
    <div class="hero-visual">
      <div class="slideshow-container">
        <div class="slide active">
          <img src="uploads/slide1.jpg" alt="Strawberry Vanilla Bean Bliss">
          <div class="caption">Strawberry Vanilla Bean Bliss</div>
        </div>
        <div class="slide">
          <img src="uploads/slide2.jpg" alt="Red Velvet Messy Bun">
          <div class="caption">Red Velvet Messy Bun</div>
        </div>
        <div class="slide">
          <img src="uploads/slide3.jpg" alt="Brookie Pizza">
          <div class="caption">Signature Brookie Pizza</div>
        </div>
      </div>
      
      <div class="dots">
        <span class="dot active" onclick="showSlide(0)"></span>
        <span class="dot" onclick="showSlide(1)"></span>
        <span class="dot" onclick="showSlide(2)"></span>
      </div>
    </div>
  </div>
</section>

<!-- Products Section -->
<section class="products-section" id="menu">
  <div class="section-header">
    <h2 class="section-title">Featured Products</h2>
    <p class="section-subtitle">Discover our carefully curated selection of premium baked goods, each crafted with passion and the finest ingredients.</p>
  </div>
  <div id="products-container" class="products-container">
    <div class="loading"></div>
  </div>
</section>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>La Croissanterie</h3>
      <p>Quality baked goods made with love and care. Bringing artisan craftsmanship and premium ingredients to create unforgettable flavors that delight every palate.</p>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#menu">Products</a></li>
        <li><a href="#about">About Us</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div>
    <div class="footer-section">
      <h3>Contact Info</h3>
      <ul>
        <li>📧 info@lacroissanterie.com</li>
        <li>📞 +63 123 456 7890</li>
        <li>📍 123 Bakery Street, Manila</li>
        <li>🕒 Daily: 6AM - 10PM</li>
      </ul>
    </div>
  </div>
  <div class="copyright">
    &copy; 2025 La Croissanterie. All rights reserved. Made with ❤️ for pastry lovers.
  </div>
</footer>

<script>
  // Smooth scrolling
  function scrollToProducts() {
    document.getElementById('menu').scrollIntoView({ 
      behavior: 'smooth' 
    });
  }

  // Header scroll effect
  window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 100) {
      header.style.background = 'rgba(245, 241, 235, 0.98)';
      header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
    } else {
      header.style.background = 'rgba(245, 241, 235, 0.95)';
      header.style.boxShadow = 'none';
    }
  });

  // Slideshow functionality
  let currentSlide = 0;
  const slides = document.querySelectorAll(".slide");
  const dots = document.querySelectorAll(".dot");

  function showSlide(index) {
    slides.forEach((slide, i) => {
      slide.classList.remove("active");
      dots[i].classList.remove("active");
    });
    slides[index].classList.add("active");
    dots[index].classList.add("active");
    currentSlide = index;
  }

  function autoSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }

  setInterval(autoSlide, 6000);

  // Product data using your uploaded images
  const productsData = [
    {
      id: 1,
      name: "Vanilla Bean Strawberry Bliss",
      price: "250",
      description: "A delightful cake featuring layers of vanilla bean sponge with fresh strawberry filling, topped with vanilla buttercream.",
      image: "uploads/pic1.png"
    },
    {
      id: 2,
      name: "Cherry Blossom Bun",
      price: "40",
      description: "Soft and fluffy bun with a sweet cherry filling, decorated with pink cherry blossom motif.",
      image: "uploads/pic2.jpg"
    },
    {
      id: 3,
      name: "Mini Floss Pillows",
      price: "85",
      description: "Savory bite-sized pastries filled with pork floss, encased in a light, flaky pastry shell.",
      image: "uploads/pic3.jpg"
    },
    {
      id: 4,
      name: "Mini Cheese Pillows",
      price: "85",
      description: "Delectable mini pastries with a rich, creamy cheese filling in a buttery, flaky crust.",
      image: "uploads/pic4.jpg"
    },
    {
      id: 5,
      name: "Assorted Brookie",
      price: "350",
      description: "A delicious hybrid of brownies and cookies in assorted flavors, offering the perfect balance of chewy and crunchy textures.",
      image: "uploads/pic5.jpg"
    },
    {
      id: 6,
      name: "Ube Coco Brookie Pizza",
      price: "350",
      description: "A unique dessert pizza combining the flavors of purple yam and coconut in a brownie-cookie base, perfect for sharing.",
      image: "uploads/pic6.jpg"
    },

  ];

  // Display products
  function displayProducts() {
    const productsContainer = document.getElementById("products-container");
    productsContainer.innerHTML = "";
    
    productsData.forEach(product => {
      const productCard = document.createElement("div");
      productCard.className = "product-card";
      productCard.innerHTML = `
        <img src="${product.image}" alt="${product.name}" class="product-image">
        <div class="product-info">
          <h3 class="product-name">${product.name}</h3>
          <p class="product-price">₱${product.price}</p>
          <p class="product-description">${product.description}</p>
          <button class="buy-button" onclick="addToCart(${product.id})">Add to Cart</button>
        </div>
      `;
      productsContainer.appendChild(productCard);
    });
  }

  function addToCart(id) {
    const product = productsData.find(p => p.id === id);
    alert(`${product.name} has been added to your cart!`);
  }

  // Load products with animation
  setTimeout(() => {
    displayProducts();
    
    // Animate product cards on scroll
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, observerOptions);
    
    document.querySelectorAll('.product-card').forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      card.style.transition = `all 0.6s ease ${index * 0.1}s`;
      observer.observe(card);
    });
  }, 1000);
</script>

</body>
</html>
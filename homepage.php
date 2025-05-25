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

    .slideshow-container {
      max-width: 100%;
      position: relative;
      overflow: hidden;
      height: 700px;
      box-shadow: inset 0 0 100px rgba(0,0,0,0.2);
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
      filter: brightness(0.85) contrast(1.1);
    }
    
    .active {
      display: block;
    }

    .caption {
      position: absolute;
      bottom: 60px;
      left: 50%;
      transform: translateX(-50%);
      color: white;
      font-size: 28px;
      font-weight: 300;
      background: rgba(0, 0, 0, 0.3);
      padding: 10px 30px;
      text-transform: lowercase;
      letter-spacing: 3px;
      border-radius: 2px;
      text-align: center;
      width: auto;
    }

    .dots {
      text-align: center;
      padding: 12px;
    }
    
    .dot {
      cursor: pointer;
      height: 8px;
      width: 8px;
      margin: 0 5px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.3s;
    }
    
    .dot.active {
      background-color: var(--accent-color);
    }

    .products-section {
      padding: 60px 40px;
      text-align: center;
    }
    
    .section-title {
      font-size: 24px;
      margin-bottom: 40px;
      color: var(--primary-color);
      position: relative;
      display: inline-block;
      font-weight: 300;
      text-transform: lowercase;
      letter-spacing: 2px;
    }
    
    .section-title:after {
      content: '';
      display: block;
      width: 40px;
      height: 1px;
      background-color: var(--accent-color);
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
    }
    
    .products-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .product-card {
      background-color: white;
      border-radius: 2px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .product-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.12);
    }
    
    .product-image {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    
    .product-info {
      padding: 20px;
    }
    
    .product-name {
      font-weight: 400;
      font-size: 16px;
      margin: 0 0 8px 0;
      color: var(--primary-color);
    }
    
    .product-price {
      font-size: 15px;
      color: var(--accent-color);
      font-weight: 400;
      margin-bottom: 12px;
    }
    
    .product-description {
      font-size: 13px;
      color: #777;
      margin-bottom: 18px;
      line-height: 1.5;
    }
    
    .buy-button {
      background-color: var(--light-color);
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      padding: 8px 15px;
      border-radius: 2px;
      cursor: pointer;
      font-weight: 400;
      transition: all 0.3s;
      text-transform: lowercase;
      letter-spacing: 0.5px;
    }
    
    .buy-button:hover {
      background-color: var(--primary-color);
      color: white;
    }

    footer {
      background-color: white;
      color: var(--text-color);
      text-align: center;
      padding: 40px 20px;
      margin-top: 30px;
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
    
    .loading {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 200px;
    }
    
    .loading:after {
      content: " ";
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 2px solid var(--accent-color);
      border-color: var(--accent-color) transparent var(--accent-color) transparent;
      animation: loading 1.2s linear infinite;
    }
    
    @keyframes loading {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
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


<!-- Slideshow -->
<div class="slideshow-container">
  <div class="slide active">
    <img src="uploads/slide1.png" alt="Strawberry Vanilla Bean Bliss">
  </div>
  <div class="slide">
    <img src="uploads/slide2.png" alt="Red Velvent Messy Bun">
  </div>
  <div class="slide">
    <img src="uploads/slide3.png" alt="Brookie Pizza">
  </div>
</div>

<div class="dots">
  <span class="dot active" onclick="showSlide(0)"></span>
  <span class="dot" onclick="showSlide(1)"></span>
  <span class="dot" onclick="showSlide(2)"></span>
</div>

<!-- Products Section -->
<section class="products-section">
  <h2 class="section-title">Our Featured Products</h2>
  <div id="products-container" class="products-container">
    <div class="loading"></div>
  </div>
</section>

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

  setInterval(autoSlide, 5000); // Slide every 5 seconds

  // XML Products data
  const productsXML = `
  <products>
    <product>
      <id>1</id>
      <name>Vanilla Bean Strawberry Bliss</name>
      <price>250</price>
      <description> "A delightful cake featuring layers of vanilla bean sponge with fresh strawberry filling, topped with vanilla buttercream."</description>
      <image>uploads/pic1.jpg</image>
    </product>
    <product>
      <id>2</id>
      <name>Cherry Blossom Bun</name>
      <price>40</price>
      <description>"Soft and fluffy bun with a sweet cherry filling, decorated with pink cherry blossom motif."</description>
      <image>uploads/pic2.jpg</image>
    </product>
    <product>
      <id>3</id>
      <name>Mini Floss Pillows</name>
      <price>85</price>
      <description>"Savory bite-sized pastries filled with pork floss, encased in a light, flaky pastry shell."</description>
      <image>uploads/pic3.jpg</image>
    </product>
    <product>
      <id>4</id>
      <name>Mini Cheese Pillows</name>
      <price>85</price>
      <description> "Delectable mini pastries with a rich, creamy cheese filling in a buttery, flaky crust."</description>
      <image>uploads/pic4.jpg</image>
    </product>
    <product>
      <id>5</id>
      <name>Assorted Brookie</name>
      <price>350</price>
      <description>"A delicious hybrid of brownies and cookies in assorted flavors, offering the perfect balance of chewy and crunchy textures."</description>
      <image>uploads/pic5.jpg</image>
    </product>
    <product>
      <id>6</id>
      <name>Ube Coco Brookie Pizza </name>
      <price>350</price>
      <description>"A unique dessert pizza combining the flavors of purple yam and coconut in a brownie-cookie base, perfect for sharing."</description>
      <image>uploads/pic6.jpg</image>
    </product>
    <product>
      <id>7</id>
      <name>Choco Chip Brookie Pizza</name>
      <price>350</price>
      <description>"Indulgent chocolate chip cookie-brownie fusion in pizza form, loaded with chocolate chips throughout."</description>
      <image>uploads/pic7.jpg</image>
    </product>
    <product>
      <id>8</id>
      <name>Three Cheese Brookie Pizza</name>
      <price>350</price>
      <description>"Decadent brookie pizza featuring three premium cheeses blended into a sweet-savory dessert experience."</description>
      <image>uploads/pic8.jpg</image>
    </product>
  </products>
  `;

  // Parse XML and display products
  function displayProducts() {
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(productsXML, "text/xml");
    const products = xmlDoc.getElementsByTagName("product");
    const productsContainer = document.getElementById("products-container");
    
    // Clear loading indicator
    productsContainer.innerHTML = "";
    
    for (let i = 0; i < products.length; i++) {
      const product = products[i];
      const id = product.getElementsByTagName("id")[0].textContent;
      const name = product.getElementsByTagName("name")[0].textContent;
      const price = product.getElementsByTagName("price")[0].textContent;
      const description = product.getElementsByTagName("description")[0].textContent;
      const image = product.getElementsByTagName("image")[0].textContent;
      
      const productCard = document.createElement("div");
      productCard.className = "product-card";
      productCard.innerHTML = `
        <img src="${image}" alt="${name}" class="product-image">
        <div class="product-info">
          <h3 class="product-name">${name}</h3>
          <p class="product-price">₱${price}</p>
          <p class="product-description">${description}</p>
        </div>
      `;
      
      productsContainer.appendChild(productCard);
    }
  }

  // Simulate adding to cart
  function addToCart(id) {
    alert(`Product #${id} added to cart!`);
  }

  // Load products after a small delay to simulate fetching from a server
  setTimeout(displayProducts, 1000);
</script>

</body>
</html>
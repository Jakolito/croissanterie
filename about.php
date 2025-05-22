<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - La Croissanterie</title>
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

    /* About Page Header Banner */
    .about-banner {
      height: 400px;
      background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('uploads/about-banner.png');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      position: relative;
    }

    .banner-content {
      text-align: center;
      z-index: 2;
    }

    .banner-title {
      font-size: 3rem;
      font-weight: 300;
      margin-bottom: 20px;
      letter-spacing: 2px;
    }

    .banner-subtitle {
      font-size: 1.2rem;
      font-weight: 300;
      max-width: 700px;
      margin: 0 auto;
    }

    /* About Content Sections */
    .about-section {
      padding: 60px 40px;
      max-width: 1200px;
      margin: 0 auto;
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
      left: 0;
    }

    .about-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      align-items: center;
    }

    .about-image {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 2px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .about-text {
      font-size: 16px;
      line-height: 1.8;
      color: var(--text-color);
    }

    .values-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 30px;
      margin-top: 40px;
    }

    .value-card {
      background-color: white;
      padding: 30px;
      border-radius: 2px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .value-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.12);
    }

    .value-icon {
      font-size: 36px;
      color: var(--accent-color);
      margin-bottom: 15px;
    }

    .value-title {
      font-size: 18px;
      font-weight: 400;
      margin-bottom: 15px;
      color: var(--primary-color);
    }

    .team-section {
      background-color: white;
      padding: 60px 40px;
      text-align: center;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 40px auto 0;
    }

    .team-member {
      position: relative;
      overflow: hidden;
      border-radius: 2px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .team-member:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.12);
    }

    .team-image {
      width: 100%;
      height: 300px;
      object-fit: cover;
      transition: transform 0.3s;
    }

    .team-member:hover .team-image {
      transform: scale(1.05);
    }

    .team-info {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(transparent, rgba(0,0,0,0.7));
      color: white;
      padding: 20px;
      text-align: left;
    }

    .team-name {
      font-size: 18px;
      font-weight: 400;
      margin-bottom: 5px;
    }

    .team-role {
      font-size: 14px;
      font-weight: 300;
      color: rgba(255,255,255,0.8);
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .about-grid {
        grid-template-columns: 1fr;
      }
      
      .header-container {
        flex-direction: column;
        padding: 20px;
      }
      
      .main-nav {
        margin-top: 20px;
      }
      
      .about-section {
        padding: 40px 20px;
      }
      
      .banner-title {
        font-size: 2.5rem;
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

<!-- About Banner -->
<div class="about-banner">
  <div class="banner-content">
    <h1 class="banner-title">Our Story</h1>
    <p class="banner-subtitle">Crafting delightful baked goods with passion and dedication since 2020</p>
  </div>
</div>

<!-- About Company Section -->
<section class="about-section">
  <h2 class="section-title">Who We Are</h2>
  <div class="about-grid">
    <div>
      <p class="about-text">
        Founded in 2020, La Croissanterie began as a small home bakery with a big dream: to bring Japanese-inspired pastries with a Filipino twist to the local community. What started as a passion project during uncertain times quickly blossomed into a beloved neighborhood establishment.
      </p>
      <p class="about-text">
        At La Croissanterie, we believe that great pastries come from great ingredients and even greater care. Every item is baked fresh daily using premium ingredients, traditional techniques, and innovative recipes that blend international flavors with local tastes.
      </p>
      <p class="about-text">
        Today, we continue to grow while maintaining our commitment to quality, creativity, and community. Our team of skilled bakers works tirelessly to create memorable treats that bring joy to our customers' everyday lives.
      </p>
    </div>
    <div>
      <img src="uploads/about.png" alt="La Croissanterie bakery interior" class="about-image">
    </div>
  </div>
</section>

<!-- Our Values Section -->
<section class="about-section">
  <h2 class="section-title">Our Values</h2>
  <p class="about-text">
    At La Croissanterie, our core values guide everything we do - from how we source our ingredients to how we serve our customers.
  </p>
  <div class="values-container">
    <div class="value-card">
      <div class="value-icon">✦</div>
      <h3 class="value-title">Quality First</h3>
      <p>We never compromise on ingredients or preparation. Every pastry that leaves our kitchen meets our exacting standards.</p>
    </div>
    <div class="value-card">
      <div class="value-icon">✦</div>
      <h3 class="value-title">Creative Innovation</h3>
      <p>We constantly explore new flavor combinations and techniques, pushing the boundaries of traditional pastry making.</p>
    </div>
    <div class="value-card">
      <div class="value-icon">✦</div>
      <h3 class="value-title">Community Connection</h3>
      <p>We're proud to be part of our local community and strive to create a welcoming space for all our customers.</p>
    </div>
  </div>
</section>

<!-- Our Process Section -->
<section class="about-section">
  <h2 class="section-title">Our Process</h2>
  <div class="about-grid">
    <div>
      <img src="uploads/process.png" alt="Bakers working in La Croissanterie kitchen" class="about-image">
    </div>
    <div>
      <p class="about-text">
        Our baking process begins long before the sun rises. Our dedicated team starts each day at 3:00 AM, preparing doughs, fillings, and creams from scratch. We believe that taking time is essential to creating exceptional pastries.
      </p>
      <p class="about-text">
        For our signature croissants and laminated pastries, we use a traditional three-day process. This slow fermentation develops complex flavors and creates the perfect flaky texture that our customers have come to love.
      </p>
      <p class="about-text">
        Throughout the day, we bake in small batches to ensure freshness. Any unsold items at the end of the day are donated to local food banks, supporting our community while reducing waste.
      </p>
    </div>
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
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="franchising.php">Franchising</a></li>
        <li><a href="contact.php">Contact Us</a></li>
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

</body>
</html>
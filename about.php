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

    /* Hero Banner */
    .about-hero {
      height: 70vh;
      background: linear-gradient(135deg, rgba(81, 56, 38, 0.8), rgba(166, 124, 82, 0.6)), 
                  url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f5f1eb" width="1200" height="600"/><g fill="%23a67c52" opacity="0.1"><circle cx="300" cy="150" r="80"/><circle cx="900" cy="300" r="120"/><circle cx="150" cy="450" r="60"/></g></svg>');
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      position: relative;
      margin-top: 70px;
    }

    .hero-content {
      text-align: center;
      max-width: 800px;
      padding: 0 30px;
      animation: fadeInUp 1s ease-out;
    }

    .hero-title {
      font-size: 4rem;
      font-weight: 300;
      margin-bottom: 25px;
      letter-spacing: -1px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .hero-subtitle {
      font-size: 1.3rem;
      font-weight: 300;
      opacity: 0.95;
      line-height: 1.6;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }

    /* Section Styles */
    .about-section {
      padding: 100px 30px;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
    }

    .section-header {
      text-align: center;
      margin-bottom: 80px;
    }

    .section-title {
      font-size: 3rem;
      font-weight: 300;
      color: var(--primary-color);
      margin-bottom: 20px;
      letter-spacing: -1px;
    }

    .section-subtitle {
      font-size: 1.1rem;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.8;
    }

    /* Story Section */
    .story-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
      margin-bottom: 60px;
    }

    .story-text {
      animation: fadeInLeft 0.8s ease-out;
    }

    .story-text p {
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 25px;
      color: #555;
    }

    .story-image {
      position: relative;
      animation: fadeInRight 0.8s ease-out;
    }

    .story-img {
      width: 100%;
      height: 400px;
      object-fit: cover;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      transition: transform 0.4s ease;
    }

    .story-img:hover {
      transform: scale(1.02);
    }

    /* Values Section */
    .values-section {
      background: white;
      border-radius: 30px;
      padding: 80px 60px;
      margin: 80px 0;
      box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    }

    .values-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 40px;
      margin-top: 50px;
    }

    .value-card {
      background: var(--light-color);
      padding: 40px 30px;
      border-radius: 20px;
      text-align: center;
      transition: all 0.4s ease;
      border: 1px solid rgba(166, 124, 82, 0.1);
      position: relative;
      overflow: hidden;
    }

    .value-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, var(--accent-color), var(--primary-color));
      transition: left 0.4s ease;
    }

    .value-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }

    .value-card:hover::before {
      left: 0;
    }

    .value-icon {
      font-size: 3rem;
      color: var(--accent-color);
      margin-bottom: 20px;
      display: block;
    }

    .value-title {
      font-size: 1.4rem;
      font-weight: 500;
      margin-bottom: 15px;
      color: var(--primary-color);
    }

    .value-description {
      color: #666;
      line-height: 1.6;
    }

    /* Process Section */
    .process-section {
      background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
      color: white;
      border-radius: 30px;
      padding: 80px 60px;
      margin: 80px 0;
      position: relative;
      overflow: hidden;
    }

    .process-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 400px;
      height: 400px;
      background: rgba(255,255,255,0.05);
      border-radius: 50%;
      z-index: 1;
    }

    .process-content {
      position: relative;
      z-index: 2;
    }

    .process-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
      margin-top: 50px;
    }

    .process-text p {
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 25px;
      opacity: 0.95;
    }

    .process-image {
      position: relative;
    }

    .process-img {
      width: 100%;
      height: 350px;
      object-fit: cover;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    /* Timeline Section */
    .timeline-section {
      padding: 100px 30px;
    }

    .timeline {
      position: relative;
      max-width: 800px;
      margin: 0 auto;
    }

    .timeline::after {
      content: '';
      position: absolute;
      width: 3px;
      background: var(--accent-color);
      top: 0;
      bottom: 0;
      left: 50%;
      margin-left: -1.5px;
    }

    .timeline-item {
      padding: 30px 40px;
      position: relative;
      background: inherit;
      width: 50%;
      animation: fadeIn 0.8s ease-out;
    }

    .timeline-item::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      right: -10px;
      background: var(--accent-color);
      border: 3px solid white;
      top: 50px;
      border-radius: 50%;
      z-index: 1;
      box-shadow: 0 0 0 3px var(--accent-color);
    }

    .timeline-item:nth-child(even) {
      left: 50%;
    }

    .timeline-item:nth-child(even)::after {
      left: -10px;
    }

    .timeline-content {
      padding: 30px;
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      position: relative;
    }

    .timeline-year {
      font-size: 1.8rem;
      font-weight: 600;
      color: var(--accent-color);
      margin-bottom: 10px;
    }

    .timeline-title {
      font-size: 1.3rem;
      font-weight: 500;
      color: var(--primary-color);
      margin-bottom: 15px;
    }

    .timeline-description {
      color: #666;
      line-height: 1.6;
    }

    /* Footer - Matching homepage */
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

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes fadeInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .section-title {
        font-size: 2rem;
      }
      
      .story-grid,
      .process-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }
      
      .values-section,
      .process-section {
        padding: 60px 30px;
        margin: 40px 0;
      }
      
      .timeline::after {
        left: 31px;
      }
      
      .timeline-item {
        width: 100%;
        padding-left: 70px;
        padding-right: 25px;
      }
      
      .timeline-item::after {
        left: 21px;
      }
      
      .timeline-item:nth-child(even) {
        left: 0%;
      }
      
      header {
        padding: 15px 30px;
      }
      
      .main-nav {
        gap: 20px;
      }
    }

    @media (max-width: 480px) {
      .hero-title {
        font-size: 2rem;
      }
      
      .about-section {
        padding: 60px 20px;
      }
      
      .values-section,
      .process-section {
        padding: 40px 20px;
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
<section class="about-hero">
  <div class="hero-content">
    <h1 class="hero-title">Our Story</h1>
    <p class="hero-subtitle">Crafting delightful baked goods with passion and dedication since 2020</p>
  </div>
</section>

<!-- Our Story Section -->
<section class="about-section">
  <div class="section-header">
    <h2 class="section-title">Who We Are</h2>
    <p class="section-subtitle">A journey of passion, creativity, and dedication to the art of baking</p>
  </div>
  
  <div class="story-grid">
    <div class="story-text">
      <p>Founded in 2020, La Croissanterie began as a small home bakery with a big dream: to bring Japanese-inspired pastries with a Filipino twist to the local community. What started as a passion project during uncertain times quickly blossomed into a beloved neighborhood establishment.</p>
      
      <p>At La Croissanterie, we believe that great pastries come from great ingredients and even greater care. Every item is baked fresh daily using premium ingredients, traditional techniques, and innovative recipes that blend international flavors with local tastes.</p>
      
      <p>Today, we continue to grow while maintaining our commitment to quality, creativity, and community. Our team of skilled bakers works tirelessly to create memorable treats that bring joy to our customers' everyday lives.</p>
    </div>
    <div class="story-image">
      <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="La Croissanterie bakery interior" class="story-img">
    </div>
  </div>
</section>

<!-- Our Values Section -->
<section class="about-section">
  <div class="values-section">
    <div class="section-header">
      <h2 class="section-title">Our Values</h2>
      <p class="section-subtitle">The principles that guide everything we do, from sourcing ingredients to serving customers</p>
    </div>
    
    <div class="values-grid">
      <div class="value-card">
        <span class="value-icon">🌟</span>
        <h3 class="value-title">Quality First</h3>
        <p class="value-description">We never compromise on ingredients or preparation. Every pastry that leaves our kitchen meets our exacting standards for taste, texture, and presentation.</p>
      </div>
      
      <div class="value-card">
        <span class="value-icon">🎨</span>
        <h3 class="value-title">Creative Innovation</h3>
        <p class="value-description">We constantly explore new flavor combinations and techniques, pushing the boundaries of traditional pastry making while respecting time-honored methods.</p>
      </div>
      
      <div class="value-card">
        <span class="value-icon">🤝</span>
        <h3 class="value-title">Community Connection</h3>
        <p class="value-description">We're proud to be part of our local community and strive to create a welcoming space where everyone feels at home and valued.</p>
      </div>
    </div>
  </div>
</section>

<!-- Our Process Section -->
<section class="about-section">
  <div class="process-section">
    <div class="process-content">
      <div class="section-header">
        <h2 class="section-title" style="color: white;">Our Process</h2>
        <p class="section-subtitle" style="color: rgba(255,255,255,0.9);">The dedication and craft behind every single pastry we create</p>
      </div>
      
      <div class="process-grid">
        <div class="process-text">
          <p>Our baking process begins long before the sun rises. Our dedicated team starts each day at 3:00 AM, preparing doughs, fillings, and creams from scratch. We believe that taking time is essential to creating exceptional pastries.</p>
          
          <p>For our signature croissants and laminated pastries, we use a traditional three-day process. This slow fermentation develops complex flavors and creates the perfect flaky texture that our customers have come to love.</p>
          
          <p>Throughout the day, we bake in small batches to ensure freshness. Any unsold items at the end of the day are donated to local food banks, supporting our community while reducing waste.</p>
        </div>
        <div class="process-image">
          <img src="uploads/process.jpg" alt="Bakers working in La Croissanterie kitchen" class="process-img">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Timeline Section -->
<section class="about-section">
  <div class="timeline-section">
    <div class="section-header">
      <h2 class="section-title">Our Journey</h2>
      <p class="section-subtitle">Key milestones in our growth and development</p>
    </div>
    
    <div class="timeline">
      <div class="timeline-item">
        <div class="timeline-content">
          <h3 class="timeline-year">2020</h3>
          <h4 class="timeline-title">The Beginning</h4>
          <p class="timeline-description">Started as a home bakery during the pandemic, focusing on Japanese-inspired pastries with local flavors.</p>
        </div>
      </div>
      
      <div class="timeline-item">
        <div class="timeline-content">
          <h3 class="timeline-year">2021</h3>
          <h4 class="timeline-title">First Location</h4>
          <p class="timeline-description">Opened our first physical store, bringing our artisan pastries directly to the community.</p>
        </div>
      </div>
      
      <div class="timeline-item">
        <div class="timeline-content">
          <h3 class="timeline-year">2023</h3>
          <h4 class="timeline-title">Signature Products</h4>
          <p class="timeline-description">Launched our signature Brookie Pizza and expanded our menu with unique fusion creations.</p>
        </div>
      </div>
      
      <div class="timeline-item">
        <div class="timeline-content">
          <h3 class="timeline-year">2025</h3>
          <h4 class="timeline-title">Growing Strong</h4>
          <p class="timeline-description">Continuing to innovate and serve our community with fresh, quality baked goods every day.</p>
        </div>
      </div>
    </div>
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
  // Header scroll effect
  window.addEventListener('scroll', () => {
    const header = document.querySelector('.header-container');
    if (window.scrollY > 100) {
      header.style.background = 'rgba(245, 241, 235, 0.98)';
      header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
    } else {
      header.style.background = 'rgba(245, 241, 235, 0.95)';
      header.style.boxShadow = 'none';
    }
  });

  // Smooth scrolling for navigation
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Intersection Observer for animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animated');
      }
    });
  }, observerOptions);

  // Observe elements for animation
  document.querySelectorAll('.value-card, .timeline-item').forEach(el => {
    observer.observe(el);
  });
</script>

</body>
</html>
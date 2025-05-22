<?php 
include('connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data
$username = $_SESSION['user'];
$query = "SELECT * FROM account WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - La Croissanterie</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
        justify-content: center;
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

    /* Dashboard Specific Styles */
    .dashboard-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .welcome-section {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .welcome-section h1 {
        color: var(--primary-color);
        margin-top: 0;
        font-weight: 500;
    }
    
    .welcome-section p {
        color: #777;
        font-size: 16px;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .dashboard-card {
        background-color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
        cursor: pointer;
    }
    
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    
    .dashboard-card i {
        font-size: 36px;
        color: var(--accent-color);
        margin-bottom: 15px;
        display: block;
    }
    
    .dashboard-card h3 {
        margin-top: 0;
        color: var(--primary-color);
    }
    
    .recent-orders {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    .recent-orders h2 {
        margin-top: 0;
        color: var(--primary-color);
        margin-bottom: 20px;
    }
    
    .order-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .order-table th, .order-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .order-table th {
        background-color: #f9f9f9;
        font-weight: 500;
    }
    
    .logout-btn {
        background-color: var(--accent-color);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
        text-decoration: none;
        font-size: 14px;
    }
    
    .logout-btn:hover {
        background-color: #8e6b47;
    }

    .view-button {
        background-color: var(--accent-color);
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .view-button:hover {
        background-color: #8e6b47;
    }

    .user-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
        gap: 10px;
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
                <li><a href="#">About</a></li>
                <li><a href="#">Menu</a></li>
                <li><a href="#">Feedback</a></li>
                <li><a href="userdashboard.php">My Account</a></li>
            </ul>
        </nav>
    </header>
</div>

<div class="dashboard-container">
    <div class="welcome-section">
        <div class="user-actions">
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname']); ?>!</h1>
        <p>Manage your account, view your order history, and explore our delicious pastries.</p>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <i class='bx bx-shopping-bag'></i>
            <h3>Order Now</h3>
            <p>Browse our menu and place a new order.</p>
            <a href="products-view.html" class="view-button">Shop Now</a>
        </div>
        
        <div class="dashboard-card">
            <i class='bx bx-user'></i>
            <h3>My Profile</h3>
            <p>View and update your personal information.</p>
            <a href="#" class="view-button">View Profile</a>
        </div>
        
        <div class="dashboard-card">
            <i class='bx bx-heart'></i>
            <h3>Favorites</h3>
            <p>Check your favorite products and save for later.</p>
            <a href="#" class="view-button">View Favorites</a>
        </div>
    </div>
    
    <div class="recent-orders">
        <h2>Recent Orders</h2>
        
        <?php
        // Sample query to fetch recent orders - you'll need to modify this based on your database structure
        $ordersQuery = "SELECT * FROM orders WHERE username = ? ORDER BY order_date DESC LIMIT 5";
        $stmt = mysqli_prepare($conn, $ordersQuery);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $ordersResult = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($ordersResult) > 0) {
                echo '<table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                while ($order = mysqli_fetch_assoc($ordersResult)) {
                    echo '<tr>
                            <td>#' . htmlspecialchars($order['order_id']) . '</td>
                            <td>' . htmlspecialchars($order['order_date']) . '</td>
                            <td>₱' . htmlspecialchars($order['total_amount']) . '</td>
                            <td>' . htmlspecialchars($order['status']) . '</td>
                            <td><button class="view-button">View Details</button></td>
                          </tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<p>You have no recent orders.</p>';
            }
            
            mysqli_stmt_close($stmt);
        } else {
            echo '<p>Unable to fetch your recent orders. Please try again later.</p>';
        }
        ?>
    </div>
</div>

</body>
</html>
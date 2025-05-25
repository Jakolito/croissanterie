<?php
include('connect.php');
session_start();

// Check if email is set in the session
if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
    echo "<script>alert('No email provided. Please login or register first.'); window.location.href = 'login.php';</script>";
    exit;
}

$email = $_SESSION['email']; // Get email from session

// Handle POST request for OTP verification
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = $_POST['otp'];

    // Fetch OTP and verification details from the database
    $query = "SELECT verification_code, verified FROM account WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Check if OTP matches and account isn't already verified
            if ($otp == $user['verification_code']) {
                if (!$user['verified']) {
                    // Mark account as verified
                    $update_query = "UPDATE account SET verified = 1 WHERE email = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    if ($update_stmt) {
                        mysqli_stmt_bind_param($update_stmt, "s", $email);
                        mysqli_stmt_execute($update_stmt);

                        if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                            echo "<script>alert('Account verified successfully!'); window.location.href = 'login.php';</script>";
                        } else {
                            echo "<script>alert('Verification failed. Please try again.'); window.location.href = 'verify.php';</script>";
                        }
                    }
                } else {
                    echo "<script>alert('Your account is already verified. Please log in.'); window.location.href = 'login.php';</script>";
                }
            } else {
                echo "<script>alert('Invalid OTP. Please try again.'); window.location.href = 'verify.php';</script>";
            }
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('System error: Unable to prepare query.'); window.location.href = 'verify.php';</script>";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <title>Email Verification - La Croissanterie</title>
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
    /* Verification Form Styling */
    .main-content {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: calc(100vh - 120px);
      padding: 20px;
    }
    
    .verify-container {
      background-color: #fff;
      width: 400px;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
    }
    
    .verify-container h2 {
      margin-bottom: 30px;
      color: var(--primary-color);
      font-weight: 400;
      letter-spacing: 1px;
    }
    
    .verify-form label {
      display: block;
      text-align: left;
      margin-bottom: 8px;
      color: var(--text-color);
      font-size: 14px;
    }
    
    .verify-form input[type="text"] {
      width: 100%;
      padding: 12px 15px;
      border-radius: 4px;
      border: 1px solid #ddd;
      margin-bottom: 20px;
      font-size: 16px;
      background-color: rgba(255, 255, 255, 0.9);
      color: var(--text-color);
      transition: 0.3s ease;
      box-sizing: border-box;
    }
    
    .verify-form input[type="text"]:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 5px rgba(166, 124, 82, 0.3);
      outline: none;
    }
    
    .verify-button {
      background-color: var(--accent-color);
      color: white;
      height: 45px;
      width: 100%;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 400;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .verify-button:hover {
      background-color: #8e6b47;
    }
    
    .info-text {
      margin-top: 20px;
      font-size: 14px;
      color: #777;
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
        <li><a href="login.php">Login</a></li>
      </ul>
    </nav>
    </div>
</header>

<div class="main-content">
  <div class="verify-container">
    <h2>Email Verification</h2>
    <form class="verify-form" method="POST" action="">
      <label for="otp">Enter Verification Code:</label>
      <input type="text" id="otp" name="otp" placeholder="Enter OTP" required>
      <button class="verify-button" type="submit">Verify</button>
    </form>
    <p class="info-text">Please enter the verification code sent to your email address.</p>
  </div>
</div>
</body>
</html>


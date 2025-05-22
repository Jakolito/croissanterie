<?php 
include('connect.php');
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - La Croissanterie</title>
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
    /* Login Form Styling */
    .main-content {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 120px); /* Subtract header height */
        padding: 20px;
    }

    .container-login {
        background-color: #fff;
        width: 400px;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        position: relative;
    }

    .container-login h2 {
        margin-bottom: 30px;
        color: var(--primary-color);
        font-weight: 400;
        letter-spacing: 1px;
    }

    .logo-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .form-logo {
        height: 75px;
        border-radius: 50%;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        border-radius: 4px;
        border: 1px solid #ddd;
        margin-bottom: 15px;
        font-size: 16px;
        background-color: rgba(255, 255, 255, 0.9);
        color: var(--text-color);
        transition: 0.3s ease;
        box-sizing: border-box;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 5px rgba(166, 124, 82, 0.3);
        outline: none;
    }

    .loginBtn {
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

    .loginBtn:hover {
        background-color: #8e6b47;
    }

    .form-footer {
        margin-top: 20px;
        font-size: 14px;
        color: #777;
    }

    .form-footer a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 400;
    }

    .form-footer a:hover {
        text-decoration: underline;
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
<div class="main-content">
    <div class="container-login">
        <h2>Log in</h2>
        
        <form action="login.php" method="POST">
            <input type="text" name="username" id="username" placeholder="Enter Username or Email" required>
            <input type="password" name="password" id="password" placeholder="Enter Password" required>
            <input class="loginBtn" type="submit" value="Log in" name="loginBtn" id="loginBtn">
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                <p>Forgotten Password? <a href="reset_password.php">Reset Password</a></p>
            </div>
        </form>
    </div>
</div>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = mysqli_real_escape_string($conn, htmlspecialchars($_POST['username']));
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all fields.'); window.location.href = 'login.php';</script>";
        exit;
    }

    // Admin login check
    $adminQuery = "SELECT * FROM admin WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $adminQuery);
    if (!$stmt) {
        die("Admin query preparation failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $adminResult = mysqli_stmt_get_result($stmt);

    if ($adminResult && mysqli_num_rows($adminResult) > 0) {
        $admin = mysqli_fetch_assoc($adminResult);

        if (password_verify($password, $admin['passwords']) || $password === $admin['passwords']) {
            $_SESSION['admin'] = $admin['email'];
            $_SESSION['admin_username'] = $admin['username'];
            echo "<script>alert('Welcome, Admin!'); window.location.href = 'admindashboard.php';</script>";
            exit;
        } else {
            echo "<script>alert('Invalid admin username or password.'); window.location.href = 'login.php';</script>";
            exit;
        }
    }

    // Regular user login check
    $userQuery = "SELECT * FROM account WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $userQuery);
    if (!$stmt) {
        die("User query preparation failed: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $userResult = mysqli_stmt_get_result($stmt);

    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $user = mysqli_fetch_assoc($userResult);

        // Check if account is already inactive
        if ($user['status'] === 'Inactive') {
            echo "<script>alert('Your account is inactive. Please contact admin for reactivation.'); window.location.href = 'login.php';</script>";
            exit();
        }

        // Verify password
        if (password_verify($password, $user['passwords']) || $password === $user['passwords']) {
            // Reset login attempts on successful login
            $resetAttemptsQuery = "UPDATE account SET failed_login_attempts = 0 WHERE AccountID = ?";
            $resetStmt = mysqli_prepare($conn, $resetAttemptsQuery);
            mysqli_stmt_bind_param($resetStmt, "i", $user['AccountID']);
            mysqli_stmt_execute($resetStmt);
            mysqli_stmt_close($resetStmt);
            
            // Set session variables and redirect
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['account_type'] = $user['account_type'];
            $_SESSION['user'] = $user['username'];

            echo "<script>alert('Login successful! Welcome back.'); window.location.href = 'menu2.php';</script>";
            exit;
        } else {
            // Increment failed login attempts
            $attempts = $user['failed_login_attempts'] + 1;
            
            if ($attempts >= 3) {
                // Block account after 3 failed attempts
                $blockAccountQuery = "UPDATE account SET status = 'Inactive', failed_login_attempts = ? WHERE AccountID = ?";
                $blockStmt = mysqli_prepare($conn, $blockAccountQuery);
                mysqli_stmt_bind_param($blockStmt, "ii", $attempts, $user['AccountID']);
                mysqli_stmt_execute($blockStmt);
                mysqli_stmt_close($blockStmt);
                
                echo "<script>alert('Account blocked due to multiple failed login attempts. Please contact admin for assistance.'); window.location.href = 'login.php';</script>";
                exit;
            } else {
                // Just increment the attempt counter
                $updateAttemptsQuery = "UPDATE account SET failed_login_attempts = ? WHERE AccountID = ?";
                $updateStmt = mysqli_prepare($conn, $updateAttemptsQuery);
                mysqli_stmt_bind_param($updateStmt, "ii", $attempts, $user['AccountID']);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                
                $remainingAttempts = 3 - $attempts;
                echo "<script>alert('Invalid password. You have $remainingAttempts attempts remaining before your account is blocked.'); window.location.href = 'login.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href = 'login.php';</script>";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
<?php
include ('connect.php');
session_start();

// Fix the paths to point to your local PHPMailer folder
require "./PHPMailer/PHPMailer/src/Exception.php";
require "./PHPMailer/PHPMailer/src/PHPMailer.php";
require "./PHPMailer/PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify reCAPTCHA
    $recaptchaSecret = '6Lc3xS0rAAAAAN0DNhkuL6V8tn_JercdZnbjS1tJ'; // Replace with your secret key
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}");
    $responseData = json_decode($verify);

    if (!$responseData->success) {
        echo "<script>alert('reCAPTCHA validation failed. Please try again.'); window.location.href = 'register.php';</script>";
        exit;
    }

    $username = $_POST['username'];
    $first_name = $_POST['first-name'];
    $last_name = $_POST['last-name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check_email_query = "SELECT * FROM account WHERE email = ?";
    $check_email_stmt = mysqli_prepare($conn, $check_email_query);
    mysqli_stmt_bind_param($check_email_stmt, "s", $email);
    mysqli_stmt_execute($check_email_stmt);
    $check_result = mysqli_stmt_get_result($check_email_stmt);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('This email is already registered. Please use a different email.'); window.location.href = 'register.php';</script>";
        exit;
    }

    if (!$conn) {
        die('Database connection failed: ' . mysqli_connect_error());
    }

    $stmt = $conn->prepare("INSERT INTO account (username, fname, lname, email, passwords, verified, status) VALUES (?, ?, ?, ?, ?, 0, 'Active')");

    if (!$stmt) {
        die('Statement preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("sssss", $username, $first_name, $last_name, $email, $password);

    if ($stmt->execute()) {
        $accountID = mysqli_insert_id($conn);

        $update_user_id_query = "UPDATE account SET user = ? WHERE AccountID = ?";
        $update_user_id_stmt = $conn->prepare($update_user_id_query);
        $update_user_id_stmt->bind_param("ii", $accountID, $accountID);
        $update_user_id_stmt->execute();

        $_SESSION['email'] = $email;
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300;

        $update_otp_query = "UPDATE account SET verification_code = ? WHERE email = ?";
        $update_otp_stmt = mysqli_prepare($conn, $update_otp_query);
        if ($update_otp_stmt) {
            mysqli_stmt_bind_param($update_otp_stmt, "is", $otp, $email);
            mysqli_stmt_execute($update_otp_stmt);
        } else {
            echo "<script>alert('Error updating OTP in database.');</script>";
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'silverlinebank@gmail.com';
            $mail->Password = 'lxcn mfpw vkrd cujf'; // App Password
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('silverlinebank@gmail.com', 'Silverline Bank');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Welcome to Silver Line Bank! Verify Your Email';
            $mail->Body = 'Dear '.$username.',<br>Welcome to Silver Line Bank!<br><br>
            To complete your registration, please verify your email with the OTP below:<br>
            <b>Your OTP: ' . $otp . '</b><br><br>
            This OTP is valid for 5 minutes. Do not share this code.<br><br>
            If this wasn\'t you, contact us at silverline@gmail.com or call 09481328201.<br><br>
            Thanks,<br><b>Silver Line Bank Team</b>';

            $mail->send();
        } catch (Exception $e) {
            echo '<script>alert("Mailer Exception: ' . $e->getMessage() . '\\nError Info: ' . $mail->ErrorInfo . '")</script>';
        }

        echo '<script>
            if (confirm("Registration successful! Do you want to verify your account now?")) {
                window.location.href = "verify.php";
            } else {
                window.location.href = "login.php";
            }
        </script>';
    }
    $stmt->close();
}
?>

<!-- HTML BELOW -->
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration - Silverline Bank</title>
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
        background: #fff;
        padding: 30px 40px;
        border-radius: 10px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
        margin: 120px auto 50px; /* Added top margin to account for fixed header */
    }

    h2 {
        font-size: 28px;
        color: #A59D84;
        text-align: center;
        margin-bottom: 30px;
    }

    form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .form-group {
        flex: 1 1 45%;
        display: flex;
        flex-direction: column;
    }

    label {
        font-weight: 600;
        margin-bottom: 6px;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        padding: 10px;
        font-size: 16px;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .buttons {
        width: 100%;
        text-align: center;
    }

    .next-button {
        background-color: #A59D84;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .next-button:hover {
        background-color: #4d382a;
    }

    .footer-text {
        text-align: center;
        margin-top: 25px;
    }

    .footer-text a {
        color: #A59D84;
        font-weight: bold;
        text-decoration: none;
    }

    @media (max-width: 600px) {
        form {
            flex-direction: column;
        }

        .form-group {
            flex: 1 1 100%;
        }
        
        header {
            flex-direction: column;
            padding: 10px;
        }
        
        nav {
            margin: 10px 0;
        }
        
        .container {
            margin-top: 180px; /* Increased for mobile layout */
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
        <li><a href="#">About</a></li>
        <li><a href="#">Menu</a></li>
        <li><a href="#">Feedback</a></li>
        <li><a href="login.php">Login</a></li>
      </ul>
    </nav>
    </div>
</header>

    <div class="container">
        <h2>Registration</h2>
        <form id="registerForm" method="post" onsubmit="return validatePasswords()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter Username" required>
            </div>
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input type="text" name="first-name" id="first-name" placeholder="Enter First Name" required>
            </div>
            <div class="form-group">
                <label for="last-name">Last Name</label>
                <input type="text" name="last-name" id="last-name" placeholder="Enter Last Name" required>
            </div>
            <div class="form-group">
                <label for="email">Email </label>
                <input type="email" name="email" id="email" placeholder="Enter Email Address" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter Password" required>
            </div>
            <div class="form-group">
                <label for="confirmpassword">Confirm Password</label>
                <input type="password" name="confirmpassword" id="confirmpassword" placeholder="Confirm Password" required>
            </div>

            <!-- Google reCAPTCHA Widget -->
            <div style="width:100%;">
                <div class="g-recaptcha" data-sitekey="6Lc3xS0rAAAAAF5YBcvyg1L2ezRehsfHiPhB3p00"></div>
            </div>

            <div class="buttons">
                <button type="submit" class="next-button">Submit</button>
            </div>
        </form>
        <p class="footer-text">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>

    <!-- reCAPTCHA Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        function validatePasswords() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirmpassword").value;

            if (password !== confirmPassword) {
                alert("The passwords do not match. Please try again.");
                document.getElementById("confirmpassword").value = "";
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
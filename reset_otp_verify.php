<?php 
include ('connect.php');
session_start();

// Check if user has a reset session
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit;
}

// Theme configuration retrieval
$default_navigator_color = "#A59D84";
$default_body_color = "#ECEBDE";
$default_button_color = "#6f4e37";
$default_logo_image = "img/logo.jpg";

$query = "SELECT * FROM theme_cafe WHERE id = 1";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
  $row = mysqli_fetch_assoc($result);
  $current_navigator_color = $row['navigator'];
  $current_body_color = $row['body'];
  $current_font_color = $row['font'];
  $current_button_color = $row['button'];
  $current_logo_image = $row['logo'];
} else {
  $current_navigator_color = $default_navigator_color;
  $current_body_color = $default_body_color;
  $current_button_color = $default_button_color;
  $current_logo_image = $default_logo_image;
}

$email = $_SESSION['reset_email'];
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'];
    $stored_otp = $_SESSION['reset_otp'];
    
    // Check if OTP is expired
    if (time() > $_SESSION['otp_expiry']) {
        $errorMsg = "OTP has expired. Please request a new one.";
    } 
    // Check if OTP is correct
    else if ($entered_otp == $stored_otp) {
        // OTP correct, proceed to password reset page
        header("Location: reset_password.php");
        exit;
    } else {
        $errorMsg = "Invalid OTP. Please try again.";
    }
}

// Function to mask email for privacy
function maskEmail($email) {
    $arr = explode("@", $email);
    $name = $arr[0];
    $domain = $arr[1];
    
    $masked_name = substr($name, 0, 2) . str_repeat('*', strlen($name) - 3) . substr($name, -1);
    $masked_domain = substr($domain, 0, 2) . str_repeat('*', strlen($domain) - 6) . substr($domain, -4);
    
    return $masked_name . "@" . $masked_domain;
}

$masked_email = maskEmail($email);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - La Croissanterie</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
      background-color: <?php echo $current_body_color ?>;
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

    .container {
        background: #fff;
        padding: 30px 40px;
        border-radius: 10px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        margin: 120px auto 50px;
    }

    h2 {
        font-size: 28px;
        color: #A59D84;
        text-align: center;
        margin-bottom: 30px;
    }

    .description {
        font-size: 16px;
        color: #555;
        margin-bottom: 20px;
        text-align: center;
    }

    .email-info {
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    .input-container {
        position: relative;
    }

    input[type="text"] {
        width: 100%;
        padding: 12px 15px;
        font-size: 16px;
        border-radius: 5px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        text-align: center;
        letter-spacing: 5px;
        font-weight: bold;
    }

    input[type="text"]:focus {
        border-color: #A59D84;
        box-shadow: 0 0 5px rgba(165, 157, 132, 0.5);
        outline: none;
    }

    .error-message {
        color: #e74c3c;
        text-align: center;
        margin-bottom: 15px;
    }

    .buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
    }

    .verify-button {
        background-color: <?php echo $current_button_color ?>;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        flex: 1;
        margin-right: 10px;
    }

    .verify-button:hover {
        background-color: #4d382a;
    }

    .resend-button {
        background-color: #dddddd;
        color: #555;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        text-decoration: none;
        text-align: center;
        flex: 1;
        margin-left: 10px;
    }

    .resend-button:hover {
        background-color: #cccccc;
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

    #countdown {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: #777;
    }

    @media (max-width: 600px) {
        header {
            flex-direction: column;
            padding: 10px;
        }
        
        nav {
            margin: 10px 0;
        }
        
        .container {
            margin: 150px 20px 50px;
            padding: 20px;
        }
        
        .buttons {
            flex-direction: column;
        }
        
        .verify-button, .resend-button {
            margin: 5px 0;
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
  </header>
</div>

<div class="container">
    <h2>Verify Your Identity</h2>
    <p class="description">We've sent a verification code to your email address.</p>
    <p class="email-info">OTP sent to: <?php echo $masked_email; ?></p>
    
    <?php if ($errorMsg): ?>
        <p class="error-message"><?php echo $errorMsg; ?></p>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="otp">Enter 6-Digit OTP</label>
            <div class="input-container">
                <input type="text" id="otp" name="otp" maxlength="6" placeholder="------" required>
            </div>
        </div>
        
        <div class="buttons">
            <button type="submit" class="verify-button">Verify</button>
            <a href="forgot_password.php" class="resend-button">Resend OTP</a>
        </div>
    </form>
    
    <div id="countdown">OTP expires in: <span id="timer">5:00</span></div>
    
    <p class="footer-text">
        <a href="login.php">Back to Login</a>
    </p>
</div>

<script>
    // Countdown timer for OTP expiration
    function startTimer(duration, display) {
        var timer = duration, minutes, seconds;
        var countdown = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (--timer < 0) {
                clearInterval(countdown);
                display.textContent = "Expired";
                alert("OTP has expired. Please request a new one.");
                window.location.href = "forgot_password.php";
            }
        }, 1000);
    }

    window.onload = function () {
        var fiveMinutes = 60 * 5,
            display = document.querySelector('#timer');
        startTimer(fiveMinutes, display);
    };
</script>
</body>
</html>
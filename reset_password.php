<?php
include('connect.php');
session_start();

require './PHPMailer/PHPMailer/src/Exception.php';
require './PHPMailer/PHPMailer/src/PHPMailer.php';
require './PHPMailer/PHPMailer/src/SMTP.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate a random OTP
function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[rand(0, strlen($digits) - 1)];
    }
    return $otp;
}

// If form is submitted with email
if (isset($_POST['txtemail']) && !isset($_POST['txtotp']) && !isset($_POST['reset_confirmed'])) {
    $email = mysqli_real_escape_string($conn, $_POST['txtemail']);
    
    // Check if the email exists in the database
    $query = "SELECT * FROM account WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Email exists, generate OTP
        $otp = generateOTP();
        
        // Store OTP in session and database
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time'] = time(); // To check OTP expiration
        
        // Update OTP in database
        $update_otp_query = "UPDATE account SET verification_code = ? WHERE email = ?";
        $update_otp_stmt = mysqli_prepare($conn, $update_otp_query);
        
        if ($update_otp_stmt) {
            mysqli_stmt_bind_param($update_otp_stmt, "is", $otp, $email);
            mysqli_stmt_execute($update_otp_stmt);
            mysqli_stmt_close($update_otp_stmt);
        } else {
            echo "<script>alert('Error updating OTP in database.'); window.location='reset_password.php';</script>";
            exit();
        }
        
        // Send OTP via email
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'silverlinebank@gmail.com';
            $mail->Password = 'lxcn mfpw vkrd cujf'; // App Password
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('silverlinebank@gmail.com', 'La Croissanterie');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - La Croissanterie';
            $mail->Body = 'Dear Customer,<br><br>
            We received a request to reset your password.<br><br>
            Your verification code is: <b>' . $otp . '</b><br><br>
            This code is valid for 5 minutes. If you did not request this change, please ignore this email or contact customer support.<br><br>
            Thank you,<br><b>La Croissanterie Team</b>';

            $mail->send();
            
            // Display success message
            $emailSent = true;
        } catch (Exception $e) {
            echo "<script>alert('Error sending email: " . addslashes($mail->ErrorInfo) . "'); window.location='reset_password.php';</script>";
            exit();
        }
        
        // Show OTP verification form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>La Croissanterie - Verify OTP</title>
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
                    font-family: var(--font-main);
                    background-color: var(--light-color);
                    color: var(--text-color);
                    line-height: 1.6;
                    margin: 0;
                    padding: 0;
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
                
                .div_otp {
                    max-width: 500px;
                    margin: 100px auto 50px;
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
                }
                
                h2 {
                    color: #A59D84;
                    margin-bottom: 20px;
                    text-align: center;
                    font-size: 28px;
                }
                
                form {
                    display: flex;
                    flex-direction: column;
                }
                
                label {
                    margin-bottom: 8px;
                    color: #555;
                    font-weight: 600;
                }
                
                .otp-message {
                    background-color: #e8f5e9;
                    padding: 15px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    border-left: 4px solid #4caf50;
                }
                
                input[type="text"] {
                    padding: 12px;
                    margin-bottom: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 16px;
                }
                
                input[type="submit"] {
                    background-color: #A59D84;
                    color: white;
                    border: none;
                    padding: 12px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: background-color 0.3s;
                }
                
                input[type="submit"]:hover {
                    background-color: #4d382a;
                }
                
                .cancel-btn {
                    text-align: center;
                    margin-top: 15px;
                }
                
                .cancel-btn a {
                    color: #666;
                    text-decoration: none;
                }
                
                .cancel-btn a:hover {
                    text-decoration: underline;
                }
                
                @media (max-width: 600px) {
                    header {
                        flex-direction: column;
                        padding: 10px;
                    }
                    
                    .div_otp {
                        margin: 150px 20px 50px;
                        padding: 20px;
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

            <div class="div_otp">
                <h2>Verify Your Identity</h2>
                <div class="otp-message">
                    <?php if(isset($emailSent) && $emailSent): ?>
                        <p><i class="fas fa-check-circle"></i> A verification code has been sent to your email address. Please check your inbox and enter the code below.</p>
                    <?php endif; ?>
                    <p>The verification code will expire in 5 minutes.</p>
                </div>
                <form method="post" action="reset_password.php">
                    <label for="txtotp">Enter Verification Code:</label>
                    <input type="text" id="txtotp" name="txtotp" placeholder="Enter 6-digit code" required>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="submit" value="Verify">
                </form>
                <div class="cancel-btn">
                    <a href="login.php">Cancel</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit(); // Stop further execution
    } else {
        // Email doesn't exist
        echo "<script>alert('Email not found. Please try again.'); window.location='reset_password.php';</script>";
        exit();
    }
}

// If OTP form is submitted
if (isset($_POST['txtotp'])) {
    $submitted_otp = $_POST['txtotp'];
    $email = isset($_POST['email']) ? $_POST['email'] : $_SESSION['reset_email'];
    
    // Check if OTP session exists and is valid
    if (isset($_SESSION['reset_otp']) && isset($_SESSION['otp_time'])) {
        // Check if OTP has expired (5 minutes expiration)
        $current_time = time();
        $otp_time = $_SESSION['otp_time'];
        
        if (($current_time - $otp_time) > 300) { // 300 seconds = 5 minutes
            // OTP expired
            echo "<script>alert('Verification code has expired. Please request a new one.'); window.location='reset_password.php';</script>";
            exit();
        }
        
        // Check if OTP matches
        if ($submitted_otp == $_SESSION['reset_otp']) {
            // OTP is correct, show password reset form
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>La Croissanterie - Reset Password</title>
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
                        font-family: var(--font-main);
                        background-color: var(--light-color);
                        color: var(--text-color);
                        line-height: 1.6;
                        margin: 0;
                        padding: 0;
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
                    
                    .div_reset {
                        max-width: 500px;
                        margin: 100px auto 50px;
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
                    }
                    
                    h2 {
                        color: #A59D84;
                        margin-bottom: 20px;
                        text-align: center;
                        font-size: 28px;
                    }
                    
                    form {
                        display: flex;
                        flex-direction: column;
                    }
                    
                    label {
                        margin-bottom: 8px;
                        color: #555;
                        font-weight: 600;
                    }
                    
                    .password-container {
                        position: relative;
                        margin-bottom: 20px;
                    }
                    
                    .password-container input {
                        padding: 12px;
                        width: 100%;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                        font-size: 16px;
                        box-sizing: border-box;
                    }
                    
                    .toggle-password {
                        position: absolute;
                        right: 10px;
                        top: 50%;
                        transform: translateY(-50%);
                        cursor: pointer;
                        color: #666;
                    }
                    
                    .password-requirements {
                        margin-top: 5px;
                        font-size: 12px;
                        color: #666;
                    }
                    
                    input[type="submit"] {
                        background-color: #A59D84;
                        color: white;
                        border: none;
                        padding: 12px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 16px;
                        transition: background-color 0.3s;
                    }
                    
                    input[type="submit"]:hover {
                        background-color: #4d382a;
                    }
                    
                    .cancel-btn {
                        text-align: center;
                        margin-top: 15px;
                    }
                    
                    .cancel-btn a {
                        color: #666;
                        text-decoration: none;
                    }
                    
                    .cancel-btn a:hover {
                        text-decoration: underline;
                    }
                    
                    @media (max-width: 600px) {
                        header {
                            flex-direction: column;
                            padding: 10px;
                        }
                        
                        .div_reset {
                            margin: 150px 20px 50px;
                            padding: 20px;
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

                <div class="div_reset">
                    <h2>Create New Password</h2>
                    <form method="post" action="reset_password.php" onsubmit="return validatePassword()">
                        <input type="hidden" name="reset_confirmed" value="1">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        
                        <label for="newpassword">New Password:</label>
                        <div class="password-container">
                            <input type="password" id="newpassword" name="newpassword" placeholder="Enter new password" required>
                            <i class="toggle-password fas fa-eye" onclick="togglePassword('newpassword')"></i>
                            <div class="password-requirements">
                                Password must be at least 8 characters with at least one letter and one number
                            </div>
                        </div>
                        
                        <label for="confirmpassword">Confirm Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm password" required>
                            <i class="toggle-password fas fa-eye" onclick="togglePassword('confirmpassword')"></i>
                        </div>
                        
                        <input type="submit" value="Reset Password">
                    </form>
                    <div class="cancel-btn">
                        <a href="login.php">Cancel</a>
                    </div>
                </div>

                <script>
                    function togglePassword(inputId) {
                        const input = document.getElementById(inputId);
                        const icon = input.nextElementSibling;
                        
                        if (input.type === "password") {
                            input.type = "text";
                            icon.classList.remove("fa-eye");
                            icon.classList.add("fa-eye-slash");
                        } else {
                            input.type = "password";
                            icon.classList.remove("fa-eye-slash");
                            icon.classList.add("fa-eye");
                        }
                    }
                    
                    function validatePassword() {
                        const newPassword = document.getElementById('newpassword').value;
                        const confirmPassword = document.getElementById('confirmpassword').value;
                        
                        if (newPassword !== confirmPassword) {
                            alert("Passwords do not match!");
                            return false;
                        }
                        
                        // Password strength validation (minimum 8 characters, at least one letter and one number)
                        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
                        if (!passwordRegex.test(newPassword)) {
                            alert("Password must be at least 8 characters long and contain at least one letter and one number.");
                            return false;
                        }
                        
                        return true;
                    }
                </script>
            </body>
            </html>
            <?php
            exit(); // Stop further execution
        } else {
            // Incorrect OTP
            echo "<script>alert('Incorrect verification code. Please try again.'); window.history.back();</script>";
            exit();
        }
    } else {
        // OTP session not found
        echo "<script>alert('Session expired. Please request a new verification code.'); window.location='reset_password.php';</script>";
        exit();
    }
}

// Handle password reset form submission
if (isset($_POST['reset_confirmed'])) {
    if (isset($_POST['newpassword']) && isset($_POST['confirmpassword']) && isset($_POST['email'])) {
        $newPassword = $_POST['newpassword'];
        $confirmPassword = $_POST['confirmpassword'];
        $email = $_POST['email'];
        
        // Verify passwords match
        if ($newPassword !== $confirmPassword) {
            echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
            exit();
        }
        
        // Password strength validation
        if (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            echo "<script>alert('Password must be at least 8 characters long and contain at least one letter and one number.'); window.history.back();</script>";
            exit();
        }
        
        // Hash the password for security
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update the password in the database
        $updateQuery = "UPDATE account SET passwords = ? WHERE email = ?";
        $update_stmt = mysqli_prepare($conn, $updateQuery);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "ss", $hashedPassword, $email);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Clear session variables
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_time']);
                
                // Success message
                echo "<script>alert('Password reset successful! You can now login with your new password.'); window.location='login.php';</script>";
                exit();
            } else {
                // Database error
                echo "<script>alert('Error updating password: " . mysqli_error($conn) . "'); window.history.back();</script>";
                exit();
            }
            
            mysqli_stmt_close($update_stmt);
        } else {
            // Statement preparation error
            echo "<script>alert('Error preparing statement: " . mysqli_error($conn) . "'); window.history.back();</script>";
            exit();
        }
    } else {
        // Missing form data
        echo "<script>alert('Missing required information. Please try again.'); window.location='reset_password.php';</script>";
        exit();
    }
}

// Default view - show email form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Croissanterie - Reset Password</title>
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
            font-family: var(--font-main);
            background-color: var(--light-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
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
            background-color: var(--text-color);
            bottom: 0;
            left: 0;
        }
        
        .div_forgot {
            max-width: 500px;
            margin: 100px auto 50px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: #A59D84;
            margin-bottom: 20px;
            text-align: center;
            font-size: 28px;
        }
        
        .reset-info {
            margin-bottom: 25px;
            text-align: center;
            color: #666;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        input[type="email"] {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        input[type="submit"] {
            background-color: #A59D84;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        input[type="submit"]:hover {
            background-color: #4d382a;
        }
        
        .cancel-btn {
            text-align: center;
            margin-top: 15px;
        }
        
        .cancel-btn a {
            color: #666;
            text-decoration: none;
        }
        
        .cancel-btn a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            header {
                flex-direction: column;
                padding: 10px;
            }
            
            .div_forgot {
                margin: 150px 20px 50px;
                padding: 20px;
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

    <div class="div_forgot">
        <h2>Reset Your Password</h2>
        <div class="reset-info">
            <p>Enter your email address to receive a verification code.</p>
        </div>
        <form method="post" action="reset_password.php">
            <label for="txtemail">Email Address:</label>
            <input type="email" id="txtemail" name="txtemail" placeholder="Enter your email" required>
            <input type="submit" value="Send Reset Code">
        </form>
        <div class="cancel-btn">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>
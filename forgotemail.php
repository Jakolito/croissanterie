<?php
include('connect.php');
session_start();

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
if (isset($_POST['txtemail'])) {
    $email = mysqli_real_escape_string($conn, $_POST['txtemail']);
    
    // Check if the email exists in the database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        // Email exists, generate OTP
        $otp = generateOTP();
        
        // Store OTP in session
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['otp_time'] = time(); // To check OTP expiration
        
        // In a real application, you would send this OTP via email
        // For this example, we'll just display it (in a real app, remove this)
        $otpMessage = "Your OTP is: $otp"; // In real app, this would be sent via email
        
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
                body {
                    font-family: 'Arial', sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                
                .header-container {
                    background-color: #ffffff;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    padding: 10px 20px;
                }
                
                header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    max-width: 1200px;
                    margin: 0 auto;
                }
                
                .logo {
                    display: flex;
                    align-items: center;
                    color: #8B4513;
                    font-weight: bold;
                }
                
                .logo-text {
                    margin-left: 10px;
                    font-size: 1.5rem;
                }
                
                .main-nav {
                    display: flex;
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                
                .main-nav li {
                    margin-left: 20px;
                }
                
                .main-nav a {
                    text-decoration: none;
                    color: #333;
                    font-weight: 500;
                    transition: color 0.3s;
                }
                
                .main-nav a:hover {
                    color: #8B4513;
                }
                
                .div_otp {
                    max-width: 500px;
                    margin: 50px auto;
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 0 15px rgba(0,0,0,0.1);
                }
                
                h2 {
                    color: #8B4513;
                    margin-bottom: 20px;
                    text-align: center;
                }
                
                form {
                    display: flex;
                    flex-direction: column;
                }
                
                label {
                    margin-bottom: 8px;
                    color: #555;
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
                    border-radius: 4px;
                    font-size: 16px;
                }
                
                input[type="submit"] {
                    background-color: #8B4513;
                    color: white;
                    border: none;
                    padding: 12px;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: background-color 0.3s;
                }
                
                input[type="submit"]:hover {
                    background-color: #6b3000;
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
                <h2>Verify OTP</h2>
                <div class="otp-message">
                    <?php echo $otpMessage; ?>
                    <p>We've sent a verification code to your email address. Please enter it below to continue.</p>
                </div>
                <form method="post" action="forgot_email_otp.php">
                    <label for="txtotp">Enter OTP:</label>
                    <input type="text" id="txtotp" name="txtotp" placeholder="Enter 6-digit OTP" required>
                    <input type="submit" value="Verify">
                </form>
                <div class="cancel-btn">
                    <a href="log.php">Cancel</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit(); // Stop further execution
    } else {
        // Email doesn't exist
        echo "<script>alert('Email not found. Please try again.'); window.location='forgot_account.php';</script>";
        exit();
    }
}

// If OTP form is submitted
if (isset($_POST['txtotp'])) {
    $submitted_otp = $_POST['txtotp'];
    
    // Check if OTP session exists and is valid
    if (isset($_SESSION['reset_otp']) && isset($_SESSION['otp_time'])) {
        // Check if OTP has expired (10 minutes expiration)
        $current_time = time();
        $otp_time = $_SESSION['otp_time'];
        
        if (($current_time - $otp_time) > 600) { // 600 seconds = 10 minutes
            // OTP expired
            echo "<script>alert('OTP has expired. Please request a new one.'); window.location='forgot_account.php';</script>";
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
                    body {
                        font-family: 'Arial', sans-serif;
                        background-color: #f4f4f4;
                        margin: 0;
                        padding: 0;
                    }
                    
                    .header-container {
                        background-color: #ffffff;
                        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        padding: 10px 20px;
                    }
                    
                    header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        max-width: 1200px;
                        margin: 0 auto;
                    }
                    
                    .logo {
                        display: flex;
                        align-items: center;
                        color: #8B4513;
                        font-weight: bold;
                    }
                    
                    .logo-text {
                        margin-left: 10px;
                        font-size: 1.5rem;
                    }
                    
                    .main-nav {
                        display: flex;
                        list-style: none;
                        padding: 0;
                        margin: 0;
                    }
                    
                    .main-nav li {
                        margin-left: 20px;
                    }
                    
                    .main-nav a {
                        text-decoration: none;
                        color: #333;
                        font-weight: 500;
                        transition: color 0.3s;
                    }
                    
                    .main-nav a:hover {
                        color: #8B4513;
                    }
                    
                    .div_reset {
                        max-width: 500px;
                        margin: 50px auto;
                        background: white;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 0 15px rgba(0,0,0,0.1);
                    }
                    
                    h2 {
                        color: #8B4513;
                        margin-bottom: 20px;
                        text-align: center;
                    }
                    
                    form {
                        display: flex;
                        flex-direction: column;
                    }
                    
                    label {
                        margin-bottom: 8px;
                        color: #555;
                    }
                    
                    .password-container {
                        position: relative;
                        margin-bottom: 20px;
                    }
                    
                    .password-container input {
                        padding: 12px;
                        width: 100%;
                        border: 1px solid #ddd;
                        border-radius: 4px;
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
                    
                    input[type="submit"] {
                        background-color: #8B4513;
                        color: white;
                        border: none;
                        padding: 12px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 16px;
                        transition: background-color 0.3s;
                    }
                    
                    input[type="submit"]:hover {
                        background-color: #6b3000;
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
                    <h2>Reset Password</h2>
                    <form method="post" action="forgot_email_otp.php" onsubmit="return validatePassword()">
                        <input type="hidden" name="reset_confirmed" value="1">
                        
                        <label for="newpassword">New Password:</label>
                        <div class="password-container">
                            <input type="password" id="newpassword" name="newpassword" placeholder="Enter new password" required>
                            <i class="toggle-password fas fa-eye" onclick="togglePassword('newpassword')"></i>
                        </div>
                        
                        <label for="confirmpassword">Confirm Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm password" required>
                            <i class="toggle-password fas fa-eye" onclick="togglePassword('confirmpassword')"></i>
                        </div>
                        
                        <input type="submit" value="Reset Password">
                    </form>
                    <div class="cancel-btn">
                        <a href="log.php">Cancel</a>
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
            echo "<script>alert('Incorrect OTP. Please try again.'); window.location='forgot_account.php';</script>";
            exit();
        }
    } else {
        // OTP session not found
        echo "<script>alert('Session expired. Please try again.'); window.location='forgot_account.php';</script>";
        exit();
    }
}

// Handle password reset form submission
if (isset($_POST['reset_confirmed'])) {
    if (isset($_POST['newpassword']) && isset($_POST['confirmpassword'])) {
        $newPassword = $_POST['newpassword'];
        $confirmPassword = $_POST['confirmpassword'];
        
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
        
        // Get email from session
        if (isset($_SESSION['reset_email'])) {
            $email = $_SESSION['reset_email'];
            
            // Hash the password for security
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update the password in the database
            $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email'";
            if (mysqli_query($conn, $updateQuery)) {
                // Clear session variables
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_time']);
                
                // Success message
                echo "<script>alert('Password reset successful! You can now login with your new password.'); window.location='login.php';</script>";
                exit();
            } else {
                // Database error
                echo "<script>alert('Error updating password. Please try again.'); window.history.back();</script>";
                exit();
            }
        } else {
            // Email not found in session
            echo "<script>alert('Session expired. Please try again.'); window.location='forgot_account.php';</script>";
            exit();
        }
    }
}

// If we reach here, redirect to forgot account page
header("Location: forgot_account.php");
exit();
?>
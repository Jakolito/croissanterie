<?php
session_start();
include("connect.php");

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Initialize message variables
$success_message = '';
$error_message = '';

// Get current user data
$username = $_SESSION['user'];
$query = "SELECT * FROM account WHERE username = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header("Location: login.php");
    exit();
}



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate required fields
    if (empty($fname) || empty($email)) {
        $error_message = "First name and email are required.";
    } else {
        // Check if email already exists (excluding current user)
        $emailCheckQuery = "SELECT AccountID FROM account WHERE email = ? AND AccountID != ?";
        $emailStmt = mysqli_prepare($conn, $emailCheckQuery);
        mysqli_stmt_bind_param($emailStmt, "si", $email, $user['AccountID']);
        mysqli_stmt_execute($emailStmt);
        $emailResult = mysqli_stmt_get_result($emailStmt);
        
        if (mysqli_num_rows($emailResult) > 0) {
            $error_message = "Email address is already in use by another account.";
        } else {
            $updateQuery = "UPDATE account SET fname = ?, lname = ?, email = ?";
            $params = [$fname, $lname, $email];
            $types = "sss";
            
            // Handle password change
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error_message = "Current password is required to set a new password.";
                } elseif ($new_password !== $confirm_password) {
                    $error_message = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error_message = "New password must be at least 6 characters long.";
                } else {
                    // Verify current password
                    if (!password_verify($current_password, $user['passwords'])) {
                        $error_message = "Current password is incorrect.";
                    } else {
                        // Add password to update query
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $updateQuery .= ", passwords = ?";
                        $params[] = $hashed_password;
                        $types .= "s";
                    }
                }
            }
            
            if (empty($error_message)) {
                $updateQuery .= " WHERE AccountID = ?";
                $params[] = $user['AccountID'];
                $types .= "i";
                
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                mysqli_stmt_bind_param($updateStmt, $types, ...$params);
                
                if (mysqli_stmt_execute($updateStmt)) {
                    // Update session variables
                    $_SESSION['fname'] = $fname;
                    $_SESSION['lname'] = $lname;
                    
                    // Refresh user data
                    $refreshQuery = "SELECT * FROM account WHERE AccountID = ?";
                    $refreshStmt = mysqli_prepare($conn, $refreshQuery);
                    mysqli_stmt_bind_param($refreshStmt, "i", $user['AccountID']);
                    mysqli_stmt_execute($refreshStmt);
                    $refreshResult = mysqli_stmt_get_result($refreshStmt);
                    $user = mysqli_fetch_assoc($refreshResult);
                    
                    $success_message = "Profile updated successfully!";
                    
                    if (!empty($new_password)) {
                        $success_message .= " Your password has been changed.";
                    }
                } else {
                    $error_message = "Error updating profile. Please try again.";
                }
                mysqli_stmt_close($updateStmt);
            }
        }
        mysqli_stmt_close($emailStmt);
    }
}

mysqli_stmt_close($stmt);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - La Croissanterie</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #8B4513;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }

        .profile-form {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-input {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #8B4513;
        }

        .password-section {
            border-top: 2px solid #f0f0f0;
            padding-top: 1.5rem;
            margin-top: 1rem;
        }

        .password-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-primary {
            background: #8B4513;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background: #6d3510;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .account-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .account-info h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .account-info p {
            margin: 0.25rem 0;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .verified-badge {
            background: #d1ecf1;
            color: #0c5460;
        }

        .unverified-badge {
            background: #fff3cd;
            color: #856404;
        }

        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    
   <nav>
      <ul class="main-nav">
        <li><a href="menu2.php">Menu</a></li>
        <li><a href="order_history.php">Your Orders</a></li>
      </ul>
    </nav>
    
    <div class="profile-dropdown">
      <div class="dropdown-toggle" id="profileDropdown">
        <div class="profile-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <span class="profile-name"><?php echo htmlspecialchars($user['fname']); ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
      <div class="dropdown-menu" id="profileMenu">
        <a href="profile.php">My Profile</a>
        <div class="dropdown-divider"></div>
        <a href="homepage.php" id="logoutBtn">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="container">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['fname'], 0, 1)); ?>
            </div>
            <h1>My Profile</h1>
            <p>Manage your account information and settings</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="account-info">
            <h3>Account Information</h3>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            <p><strong>Account Status:</strong> 
                <span class="status-badge <?php echo $user['status'] === 'Active' ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo htmlspecialchars($user['status']); ?>
                </span>
            </p>
            <p><strong>Email Verification:</strong> 
                <span class="status-badge <?php echo $user['verified'] ? 'verified-badge' : 'unverified-badge'; ?>">
                    <?php echo $user['verified'] ? 'Verified' : 'Not Verified'; ?>
                </span>
            </p>
        </div>

        <form method="POST" class="profile-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="fname" class="form-label">First Name *</label>
                    <input type="text" id="fname" name="fname" class="form-input" 
                           value="<?php echo htmlspecialchars($user['fname']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="lname" class="form-label">Last Name</label>
                    <input type="text" id="lname" name="lname" class="form-input" 
                           value="<?php echo htmlspecialchars($user['lname']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="password-section">
                <h3>Change Password</h3>
                <p style="color: #666; margin-bottom: 1rem;">Leave password fields empty if you don't want to change your password.</p>
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-input">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input">
                        <div class="password-requirements">Minimum 6 characters required</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                    </div>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                <a href="menu2.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-content logout-modal-content">
    <span class="modal-close" id="closeLogoutModal">&times;</span>
    <div class="logout-modal-body">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <div class="logout-modal-buttons">
        <button class="cancel-btn" id="cancelLogout">Cancel</button>
        <button class="confirm-btn" id="confirmLogout">Logout</button>
      </div>
    </div>
  </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3 class="footer-title">La Croissanterie</h3>
            <p>Authentic French pastries baked fresh daily with premium ingredients.</p>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Quick Links</h3>
            <ul class="footer-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="menu2.php">Menu</a></li>
                <li><a href="cart.php">Cart</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Contact Us</h3>
            <ul class="footer-links">
                <li>123 Bakery Street, Manila</li>
                <li>Phone: (02) 8123-4567</li>
                <li>Email: info@lacroissanterie.com</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Opening Hours</h3>
            <ul class="footer-links">
                <li>Monday - Friday: 7am - 8pm</li>
                <li>Saturday: 8am - 9pm</li>
                <li>Sunday: 8am - 7pm</li>
            </ul>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> La Croissanterie. All rights reserved.
        </div>
    </div>
</footer>

<script>
    // Initialize profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    profileDropdown.addEventListener('click', () => {
        profileMenu.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    window.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-dropdown')) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Initialize logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const closeLogoutModal = document.getElementById('closeLogoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');
    
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        logoutModal.classList.add('show');
    });
    
    closeLogoutModal.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    cancelLogout.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    confirmLogout.addEventListener('click', () => {
        window.location.href = 'homepage.php';
    });
    
    // Close modal when clicking outside
    logoutModal.addEventListener('click', (e) => {
        if (e.target === logoutModal) {
            logoutModal.classList.remove('show');
        }
    });

    // Auto-hide alert messages after 5 seconds
    const alertBoxes = document.querySelectorAll('.alert');
    alertBoxes.forEach(alertBox => {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 500);
        }, 5000);
    });

    // Password validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const currentPassword = document.getElementById('current_password');

    function validatePasswords() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else if (newPassword.value.length < 6) {
                newPassword.setCustomValidity('Password must be at least 6 characters long');
            } else {
                confirmPassword.setCustomValidity('');
                newPassword.setCustomValidity('');
            }
        }
    }

    newPassword.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);

    // Require current password if new password is entered
    newPassword.addEventListener('input', function() {
        if (this.value) {
            currentPassword.required = true;
            currentPassword.setAttribute('placeholder', 'Current password required');
        } else {
            currentPassword.required = false;
            currentPassword.setAttribute('placeholder', '');
        }
    });

    // Form validation before submit
    document.querySelector('.profile-form').addEventListener('submit', function(e) {
        const newPass = newPassword.value;
        const confirmPass = confirmPassword.value;
        const currentPass = currentPassword.value;
        
        if (newPass && !currentPass) {
            e.preventDefault();
            alert('Current password is required to change your password.');
            currentPassword.focus();
            return false;
        }
        
        if (newPass && newPass !== confirmPass) {
            e.preventDefault();
            alert('New passwords do not match.');
            confirmPassword.focus();
            return false;
        }
        
        if (newPass && newPass.length < 6) {
            e.preventDefault();
            alert('New password must be at least 6 characters long.');
            newPassword.focus();
            return false;
        }
    });
</script>
</body>
</html>
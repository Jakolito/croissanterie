<?php
include('connect.php');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$admin_email = $_SESSION['admin'];
$success_message = '';
$error_message = '';

// Fetch current admin data
$query = "SELECT * FROM admin WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $admin_email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $new_username = mysqli_real_escape_string($conn, htmlspecialchars($_POST['username']));
        $new_email = mysqli_real_escape_string($conn, htmlspecialchars($_POST['email']));
        $new_fullname = mysqli_real_escape_string($conn, htmlspecialchars($_POST['fullname']));
        
        // Check if email already exists (excluding current admin)
        $check_email = "SELECT * FROM admin WHERE email = ? AND email != ?";
        $stmt_check = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt_check, "ss", $new_email, $admin_email);
        mysqli_stmt_execute($stmt_check);
        $email_result = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($email_result) > 0) {
            $error_message = "Email already exists!";
        } else {
            // Update profile information
            $update_query = "UPDATE admin SET username = ?, email = ?, fullname = ? WHERE email = ?";
            $stmt_update = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt_update, "ssss", $new_username, $new_email, $new_fullname, $admin_email);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['admin'] = $new_email;
                $_SESSION['admin_username'] = $new_username;
                $success_message = "Profile updated successfully!";
                
                // Refresh admin data
                $admin_email = $new_email;
                $query = "SELECT * FROM admin WHERE email = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $admin_email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $admin_data = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Error updating profile!";
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (password_verify($current_password, $admin_data['passwords']) || $current_password === $admin_data['passwords']) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_password = "UPDATE admin SET passwords = ? WHERE email = ?";
                    $stmt_password = mysqli_prepare($conn, $update_password);
                    mysqli_stmt_bind_param($stmt_password, "ss", $hashed_password, $admin_email);
                    
                    if (mysqli_stmt_execute($stmt_password)) {
                        $success_message = "Password changed successfully!";
                    } else {
                        $error_message = "Error changing password!";
                    }
                } else {
                    $error_message = "Password must be at least 6 characters long!";
                }
            } else {
                $error_message = "New passwords do not match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Settings - La Croissanterie</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="admin.css">
  <style>
    .settings-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .profile-header {
      text-align: center;
      margin-bottom: 40px;
      padding-bottom: 20px;
      border-bottom: 1px solid #dee2e6;
    }
    
    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background-color: #8B4513;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      font-weight: bold;
      margin: 0 auto 20px;
    }
    
    .profile-title {
      font-size: 28px;
      font-weight: 600;
      color: #343a40;
      margin: 0 0 10px 0;
    }
    
    .profile-subtitle {
      color: #8B4513;
      font-size: 16px;
      margin: 0;
    }
    
    .account-info-section {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #343a40;
      margin: 0 0 20px 0;
    }
    
    .info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 15px;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    
    .info-item.full-width {
      grid-column: 1 / -1;
    }
    
    .info-label {
      font-weight: 600;
      color: #6c757d;
      font-size: 14px;
    }
    
    .info-value {
      color: #495057;
      font-weight: 500;
      font-size: 14px;
    }
    
    .status-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .status-active {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-verified {
      background-color: #d1ecf1;
      color: #0c5460;
    }
    
    .form-section {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      padding: 25px;
      margin-bottom: 25px;
    }
    
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    
    .form-group label {
      font-size: 14px;
      font-weight: 600;
      color: #495057;
      margin: 0;
    }
    
    .required {
      color: #dc3545;
    }
    
    .form-control {
      padding: 12px 15px;
      border: 1px solid #ced4da;
      border-radius: 6px;
      font-size: 14px;
      transition: all 0.15s ease-in-out;
      box-sizing: border-box;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #8B4513;
      box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
    }
    
    .password-help {
      font-size: 12px;
      color: #6c757d;
      margin-bottom: 20px;
    }
    
    .password-requirement {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #dc3545;
      margin-top: 5px;
    }
    
    .password-requirement.valid {
      color: #28a745;
    }
    
    .password-match {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #dc3545;
      margin-top: 5px;
    }
    
    .password-match.valid {
      color: #28a745;
    }
    
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease-in-out;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .btn-primary {
      background-color: #8B4513;
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #6d3410;
      transform: translateY(-1px);
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #545b62;
      transform: translateY(-1px);
    }
    
    .btn-group {
      display: flex;
      gap: 15px;
      justify-content: flex-start;
      margin-top: 20px;
    }
    
    .alert {
      padding: 15px 20px;
      margin-bottom: 25px;
      border: 1px solid transparent;
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .alert-success {
      color: #155724;
      background-color: #d4edda;
      border-color: #c3e6cb;
    }
    
    .alert-danger {
      color: #721c24;
      background-color: #f8d7da;
      border-color: #f5c6cb;
    }
    
    @media (max-width: 768px) {
      .settings-container {
        padding: 15px;
      }
      
      .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .info-grid {
        grid-template-columns: 1fr;
        gap: 10px;
      }
      
      .btn-group {
        flex-direction: column;
      }
      
      .btn {
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-header">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    <div class="sidebar-menu">
      <a href="admindashboard.php" class="menu-item">
        <i class="fas fa-tachometer-alt"></i>
        <span class="menu-text">Dashboard</span>
      </a>
      <a href="product.php" class="menu-item">
        <i class="fas fa-box"></i>
        <span class="menu-text">Products</span>
      </a>
      <a href="user_list.php" class="menu-item">
        <i class="fas fa-users"></i>
        <span class="menu-text">Users</span>
      </a>
      <a href="adminorder.php" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="menu-text">Orders</span>
      </a>
      <a href="adminTransaction.php" class="menu-item">
        <i class="fas fa-money-bill-wave"></i>
        <span class="menu-text">Transactions</span>
      </a>
      <a href="adminSetting.php" class="menu-item active">
        <i class="fas fa-cog"></i>
        <span class="menu-text">Settings</span>
      </a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h1 class="page-title">Admin Settings</h1>
      <div class="user-info">
        <?php if(!empty($admin_profile)): ?>
          <img src="<?php echo htmlspecialchars($admin_profile); ?>" alt="Profile" class="profile-pic">
        <?php endif; ?>
        
        <a href="#" onclick="confirmLogout()" class="logout-btn">Logout</a>

        <script>
        function confirmLogout() {
          if (confirm("Are you sure you want to logout?")) {
            window.location.href = "logout.php";
          }
        }
        </script>
      </div>
    </div>

    <div class="content">
      <div class="settings-container">
        
        <!-- Profile Header -->
        <div class="profile-header">
          <div class="profile-avatar"><?php echo strtoupper(substr($admin_data['username'], 0, 1)); ?></div>
          <h1 class="profile-title">My Profile</h1>
          <p class="profile-subtitle">Manage your account information and settings</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        

        <!-- Profile Information Form -->
        <div class="form-section">
          <form method="POST" action="">
            <div class="form-row">
              <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($admin_data['username']); ?>" required>
              </div>
              <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($admin_data['fullname'] ?? ''); ?>">
              </div>
            </div>
            <div class="form-group full-width">
              <label for="email">Email Address <span class="required">*</span></label>
              <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
            </div>
            <div class="btn-group">
              <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Profile
              </button>
              <button type="button" class="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Change Password Form -->
        <div class="form-section">
          <h2 class="section-title">Change Password</h2>
          <p class="password-help">Leave password fields empty if you don't want to change your password</p>
          
          <form method="POST" action="">
            <div class="form-group full-width">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
                <div class="password-requirement" id="password-requirement">
                  <i class="fas fa-times-circle"></i> Minimum 6 characters required
                </div>
              </div>
              <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                <div class="password-match" id="password-match" style="display: none;">
                  <i class="fas fa-times-circle"></i> Passwords do not match
                </div>
              </div>
            </div>
            <div class="btn-group">
              <button type="submit" name="change_password" class="btn btn-primary">
                <i class="fas fa-key"></i> Change Password
              </button>
              <button type="button" class="btn btn-secondary">Cancel</button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </div>

  <script>
    // Real-time password validation
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordRequirement = document.getElementById('password-requirement');
    const passwordMatch = document.getElementById('password-match');

    // Check password length in real-time
    newPasswordInput.addEventListener('input', function() {
      const password = this.value;
      const requirement = document.getElementById('password-requirement');
      
      if (password.length >= 6) {
        requirement.classList.add('valid');
        requirement.innerHTML = '<i class="fas fa-check-circle"></i> Password meets minimum requirement';
      } else {
        requirement.classList.remove('valid');
        requirement.innerHTML = '<i class="fas fa-times-circle"></i> Minimum 6 characters required';
      }

      // Also check password match when new password changes
      checkPasswordMatch();
    });

    // Check password match in real-time
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    function checkPasswordMatch() {
      const newPassword = newPasswordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      const matchIndicator = document.getElementById('password-match');

      if (confirmPassword.length > 0) {
        matchIndicator.style.display = 'flex';
        
        if (newPassword === confirmPassword && newPassword.length > 0) {
          matchIndicator.classList.add('valid');
          matchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
        } else {
          matchIndicator.classList.remove('valid');
          matchIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
        }
      } else {
        matchIndicator.style.display = 'none';
      }
    }

    // Enhanced form validation
    document.querySelector('form')?.addEventListener('submit', function(e) {
      const form = e.target;
      if (form.querySelector('[name="change_password"]')) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword && newPassword !== confirmPassword) {
          e.preventDefault();
          alert('New passwords do not match!');
          return false;
        }
        
        if (newPassword && newPassword.length < 6) {
          e.preventDefault();
          alert('Password must be at least 6 characters long!');
          return false;
        }
      }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(function(alert) {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(function() {
          alert.remove();
        }, 500);
      });
    }, 5000);
  </script>
</body>
</html>
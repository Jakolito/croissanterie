<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit();
}

// Get admin information from database
require_once 'connect.php';

$admin_username = $_SESSION['admin'];
$stmt = $conn->prepare("SELECT id, fullname, profile_picture, email FROM admin WHERE username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $admin_id = $row['id'];
  $admin_fullname = $row['fullname'];
  $admin_profile = $row['profile_picture'];
  $admin_email = $row['email'];
} else {
  // This shouldn't happen if session is valid, but handle gracefully
  $admin_id = 1;
  $admin_fullname = $admin_username;
  $admin_profile = '';
  $admin_email = '';
}
$stmt->close();

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['upload_profile'])) {
    $update_fields = [];
    $update_values = [];
    $update_types = "";
    
    // Validate and sanitize input data
    $new_fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $new_email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    
    // Check if fullname needs to be updated
    if (!empty($new_fullname) && $new_fullname !== $admin_fullname) {
        if (strlen($new_fullname) < 2) {
            $error_message = "Full name must be at least 2 characters long.";
        } elseif (!preg_match('/^[a-zA-Z\s\.-]+$/', $new_fullname)) {
            $error_message = "Full name can only contain letters, spaces, dots, and hyphens.";
        } else {
            $update_fields[] = "fullname = ?";
            $update_values[] = $new_fullname;
            $update_types .= "s";
        }
    }
    
    // Check if email needs to be updated
    if (!empty($new_email) && $new_email !== $admin_email) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } else {
            // Check if email already exists for other admins
            $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $new_email, $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "This email is already used by another admin.";
            } else {
                $update_fields[] = "email = ?";
                $update_values[] = $new_email;
                $update_types .= "s";
            }
            $stmt->close();
        }
    }
    
    // Handle password change
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error_message = "Current password is required when changing password.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password and confirmation do not match.";
        } elseif (strlen($new_password) < 3) {
            $error_message = "New password must be at least 3 characters long.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT passwords FROM admin WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin_data = $result->fetch_assoc();
            $stmt->close();
            
            if ($admin_data['passwords'] !== $current_password) {
                $error_message = "Current password is incorrect.";
            } else {
                $update_fields[] = "passwords = ?";
                $update_values[] = $new_password;
                $update_types .= "s";
            }
        }
    }
    
    // Execute update if there are fields to update and no errors
    if (!empty($update_fields) && empty($error_message)) {
        $update_query = "UPDATE admin SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $update_values[] = $admin_id;
        $update_types .= "i";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($update_types, ...$update_values);
        
        if ($stmt->execute()) {
            $success_message = "Settings updated successfully!";
            
            // Update local variables for display
            if (isset($new_fullname) && !empty($new_fullname) && $new_fullname !== $admin_fullname) {
                $admin_fullname = $new_fullname;
            }
            if (isset($new_email) && !empty($new_email) && $new_email !== $admin_email) {
                $admin_email = $new_email;
            }
            
            // Refresh admin data from database to ensure consistency
            $stmt = $conn->prepare("SELECT fullname, email FROM admin WHERE id = ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $updated_data = $result->fetch_assoc();
                $admin_fullname = $updated_data['fullname'];
                $admin_email = $updated_data['email'];
            }
            $stmt->close();
            
        } else {
            $error_message = "Failed to update settings. Please try again.";
        }
        $stmt->close();
    } elseif (empty($update_fields) && empty($error_message)) {
        $error_message = "No changes detected.";
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
    }
    
    .settings-card {
      background-color: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
      overflow: hidden;
    }
    
    .card-header {
      background-color: #f8f9fa;
      padding: 20px;
      border-bottom: 1px solid #dee2e6;
    }
    
    .card-header h3 {
      margin: 0;
      color: #343a40;
      font-size: 18px;
      font-weight: 600;
    }
    
    .card-body {
      padding: 20px;
    }
    
    .admin-info-display {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 15px;
      margin-bottom: 25px;
    }
    
    .admin-info-display h4 {
      margin: 0 0 10px 0;
      color: #495057;
      font-size: 16px;
    }
    
    .info-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #dee2e6;
    }
    
    .info-item:last-child {
      border-bottom: none;
    }
    
    .info-label {
      font-weight: 600;
      color: #6c757d;
      min-width: 100px;
    }
    
    .info-value {
      color: #495057;
      font-weight: 500;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 600;
      color: #495057;
    }
    
    .form-control {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      font-size: 14px;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
      box-sizing: border-box;
    }
    
    .form-control:focus {
      outline: none;
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .form-control:disabled {
      background-color: #e9ecef;
      opacity: 1;
    }
    
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.15s ease-in-out;
    }
    
    .btn-primary {
      background-color: #007bff;
      color: white;
    }
    
    .btn-primary:hover {
      background-color: #0056b3;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background-color: #545b62;
    }
    
    .alert {
      padding: 12px 15px;
      margin-bottom: 20px;
      border: 1px solid transparent;
      border-radius: 4px;
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
    
    .profile-upload-section {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .current-profile {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #dee2e6;
    }
    
    .default-profile {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background-color: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 3px solid #dee2e6;
    }
    
    .default-profile i {
      font-size: 30px;
      color: #6c757d;
    }
    
    .upload-controls {
      flex: 1;
    }
    
    .file-input {
      margin-bottom: 10px;
    }
    
    .password-section {
      border-top: 1px solid #dee2e6;
      padding-top: 20px;
      margin-top: 20px;
    }
    
    .password-section h4 {
      margin-bottom: 15px;
      color: #343a40;
      font-size: 16px;
    }
    
    .form-row {
      display: flex;
      gap: 15px;
    }
    
    .form-row .form-group {
      flex: 1;
    }
    
    .form-help {
      font-size: 12px;
      color: #6c757d;
      margin-top: 5px;
    }
    
    @media (max-width: 768px) {
      .form-row {
        flex-direction: column;
        gap: 0;
      }
      
      .profile-upload-section {
        flex-direction: column;
        text-align: center;
      }
      
      .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
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

    <div class="dashboard-content">
      <div class="settings-container">
        
        <!-- Display Messages -->
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>

        <!-- Current Admin Information Display -->
        <div class="settings-card">
          <div class="card-header">
            <h3><i class="fas fa-user"></i> Current Admin Information</h3>
          </div>
          <div class="card-body">
            <div class="admin-info-display">
              <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($admin_username); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($admin_fullname); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($admin_email); ?></span>
              </div>
            </div>
          </div>
        </div>


        <!-- Account Information Section -->
        <div class="settings-card">
          <div class="card-header">
            <h3><i class="fas fa-edit"></i> Update Account Information</h3>
          </div>
          <div class="card-body">
            <form method="POST">
              <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($admin_username); ?>" disabled>
                <div class="form-help">Username cannot be changed</div>
              </div>
              
              <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($admin_fullname); ?>" required>
                <div class="form-help">Enter your full name</div>
              </div>
              
              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                <div class="form-help">Enter a valid email address</div>
              </div>
              
              <div class="password-section">
                <h4>Change Password (Optional)</h4>
                <p style="color: #6c757d; margin-bottom: 15px;">Leave password fields empty if you don't want to change your password.</p>
                
                <div class="form-row">
                  <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" minlength="3">
                    <div class="form-help">Minimum 3 characters</div>
                  </div>
                  
                  <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="3">
                    <div class="form-help">Must match new password</div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="current_password">Current Password</label>
                  <input type="password" id="current_password" name="current_password" class="form-control">
                  <div class="form-help">Required when changing password</div>
                </div>
              </div>
              
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function() {
      const newPassword = document.getElementById('new_password').value;
      const confirmPassword = this.value;
      
      if (newPassword !== confirmPassword && confirmPassword !== '') {
        this.setCustomValidity('Passwords do not match');
      } else {
        this.setCustomValidity('');
      }
    });
    
    document.getElementById('new_password').addEventListener('input', function() {
      const confirmPassword = document.getElementById('confirm_password');
      const newPassword = this.value;
      
      if (newPassword !== confirmPassword.value && confirmPassword.value !== '') {
        confirmPassword.setCustomValidity('Passwords do not match');
      } else {
        confirmPassword.setCustomValidity('');
      }
    });
  </script>
</body>
</html>
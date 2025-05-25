<?php
include('connect.php');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = $_POST['account_id'];
    $adminPassword = $_POST['admin_password'];

    // Debug: Check what we're receiving
    error_log("Received account_id: " . $accountId);
    error_log("Received admin_password: " . $adminPassword);

    try {
        // First, let's check what admin records exist and their structure
        $adminQuery = "SELECT * FROM admin LIMIT 1";
        $adminResult = $conn->query($adminQuery);
        
        if (!$adminResult) {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
            header("Location: user_list.php");
            exit();
        }

        if ($adminResult->num_rows === 0) {
            $_SESSION['error_message'] = "No admin records found in database.";
            header("Location: user_list.php");
            exit();
        }

        $adminRow = $adminResult->fetch_assoc();
        
        // Debug: Log admin data structure (remove this in production)
        error_log("Admin record structure: " . print_r($adminRow, true));

        // Try different possible column names for password
        $storedPassword = null;
        if (isset($adminRow['passwords'])) {
            $storedPassword = $adminRow['passwords'];
        } elseif (isset($adminRow['password'])) {
            $storedPassword = $adminRow['password'];
        } elseif (isset($adminRow['admin_password'])) {
            $storedPassword = $adminRow['admin_password'];
        }

        if ($storedPassword === null) {
            $_SESSION['error_message'] = "Could not find password field in admin table.";
            header("Location: user_list.php");
            exit();
        }

        // Debug: Log password comparison (remove in production)
        error_log("Stored password: " . $storedPassword);
        error_log("Input password: " . $adminPassword);

        // Compare passwords - try multiple methods
        $passwordMatch = false;
        
        // Method 1: Direct comparison (for plain text)
        if ($adminPassword === $storedPassword) {
            $passwordMatch = true;
        }
        // Method 2: Trimmed comparison (in case of whitespace issues)
        elseif (trim($adminPassword) === trim($storedPassword)) {
            $passwordMatch = true;
        }
        // Method 3: Password verification (if it's hashed)
        elseif (password_verify($adminPassword, $storedPassword)) {
            $passwordMatch = true;
        }

        if ($passwordMatch) {
            // Password is correct, proceed with deletion
            
            // First, check if the user exists
            $checkUser = $conn->prepare("SELECT username FROM account WHERE AccountID = ?");
            $checkUser->bind_param("i", $accountId);
            $checkUser->execute();
            $userResult = $checkUser->get_result();
            
            if ($userResult->num_rows === 0) {
                $_SESSION['error_message'] = "User not found.";
                header("Location: user_list.php");
                exit();
            }
            
            $userData = $userResult->fetch_assoc();
            $username = $userData['username'];
            
            // Delete the user
            $deleteQuery = $conn->prepare("DELETE FROM account WHERE AccountID = ?");
            $deleteQuery->bind_param("i", $accountId);
            
            if ($deleteQuery->execute()) {
                if ($deleteQuery->affected_rows > 0) {
                    $_SESSION['success_message'] = "User '{$username}' has been deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "No user was deleted. User may not exist.";
                }
            } else {
                $_SESSION['error_message'] = "Database error while deleting user: " . $conn->error;
            }
            
            $deleteQuery->close();
            $checkUser->close();
        } else {
            $_SESSION['error_message'] = "Invalid admin password. Please try again.";
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        error_log("Delete user error: " . $e->getMessage());
    }

    header("Location: user_list.php");
    exit();
} else {
    // If not POST request, redirect back
    header("Location: user_list.php");
    exit();
}
?>
<?php
include('connect.php');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location:login.php");
    exit();
}
require_once 'connect.php'; // Make sure you have this file for database connection

// Check if account ID was provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['account_id'])) {
    $account_id = $_POST['account_id'];
    
    // Proceed with unblocking the user without password verification
    $update_query = "UPDATE account SET status = 'active' WHERE AccountID = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $account_id);
    
    if ($update_stmt->execute()) {
        // Unblock successful, redirect back to user list
        $_SESSION['success_message'] = "User has been successfully unblocked.";
        header("Location: user_list.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Failed to unblock user. Please try again.";
        header("Location: user_list.php");
        exit();
    }
} else {
    // Direct access without form submission
    header("Location: user_list.php");
    exit();
}
?>
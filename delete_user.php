<?php
include('connect.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountId = $_POST['account_id'];
    $adminPassword = $_POST['admin_password'];

    // Fetch plain text admin password
    $result = $conn->query("SELECT passwords FROM admin WHERE id = 1"); // Adjust ID if needed

    if ($result && $row = $result->fetch_assoc()) {
        if ($adminPassword === $row['passwords']) {
            $deleteQuery = $conn->prepare("DELETE FROM account WHERE AccountID = ?");
            $deleteQuery->bind_param("i", $accountId);
            if ($deleteQuery->execute()) {
                echo "<script>alert('User deleted successfully.'); window.location.href='user_list.php';</script>";
            } else {
                echo "<script>alert('Error deleting user.'); window.location.href='user_list.php';</script>";
            }
        } else {
            echo "<script>alert('Invalid admin password.'); window.location.href='user_list.php';</script>";
        }
    } else {
        echo "<script>alert('Admin record not found.'); window.location.href='user_list.php';</script>";
    }
}
?>

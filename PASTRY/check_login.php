<?php
session_start(); // Start session to access session variables

// Check if the user is logged in
if (isset($_SESSION['user'])) {
    // User is logged in, return a success response
    echo json_encode(['status' => 'logged_in']);
} else {
    // User is not logged in, return a failure response
    echo json_encode(['status' => 'not_logged_in']);
}
?>

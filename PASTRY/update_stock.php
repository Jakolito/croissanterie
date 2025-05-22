<?php
include('connect.php');
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name']) && isset($_POST['stock_action']) && isset($_POST['stock_quantity'])) {
    // XML file path
    $xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
    
    // Load existing XML file
    if (file_exists($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
    } else {
        $_SESSION['message'] = "Error: XML file not found.";
        header("Location: product.php");
        exit();
    }
    
    $product_name = trim($_POST['product_name']);
    $stock_action = $_POST['stock_action'];
    $stock_quantity = intval($_POST['stock_quantity']);
    
    if ($stock_quantity <= 0) {
        $_SESSION['message'] = "Please enter a valid quantity (greater than 0).";
        header("Location: product.php");
        exit();
    }
    
    $productFound = false;
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $product_name) {
            $productFound = true;
            $current_quantity = intval($pastry->quantity);
            
            // Update quantity based on action
            switch ($stock_action) {
                case 'add':
                    $pastry->quantity = $current_quantity + $stock_quantity;
                    $action_text = "Added $stock_quantity to";
                    break;
                case 'subtract':
                    $new_quantity = max(0, $current_quantity - $stock_quantity);
                    $pastry->quantity = $new_quantity;
                    $action_text = "Removed $stock_quantity from";
                    break;
                case 'set':
                    $pastry->quantity = $stock_quantity;
                    $action_text = "Set to $stock_quantity for";
                    break;
                default:
                    $_SESSION['message'] = "Invalid stock action.";
                    header("Location: product.php");
                    exit();
            }
            
            break;
        }
    }
    
    if (!$productFound) {
        $_SESSION['message'] = "Product not found.";
        header("Location: product.php");
        exit();
    }
    
    // Save XML file
    if ($xml->asXML($xmlPath)) {
        $_SESSION['message'] = "Stock successfully updated! $action_text $product_name.";
    } else {
        $_SESSION['message'] = "Failed to update stock. Please try again.";
    }
    
    // Redirect back to product management page
    header("Location: product.php");
    exit();
} else {
    // If accessed directly without POST data, redirect to product management
    header("Location: product.php");
    exit();
}
?>
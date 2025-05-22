<?php
include('connect.php');
session_start();

// XML file path
$xmlPath = 'C:\xampp\htdocs\PASTRY1\pastry.xml';

// Check if the XML file exists and is readable
if (!file_exists($xmlPath)) {
    $_SESSION['message'] = "Error: XML file not found!";
    header("Location: product.php");
    exit();
}

// Load the XML file
$xml = simplexml_load_file($xmlPath);
if ($xml === false) {
    $_SESSION['message'] = "Error: Failed to load XML file!";
    header("Location: product.php");
    exit();
}

// Check if form data is present
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name']) && isset($_POST['stock_action']) && isset($_POST['stock_quantity'])) {
    $productName = $_POST['product_name'];
    $stockAction = $_POST['stock_action'];
    $stockQuantity = intval($_POST['stock_quantity']);
    
    // Validate stock quantity
    if ($stockQuantity <= 0) {
        $_SESSION['message'] = "Error: Quantity must be greater than zero!";
        header("Location: product.php");
        exit();
    }
    
    // Find the product by name
    $found = false;
    $existingPastry = null;
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $productName) {
            $existingPastry = $pastry;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['message'] = "Error: Product not found!";
        header("Location: product.php");
        exit();
    }
    
    // Calculate new quantity based on action
    $currentQuantity = intval($existingPastry->quantity);
    $newQuantity = $currentQuantity;
    
    switch ($stockAction) {
        case 'add':
            $newQuantity = $currentQuantity + $stockQuantity;
            break;
        case 'subtract':
            $newQuantity = $currentQuantity - $stockQuantity;
            if ($newQuantity < 0) {
                $newQuantity = 0;
                $_SESSION['message'] = "Warning: Stock cannot be negative, set to 0.";
            }
            break;
        case 'set':
            $newQuantity = $stockQuantity;
            break;
        default:
            $_SESSION['message'] = "Error: Invalid stock action!";
            header("Location: product.php");
            exit();
    }
    
    // Update the quantity
    $existingPastry->quantity = $newQuantity;
    
    // Save the updated XML
    if ($xml->asXML($xmlPath)) {
        if (empty($_SESSION['message'])) {
            $_SESSION['message'] = "Stock updated successfully!";
        }
    } else {
        $_SESSION['message'] = "Error: Failed to save updated stock data!";
    }
} else {
    $_SESSION['message'] = "Error: Invalid request!";
}

// Redirect back to product page
header("Location: product.php");
exit();
?>
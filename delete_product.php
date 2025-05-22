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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'])) {
    $productName = $_POST['product_name'];
    
    // Find the product index by name
    $index = -1;
    $count = 0;
    $imagePath = '';
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $productName) {
            $index = $count;
            $imagePath = (string)$pastry->image;
            break;
        }
        $count++;
    }
    
    if ($index === -1) {
        $_SESSION['message'] = "Error: Product not found!";
        header("Location: product.php");
        exit();
    }
    
    // Remove the pastry node from XML
    unset($xml->pastry[$index]);
    
    // Save the updated XML
    if ($xml->asXML($xmlPath)) {
        // Try to remove the associated image file if exists
        if (!empty($imagePath) && file_exists($imagePath) && strpos($imagePath, 'uploads/') === 0) {
            @unlink($imagePath);
        }
        $_SESSION['message'] = "Product deleted successfully!";
    } else {
        $_SESSION['message'] = "Error: Failed to save updated product data!";
    }
} else {
    $_SESSION['message'] = "Error: Invalid request!";
}

// Redirect back to product page
header("Location: product.php");
exit();
?>
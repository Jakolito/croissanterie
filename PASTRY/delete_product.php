<?php
include('connect.php');
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'])) {
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
    $productFound = false;
    $imagePath = '';
    
    // Find index of product to remove
    $index = 0;
    $removeIndex = -1;
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $product_name) {
            $productFound = true;
            $removeIndex = $index;
            $imagePath = (string)$pastry->image;
            break;
        }
        $index++;
    }
    
    if (!$productFound) {
        $_SESSION['message'] = "Product not found.";
        header("Location: product.php");
        exit();
    }
    
    // Convert SimpleXMLElement to DOM for better removal handling
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("/pastries/pastry");
    
    if ($nodes->length > 0 && $removeIndex >= 0 && $removeIndex < $nodes->length) {
        $nodeToRemove = $nodes->item($removeIndex);
        $nodeToRemove->parentNode->removeChild($nodeToRemove);
        
        // Save updated XML
        $dom->formatOutput = true;
        if ($dom->save($xmlPath)) {
            // Delete product image if it exists and is not a default image
            if (!empty($imagePath) && file_exists($imagePath) && strpos($imagePath, 'default') === false) {
                unlink($imagePath);
            }
            
            $_SESSION['message'] = "Product deleted successfully!";
        } else {
            $_SESSION['message'] = "Failed to delete product. Please try again.";
        }
    } else {
        $_SESSION['message'] = "Error finding product to delete.";
    }
    
    // Redirect back to product management page
    header("Location: product.php");
    exit();
} else {
    // If accessed directly without POST data, redirect to product management
    header("Location: product.php");
    exit();
}
?><?php
include('connect.php');
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_name'])) {
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
    $productFound = false;
    $imagePath = '';
    
    // Find index of product to remove
    $index = 0;
    $removeIndex = -1;
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $product_name) {
            $productFound = true;
            $removeIndex = $index;
            $imagePath = (string)$pastry->image;
            break;
        }
        $index++;
    }
    
    if (!$productFound) {
        $_SESSION['message'] = "Product not found.";
        header("Location: product.php");
        exit();
    }
    
    // Convert SimpleXMLElement to DOM for better removal handling
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("/pastries/pastry");
    
    if ($nodes->length > 0 && $removeIndex >= 0 && $removeIndex < $nodes->length) {
        $nodeToRemove = $nodes->item($removeIndex);
        $nodeToRemove->parentNode->removeChild($nodeToRemove);
        
        // Save updated XML
        $dom->formatOutput = true;
        if ($dom->save($xmlPath)) {
            // Delete product image if it exists and is not a default image
            if (!empty($imagePath) && file_exists($imagePath) && strpos($imagePath, 'default') === false) {
                unlink($imagePath);
            }
            
            $_SESSION['message'] = "Product deleted successfully!";
        } else {
            $_SESSION['message'] = "Failed to delete product. Please try again.";
        }
    } else {
        $_SESSION['message'] = "Error finding product to delete.";
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
<?php
include('connect.php');
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // XML file path
    $xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
    
    // Load existing XML file or create new one
    if (file_exists($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
    } else {
        // Create new XML structure if file doesn't exist
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pastries></pastries>');
    }
    
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $producttype = trim($_POST['producttype']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $producttag = trim($_POST['producttag']);
    
    // Check if product name already exists
    $nameExists = false;
    foreach ($xml->pastry as $pastry) {
        if (strtolower((string)$pastry->name) === strtolower($name)) {
            $nameExists = true;
            break;
        }
    }
    
    if ($nameExists) {
        $_SESSION['message'] = "A product with this name already exists. Please use a different name.";
        header("Location: product.php");
        exit();
    }
    
    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_file = $upload_dir . $file_name;
        
        // Check file type
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION['message'] = "Only JPG, JPEG, PNG and GIF files are allowed.";
            header("Location: product.php");
            exit();
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
            $imagePath = $upload_file;
        } else {
            $_SESSION['message'] = "Failed to upload image. Please try again.";
            header("Location: product.php");
            exit();
        }
    }
    
    // Add new product to XML
    $newPastry = $xml->addChild('pastry');
    $newPastry->addChild('name', $name);
    $newPastry->addChild('description', $description);
    $newPastry->addChild('producttype', $producttype);
    $newPastry->addChild('price', $price);
    $newPastry->addChild('quantity', $quantity);
    $newPastry->addChild('producttag', $producttag);
    $newPastry->addChild('image', $imagePath);
    
    // Save XML file
    if ($xml->asXML($xmlPath)) {
        $_SESSION['message'] = "Product added successfully!";
    } else {
        $_SESSION['message'] = "Failed to save product. Please try again.";
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
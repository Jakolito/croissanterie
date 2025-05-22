<?php
include('connect.php');
session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    
    // Get form data
    $original_name = trim($_POST['original_name']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $producttype = trim($_POST['producttype']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $producttag = trim($_POST['producttag']);
    
    // Check if new name already exists (if name was changed)
    if ($original_name !== $name) {
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
    }
    
    // Find the product to update
    $productFound = false;
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $original_name) {
            $productFound = true;
            
            // Handle image upload if new image is provided
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
                    // Delete old image if exists and is not the default image
                    $old_image = (string)$pastry->image;
                    if (!empty($old_image) && file_exists($old_image) && strpos($old_image, 'default') === false) {
                        unlink($old_image);
                    }
                    
                    $pastry->image = $upload_file;
                } else {
                    $_SESSION['message'] = "Failed to upload image. Other details updated successfully.";
                }
            }
            
            // Update product details
            $pastry->name = $name;
            $pastry->description = $description;
            $pastry->producttype = $producttype;
            $pastry->price = $price;
            $pastry->quantity = $quantity;
            $pastry->producttag = $producttag;
            
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
        $_SESSION['message'] = "Product updated successfully!";
    } else {
        $_SESSION['message'] = "Failed to update product. Please try again.";
    }
    
    // Redirect back to product management page
    header("Location: product.php");
    exit();
} else {
    // If accessed directly without POST data, redirect to product management
    header("Location: productt.php");
    exit();
}
?>
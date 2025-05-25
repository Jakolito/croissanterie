<?php
include('connect.php');
session_start();

// XML file path
$xmlPath = 'pastry.xml';

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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $originalName = $_POST['original_name'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $producttype = trim($_POST['producttype']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $producttag = trim($_POST['producttag']);
    
    // Find the product by name
    $found = false;
    $existingPastry = null;
    
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $originalName) {
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
    
    // Check if new name conflicts with another product (only if name is changed)
    if ($originalName !== $name) {
        foreach ($xml->pastry as $pastry) {
            if ((string)$pastry->name === $name && (string)$pastry->name !== $originalName) {
                $_SESSION['message'] = "Error: Another product with this name already exists!";
                header("Location: product.php");
                exit();
            }
        }
    }
    
    // Handle file upload
    $imagePath = (string)$existingPastry->image; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $targetFilename = basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($targetFilename, PATHINFO_EXTENSION));
        
        // Generate a unique filename
        $targetFilename = uniqid() . '.' . $imageFileType;
        $targetFile = $uploadDir . $targetFilename;
        
        // Check if it's an actual image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check === false) {
            $_SESSION['message'] = "Error: File is not an image!";
            header("Location: product.php");
            exit();
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['image']['size'] > 5000000) {
            $_SESSION['message'] = "Error: File is too large! Max 5MB allowed.";
            header("Location: product.php");
            exit();
        }
        
        // Allow certain file formats
        $allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowedFormats)) {
            $_SESSION['message'] = "Error: Only JPG, JPEG, PNG & GIF files are allowed!";
            header("Location: product.php");
            exit();
        }
        
        // Try to upload the file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // If upload successful, delete old image file if it exists
            if (!empty($imagePath) && file_exists($imagePath) && strpos($imagePath, 'uploads/') === 0) {
                @unlink($imagePath); // Try to delete the old file
            }
            $imagePath = $targetFile;
        } else {
            $_SESSION['message'] = "Error: There was an error uploading your file!";
            header("Location: product.php");
            exit();
        }
    }
    
    // Update the pastry data
    $existingPastry->name = $name;
    $existingPastry->description = $description;
    $existingPastry->producttype = $producttype;
    $existingPastry->price = $price;
    $existingPastry->quantity = $quantity;
    $existingPastry->producttag = $producttag;
    $existingPastry->image = $imagePath;
    
    // Save the updated XML
    if ($xml->asXML($xmlPath)) {
        $_SESSION['message'] = "Product updated successfully!";
    } else {
        $_SESSION['message'] = "Error: Failed to save product data!";
    }
} else {
    $_SESSION['message'] = "Error: Invalid request method!";
}

// Redirect back to product page
header("Location: product.php");
exit();
?>
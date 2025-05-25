<?php
include('connect.php');
session_start();

// XML file paths
$xmlPath = 'pastry.xml';
$categoriesXmlPath = 'categories.xml';

// Check if the XML files exist and are readable
if (!file_exists($xmlPath)) {
    $_SESSION['message'] = "Error: Pastry XML file not found!";
    header("Location: product.php");
    exit();
}

if (!file_exists($categoriesXmlPath)) {
    $_SESSION['message'] = "Error: Categories XML file not found!";
    header("Location: product.php");
    exit();
}

// Load the XML files
$xml = simplexml_load_file($xmlPath);
if ($xml === false) {
    $_SESSION['message'] = "Error: Failed to load pastry XML file!";
    header("Location: product.php");
    exit();
}

$categoriesXml = simplexml_load_file($categoriesXmlPath);
if ($categoriesXml === false) {
    $_SESSION['message'] = "Error: Failed to load categories XML file!";
    header("Location: product.php");
    exit();
}

// Check if form data is present
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $producttype = trim($_POST['producttype']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $producttag = trim($_POST['producttag']);
    
    // Validate product type against available categories
    $categoryFound = false;
    foreach ($categoriesXml->category as $category) {
        if (strcasecmp((string)$category, $producttype) === 0) {
            $categoryFound = true;
            $producttype = (string)$category; // Use the exact case from the XML
            break;
        }
    }
    
    // If category wasn't found and isn't empty, check if it should be added
    if (!$categoryFound && !empty($producttype)) {
        // Option 1: Add a new category to the categories.xml
        $categoriesXml->addChild('category', $producttype);
        if ($categoriesXml->asXML($categoriesXmlPath)) {
            $categoryFound = true;
        } else {
            $_SESSION['message'] = "Error: Failed to add new category!";
            header("Location: product.php");
            exit();
        }
    }
    
    // Generate a unique ID for the new pastry
    $newId = 'p' . str_pad(count($xml->pastry) + 1, 3, '0', STR_PAD_LEFT);
    
    // Check if product with same name already exists
    $exists = false;
    foreach ($xml->pastry as $pastry) {
        if ((string)$pastry->name === $name) {
            $exists = true;
            break;
        }
    }
    
    if ($exists) {
        $_SESSION['message'] = "Error: A product with this name already exists!";
        header("Location: product.php");
        exit();
    }
    
    // Handle file upload
    $imagePath = '';
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
            $imagePath = $targetFile;
        } else {
            $_SESSION['message'] = "Error: There was an error uploading your file!";
            header("Location: product.php");
            exit();
        }
    }
    
    // Add new pastry to XML
    $pastry = $xml->addChild('pastry');
    $pastry->addAttribute('id', $newId);
    $pastry->addChild('name', $name);
    $pastry->addChild('description', $description);
    $pastry->addChild('producttype', $producttype);
    $pastry->addChild('price', $price);
    $pastry->addChild('quantity', $quantity);
    $pastry->addChild('producttag', $producttag);
    $pastry->addChild('image', $imagePath);
    
    // Save the updated XML
    if ($xml->asXML($xmlPath)) {
        $_SESSION['message'] = "Product added successfully!";
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
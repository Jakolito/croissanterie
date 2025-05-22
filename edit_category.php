<?php
include('connect.php');
session_start();

// Categories XML file path
$categoriesXmlPath = 'C:\xampp\htdocs\PASTRY1\categories.xml';

// Check if the XML file exists and is readable
if (!file_exists($categoriesXmlPath)) {
    $_SESSION['message'] = "Error: Categories XML file not found!";
    header("Location: product.php");
    exit();
}

// Load the XML file
$xml = simplexml_load_file($categoriesXmlPath);
if ($xml === false) {
    $_SESSION['message'] = "Error: Failed to load categories XML file!";
    header("Location: product.php");
    exit();
}

// Check if form data is present
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['original_category']) && isset($_POST['new_category_name'])) {
    $originalCategory = trim($_POST['original_category']);
    $newCategoryName = trim($_POST['new_category_name']);
    
    // Validate new category name
    if (empty($newCategoryName)) {
        $_SESSION['message'] = "Error: Category name cannot be empty!";
        header("Location: product.php");
        exit();
    }
    
    // Check if the new name already exists (case-insensitive)
    $categoryExists = false;
    foreach ($xml->category as $category) {
        if (strcasecmp((string)$category, $newCategoryName) === 0 && strcasecmp((string)$category, $originalCategory) !== 0) {
            $categoryExists = true;
            break;
        }
    }
    
    if ($categoryExists) {
        $_SESSION['message'] = "Error: A category with this name already exists!";
        header("Location: product.php");
        exit();
    }
    
    // Find and update the category
    $updated = false;
    
    foreach ($xml->category as $category) {
        if (strcasecmp((string)$category, $originalCategory) === 0) {
            $category[0] = $newCategoryName;
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        $_SESSION['message'] = "Error: Category not found!";
        header("Location: product.php");
        exit();
    }
    
    // Save the updated XML
    if ($xml->asXML($categoriesXmlPath)) {
        // Now update all products using this category
        $pastryXmlPath = 'C:\xampp\htdocs\PASTRY1\pastry.xml';
        
        if (file_exists($pastryXmlPath)) {
            $pastryXml = simplexml_load_file($pastryXmlPath);
            
            if ($pastryXml !== false) {
                $updated = false;
                
                foreach ($pastryXml->pastry as $pastry) {
                    if (strcasecmp((string)$pastry->producttype, $originalCategory) === 0) {
                        $pastry->producttype = $newCategoryName;
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    $pastryXml->asXML($pastryXmlPath);
                }
            }
        }
        
        $_SESSION['message'] = "Category updated successfully!";
    } else {
        $_SESSION['message'] = "Error: Failed to save category changes!";
    }
} else {
    $_SESSION['message'] = "Error: Invalid request!";
}

// Redirect back to product page
header("Location: product.php");
exit();
?>
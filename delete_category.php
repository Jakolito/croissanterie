<?php
include('connect.php');
session_start();

// Categories XML file path
$categoriesXmlPath = 'categories.xml';

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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name'])) {
    $categoryName = trim($_POST['category_name']);
    
    // Check if we have at least one other category before deleting
    if (count($xml->category) <= 1) {
        $_SESSION['message'] = "Error: Cannot delete the last category!";
        header("Location: product.php");
        exit();
    }
    
    // Find the category index to delete
    $index = -1;
    $count = 0;
    
    foreach ($xml->category as $category) {
        if (strcasecmp((string)$category, $categoryName) === 0) {
            $index = $count;
            break;
        }
        $count++;
    }
    
    if ($index === -1) {
        $_SESSION['message'] = "Error: Category not found!";
        header("Location: product.php");
        exit();
    }
    
    // Get the first other category to reassign products to
    $newCategory = null;
    foreach ($xml->category as $category) {
        if (strcasecmp((string)$category, $categoryName) !== 0) {
            $newCategory = (string)$category;
            break;
        }
    }
    
    // Remove the category node from XML
    unset($xml->category[$index]);
    
    // Save the updated XML
    if ($xml->asXML($categoriesXmlPath)) {
        // Now update all products using this category
        $pastryXmlPath = 'pastry.xml';
        
        if (file_exists($pastryXmlPath) && $newCategory !== null) {
            $pastryXml = simplexml_load_file($pastryXmlPath);
            
            if ($pastryXml !== false) {
                $updated = false;
                
                foreach ($pastryXml->pastry as $pastry) {
                    if (strcasecmp((string)$pastry->producttype, $categoryName) === 0) {
                        $pastry->producttype = $newCategory;
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    $pastryXml->asXML($pastryXmlPath);
                    $_SESSION['message'] = "Category deleted and products reassigned successfully!";
                } else {
                    $_SESSION['message'] = "Category deleted successfully!";
                }
            } else {
                $_SESSION['message'] = "Category deleted successfully!";
            }
        } else {
            $_SESSION['message'] = "Category deleted successfully!";
        }
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
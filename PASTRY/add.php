<?php
session_start();

if (isset($_POST['add'])) {
    // Get form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $producttype = $_POST['producttype'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $producttag = $_POST['producttag'];

    // Handle image upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = basename($_FILES['image']['name']);
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imagePath = 'uploads/' . $imageName;
        move_uploaded_file($imageTmpName, $imagePath);
    }

    // Load the XML file
    $xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
    if (file_exists($xmlPath)) {
        $xml = simplexml_load_file($xmlPath);
    } else {
        $xml = new SimpleXMLElement('<pastries></pastries>');
    }

    // Add new pastry to XML
    $pastry = $xml->addChild('pastry');
    $pastry->addChild('name', $name);
    $pastry->addChild('description', $description);
    $pastry->addChild('producttype', $producttype);
    $pastry->addChild('price', $price);
    $pastry->addChild('quantity', $quantity);
    $pastry->addChild('producttag', $producttag);
    $pastry->addChild('image', $imagePath); // ✅ Save with relative path

    // Save XML with formatting
    $dom = new DomDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($xmlPath);

    $_SESSION['message'] = 'Pastry added successfully!';
    header('location: index.php');
} else {
    $_SESSION['message'] = 'Fill up the add form first';
    header('location: index.php');
}
?>

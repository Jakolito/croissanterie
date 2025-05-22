<?php
session_start();

if (isset($_POST['edit'])) {
    // Load the XML file
    $xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
    $file = simplexml_load_file($xmlPath);

    // Check if the XML file was loaded successfully
    if ($file !== false) {
        // Loop through each pastry and find the one with the matching name
        foreach ($file->pastry as $pastry) {
            if ($pastry->name == $_POST['name']) {
                // Update the fields with the new values from the form
                $pastry->description = $_POST['description'];
                $pastry->producttype = $_POST['producttype'];
                $pastry->price = $_POST['price'];
                $pastry->quantity = $_POST['quantity'];
                $pastry->producttag = $_POST['producttag'];

                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $imageTmp = $_FILES['image']['tmp_name'];
                    $imageName = basename($_FILES['image']['name']);
                    $imagePath = 'uploads/' . $imageName;
                    move_uploaded_file($imageTmp, $imagePath);
                    $pastry->image = $imagePath; // Update image path in XML
                }

                // Save the updated XML data back to the file
                file_put_contents($xmlPath, $file->asXML());

                // Set a success message in session
                $_SESSION['message'] = 'Pastry updated successfully!';
                header('location: index.php'); // Redirect back to index page
                exit();
            }
        }

        // If no matching pastry was found, set an error message
        $_SESSION['message'] = 'Pastry not found.';
        header('location: index.php');
    } else {
        // If the XML file could not be loaded, set an error message
        $_SESSION['message'] = 'Failed to load XML file.';
        header('location: index.php');
    }
} else {
    // If no form was submitted, show an error message
    $_SESSION['message'] = 'Select a pastry to edit first.';
    header('location: index.php');
}
?>

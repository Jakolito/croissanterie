<?php
session_start();
if (isset($_GET['name'])) {
    $xmlPath = 'C:\xampp\htdocs\PASTRY\pastry.xml';
    $file = simplexml_load_file($xmlPath);

    foreach ($file->pastry as $pastry) {
        if ($pastry->name == $_GET['name']) {
            $dom = dom_import_simplexml($pastry);
            $dom->parentNode->removeChild($dom);
            break;
        }
    }

    file_put_contents($xmlPath, $file->asXML());
    $_SESSION['message'] = 'Pastry deleted successfully';
    header('Location: index.php');
}
?>

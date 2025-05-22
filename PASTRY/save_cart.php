<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'];
    $price = $_POST['price'];
    $image = $_POST['image'];
    $quantity = (int)$_POST['quantity'];

    $product = [
        'name' => $name,
        'price' => $price,
        'image' => $image,
        'quantity' => $quantity
    ];

    // If cart doesn't exist, create it
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if product already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] === $name) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $product;
    }

    header("Location: menu.php");
    exit;
}
?>

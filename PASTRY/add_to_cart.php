<?php
session_start();

if (isset($_GET['name'], $_GET['price'], $_GET['quantity'])) {
    $item = [
        'name' => $_GET['name'],
        'price' => (float) $_GET['price'],
        'quantity' => (int) $_GET['quantity']
    ];

    // Initialize cart if not yet set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if item already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$cartItem) {
        if ($cartItem['name'] === $item['name']) {
            $cartItem['quantity'] += $item['quantity'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $item;
    }

    header('Location: cart.php');
    exit();
} else {
    echo "Missing product information.";
}

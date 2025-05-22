<?php
$xml = simplexml_load_file('C:/xampp/htdocs/PASTRY/pastry.xml');

if (!$xml) {
    echo "<h2>Failed to load product data.</h2>";
    exit;
}

// Use product name instead of ID
$productName = $_GET['name'] ?? null;
$product = null;

foreach ($xml->product as $p) {
    if ((string)$p->name === $productName) {
        $product = $p;
        break;
    }
}

if (!$product) {
    echo "<h2>Product not found.</h2>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product->name) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f6ef;
            padding: 40px;
            color: #4b2e00;
        }
        .container {
            display: flex;
            max-width: 1000px;
            margin: auto;
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }
        .image {
            flex: 1;
            margin-right: 30px;
        }
        .image img {
            width: 100%;
            border-radius: 12px;
        }
        .info {
            flex: 1;
        }
        .product-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 20px;
            margin-bottom: 15px;
        }
        .product-description {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            border-radius: 30px;
            padding: 10px 25px;
            margin: 10px 5px 0 0;
            font-weight: bold;
            border: 2px solid #5e3c00;
            background: transparent;
            color: #5e3c00;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn:hover {
            background-color: #5e3c00;
            color: #fff;
        }
        .btn.buy {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        .btn.buy:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="image">
            <img src="<?= htmlspecialchars($product->image) ?>" alt="<?= htmlspecialchars($product->name) ?>">
        </div>
        <div class="info">
            <div class="product-name"><?= htmlspecialchars($product->name) ?></div>
            <div class="product-price">₱<?= number_format((float)$product->price, 2) ?> PHP</div>
            <div class="product-description"><?= htmlspecialchars($product->description) ?></div>
            <a href="#" class="btn">Add to Cart</a>
            <a href="#" class="btn buy">Buy it now</a>
        </div>
    </div>
</body>
</html>

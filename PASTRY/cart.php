<?php
session_start(); // Start the session to check for the logged-in user

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <link rel="stylesheet" href="menu.css">
  <style>
    /* Your existing styles here */
  </style>
    <style>
    body {
      background-color: #f7f3ef;
      font-family: 'Segoe UI', sans-serif;
      padding: 20px;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
      color: #5c4033;
    }

    .cart-container {
      max-width: 800px;
      margin: 0 auto;
      background: #fff8f0;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 0;
      border-bottom: 1px solid #ddd;
    }

    .cart-item:last-child {
      border-bottom: none;
    }

    .cart-item img {
      width: 80px;
      height: 80px;
      border-radius: 8px;
      object-fit: cover;
      margin-right: 15px;
    }

    .item-details {
      flex: 1;
      display: flex;
      align-items: center;
    }

    .item-info {
      display: flex;
      flex-direction: column;
    }

    .item-name {
      font-size: 16px;
      font-weight: bold;
      color: #333;
    }

    .item-price, .item-subtotal {
      font-size: 14px;
      color: #666;
    }

    .item-quantity {
      margin-left: 20px;
      font-size: 14px;
      color: #444;
    }

    .remove-btn {
      background-color: #ff6961;
      border: none;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
    }

    .total {
      text-align: right;
      font-size: 18px;
      font-weight: bold;
      margin-top: 20px;
      color: #5c4033;
    }

    .clear-btn {
      display: block;
      margin: 20px auto 0;
      background-color: #999;
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
    }

    .checkout-btn {
      display: block;
      margin: 20px auto 0;
      background-color: #4CAF50;
      border: none;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <h1>Your Cart</h1>
  <div class="cart-container" id="cartContainer">
    <!-- Cart items will be injected here -->
  </div>
  <div class="total" id="cartTotal">Total: ₱0.00</div>
  <button class="clear-btn" onclick="clearCart()">Clear Cart</button>
  
  <!-- Checkout Button (will be shown conditionally) -->
  <button class="checkout-btn" id="checkoutBtn" onclick="checkout()">Proceed to Checkout</button>

  <script>
    const cart = JSON.parse(localStorage.getItem('cart')) || {};
    const cartContainer = document.getElementById('cartContainer');
    const cartTotal = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    // Function to render the cart items
    function renderCart() {
      cartContainer.innerHTML = '';
      let total = 0;

      const keys = Object.keys(cart);
      if (keys.length === 0) {
        cartContainer.innerHTML = '<p style="text-align:center;">Your cart is empty.</p>';
        cartTotal.textContent = 'Total: ₱0.00';
        checkoutBtn.style.display = 'none'; // Hide checkout button if cart is empty
        return;
      }

      keys.forEach(id => {
        const item = cart[id];
        const price = parseFloat(item.price); // Ensure price is a number
        const subtotal = item.quantity * price;
        total += subtotal;

        const itemHTML = `
          <div class="cart-item">
            <div class="item-details">
              <img src="${item.image}" alt="${item.name}">
              <div class="item-info">
                <span class="item-name">${item.name}</span>
                <span class="item-price">₱${price.toFixed(2)}</span>
                <span class="item-quantity">Qty: ${item.quantity}</span>
                <span class="item-subtotal">Subtotal: ₱${subtotal.toFixed(2)}</span>
              </div>
            </div>
            <button class="remove-btn" onclick="removeItem('${id}')">Remove</button>
          </div>
        `;
        cartContainer.innerHTML += itemHTML;
      });

      cartTotal.textContent = 'Total: ₱' + total.toFixed(2);

      // Show checkout button if cart is not empty
      checkoutBtn.style.display = keys.length > 0 ? 'block' : 'none';
    }

    // Function to remove an item from the cart
    function removeItem(id) {
      delete cart[id];
      localStorage.setItem('cart', JSON.stringify(cart));
      renderCart();
    }

    // Function to clear the entire cart
    function clearCart() {
      if (confirm('Are you sure you want to clear your cart?')) {
        localStorage.removeItem('cart');
        renderCart();
      }
    }

    // Checkout function (check if user is logged in)
    function checkout() {
      // Send an AJAX request to check login status
      const xhr = new XMLHttpRequest();
      xhr.open('GET', 'check_login.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);

          if (response.status === 'logged_in') {
            // User is logged in, redirect to checkout page
            window.location.href = 'checkout.php';
          } else {
            // User is not logged in, redirect to login page
            alert('You must log in to proceed to checkout.');
            window.location.href = 'login.php';
          }
        }
      };
      xhr.send();
    }

    // Initial render of the cart
    renderCart();
  </script>
</body>
</html>

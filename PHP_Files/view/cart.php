<?php
session_start();
require_once('../settings/core.php');
require_once('../settings/db_class.php');

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_register.php");
    exit();
}
$db = new db_connection();

// Get cart items with product details
$query = "SELECT c.cart_id, c.item_id, c.quantity, c.added_at,
                 m.title, m.description, m.price, m.image_url, m.category, m.status,
                 u.user_id as seller_id, u.first_name as seller_fname, u.last_name as seller_lname
          FROM Cart c
          INNER JOIN MarketplaceItems m ON c.item_id = m.item_id
          INNER JOIN Users u ON m.seller_id = u.user_id
          WHERE c.user_id = ?
          ORDER BY c.added_at DESC";

$stmt = $db->db_conn()->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    if ($item['status'] === 'available') {
        $subtotal += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ReConnect</title>
    <link rel="stylesheet" href="css/libs.min.css">
    <link rel="stylesheet" href="css/socialv.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }

        body {
            background: #f9fafb;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .navbar-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .navbar-menu a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar-menu a:hover {
            color: var(--primary);
        }
        
        .navbar-menu .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .navbar-menu .btn.danger {
            background: var(--danger);
            color: white;
        }
        
        .navbar-menu .btn.danger:hover {
            background: #c0392b;
        }

        /* Main Container */
        .cart-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .breadcrumb {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--light);
        }

        .cart-item-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cart-item-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .cart-item-seller {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .cart-item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .cart-item-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-available {
            background: #d1fae5;
            color: #065f46;
        }

        .status-sold {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-display {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            color: var(--danger);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #fee2e2;
        }

        /* Cart Actions */
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: flex-end;
        }

        .cart-item-subtotal {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .cart-summary h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-row:last-of-type {
            border-bottom: 2px solid var(--dark);
            font-size: 1.2rem;
            font-weight: 700;
            margin-top: 12px;
            padding-top: 20px;
        }

        .checkout-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .checkout-btn:hover {
            background: var(--primary-dark);
        }

        .checkout-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .empty-cart p {
            color: #6b7280;
            margin-bottom: 24px;
        }

        .btn-primary {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 100px 1fr;
                gap: 15px;
            }

            .cart-item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .navbar-menu {
                gap: 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand"><i class="fas fa-graduation-cap"></i> ReConnect</a>
            
            <?php include 'search_component.php'; ?>
            
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="cart.php" class="active"><i class="fas fa-shopping-cart"></i> Cart (<?php echo $item_count; ?>)</a></li>
                <li>
                    <form method="post" action="../actions/logout_user_action.php" style="margin:0">
                        <button type="submit" class="btn danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <div class="cart-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="dashboard.php">Home</a> / <a href="marketplace.php">Marketplace</a> / Shopping Cart
            </div>
            <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success_message']); 
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error_message']); 
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Browse our marketplace and add items to your cart</p>
                <a href="marketplace.php" class="btn-primary">
                    <i class="fas fa-store"></i> Browse Marketplace
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Layout -->
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $item_subtotal = $item['price'] * $item['quantity'];
                        $is_available = $item['status'] === 'available';
                        ?>
                        <div class="cart-item">
                            <!-- Item Image -->
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                     class="cart-item-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php else: ?>
                                <div class="cart-item-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                    <i class="fas fa-image fa-2x"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Item Details -->
                            <div class="cart-item-details">
                                <div class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="cart-item-seller">
                                    Sold by: <?php echo htmlspecialchars($item['seller_fname'] . ' ' . $item['seller_lname']); ?>
                                </div>
                                <div class="cart-item-price">GH₵<?php echo number_format($item['price'], 2); ?></div>
                                
                                <?php if (!$is_available): ?>
                                    <span class="cart-item-status status-sold">No Longer Available</span>
                                <?php else: ?>
                                    <span class="cart-item-status status-available">Available</span>
                                <?php endif; ?>

                                <!-- Quantity Controls -->
                                <?php if ($is_available): ?>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" 
                                                onclick="updateCartQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                                <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                        <button class="quantity-btn" 
                                                onclick="updateCartQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Item Actions -->
                            <div class="cart-item-actions">
                                <?php if ($is_available): ?>
                                    <div class="cart-item-subtotal">
                                        GH₵<?php echo number_format($item_subtotal, 2); ?>
                                    </div>
                                <?php endif; ?>
                                <button class="remove-btn" onclick="removeFromCart(<?php echo $item['cart_id']; ?>)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Items (<?php echo $item_count; ?>)</span>
                        <span>GH₵<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery</span>
                        <span>TBD</span>
                    </div>
                    <div class="summary-row">
                        <span>Total</span>
                        <span>GH₵<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <?php if ($subtotal > 0): ?>
                        <a href="checkout.php" class="checkout-btn" style="text-decoration:none;">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <button class="checkout-btn" disabled>
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </button>
                    <?php endif; ?>
                    <div style="text-align:center;margin-top:16px;">
                        <a href="marketplace.php" style="color:var(--primary);text-decoration:none;font-size:0.9rem;">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateCartQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../actions/cart_update_quantity.php';
            
            const cartIdInput = document.createElement('input');
            cartIdInput.type = 'hidden';
            cartIdInput.name = 'cart_id';
            cartIdInput.value = cartId;
            
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'quantity';
            quantityInput.value = newQuantity;
            
            form.appendChild(cartIdInput);
            form.appendChild(quantityInput);
            document.body.appendChild(form);
            form.submit();
        }

        function removeFromCart(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../actions/cart_remove.php';
                
                const cartIdInput = document.createElement('input');
                cartIdInput.type = 'hidden';
                cartIdInput.name = 'cart_id';
                cartIdInput.value = cartId;
                
                form.appendChild(cartIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

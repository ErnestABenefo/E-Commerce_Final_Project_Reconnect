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

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header("Location: marketplace.php");
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get order details
$order_query = "SELECT po.*, u.first_name, u.last_name, u.email 
                FROM PaymentOrders po
                INNER JOIN Users u ON po.user_id = u.user_id
                WHERE po.order_id = ? AND po.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header("Location: marketplace.php");
    exit();
}

// Get order items
$items_query = "SELECT poi.*, m.title, m.description, m.image_url, m.category,
                       s.first_name as seller_fname, s.last_name as seller_lname
                FROM PaymentOrderItems poi
                INNER JOIN MarketplaceItems m ON poi.item_id = m.item_id
                INNER JOIN Users s ON poi.seller_id = s.user_id
                WHERE poi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - ReConnect</title>
    <link rel="stylesheet" href="css/libs.min.css">
    <link rel="stylesheet" href="css/socialv.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }

        body {
            background: #f9fafb;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

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

        .success-container {
            max-width: 900px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .success-header {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .success-message {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .order-reference {
            display: inline-block;
            background: var(--light);
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--dark);
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
        }

        .order-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid var(--border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--light);
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .item-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .item-seller {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .item-category {
            display: inline-block;
            background: #ddd6fe;
            color: #5b21b6;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            width: fit-content;
        }

        .item-pricing {
            text-align: right;
        }

        .item-quantity {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .order-summary {
            display: grid;
            gap: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-row.total {
            border-top: 2px solid var(--dark);
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-top: 12px;
            padding-top: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 16px;
            background: var(--light);
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: var(--dark);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .order-item {
                grid-template-columns: 80px 1fr;
            }

            .item-pricing {
                grid-column: 1 / -1;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">ReConnect</a>
        </div>
    </nav>

    <div class="success-container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-message">Your order has been placed successfully. Thank you for your purchase!</p>
            <div class="order-reference">
                Order Reference: <?php echo htmlspecialchars($order['payment_reference']); ?>
            </div>
        </div>

        <!-- Order Information -->
        <div class="section">
            <div class="section-title">Order Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Order ID</div>
                    <div class="info-value">#<?php echo $order['order_id']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-completed">
                            <i class="fas fa-check-circle"></i> <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="section">
            <div class="section-title">Order Items</div>
            <?php foreach ($order_items as $item): ?>
                <?php $item_subtotal = $item['price'] * $item['quantity']; ?>
                <div class="order-item">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="item-image">
                    <?php else: ?>
                        <div class="item-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                            <i class="fas fa-image fa-2x"></i>
                        </div>
                    <?php endif; ?>

                    <div class="item-details">
                        <div class="item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="item-seller">
                            <i class="fas fa-store"></i> Sold by: <?php echo htmlspecialchars($item['seller_fname'] . ' ' . $item['seller_lname']); ?>
                        </div>
                        <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                    </div>

                    <div class="item-pricing">
                        <div class="item-quantity">Qty: <?php echo $item['quantity']; ?> × GH₵<?php echo number_format($item['price'], 2); ?></div>
                        <div class="item-price">GH₵<?php echo number_format($item_subtotal, 2); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="section">
            <div class="section-title">Payment Summary</div>
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>GH₵<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>GH₵0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total Paid</span>
                    <span>GH₵<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="marketplace.php" class="btn btn-primary">
                <i class="fas fa-store"></i> Continue Shopping
            </a>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="fas fa-home"></i> Go to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

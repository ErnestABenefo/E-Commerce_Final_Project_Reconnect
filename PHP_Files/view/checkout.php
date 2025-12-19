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
$available_items = [];
foreach ($cart_items as $item) {
    if ($item['status'] === 'available') {
        $subtotal += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
        $available_items[] = $item;
    }
}

// If cart is empty, redirect to cart
if (empty($available_items)) {
    $_SESSION['error_message'] = "Your cart is empty or contains no available items.";
    header("Location: cart.php");
    exit();
}

// Get user details
$user_query = "SELECT first_name, last_name, email, phone FROM Users WHERE user_id = ?";
$stmt = $db->db_conn()->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Convert amount to pesewas (Paystack expects amount in smallest currency unit)
$amount_in_pesewas = $subtotal * 100;

// Generate unique reference
$reference = 'RC_' . time() . '_' . $user_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ReConnect</title>
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

        .checkout-container {
            max-width: 800px;
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

        .checkout-section {
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
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--light);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .order-item-qty {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .order-item-price {
            font-weight: 700;
            color: var(--primary);
        }

        .customer-info {
            display: grid;
            gap: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
        }

        .info-value {
            color: var(--dark);
            font-weight: 600;
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
            border-bottom: none;
            border-top: 2px solid var(--dark);
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-top: 12px;
            padding-top: 20px;
        }

        .btn-paystack {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-paystack:hover {
            background: var(--primary-dark);
        }

        .btn-paystack i {
            font-size: 1.3rem;
        }

        .security-note {
            text-align: center;
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 16px;
        }

        .security-note i {
            color: var(--success);
        }

        @media (max-width: 768px) {
            .order-item {
                flex-direction: column;
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

    <div class="checkout-container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="dashboard.php">Home</a> / <a href="marketplace.php">Marketplace</a> / <a href="cart.php">Cart</a> / Checkout
            </div>
            <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        </div>

        <!-- Order Summary -->
        <div class="checkout-section">
            <div class="section-title">Order Summary</div>
            <?php foreach ($available_items as $item): ?>
                <?php $item_subtotal = $item['price'] * $item['quantity']; ?>
                <div class="order-item">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="order-item-image">
                    <?php else: ?>
                        <div class="order-item-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                            <i class="fas fa-image fa-2x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-item-details">
                        <div class="order-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="order-item-qty">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    
                    <div class="order-item-price">
                        GH₵<?php echo number_format($item_subtotal, 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Customer Information -->
        <div class="checkout-section">
            <div class="section-title">Customer Information</div>
            <div class="customer-info">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <?php if (!empty($user['phone'])): ?>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="checkout-section">
            <div class="section-title">Payment Summary</div>
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal (<?php echo $item_count; ?> items)</span>
                    <span>GH₵<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>GH₵0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span>GH₵<?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>

            <form id="paymentForm">
                <input type="hidden" id="email-address" value="<?php echo htmlspecialchars($user['email']); ?>" />
                <input type="hidden" id="amount" value="<?php echo $amount_in_pesewas; ?>" />
                <input type="hidden" id="reference" value="<?php echo $reference; ?>" />
                
                <button type="button" class="btn-paystack" onclick="payWithPaystack()">
                    <i class="fas fa-lock"></i>
                    Pay GH₵<?php echo number_format($subtotal, 2); ?> with Paystack
                </button>
            </form>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                Secure payment powered by Paystack. Your payment information is encrypted and secure.
            </div>
        </div>
    </div>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        function payWithPaystack() {
            var email = document.getElementById('email-address').value;
            var amount = parseInt(document.getElementById('amount').value);
            var reference = document.getElementById('reference').value;
            
            // Validate amount
            if (!amount || amount <= 0) {
                alert('Invalid amount. Please try again.');
                return;
            }
            
            // Validate email
            if (!email || email === '') {
                alert('Invalid email address.');
                return;
            }
            
            console.log('Initiating payment:', { email, amount, reference });
            
            var handler = PaystackPop.setup({
                key: 'pk_test_703dd380666f113348401d75a286f32bc5769ab5',
                email: email,
                amount: amount,
                currency: 'GHS',
                ref: reference,
                channels: ['card', 'mobile_money'],
                metadata: {
                    custom_fields: [
                        {
                            display_name: "User ID",
                            variable_name: "user_id",
                            value: <?php echo $user_id; ?>
                        }
                    ]
                },
                callback: function(response) {
                    console.log('Payment successful:', response);
                    window.location.href = '../actions/verify_payment.php?reference=' + response.reference;
                },
                onClose: function() {
                    console.log('Payment window closed');
                    alert('Payment window closed. You can try again when ready.');
                }
            });
            
            handler.openIframe();
        }
    </script>
</body>
</html>

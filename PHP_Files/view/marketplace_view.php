<?php
session_start();

require_once __DIR__ . '/../settings/db_class.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$item_id) {
    header('Location: marketplace.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get product details
$stmt = $conn->prepare("SELECT m.*, u.first_name, u.last_name, u.email, u.phone, u.profile_photo 
                        FROM MarketplaceItems m 
                        JOIN Users u ON m.seller_id = u.user_id 
                        WHERE m.item_id = ?");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    header('Location: marketplace.php');
    exit();
}

$is_seller = ($item['seller_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - ReConnect Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --muted: #666;
            --light: #f4f6f8;
            --dark: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            padding-top: 70px;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 70px;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
        }
        
        .navbar-menu a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar-menu a:hover {
            color: var(--primary);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .breadcrumb span {
            color: var(--muted);
            margin: 0 10px;
        }
        
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .product-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 5rem;
        }
        
        .product-info h1 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .product-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 20px;
        }
        
        .product-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .product-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .product-status.sold {
            background: #f8d7da;
            color: #721c24;
        }
        
        .product-description {
            color: var(--muted);
            line-height: 1.8;
            margin-bottom: 30px;
            white-space: pre-wrap;
        }
        
        .seller-card {
            background: var(--light);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .seller-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .seller-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .seller-info h3 {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .seller-info p {
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .contact-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .contact-item i {
            color: var(--primary);
            width: 20px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--success);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: var(--dark);
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: var(--light);
            border-radius: 12px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: var(--primary);
            color: white;
        }
        
        .quantity-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .quantity-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            min-width: 50px;
            text-align: center;
        }
        
        .total-price {
            margin-left: auto;
            text-align: right;
        }
        
        .total-price-label {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 5px;
        }
        
        .total-price-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .btn-cart {
            background: var(--primary);
            color: white;
            width: 100%;
            font-size: 1.1rem;
        }
        
        .btn-cart:hover {
            background: var(--primary-dark);
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
            
            .quantity-selector {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .total-price {
                margin-left: 0;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-graduation-cap"></i>
                ReConnect
            </a>
            
            <?php include 'search_component.php'; ?>
            
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Home</a>
            <span>/</span>
            <a href="marketplace.php">Marketplace</a>
            <span>/</span>
            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
        </div>

        <div class="product-detail">
            <div>
                <div class="product-image" style="<?php echo !empty($item['image_url']) ? 'background:none;' : ''; ?>">
                    <?php if (!empty($item['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                    <?php else: ?>
                        <i class="fas fa-tag"></i>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-info">
                <?php if (!empty($item['category'])): ?>
                    <span class="product-category"><?php echo htmlspecialchars($item['category']); ?></span>
                <?php endif; ?>
                
                <h1><?php echo htmlspecialchars($item['title']); ?></h1>
                
                <div class="product-price">GH₵<?php echo number_format($item['price'], 2); ?></div>
                
                <span class="product-status <?php echo $item['status']; ?>">
                    <?php echo ucfirst($item['status']); ?>
                </span>

                <div class="product-description">
                    <?php echo htmlspecialchars($item['description']); ?>
                </div>

                <?php if (!$is_seller && $item['status'] === 'available'): ?>
                    <!-- Quantity Selector and Total Price -->
                    <div class="quantity-selector">
                        <div>
                            <div style="font-weight:600;color:var(--dark);margin-bottom:8px;">Quantity:</div>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" id="decreaseBtn" onclick="decreaseQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <div class="quantity-display" id="quantity">1</div>
                                <button type="button" class="quantity-btn" onclick="increaseQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="total-price">
                            <div class="total-price-label">Total Price:</div>
                            <div class="total-price-value" id="totalPrice">GH₵<?php echo number_format($item['price'], 2); ?></div>
                        </div>
                    </div>

                    <!-- Add to Cart Button -->
                    <form id="addToCartForm" method="POST" action="../actions/cart_add.php">
                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                        <input type="hidden" name="quantity" id="quantityInput" value="1">
                        <input type="hidden" name="redirect_to_cart" value="1">
                        <button type="submit" class="btn btn-cart">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                <?php endif; ?>

                <div class="seller-card">
                    <h3 style="margin-bottom:15px;"><i class="fas fa-user"></i> Seller Information</h3>
                    <div class="seller-header">
                        <?php if (!empty($item['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($item['profile_photo']); ?>" alt="Seller" class="seller-avatar" style="object-fit:cover;">
                        <?php else: ?>
                            <div class="seller-avatar">
                                <?php echo strtoupper(substr($item['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="seller-info">
                            <h3><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></h3>
                            <p>Member since <?php echo date('M Y', strtotime($item['created_at'])); ?></p>
                        </div>
                    </div>

                    <?php if (!$is_seller && $item['status'] === 'available'): ?>
                        <div class="contact-info">
                            <p style="font-weight:600;margin-bottom:10px;color:var(--dark);">Contact Seller:</p>
                            <?php if (!empty($item['email'])): ?>
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    <a href="mailto:<?php echo htmlspecialchars($item['email']); ?>" style="color:var(--primary);">
                                        <?php echo htmlspecialchars($item['email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($item['phone'])): ?>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    <a href="tel:<?php echo htmlspecialchars($item['phone']); ?>" style="color:var(--primary);">
                                        <?php echo htmlspecialchars($item['phone']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_seller): ?>
                    <button class="btn btn-danger" onclick="if(confirm('Are you sure you want to mark this item as sold?')) { document.getElementById('markSoldForm').submit(); }">
                        <i class="fas fa-check-circle"></i> Mark as Sold
                    </button>
                    <form id="markSoldForm" method="POST" action="../actions/marketplace_update_status.php" style="display:none;">
                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                        <input type="hidden" name="status" value="sold">
                    </form>
                <?php endif; ?>

                <a href="marketplace.php" class="btn btn-secondary" style="width:100%;margin-top:10px;">
                    <i class="fas fa-arrow-left"></i> Back to Marketplace
                </a>
            </div>
        </div>
    </div>

    <script>
        const unitPrice = <?php echo $item['price']; ?>;
        let quantity = 1;

        function increaseQuantity() {
            quantity++;
            updateDisplay();
        }

        function decreaseQuantity() {
            if (quantity > 1) {
                quantity--;
                updateDisplay();
            }
        }

        function updateDisplay() {
            document.getElementById('quantity').textContent = quantity;
            document.getElementById('quantityInput').value = quantity;
            const total = (unitPrice * quantity).toFixed(2);
            document.getElementById('totalPrice').textContent = 'GH₵' + parseFloat(total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Form submission handler (optional - for AJAX)
        document.getElementById('addToCartForm')?.addEventListener('submit', function(e) {
            // You can add AJAX submission here if needed
            // For now, it will submit normally via POST
        });
    </script>
</body>
</html>

<?php
session_start();

require_once __DIR__ . '/../settings/db_class.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

// Get user's existing products
$db = new db_connection();
$conn = $db->db_conn();
$products_query = "SELECT * FROM MarketplaceItems WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $image_url = null;
    
    if (empty($title) || empty($description) || $price <= 0) {
        $message = 'Please fill in all required fields with valid data.';
        $message_type = 'error';
    } else {
        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/marketplace/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = 'product_' . uniqid() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
                    $image_url = '../images/marketplace/' . $file_name;
                }
            } else {
                $message = 'Invalid image format. Please upload JPG, PNG, GIF, or WEBP.';
                $message_type = 'error';
            }
        }
        
        if (empty($message)) {
            $db = new db_connection();
            $conn = $db->db_conn();
            
            $stmt = $conn->prepare("INSERT INTO MarketplaceItems (seller_id, title, description, price, category, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issdss', $user_id, $title, $description, $price, $category, $image_url);
            
            if ($stmt->execute()) {
                $message = 'Product listed successfully!';
                $message_type = 'success';
                header('Location: marketplace.php');
                exit();
            } else {
                $message = 'Failed to list product. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Product - ReConnect Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --danger: #e74c3c;
            --success: #27ae60;
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
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .navbar-menu a:hover, .navbar-menu a.active {
            color: var(--primary);
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 30px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        h1 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .lead {
            color: var(--muted);
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .required {
            color: var(--danger);
        }
        
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        small {
            display: block;
            color: var(--muted);
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--success);
            color: white;
        }
        
        .btn-primary:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .image-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-upload:hover {
            border-color: var(--primary);
            background: #f9f9f9;
        }
        
        .image-upload i {
            font-size: 3rem;
            color: var(--muted);
            margin-bottom: 15px;
        }
        
        .image-preview {
            margin-top: 20px;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .image-preview-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: center;
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
        <div class="card">
            <h1><i class="fas fa-plus-circle"></i> List a Product</h1>
            <p class="lead">Sell items to fellow alumni and university community members</p>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Product Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Textbook: Introduction to Computer Science">
                    <small>Give your product a clear, descriptive title</small>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <div class="image-upload" onclick="document.getElementById('product_image').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p style="color:var(--muted);margin:0;">Click to upload product image</p>
                        <small style="color:var(--muted);">JPG, PNG, GIF, or WEBP (Max 5MB)</small>
                    </div>
                    <input type="file" id="product_image" name="product_image" accept="image/*" style="display:none;" onchange="previewImage(event)">
                    
                    <div class="image-preview" id="imagePreview">
                        <img id="preview" src="" alt="Preview">
                        <div class="image-preview-actions">
                            <button type="button" class="btn btn-secondary" onclick="removeImage()" style="flex:none;padding:8px 16px;font-size:0.9rem;">
                                <i class="fas fa-times"></i> Remove Image
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="Textbooks">Textbooks</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Clothing">Clothing & Apparel</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Sports">Sports Equipment</option>
                        <option value="Services">Services</option>
                        <option value="Event Tickets">Event Tickets</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (GH₵) <span class="required">*</span></label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00">
                    <small>Enter the selling price in Ghana Cedis</small>
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required placeholder="Describe your product in detail - condition, features, reason for selling, etc."></textarea>
                    <small>Provide detailed information to help buyers make informed decisions</small>
                </div>

                <div class="btn-group">
                    <a href="marketplace.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> List Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                    document.querySelector('.image-upload').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage() {
            document.getElementById('product_image').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelector('.image-upload').style.display = 'block';
        }
    </script>

    <!-- My Products Section -->
    <?php if (!empty($my_products)): ?>
        <div class="container" style="margin-top: 40px;">
            <h2 style="margin-bottom: 24px; color: var(--dark);">
                <i class="fas fa-box"></i> My Products (<?php echo count($my_products); ?>)
            </h2>
            
            <div class="products-table">
                <?php foreach ($my_products as $product): ?>
                    <div class="product-row">
                        <div class="product-image-cell">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <?php else: ?>
                                <div style="width:80px;height:80px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:8px;color:#999;">
                                    <i class="fas fa-image fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info-cell">
                            <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="product-meta">
                                <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                <span class="product-date">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                                </span>
                            </div>
                            <div class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</div>
                        </div>
                        
                        <div class="product-price-cell">
                            <div class="price">GH₵<?php echo number_format($product['price'], 2); ?></div>
                            <div class="status-badge status-<?php echo $product['status']; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </div>
                        </div>
                        
                        <div class="product-actions-cell">
                            <a href="marketplace_edit.php?id=<?php echo $product['item_id']; ?>" 
                               class="action-btn btn-edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="marketplace_view.php?id=<?php echo $product['item_id']; ?>" 
                               class="action-btn btn-view" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($product['status'] === 'available'): ?>
                                <form method="POST" action="../actions/marketplace_update_status.php" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $product['item_id']; ?>">
                                    <input type="hidden" name="status" value="sold">
                                    <input type="hidden" name="return_url" value="marketplace_create.php">
                                    <button type="submit" class="action-btn btn-sold" title="Mark as Sold"
                                            onclick="return confirm('Mark this product as sold?')">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="../actions/marketplace_update_status.php" style="display:inline;">
                                    <input type="hidden" name="item_id" value="<?php echo $product['item_id']; ?>">
                                    <input type="hidden" name="status" value="available">
                                    <input type="hidden" name="return_url" value="marketplace_create.php">
                                    <button type="submit" class="action-btn btn-available" title="Mark as Available"
                                            onclick="return confirm('Mark this product as available again?')">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="../actions/marketplace_delete.php" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?php echo $product['item_id']; ?>">
                                <button type="submit" class="action-btn btn-delete" title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <style>
        .products-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-row {
            display: grid;
            grid-template-columns: 100px 1fr 180px 160px;
            gap: 20px;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
            transition: background 0.2s;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row:hover {
            background: #f9f9f9;
        }

        .product-image-cell img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .product-info-cell {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .product-meta {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .product-category {
            background: #e8f0fe;
            color: #1967d2;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-date {
            color: #666;
            font-size: 0.85rem;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .product-price-cell {
            text-align: center;
        }

        .price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
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

        .product-actions-cell {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-edit {
            background: #e8f0fe;
            color: #1967d2;
        }

        .btn-edit:hover {
            background: #1967d2;
            color: white;
        }

        .btn-view {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .btn-view:hover {
            background: #7c3aed;
            color: white;
        }

        .btn-sold {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-sold:hover {
            background: #065f46;
            color: white;
        }

        .btn-available {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-available:hover {
            background: #92400e;
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #991b1b;
            color: white;
        }

        @media (max-width: 968px) {
            .product-row {
                grid-template-columns: 80px 1fr;
                gap: 16px;
            }

            .product-price-cell,
            .product-actions-cell {
                grid-column: 1 / -1;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .product-actions-cell {
                justify-content: flex-start;
            }
        }

        @media (max-width: 576px) {
            .product-row {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .product-image-cell img {
                margin: 0 auto;
            }

            .product-price-cell {
                flex-direction: column;
                gap: 8px;
            }

            .product-actions-cell {
                justify-content: center;
            }
        }
    </style>
</body>
</html>

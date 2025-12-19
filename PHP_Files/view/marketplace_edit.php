<?php
session_start();
require_once('../settings/db_class.php');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login_register.php');
    exit();
}

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$item_id) {
    $_SESSION['error_message'] = "Invalid product ID.";
    header('Location: marketplace_create.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get product details
$query = "SELECT * FROM MarketplaceItems WHERE item_id = ? AND seller_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $item_id, $user_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    $_SESSION['error_message'] = "Product not found or you don't have permission to edit it.";
    header('Location: marketplace_create.php');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $image_url = $product['image_url']; // Keep existing image by default
    
    if (empty($title) || empty($description) || $price <= 0) {
        $message = 'Please fill in all required fields with valid data.';
        $message_type = 'error';
    } else {
        // Handle image upload if new image is provided
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/marketplace/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = 'product_' . uniqid() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
                    // Delete old image if it exists
                    if (!empty($product['image_url'])) {
                        $old_image_path = __DIR__ . '/' . $product['image_url'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    $image_url = '../images/marketplace/' . $file_name;
                }
            } else {
                $message = 'Invalid image format. Please upload JPG, PNG, GIF, or WEBP.';
                $message_type = 'error';
            }
        }
        
        if (empty($message)) {
            $update_query = "UPDATE MarketplaceItems SET title = ?, description = ?, price = ?, category = ?, image_url = ? WHERE item_id = ? AND seller_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ssdssii', $title, $description, $price, $category, $image_url, $item_id, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Product updated successfully!';
                header('Location: marketplace_create.php');
                exit();
            } else {
                $message = 'Failed to update product. Please try again.';
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
    <title>Edit Product - ReConnect Marketplace</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--dark);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--muted);
        }
        
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fee;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }
        
        .alert-success {
            background: #efe;
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        small {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.875rem;
        }
        
        .image-upload {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .image-upload:hover {
            border-color: var(--primary);
            background: #f8f9ff;
        }
        
        .image-upload i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        #imagePreview {
            display: none;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }
        
        #imagePreview img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
        }
        
        #imagePreview .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        #imagePreview .remove-btn:hover {
            background: var(--danger);
            color: white;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .btn-group {
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
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Product</h1>
            <p>Update your product information</p>
        </div>

        <div class="form-card">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Product Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($product['title']); ?>"
                           placeholder="e.g., MacBook Pro 2020">
                    <small>Give your product a clear, descriptive title</small>
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="Electronics" <?php echo $product['category'] === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="Books" <?php echo $product['category'] === 'Books' ? 'selected' : ''; ?>>Books</option>
                        <option value="Clothing" <?php echo $product['category'] === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                        <option value="Furniture" <?php echo $product['category'] === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                        <option value="Sports" <?php echo $product['category'] === 'Sports' ? 'selected' : ''; ?>>Sports Equipment</option>
                        <option value="Other" <?php echo $product['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" id="product_image" name="product_image" accept="image/*" style="display: none;" onchange="previewImage(event)">
                    
                    <div id="imagePreview" <?php echo !empty($product['image_url']) ? 'style="display:block;"' : ''; ?>>
                        <img id="preview" src="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" alt="Preview">
                        <button type="button" class="remove-btn" onclick="removeImage()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="image-upload" onclick="document.getElementById('product_image').click()" 
                         <?php echo !empty($product['image_url']) ? 'style="display:none;"' : ''; ?>>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p style="margin: 10px 0; font-weight: 600;">Click to upload new image</p>
                        <p style="color: var(--muted); font-size: 0.9rem;">JPG, PNG, GIF or WEBP (Max 5MB)</p>
                    </div>
                    <small>Leave empty to keep current image</small>
                </div>

                <div class="form-group">
                    <label for="price">Price (GH₵) <span class="required">*</span></label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo $product['price']; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="marketplace_create.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
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
</body>
</html>

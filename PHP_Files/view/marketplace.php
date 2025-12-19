<?php
session_start();

require_once __DIR__ . '/../settings/db_class.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
          FROM MarketplaceItems m 
          JOIN Users u ON m.seller_id = u.user_id 
          WHERE m.status = 'available'";

if ($category !== 'all' && !empty($category)) {
    $query .= " AND m.category = '" . $conn->real_escape_string($category) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $query .= " AND (m.title LIKE '%$search_term%' OR m.description LIKE '%$search_term%')";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY m.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY m.price DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY m.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY m.created_at DESC";
}

$result = $conn->query($query);
$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Get categories for filter
$cat_query = "SELECT DISTINCT category FROM MarketplaceItems WHERE status = 'available' AND category IS NOT NULL ORDER BY category";
$cat_result = $conn->query($cat_query);
$categories = [];
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - ReConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --danger: #e74c3c;
            --success: #27ae60;
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
        
        /* Navigation Bar */
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
        
        .navbar-menu a:hover {
            color: var(--primary);
        }
        
        .navbar-menu a.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--muted);
            font-size: 1.1rem;
        }
        
        .toolbar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            flex: 1;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            min-width: 300px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn.success {
            background: var(--success);
        }
        
        .btn.success:hover {
            background: #229954;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .product-content {
            padding: 20px;
        }
        
        .product-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .product-description {
            color: var(--muted);
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .product-seller {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .seller-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .product-category {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--muted);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: var(--muted);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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
                <li><a href="marketplace.php" class="active"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-store"></i> Marketplace</h1>
            <p>Buy and sell products within the alumni network</p>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="filters">
                <form method="GET" action="marketplace.php" class="search-box">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
                
                <form method="GET" action="marketplace.php" style="display:flex;gap:15px;">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <select name="category" onchange="this.form.submit()">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </form>
            </div>
            
            <a href="marketplace_create.php" class="btn success">
                <i class="fas fa-plus"></i> Sell Product
            </a>
        </div>

        <!-- Products Grid -->
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>There are no products available at the moment. Be the first to list something!</p>
                <a href="marketplace_create.php" class="btn success">
                    <i class="fas fa-plus"></i> List Your First Product
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($items as $item): ?>
                    <div class="product-card" onclick="window.location.href='marketplace_view.php?id=<?php echo $item['item_id']; ?>'">
                        <div class="product-image" style="<?php echo !empty($item['image_url']) ? 'background:none;' : ''; ?>">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <i class="fas fa-tag"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <?php if (!empty($item['category'])): ?>
                                <span class="product-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <?php endif; ?>
                            
                            <div class="product-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars($item['description']); ?></div>
                            
                            <div class="product-footer">
                                <div class="product-price">GH₵<?php echo number_format($item['price'], 2); ?></div>
                                <div class="product-seller">
                                    <?php if (!empty($item['profile_photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['profile_photo']); ?>" alt="Seller" class="seller-avatar" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div class="seller-avatar">
                                            <?php echo strtoupper(substr($item['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

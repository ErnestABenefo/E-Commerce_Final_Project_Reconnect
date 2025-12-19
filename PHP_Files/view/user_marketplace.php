<?php
session_start();
require_once('../settings/core.php');
require_once('../settings/db_class.php');

// Check if user is logged in
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$current_user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_register.php");
    exit();
}

// Get user ID from URL
$seller_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;

$db = new db_connection();
$conn = $db->db_conn();

// Get seller information
$seller_query = "SELECT first_name, last_name, profile_photo FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($seller_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: marketplace.php");
    exit();
}

$is_own_marketplace = ($current_user_id === $seller_id);

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT m.*, u.first_name, u.last_name, u.profile_photo 
          FROM MarketplaceItems m
          JOIN Users u ON m.seller_id = u.user_id
          WHERE m.seller_id = ? AND m.status = 'available'";

$params = [$seller_id];
$types = "i";

if (!empty($search)) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($category)) {
    $query .= " AND m.category = ?";
    $params[] = $category;
    $types .= "s";
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY m.created_at ASC";
        break;
    case 'price_low':
        $query .= " ORDER BY m.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY m.price DESC";
        break;
    default:
        $query .= " ORDER BY m.created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for filter
$cat_query = "SELECT DISTINCT category FROM MarketplaceItems WHERE seller_id = ? AND status = 'available' ORDER BY category";
$stmt = $conn->prepare($cat_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?>'s Products</title>
    <link rel="stylesheet" href="css/libs.min.css">
    <link rel="stylesheet" href="css/socialv.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
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

        /* Marketplace Container */
        .marketplace-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .seller-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--light);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }

        .controls {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .search-box, .filter-select, .sort-select {
            flex: 1;
            min-width: 200px;
        }

        .search-box input, .filter-select select, .sort-select select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }

        .search-box input:focus, .filter-select select:focus, .sort-select select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--light);
        }

        .product-body {
            padding: 16px;
        }

        .product-category {
            display: inline-block;
            background: #ddd6fe;
            color: #5b21b6;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .empty-state p {
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

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 16px;
            }

            .controls {
                flex-direction: column;
            }

            .search-box, .filter-select, .sort-select {
                width: 100%;
            }

            .navbar-menu {
                gap: 1rem;
                font-size: 0.9rem;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
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

    <div class="marketplace-container">
        <!-- Page Header -->
        <div class="page-header">
            <?php if (!empty($seller['profile_photo'])): ?>
                <img src="../<?php echo htmlspecialchars($seller['profile_photo']); ?>" 
                     alt="<?php echo htmlspecialchars($seller['first_name']); ?>" 
                     class="seller-avatar">
            <?php else: ?>
                <div class="seller-avatar" style="display:flex;align-items:center;justify-content:center;background:var(--light);color:#9ca3af;">
                    <i class="fas fa-user fa-2x"></i>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="page-title">
                    <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?>'s Products
                </h1>
                <p class="page-subtitle">
                    <?php echo count($items); ?> product<?php echo count($items) !== 1 ? 's' : ''; ?> available
                </p>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <form method="GET" style="display:contents;">
                <input type="hidden" name="user_id" value="<?php echo $seller_id; ?>">
                
                <div class="search-box">
                    <input type="text" 
                           name="search" 
                           placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="filter-select">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sort-select">
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h2>No Products Found</h2>
                <p><?php echo $is_own_marketplace ? 'You haven\'t listed any products yet.' : 'This user hasn\'t listed any products yet.'; ?></p>
                <?php if ($is_own_marketplace): ?>
                    <a href="marketplace_create.php" class="btn-primary">
                        <i class="fas fa-plus"></i> List Your First Product
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($items as $item): ?>
                    <a href="marketplace_view.php?id=<?php echo $item['item_id']; ?>" class="product-card">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-body">
                            <span class="product-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <h3 class="product-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">GH₵<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

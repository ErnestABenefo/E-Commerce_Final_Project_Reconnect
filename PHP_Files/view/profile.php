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

// Get profile user ID from URL
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;

$db = new db_connection();
$conn = $db->db_conn();

// Get user information
$user_query = "SELECT u.*, 
               (SELECT COUNT(*) FROM MarketplaceItems WHERE seller_id = u.user_id AND status = 'available') as product_count
               FROM Users u 
               WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
if (!$stmt) {
    die("Query failed: " . $conn->error);
}
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}

// Set connection_count to 0 for now (Connections table not yet implemented)
$user['connection_count'] = 0;

$is_own_profile = ($current_user_id === $profile_user_id);

// Connection status - feature not yet implemented
$connection_status = null;

// Get user's university information
// Note: university_id not yet added to Users table
$university = null;
$departments = [];

// Get user's recent marketplace items
$products_query = "SELECT * FROM MarketplaceItems WHERE seller_id = ? AND status = 'available' ORDER BY created_at DESC LIMIT 6";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> - Profile</title>
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

        /* Profile Container */
        .profile-container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .profile-main {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .profile-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .meta-item {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .meta-item i {
            color: var(--primary);
            margin-right: 6px;
        }

        .profile-bio {
            color: var(--dark);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .profile-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-all {
            font-size: 0.9rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 16px;
        }

        .product-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: var(--light);
        }

        .product-info {
            padding: 12px;
        }

        .product-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1rem;
        }

        /* University Info */
        .university-card {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .uni-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .uni-detail {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .uni-detail i {
            color: var(--primary);
            margin-top: 2px;
        }

        .departments-list {
            margin-top: 20px;
        }

        .dept-group {
            margin-bottom: 16px;
        }

        .faculty-name {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .dept-item {
            padding: 8px 12px;
            background: var(--light);
            border-radius: 6px;
            margin-bottom: 6px;
            font-size: 0.85rem;
            color: #4b5563;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .profile-main {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .profile-actions {
                justify-content: center;
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .navbar-menu {
                gap: 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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

    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-main">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img src="../<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                         alt="<?php echo htmlspecialchars($user['first_name']); ?>" 
                         class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar" style="display:flex;align-items:center;justify-content:center;background:var(--light);color:#9ca3af;font-size:3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>

                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    
                    <div class="profile-meta">
                        <?php if (!empty($user['email'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($user['phone'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($user['year_group'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-graduation-cap"></i>
                                Class of <?php echo htmlspecialchars($user['year_group']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($user['bio'])): ?>
                        <div class="profile-bio">
                            <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $user['connection_count']; ?></div>
                            <div class="stat-label">Connections</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $user['product_count']; ?></div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo date('Y', strtotime($user['created_at'])); ?></div>
                            <div class="stat-label">Joined</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <?php if ($is_own_profile): ?>
                    <a href="profile_edit.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="user_marketplace.php?user_id=<?php echo $current_user_id; ?>" class="btn btn-outline">
                        <i class="fas fa-store"></i> My Products
                    </a>
                <?php else: ?>
                    <?php if ($connection_status === 'accepted'): ?>
                        <button class="btn btn-success" disabled>
                            <i class="fas fa-check"></i> Connected
                        </button>
                    <?php elseif ($connection_status === 'pending'): ?>
                        <button class="btn btn-outline" disabled>
                            <i class="fas fa-clock"></i> Request Pending
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="sendConnectionRequest(<?php echo $profile_user_id; ?>)">
                            <i class="fas fa-user-plus"></i> Connect
                        </button>
                    <?php endif; ?>
                    <a href="user_marketplace.php?user_id=<?php echo $profile_user_id; ?>" class="btn btn-outline">
                        <i class="fas fa-store"></i> View Products
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div>
                <!-- Recent Products -->
                <div class="section">
                    <div class="section-title">
                        <span><i class="fas fa-store"></i> Products</span>
                        <?php if (!empty($products)): ?>
                            <a href="user_marketplace.php?user_id=<?php echo $profile_user_id; ?>" class="view-all">View All</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <a href="marketplace_view.php?id=<?php echo $product['item_id']; ?>" class="product-card">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="product-image" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-info">
                                        <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                                        <div class="product-price">GH₵<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>No products listed yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- University Information -->
                <?php if ($university): ?>
                    <div class="section">
                        <div class="section-title">
                            <span><i class="fas fa-university"></i> University</span>
                        </div>
                        
                        <div class="university-card">
                            <div class="uni-name"><?php echo htmlspecialchars($university['university_name']); ?></div>
                            
                            <?php if (!empty($university['location'])): ?>
                                <div class="uni-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($university['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($university['established_year'])): ?>
                                <div class="uni-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span>Established <?php echo htmlspecialchars($university['established_year']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($university['university_type'])): ?>
                                <div class="uni-detail">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo ucfirst(htmlspecialchars($university['university_type'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($university['website'])): ?>
                                <div class="uni-detail">
                                    <i class="fas fa-globe"></i>
                                    <a href="<?php echo htmlspecialchars($university['website']); ?>" target="_blank" style="color:var(--primary);">
                                        Visit Website
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($university['description'])): ?>
                                <div class="uni-detail" style="margin-top:12px;">
                                    <div><?php echo nl2br(htmlspecialchars($university['description'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($departments)): ?>
                            <div class="departments-list">
                                <div style="font-weight:700;margin-bottom:12px;color:var(--dark);">
                                    <i class="fas fa-book"></i> Courses Offered
                                </div>
                                <?php 
                                $grouped_depts = [];
                                foreach ($departments as $dept) {
                                    $grouped_depts[$dept['faculty']][] = $dept['department_name'];
                                }
                                ?>
                                <?php foreach ($grouped_depts as $faculty => $depts): ?>
                                    <div class="dept-group">
                                        <div class="faculty-name"><?php echo htmlspecialchars($faculty); ?></div>
                                        <?php foreach ($depts as $dept_name): ?>
                                            <div class="dept-item">
                                                <i class="fas fa-graduation-cap" style="font-size:0.75rem;color:var(--primary);margin-right:6px;"></i>
                                                <?php echo htmlspecialchars($dept_name); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function sendConnectionRequest(userId) {
            if (confirm('Send connection request to this user?')) {
                // TODO: Implement connection request functionality
                alert('Connection request feature coming soon!');
            }
        }
    </script>
</body>
</html>

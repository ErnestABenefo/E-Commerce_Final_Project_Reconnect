<?php
session_start();
require_once('../settings/db_class.php');

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$current_user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_register.php");
    exit();
}

// Get profile user ID from URL
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$profile_user_id || $profile_user_id === $current_user_id) {
    header("Location: dashboard.php");
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get user information with stats
$user_query = "SELECT u.*,
               (SELECT COUNT(*) FROM MarketplaceItems WHERE seller_id = u.user_id AND status = 'available') as product_count,
               (SELECT COUNT(*) FROM Posts WHERE user_id = u.user_id) as post_count
               FROM Users u 
               WHERE u.user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}

// Check connection status
$connection_query = "SELECT * FROM UserConnections 
                     WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)";
$stmt = $conn->prepare($connection_query);
$stmt->bind_param("iiii", $current_user_id, $profile_user_id, $profile_user_id, $current_user_id);
$stmt->execute();
$connection = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_connected = $connection && $connection['status'] === 'accepted';
$is_pending = $connection && $connection['status'] === 'pending';
$is_sender = $connection && $connection['user_id_1'] == $current_user_id;

// Get connection count
$conn_count_query = "SELECT COUNT(*) as count FROM UserConnections 
                     WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'";
$stmt = $conn->prepare($conn_count_query);
$stmt->bind_param("ii", $profile_user_id, $profile_user_id);
$stmt->execute();
$conn_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Check if user is verified alumni and get university details
$verification_query = "SELECT av.*, u.name as university_name, u.location as university_location,
                       u.website as university_website
                       FROM AlumniVerification av
                       LEFT JOIN University u ON av.university_id = u.university_id
                       WHERE av.user_id = ? AND av.verification_status = 'approved' 
                       ORDER BY av.verified_at DESC LIMIT 1";
$stmt = $conn->prepare($verification_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$verification_data = $stmt->get_result()->fetch_assoc();
$is_verified = $verification_data ? true : false;
$stmt->close();

// Get academic profile with department details
$academic_query = "SELECT uap.*, ad.department_name, ad.faculty, u.name as university_name
                   FROM UserAcademicProfile uap
                   LEFT JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
                   LEFT JOIN University u ON ad.university_id = u.university_id
                   WHERE uap.user_id = ?
                   ORDER BY uap.graduation_year DESC";
$stmt = $conn->prepare($academic_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$academic_profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user skills
$skills_query = "SELECT skill_name FROM UserSkills WHERE user_id = ? ORDER BY skill_name";
$stmt = $conn->prepare($skills_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$skills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's recent marketplace items
$products_query = "SELECT * FROM MarketplaceItems 
                   WHERE seller_id = ? AND status = 'available' 
                   ORDER BY created_at DESC LIMIT 6";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --muted: #6b7280;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f9fafb;
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
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
            gap: 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            white-space: nowrap;
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
            margin: 0;
            padding: 0;
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
            background: #dc2626;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .profile-header-content {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .profile-avatar {
            flex-shrink: 0;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .avatar-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            border: 5px solid var(--light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .verified-badge i {
            font-size: 0.95rem;
        }

        .profile-email {
            color: var(--muted);
            font-size: 1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-bio {
            color: var(--dark);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--light);
            border-radius: 8px;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--muted);
            text-transform: uppercase;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Section */
        .section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--dark);
        }

        .section-title i {
            color: var(--primary);
        }

        /* Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: white;
        }

        .info-card.university {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .info-card.academic {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .info-card-title {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card-content {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .info-card-details {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 8px;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .skill-tag {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .academic-item {
            background: var(--light);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .academic-item-header {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .academic-item-details {
            color: var(--muted);
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            border-color: var(--primary);
        }

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: var(--light);
        }

        .product-info {
            padding: 15px;
        }

        .product-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .product-category {
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .profile-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-header-content">
                <div class="profile-avatar">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="avatar">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        <?php if ($is_verified): ?>
                            <span class="verified-badge" title="Verified Alumni">
                                <i class="fas fa-check-circle"></i> Verified Alumni
                            </span>
                        <?php endif; ?>
                    </h1>
                    
                    <div class="profile-email">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>

                    <?php if (!empty($user['phone'])): ?>
                        <div class="profile-email">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($user['phone']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($user['year_group'])): ?>
                        <div class="profile-email">
                            <i class="fas fa-graduation-cap"></i>
                            Year Group: <?php echo htmlspecialchars($user['year_group']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($user['bio'])): ?>
                        <div class="profile-bio">
                            <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $conn_count; ?></div>
                            <div class="stat-label">Connections</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $user['product_count']; ?></div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $user['post_count']; ?></div>
                            <div class="stat-label">Posts</div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <?php if ($is_connected): ?>
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-check-circle"></i> Connected
                            </button>
                            <a href="user_chat.php?user_id=<?php echo $profile_user_id; ?>" class="btn btn-primary">
                                <i class="fas fa-comments"></i> Chat
                            </a>
                        <?php elseif ($is_pending): ?>
                            <?php if ($is_sender): ?>
                                <button class="btn btn-warning" disabled>
                                    <i class="fas fa-clock"></i> Request Sent
                                </button>
                            <?php else: ?>
                                <form method="POST" action="../actions/connection_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="user_id" value="<?php echo $profile_user_id; ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-user-check"></i> Accept Request
                                    </button>
                                </form>
                                <form method="POST" action="../actions/connection_action.php" style="display:inline;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="user_id" value="<?php echo $profile_user_id; ?>">
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" action="../actions/connection_action.php">
                                <input type="hidden" name="action" value="send">
                                <input type="hidden" name="user_id" value="<?php echo $profile_user_id; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Connect
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="user_marketplace.php?user_id=<?php echo $profile_user_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-store"></i> View Marketplace
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- University & Academic Information -->
        <?php if ($is_verified || !empty($academic_profiles)): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-university"></i>
                Education & Academic Information
            </h2>
            
            <div class="info-grid">
                <?php if ($is_verified && $verification_data): ?>
                <div class="info-card university">
                    <div class="info-card-title">
                        <i class="fas fa-graduation-cap"></i>
                        Verified Alumni
                    </div>
                    <div class="info-card-content">
                        <?php echo htmlspecialchars($verification_data['university_name'] ?? 'University'); ?>
                    </div>
                    <div class="info-card-details">
                        <?php if (!empty($verification_data['graduation_year'])): ?>
                            <i class="fas fa-calendar"></i> Graduated: <?php echo htmlspecialchars($verification_data['graduation_year']); ?>
                        <?php endif; ?>
                        <?php if (!empty($verification_data['university_location'])): ?>
                            <br><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($verification_data['university_location']); ?>
                        <?php endif; ?>
                        <?php if (!empty($verification_data['student_id_number'])): ?>
                            <br><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($verification_data['student_id_number']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($academic_profiles)): ?>
            <h3 style="margin-top: 20px; margin-bottom: 15px; color: var(--dark); font-size: 1.2rem;">
                <i class="fas fa-book"></i> Academic Background
            </h3>
            <?php foreach ($academic_profiles as $profile): ?>
            <div class="academic-item">
                <div class="academic-item-header">
                    <?php echo htmlspecialchars($profile['degree'] ?? 'Degree'); ?>
                    <?php if (!empty($profile['department_name'])): ?>
                        - <?php echo htmlspecialchars($profile['department_name']); ?>
                    <?php endif; ?>
                </div>
                <div class="academic-item-details">
                    <?php if (!empty($profile['university_name'])): ?>
                        <span><i class="fas fa-university"></i> <?php echo htmlspecialchars($profile['university_name']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile['faculty'])): ?>
                        <span><i class="fas fa-building"></i> Faculty: <?php echo htmlspecialchars($profile['faculty']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($profile['graduation_year'])): ?>
                        <span><i class="fas fa-calendar-check"></i> Graduated: <?php echo htmlspecialchars($profile['graduation_year']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Skills Section -->
        <?php if (!empty($skills)): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-tools"></i>
                Skills & Expertise
            </h2>
            <div class="skills-container">
                <?php foreach ($skills as $skill): ?>
                    <span class="skill-tag">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($skill['skill_name']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Section -->
        <?php if (!empty($products)): ?>
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-shopping-bag"></i>
                Recent Products
            </h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <a href="marketplace_view.php?id=<?php echo $product['item_id']; ?>" class="product-card">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-box" style="font-size:3rem;color:#ccc;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="product-info">
                            <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                            <div class="product-price">GH₵<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Dashboard - main homepage for logged-in users
session_start();

// Require DB connection helper
require_once __DIR__ . '/../settings/db_class.php';

// Use session user id set by login controller
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Helper to safely fetch one row with prepared statement
function fetch_one($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null; // Query failed to prepare
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

// Helper to fetch all rows
function fetch_all($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return []; // Query failed to prepare
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// Load current user info
$user = fetch_one($conn, "SELECT user_id, first_name, last_name, email, phone, profile_photo, bio, created_at FROM Users WHERE user_id = ?", "i", [$user_id]);

// Check if user is verified alumni
$is_verified = false;
$verification_check = fetch_one($conn, "SELECT verification_id FROM AlumniVerification WHERE user_id = ? AND verification_status = 'approved' LIMIT 1", "i", [$user_id]);
if ($verification_check) {
    $is_verified = true;
}

// Check if acting as university and set display name accordingly
$acting_as_university = isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true;
if ($acting_as_university) {
    $displayName = htmlspecialchars($_SESSION['active_university_name']);
    $active_university_id = (int)$_SESSION['active_university_id'];
} else {
    $displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';
}

// Check if user is a university admin (for any university)
$is_admin = false;
$admin_check = fetch_one($conn, "SELECT ua_id FROM UniversityAdmins WHERE user_id = ? LIMIT 1", "i", [$user_id]);
if ($admin_check) {
    $is_admin = true;
} else {
    // Also check if user created any university
    $creator_check = fetch_one($conn, "SELECT university_id FROM University WHERE created_by = ? LIMIT 1", "i", [$user_id]);
    if ($creator_check) {
        $is_admin = true;
    }
}

// Check if user is global admin
require_once '../controllers/university_admin_controller.php';
$is_global_admin = is_global_admin_ctr($user_id);

// Stats
// Get actual connection count (accepted connections)
$connectionsRow = fetch_one($conn, "SELECT COUNT(*) AS c FROM UserConnections WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'", "ii", [$user_id, $user_id]);
$connectionsCount = $connectionsRow ? (int)$connectionsRow['c'] : 0;

// Get pending connection requests count (received)
$pendingRequestsRow = fetch_one($conn, "SELECT COUNT(*) AS c FROM UserConnections WHERE user_id_2 = ? AND status = 'pending'", "i", [$user_id]);
$pendingRequestsCount = $pendingRequestsRow ? (int)$pendingRequestsRow['c'] : 0;

$groupsRow = fetch_one($conn, "SELECT COUNT(*) AS c FROM GroupMembers WHERE user_id = ?", "i", [$user_id]);
$groupsCount = $groupsRow ? (int)$groupsRow['c'] : 0;
$mentorshipRow = fetch_one($conn, "SELECT COUNT(*) AS c FROM MentorshipPrograms WHERE (mentor_id = ? OR mentee_id = ?)", "ii", [$user_id, $user_id]);
$mentorshipCount = $mentorshipRow ? (int)$mentorshipRow['c'] : 0;

// Recent feed posts (limit 6) - Filter by context with like and comment counts
if ($acting_as_university) {
    // Show only university posts
    $recentPosts = fetch_all($conn, "SELECT p.post_id, p.content, p.image_url, p.post_type, p.created_at, p.creator_type,
        uni.name as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked
        FROM Posts p 
        JOIN University uni ON p.university_id = uni.university_id 
        WHERE p.creator_type = 'university' AND p.university_id = ?
        ORDER BY p.created_at DESC LIMIT 6", "ii", [$user_id, $active_university_id]);
} else {
    // Show only user's personal posts
    $recentPosts = fetch_all($conn, "SELECT p.post_id, p.content, p.image_url, p.post_type, p.created_at, p.creator_type,
        u.first_name, u.last_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked
        FROM Posts p 
        JOIN Users u ON p.user_id = u.user_id 
        WHERE p.creator_type = 'user' AND p.user_id = ?
        ORDER BY p.created_at DESC LIMIT 6", "ii", [$user_id, $user_id]);
}

// Upcoming events (limit 5)
$upcomingEvents = fetch_all($conn, "SELECT event_id, title, description, start_datetime, location FROM Events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC LIMIT 5");

// Marketplace items (limit 5)
$marketplace = fetch_all($conn, "SELECT item_id, title, description, price, created_at FROM MarketplaceItems WHERE status = 'available' ORDER BY created_at DESC LIMIT 5");

// Latest job listings (limit 5)
$jobs = fetch_all($conn, "SELECT job_id, title, company, location, job_type, created_at FROM JobListings ORDER BY created_at DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - ReConnect</title>
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
        
        .navbar-menu a.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        .navbar-menu .btn {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .navbar-menu .btn:hover {
            background: var(--primary-dark);
        }
        
        .navbar-menu .btn.danger {
            background: var(--danger);
        }
        
        .navbar-menu .btn.danger:hover {
            background: #c0392b;
        }
        
        /* Search Bar */
        .search-container {
            position: relative;
            flex: 1;
            max-width: 500px;
            margin: 0 20px;
        }
        
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            color: var(--muted);
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 0.95rem;
            transition: all 0.3s;
            outline: none;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
            background: white;
        }
        
        .search-results {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            max-height: 500px;
            overflow-y: auto;
            z-index: 10000;
            border: 1px solid #e0e0e0;
        }
        
        .search-results.active {
            display: block;
        }
        
        .search-category {
            padding: 15px 20px;
        }
        
        .search-category-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .search-item:hover {
            background: #f8f9fa;
        }
        
        .search-item-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .search-item-icon img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .search-item-content {
            flex: 1;
            min-width: 0;
        }
        
        .search-item-title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .search-item-meta {
            font-size: 0.85rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .search-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        
        .search-empty i {
            font-size: 3rem;
            margin-bottom: 10px;
            opacity: 0.3;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .navbar-menu {
                gap: 15px;
                font-size: 0.9rem;
            }
            
            .search-container {
                max-width: 300px;
            }
        }
        
        @media (max-width: 992px) {
            .navbar-menu li a span {
                display: none;
            }
            
            .navbar-menu {
                gap: 10px;
            }
            
            .search-container {
                max-width: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-container {
                flex-wrap: wrap;
            }
            
            .search-container {
                order: 3;
                max-width: 100%;
                width: 100%;
                margin: 10px 0 0 0;
            }
            
            .navbar-menu {
                order: 2;
                gap: 5px;
            }
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        /* Typography */
        h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
            color: var(--dark);
        }
        
        h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        h3 {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        p.lead {
            color: var(--muted);
            font-size: 1.1rem;
            margin: 0;
        }
        
        /* Buttons */
        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn.secondary {
            background: var(--danger);
        }
        
        .btn.secondary:hover {
            background: #c0392b;
        }
        
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .grid.two-col {
            grid-template-columns: 2fr 1fr;
        }
        
        @media (max-width: 1024px) {
            .grid, .grid.two-col {
                grid-template-columns: 1fr;
            }
        }
        
        /* Profile Block */
        .profile-block {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1.5rem;
        }
        
        /* Stats */
        .stats {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            min-width: 120px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat.clickable {
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        
        .stat.clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .stat-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4757;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(255, 71, 87, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Verification Badge */
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }

        .verified-badge i {
            font-size: 0.85rem;
        }
        
        /* Section */
        .section {
            margin-top: 20px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* List Items */
        .list-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        /* Links */
        a.link {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        a.link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        /* University Banner */
        .university-banner {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }
        
        .university-banner i {
            font-size: 28px;
        }
        
        .university-banner-content {
            flex: 1;
        }
        
        .university-banner-title {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .university-banner-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
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
            
            <!-- Global Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearch" placeholder="Search users, products, events, jobs..." autocomplete="off">
                </div>
                <div id="searchResults" class="search-results"></div>
            </div>
            
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="connections.php"><i class="fas fa-user-friends"></i> Connections</a></li>
                <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <?php if ($is_global_admin): ?>
                <li><a href="global_admin_panel.php" style="color: #f39c12;"><i class="fas fa-crown"></i> Admin Panel</a></li>
                <?php endif; ?>
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
        <?php if (isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true): ?>
        <!-- University Context Banner -->
        <div class="university-banner">
            <i class="fas fa-university"></i>
            <div class="university-banner-content">
                <div class="university-banner-title">Acting as <?php echo htmlspecialchars($_SESSION['active_university_name']); ?></div>
                <div class="university-banner-subtitle">All posts and groups you create will be attributed to this university</div>
            </div>
            <i class="fas fa-info-circle" style="font-size: 24px; opacity: 0.8;"></i>
        </div>
        <?php endif; ?>
        
        <!-- Welcome Header -->
        <div class="card">
            <h1>Welcome back, <?php echo $displayName; ?>! 👋</h1>
            <p class="lead">Here's what's happening in your network today.</p>
        </div>
        
        <!-- Stats Grid -->
        <div class="grid">
            <a href="connections.php" class="stat clickable" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <?php if ($pendingRequestsCount > 0): ?>
                    <span class="stat-badge"><?php echo $pendingRequestsCount; ?> New</span>
                <?php endif; ?>
                <div class="stat-number"><?php echo $connectionsCount; ?></div>
                <div class="stat-label"><i class="fas fa-user-friends"></i> Connections</div>
            </a>
            <a href="groups.php" class="stat clickable" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                <div class="stat-number"><?php echo $groupsCount; ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Groups</div>
            </a>
            <div class="stat" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                <div class="stat-number"><?php echo $mentorshipCount; ?></div>
                <div class="stat-label"><i class="fas fa-hands-helping"></i> Mentorships</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid two-col">
            <div>
                <!-- Profile Card -->
                <div class="card">
                    <h3><i class="fas fa-user-circle"></i> Your Profile</h3>
                    <div class="profile-block">
                        <div class="avatar" id="avatarWrap">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img id="profileAvatar" src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="avatar" style="width:80px;height:80px;border-radius:50%;object-fit:cover">
                            <?php else: ?>
                                <div id="profileAvatar" style="width:80px;height:80px;border-radius:50%;background:#667eea;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.5rem"><?php echo strtoupper(substr($user['first_name'] ?? 'U',0,1) . substr($user['last_name'] ?? '',0,1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1">
                            <h3 style="margin:0 0 5px 0;">
                                <?php echo $displayName; ?>
                                <?php if ($is_verified): ?>
                                    <span class="verified-badge" title="Verified Alumni">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </h3>
                            <p style="color:var(--muted);margin:0 0 8px 0"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet'); ?></p>
                            <p style="color:var(--muted);font-size:0.9rem;margin:0">
                                <i class="fas fa-calendar"></i> Joined <?php echo isset($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : ''; ?>
                            </p>

                            <div style="margin-top:15px;display:flex;gap:8px;align-items:center">
                                <input id="profilePhotoInput" type="file" accept="image/*" style="font-size:0.9rem">
                                <button id="uploadPhotoBtn" class="btn" type="button">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Bio Section -->
                <div class="card">
                    <h3><i class="fas fa-pen"></i> Edit Bio</h3>
                    <form id="bioForm" method="POST" action="../actions/update_bio.php">
                        <div style="margin-bottom:15px">
                            <textarea id="bioTextarea" name="bio" rows="4" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:8px;font-size:1rem;resize:vertical;font-family:inherit" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            <small style="display:block;margin-top:6px;color:var(--muted)">Share your interests, achievements, or what you're looking for in the alumni network</small>
                        </div>
                        <button type="submit" class="btn" style="background:#27ae60">
                            <i class="fas fa-save"></i> Save Bio
                        </button>
                    </form>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <?php include 'context_switcher.php'; ?>
                        <a href="groups.php" class="btn">
                            <i class="fas fa-users"></i> My Groups
                        </a>
                        <button id="openCreatePost" class="btn">
                            <i class="fas fa-edit"></i> New Post
                        </button>
                        <a class="btn" href="events_create.php">
                            <i class="fas fa-calendar-plus"></i> Create Event
                        </a>
                        <a class="btn" href="marketplace_create.php">
                            <i class="fas fa-tag"></i> List Item
                        </a>
                        <?php if ($is_admin): ?>
                        <a class="btn" href="register_university.php" style="background:#16a085">
                            <i class="fas fa-university"></i> Register University
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                    
                <!-- Profile Setup -->
                <div class="card">
                    <h3><i class="fas fa-cog"></i> Profile Setup</h3>
                    <div class="quick-actions">
                        <a href="alumni_verification.php" class="btn" style="background:#27ae60">
                            <i class="fas fa-graduation-cap"></i> Alumni Verification
                        </a>
                        <a href="academic_profile.php" class="btn" style="background:#f57c00">
                            <i class="fas fa-university"></i> Academic Profile
                        </a>
                        <?php if ($is_admin): ?>
                        <a href="group_setup.php" class="btn" style="background:#9c27b0">
                            <i class="fas fa-cogs"></i> Group Setup
                        </a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                        <a href="admin_verification_approval.php" class="btn" style="background:#e74c3c">
                            <i class="fas fa-user-shield"></i> Admin: Verify Alumni
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Community Feed -->
                <div class="card">
                    <h3><i class="fas fa-rss"></i> Community Feed</h3>
                    <div id="feedList">
                    <?php if (!empty($recentPosts)): ?>
                        <?php foreach ($recentPosts as $post): 
                            // Determine creator name based on creator type
                            if ($post['creator_type'] === 'university') {
                                $creatorName = htmlspecialchars($post['university_name']);
                                $creatorIcon = '<i class="fas fa-university" style="color:#ff9800;margin-right:4px;"></i>';
                            } else {
                                $creatorName = htmlspecialchars($post['first_name'] . ' ' . $post['last_name']);
                                $creatorIcon = '<i class="fas fa-user" style="color:#667eea;margin-right:4px;"></i>';
                            }
                        ?>
                            <div class="list-item post-item" data-post-id="<?php echo $post['post_id']; ?>" style="border-bottom:1px solid #eee;padding:15px 0;">
                                <!-- Post Header -->
                                <div style="font-weight:700;margin-bottom:8px;">
                                    <?php echo $creatorIcon . $creatorName; ?> 
                                    <span style="font-weight:400;color:var(--muted);font-size:0.85rem"> · <?php echo date('M j, Y H:i', strtotime($post['created_at'])); ?></span>
                                </div>
                                
                                <!-- Post Content -->
                                <div style="margin-top:6px;margin-bottom:12px;">
                                    <?php if (!empty($post['content'])): ?>
                                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                             alt="Post image" 
                                             style="max-width:100%;height:auto;border-radius:8px;margin-top:10px;cursor:pointer;"
                                             onclick="window.open(this.src, '_blank')">
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Post Actions (Like, Comment) -->
                                <div style="display:flex;gap:20px;align-items:center;padding-top:8px;border-top:1px solid #f0f0f0;">
                                    <!-- Like Button -->
                                    <button class="like-btn" data-post-id="<?php echo $post['post_id']; ?>" 
                                            style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:5px;transition:background 0.2s;"
                                            onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='none'">
                                        <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart" 
                                           style="color:<?php echo $post['user_liked'] ? '#e74c3c' : '#666'; ?>;font-size:16px;"></i>
                                        <span class="like-count" style="color:#666;font-size:14px;">❤️ <?php echo $post['like_count']; ?></span>
                                    </button>
                                    
                                    <!-- Comment Button -->
                                    <button class="comment-toggle-btn" data-post-id="<?php echo $post['post_id']; ?>"
                                            style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:5px;transition:background 0.2s;"
                                            onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='none'">
                                        <i class="far fa-comment" style="color:#666;font-size:16px;"></i>
                                        <span class="comment-count" style="color:#666;font-size:14px;">💬 <?php echo $post['comment_count']; ?></span>
                                    </button>
                                </div>
                                
                                <!-- Comments Section (Hidden by default) -->
                                <div class="comments-section" data-post-id="<?php echo $post['post_id']; ?>" style="display:none;margin-top:15px;padding-top:15px;border-top:1px solid #f0f0f0;">
                                    <!-- Comments will be loaded here -->
                                    <div class="comments-list" style="margin-bottom:10px;"></div>
                                    
                                    <!-- Add Comment Form -->
                                    <div style="display:flex;gap:8px;">
                                        <input type="text" class="comment-input" placeholder="Write a comment..." 
                                               style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:20px;outline:none;font-size:14px;">
                                        <button class="submit-comment-btn" data-post-id="<?php echo $post['post_id']; ?>" 
                                                style="background:var(--primary);color:white;border:none;padding:8px 20px;border-radius:20px;cursor:pointer;font-weight:600;">
                                            Post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-item">No posts yet. Be the first to post!</div>
                    <?php endif; ?>
                    </div>
                    <div style="text-align:center;margin-top:12px">
                        <button id="loadMoreBtn" class="btn" data-offset="<?php echo count($recentPosts); ?>">Load more</button>
                    </div>
                </div>

            </div>

            <div>
                <div class="card">
                    <h3 style="margin:0 0 8px 0;font-size:1rem">Upcoming Events</h3>
                    <?php if (!empty($upcomingEvents)): ?>
                        <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="list-item">
                                <div style="font-weight:700"><?php echo htmlspecialchars($ev['title']); ?></div>
                                <div style="color:var(--muted);font-size:0.9rem"><?php echo date('M j, Y H:i', strtotime($ev['start_datetime'])); ?> · <?php echo htmlspecialchars($ev['location']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-item">No upcoming events.</div>
                    <?php endif; ?>
                    <div style="margin-top:10px;text-align:center"><a class="link" href="events.php">View all events</a></div>
                </div>

                <div class="card section">
                    <h3 style="margin:0 0 8px 0;font-size:1rem">Marketplace</h3>
                    <?php if (!empty($marketplace)): ?>
                        <?php foreach ($marketplace as $item): ?>
                            <div class="list-item">
                                <div style="font-weight:700"><?php echo htmlspecialchars($item['title']); ?> <span style="color:var(--muted);font-size:0.9rem">· $<?php echo number_format($item['price'],2); ?></span></div>
                                <div style="color:var(--muted);font-size:0.9rem">Listed <?php echo date('M j', strtotime($item['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-item">No items in the marketplace.</div>
                    <?php endif; ?>
                    <div style="margin-top:10px;text-align:center"><a class="link" href="marketplace.php">Visit marketplace</a></div>
                </div>

                <div class="card section">
                    <h3 style="margin:0 0 8px 0;font-size:1rem">Jobs</h3>
                    <?php if (!empty($jobs)): ?>
                        <?php foreach ($jobs as $job): ?>
                            <div class="list-item">
                                <div style="font-weight:700"><?php echo htmlspecialchars($job['title']); ?></div>
                                <div style="color:var(--muted);font-size:0.9rem"><?php echo htmlspecialchars($job['company']); ?> · <?php echo htmlspecialchars($job['location']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="list-item">No job listings yet.</div>
                    <?php endif; ?>
                    <div style="margin-top:10px;text-align:center"><a class="link" href="jobs.php">Browse jobs</a></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div id="createPostModal" style="display:none;position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:9999">
    <div style="background:#fff;padding:18px;border-radius:8px;max-width:720px;width:90%">
        <h3 style="margin:0 0 10px 0">New Post</h3>
        <textarea id="newPostContent" rows="6" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px" placeholder="Share something..."></textarea>
        <div style="margin-top:10px">
            <label for="postImageInput" style="display:inline-block;padding:8px 12px;background:#f0f0f0;border-radius:4px;cursor:pointer;font-size:0.9rem">
                <i class="fas fa-image"></i> Add Image
            </label>
            <input type="file" id="postImageInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display:none">
            <div id="imagePreviewContainer" style="margin-top:10px;display:none">
                <img id="imagePreview" style="max-width:100%;max-height:300px;border-radius:8px;border:1px solid #ddd">
                <button id="removeImageBtn" type="button" style="margin-top:5px;padding:5px 10px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:0.85rem">
                    <i class="fas fa-times"></i> Remove Image
                </button>
            </div>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end">
            <button id="closeCreatePost" class="btn secondary" type="button">Cancel</button>
            <button id="createPostBtn" class="btn" type="button">Post</button>
        </div>
    </div>
</div>

<script>
// Create post modal handlers
const openCreateBtn = document.getElementById('openCreatePost');
const createModal = document.getElementById('createPostModal');
const closeCreate = document.getElementById('closeCreatePost');
const createPostBtn = document.getElementById('createPostBtn');
const newPostContent = document.getElementById('newPostContent');

const postImageInput = document.getElementById('postImageInput');
const imagePreview = document.getElementById('imagePreview');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const removeImageBtn = document.getElementById('removeImageBtn');

openCreateBtn?.addEventListener('click', () => {
    createModal.style.display = 'flex';
    newPostContent.value = '';
    postImageInput.value = '';
    imagePreviewContainer.style.display = 'none';
    imagePreview.src = '';
    newPostContent.focus();
});

closeCreate?.addEventListener('click', () => {
    createModal.style.display = 'none';
});

// Handle image preview
postImageInput?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            imagePreviewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

removeImageBtn?.addEventListener('click', () => {
    postImageInput.value = '';
    imagePreview.src = '';
    imagePreviewContainer.style.display = 'none';
});

createPostBtn?.addEventListener('click', async () => {
    const content = newPostContent.value.trim();
    const imageFile = postImageInput.files[0];
    
    if (!content && !imageFile) {
        alert('Post must have content or an image');
        return;
    }
    
    createPostBtn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('content', content);
        if (imageFile) {
            fd.append('image', imageFile);
        }
        
        const res = await fetch('../actions/create_post_action.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.status === 'success') {
            // Prepend new post to feed
            const feed = document.getElementById('feedList');
            const node = document.createElement('div');
            node.className = 'list-item';
            node.setAttribute('data-post-id', data.post.post_id);
            
            let imageHtml = '';
            if (data.post.image_url) {
                imageHtml = `<img src="${escapeHtml(data.post.image_url)}" alt="Post image" style="max-width:100%;height:auto;border-radius:8px;margin-top:10px;cursor:pointer;" onclick="window.open(this.src, '_blank')">`;
            }
            
            node.innerHTML = `
                <div style="font-weight:700">
                    ${escapeHtml(data.post.user_name)} 
                    <span style="font-weight:400;color:var(--muted);font-size:0.85rem"> · ${data.post.created_at}</span>
                </div>
                <div style="margin-top:6px">${escapeHtml(data.post.content).replace(/\n/g,'<br>')}</div>
                ${imageHtml}
                <div class="post-actions" style="margin-top:12px;display:flex;gap:16px;align-items:center">
                    <button class="like-btn" style="background:none;border:none;cursor:pointer;font-size:1rem;color:#6c757d;display:flex;align-items:center;gap:4px">
                        <i class="far fa-heart"></i> <span class="like-count">❤️ 0</span>
                    </button>
                    <button class="comment-toggle-btn" style="background:none;border:none;cursor:pointer;font-size:1rem;color:#6c757d;display:flex;align-items:center;gap:4px">
                        <i class="far fa-comment"></i> <span class="comment-count">💬 0</span>
                    </button>
                </div>
                <div class="comments-section" style="display:none;margin-top:12px;padding-top:12px;border-top:1px solid #eee">
                    <div class="comments-list"></div>
                    <div style="margin-top:10px;display:flex;gap:8px">
                        <input type="text" class="comment-input" placeholder="Add a comment..." style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <button class="submit-comment-btn" style="padding:8px 16px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer">Post</button>
                    </div>
                </div>
            `;
            
            if (feed.firstChild) feed.insertBefore(node, feed.firstChild);
            else feed.appendChild(node);
            
            // increment offset used by load more
            const loadBtn = document.getElementById('loadMoreBtn');
            if (loadBtn) loadBtn.dataset.offset = parseInt(loadBtn.dataset.offset || '0') + 1;
            
            createModal.style.display = 'none';
        } else {
            alert(data.message || 'Failed to create post');
        }
    } catch (err) {
        console.error('Error creating post:', err);
        alert('An error occurred');
    }
    createPostBtn.disabled = false;
});

// Load more posts
document.getElementById('loadMoreBtn')?.addEventListener('click', async function() {
    const btn = this;
    let offset = parseInt(btn.dataset.offset || '0');
    const limit = 6;
    btn.disabled = true;
    try {
        const resp = await fetch(`../actions/load_posts_action.php?offset=${offset}&limit=${limit}`);
        const data = await resp.json();
        if (data.status === 'success' && Array.isArray(data.posts)) {
            const feed = document.getElementById('feedList');
            data.posts.forEach(p => {
                const node = document.createElement('div');
                node.className = 'list-item';
                node.setAttribute('data-post-id', p.post_id);
                node.innerHTML = `<div style="font-weight:700">${escapeHtml(p.user_name)} <span style="font-weight:400;color:var(--muted);font-size:0.85rem"> · ${p.created_at}</span></div><div style="margin-top:6px">${escapeHtml(p.content).replace(/\n/g,'<br>')}</div>`;
                feed.appendChild(node);
            });
            offset += data.posts.length;
            btn.dataset.offset = offset;
            if (data.posts.length < limit) {
                btn.style.display = 'none';
            }
        } else {
            btn.style.display = 'none';
        }
    } catch (e) {
        console.error(e);
    }
    btn.disabled = false;
});

// Profile photo upload
document.getElementById('uploadPhotoBtn')?.addEventListener('click', async function() {
    const input = document.getElementById('profilePhotoInput');
    if (!input || !input.files || !input.files[0]) { alert('Select a photo first'); return; }
    const file = input.files[0];
    const fd = new FormData();
    fd.append('profile_photo', file);
    this.disabled = true;
    try {
        const res = await fetch('../actions/upload_profile_photo.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success' && data.url) {
            const avatar = document.getElementById('profileAvatar');
            // replace avatar with image element
            const img = document.createElement('img');
            img.src = data.url;
            img.style.width = '72px'; img.style.height = '72px'; img.style.borderRadius = '50%'; img.style.objectFit = 'cover';
            avatar.parentNode.replaceChild(img, avatar);
            img.id = 'profileAvatar';
            alert('Profile photo updated');
        } else {
            alert(data.message || 'Upload failed');
        }
    } catch (err) {
        alert('An error occurred during upload');
    }
    this.disabled = false;
});

// small helper to escape HTML
function escapeHtml(text){
    if(!text) return '';
    return String(text).replace(/[&<>"'`]/g, function(match){
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#96;'}[match];
    });
}

// ==================== LIKES AND COMMENTS FUNCTIONALITY ====================

// Handle Like Button Clicks
document.addEventListener('click', async function(e) {
    if (e.target.closest('.like-btn')) {
        const btn = e.target.closest('.like-btn');
        const postId = btn.dataset.postId;
        const icon = btn.querySelector('i');
        const countSpan = btn.querySelector('.like-count');
        
        try {
            const response = await fetch('../actions/post_like_action.php?action=toggle', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Update like count
                countSpan.textContent = '❤️ ' + data.like_count;
                
                // Toggle heart icon
                if (data.action === 'liked') {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#e74c3c';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '#666';
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }
});

// Handle Comment Toggle Button
document.addEventListener('click', async function(e) {
    if (e.target.closest('.comment-toggle-btn')) {
        const btn = e.target.closest('.comment-toggle-btn');
        const postId = btn.dataset.postId;
        const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
        
        if (commentsSection.style.display === 'none') {
            // Show comments section and load comments
            commentsSection.style.display = 'block';
            await loadComments(postId);
        } else {
            // Hide comments section
            commentsSection.style.display = 'none';
        }
    }
});

// Handle Submit Comment Button
document.addEventListener('click', async function(e) {
    if (e.target.closest('.submit-comment-btn')) {
        const btn = e.target.closest('.submit-comment-btn');
        const postId = btn.dataset.postId;
        const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
        const input = commentsSection.querySelector('.comment-input');
        const comment = input.value.trim();
        
        if (!comment) {
            alert('Please enter a comment');
            return;
        }
        
        btn.disabled = true;
        
        try {
            const response = await fetch('../actions/post_comment_action.php?action=create', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Clear input
                input.value = '';
                
                // Reload comments
                await loadComments(postId);
                
                // Update comment count
                const countSpan = document.querySelector(`.comment-toggle-btn[data-post-id="${postId}"] .comment-count`);
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent.replace(/[^0-9]/g, '')) || 0;
                    countSpan.textContent = '💬 ' + (currentCount + 1);
                }
            } else {
                alert(data.message || 'Failed to add comment');
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            alert('An error occurred while adding comment');
        }
        
        btn.disabled = false;
    }
});

// Function to load comments for a post
async function loadComments(postId) {
    try {
        const response = await fetch(`../actions/post_comment_action.php?action=get&post_id=${postId}`);
        const data = await response.json();
        
        if (data.status === 'success') {
            const commentsList = document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`);
            
            if (data.comments.length === 0) {
                commentsList.innerHTML = '<div style="color:#999;font-size:14px;padding:10px 0;">No comments yet. Be the first to comment!</div>';
            } else {
                let html = '';
                data.comments.forEach(comment => {
                    html += `
                        <div class="comment-item" style="padding:10px 0;border-bottom:1px solid #f5f5f5;">
                            <div style="font-weight:600;font-size:14px;margin-bottom:4px;">
                                <i class="fas fa-user" style="color:#667eea;margin-right:4px;font-size:12px;"></i>
                                ${escapeHtml(comment.user_name)}
                                <span style="font-weight:400;color:#999;font-size:12px;margin-left:8px;">${comment.created_at}</span>
                            </div>
                            <div style="font-size:14px;color:#333;padding-left:20px;">${escapeHtml(comment.comment)}</div>
                        </div>
                    `;
                });
                commentsList.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    }
}
// ==================== GLOBAL SEARCH FUNCTIONALITY ====================

let searchTimeout;
const searchInput = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');

if (searchInput && searchResults) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        console.log('Search query:', query); // Debug
        
        if (query.length < 2) {
            searchResults.classList.remove('active');
            searchResults.innerHTML = '';
            return;
        }
        
        // Show loading
        searchResults.innerHTML = '<div class="search-empty"><i class="fas fa-spinner fa-spin"></i><p>Searching...</p></div>';
        searchResults.classList.add('active');
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
} else {
    console.error('Search elements not found:', {searchInput, searchResults});
}

async function performSearch(query) {
    console.log('Performing search for:', query); // Debug
    try {
        const response = await fetch(`../actions/global_search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        console.log('Search results:', data); // Debug
        displaySearchResults(data);
    } catch (error) {
        console.error('Search error:', error);
    }
}

function displaySearchResults(data) {
    const hasResults = Object.values(data).some(arr => arr.length > 0);
    
    if (!hasResults) {
        searchResults.innerHTML = `
            <div class="search-empty">
                <i class="fas fa-search"></i>
                <p>No results found</p>
            </div>
        `;
        searchResults.classList.add('active');
        return;
    }
    
    let html = '';
    
    // Users
    if (data.users.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-users"></i> People</div>';
        data.users.forEach(user => {
            const initials = user.name.split(' ').map(n => n[0]).join('');
            const photoHtml = user.photo 
                ? `<img src="${user.photo}" alt="${escapeHtml(user.name)}">` 
                : initials;
            html += `
                <a href="${user.url}" class="search-item">
                    <div class="search-item-icon">${photoHtml}</div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(user.name)}</div>
                        <div class="search-item-meta">${escapeHtml(user.email)}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    // Universities
    if (data.universities.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-university"></i> Universities</div>';
        data.universities.forEach(uni => {
            html += `
                <a href="${uni.url}" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-university"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(uni.name)}</div>
                        <div class="search-item-meta">${escapeHtml(uni.location)} • ${escapeHtml(uni.type)}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    // Products
    if (data.products.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-tag"></i> Marketplace</div>';
        data.products.forEach(product => {
            const imageHtml = product.image 
                ? `<img src="${product.image}" alt="${escapeHtml(product.title)}">` 
                : '<i class="fas fa-box"></i>';
            html += `
                <a href="${product.url}" class="search-item">
                    <div class="search-item-icon">${imageHtml}</div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(product.title)}</div>
                        <div class="search-item-meta">GH₵${product.price} • ${escapeHtml(product.category)}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    // Events
    if (data.events.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-calendar"></i> Events</div>';
        data.events.forEach(event => {
            const date = new Date(event.date);
            const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            html += `
                <a href="${event.url}" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(event.title)}</div>
                        <div class="search-item-meta">${dateStr} • ${escapeHtml(event.location)}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    // Jobs
    if (data.jobs.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-briefcase"></i> Jobs</div>';
        data.jobs.forEach(job => {
            html += `
                <a href="${job.url}" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(job.title)}</div>
                        <div class="search-item-meta">${escapeHtml(job.company)} • ${escapeHtml(job.location)}</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    // Groups
    if (data.groups.length > 0) {
        html += '<div class="search-category">';
        html += '<div class="search-category-title"><i class="fas fa-users"></i> Groups</div>';
        data.groups.forEach(group => {
            html += `
                <a href="${group.url}" class="search-item">
                    <div class="search-item-icon"><i class="fas fa-users"></i></div>
                    <div class="search-item-content">
                        <div class="search-item-title">${escapeHtml(group.name)}</div>
                        <div class="search-item-meta">${escapeHtml(group.privacy)} group</div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    searchResults.innerHTML = html;
    searchResults.classList.add('active');
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-container')) {
        searchResults.classList.remove('active');
    }
});

</script>
</body>
</html>

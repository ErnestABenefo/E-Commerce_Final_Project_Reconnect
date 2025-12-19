<?php
/**
 * ReConnect - Homepage (Community Feed)
 * Displays all posts from users and universities with sidebar widgets
 */

session_start();

// ============================================
// AUTHENTICATION CHECK
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

// ============================================
// DATABASE CONNECTION & HELPERS
// ============================================
$user_id = (int)$_SESSION['user_id'];
$db = new db_connection();
$conn = $db->db_conn();

/**
 * Fetch a single row from database
 */
function fetch_one($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    
    return $row;
}

/**
 * Fetch multiple rows from database
 */
function fetch_all($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    
    return $rows;
}

// ============================================
// FETCH DATA
// ============================================

// Current user information
$user = fetch_one($conn, 
    "SELECT first_name, last_name, profile_photo FROM Users WHERE user_id = ?", 
    "i", [$user_id]
);
$displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';

// Check if user is verified alumni
$is_verified = false;
$verification_check = fetch_one($conn, 
    "SELECT verification_id FROM AlumniVerification WHERE user_id = ? AND verification_status = 'approved' LIMIT 1", 
    "i", [$user_id]
);
if ($verification_check) {
    $is_verified = true;
}

// Get all posts (users + universities)
$allPosts = [];
$sql = "
    (SELECT 
        p.post_id, p.content, p.image_url, p.post_type, p.created_at, p.creator_type,
        u.user_id as creator_id, u.first_name, u.last_name, u.profile_photo,
        NULL as university_id, NULL as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked,
        (SELECT verification_id FROM AlumniVerification WHERE user_id = u.user_id AND verification_status = 'approved' LIMIT 1) as is_verified
    FROM Posts p 
    JOIN Users u ON p.user_id = u.user_id 
    WHERE p.creator_type = 'user')
    
    UNION ALL
    
    (SELECT 
        p.post_id, p.content, p.image_url, p.post_type, p.created_at, p.creator_type,
        NULL as creator_id, NULL as first_name, NULL as last_name, NULL as profile_photo,
        uni.university_id, uni.name as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked,
        NULL as is_verified
    FROM Posts p 
    JOIN University uni ON p.university_id = uni.university_id 
    WHERE p.creator_type = 'university')
    
    ORDER BY created_at DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['creator_name'] = $row['creator_type'] === 'user' 
        ? trim($row['first_name'] . ' ' . $row['last_name'])
        : $row['university_name'];
    $allPosts[] = $row;
}
$stmt->close();

// Upcoming events - only the closest one
$upcomingEvents = fetch_all($conn, "
    SELECT e.event_id, e.title, e.start_datetime, e.location,
           (SELECT COUNT(*) FROM EventAttendees WHERE event_id = e.event_id) as attendee_count
    FROM Events e 
    WHERE e.start_datetime >= NOW() 
    ORDER BY e.start_datetime ASC 
    LIMIT 1
");

// Marketplace items - any item
$marketplaceItems = fetch_all($conn, "
    SELECT m.item_id, m.title, m.price, u.first_name, u.last_name
    FROM MarketplaceItems m
    LEFT JOIN Users u ON m.seller_id = u.user_id
    LIMIT 1
");

// Job listings - any job
$jobListings = fetch_all($conn, "
    SELECT job_id, title, company, location, job_type
    FROM JobListings 
    LIMIT 1
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - ReConnect</title>
    
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --danger: #dc3545;
            --success: #28a745;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --border: #e0e0e0;
            --shadow: 0 2px 10px rgba(0,0,0,0.08);
            --shadow-hover: 0 4px 20px rgba(0,0,0,0.12);
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
            color: var(--dark);
        }
        
        /* Navigation hover effects */
        nav ul li a:hover {
            color: #667eea !important;
        }
        
        nav button:hover {
            background: #c82333 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }
        
        nav button {
            transition: all 0.3s ease;
        }
        
        /* Search input focus */
        #globalSearch:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Main Content */
        #content-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px 50px;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            align-items: start;
        }
        
        .feed-section {
            min-width: 0;
        }
        
        .sidebar {
            position: sticky;
            top: 90px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: white;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .card-title i {
            color: var(--primary);
            font-size: 1.3rem;
        }
        
        .card-body {
            padding: 20px 24px 24px;
        }
        
        .sidebar .card-body {
            padding: 24px;
        }
        
        #postsContainer {
            display: flex;
            flex-direction: column;
        }
        
        #postsContainer > .user-post-data:first-child {
            padding-top: 0;
        }
        
        /* Post Styling */
        .user-post-data {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s ease;
        }
        
        .user-post-data:last-child {
            border-bottom: none;
        }
        
        .user-post-data:hover {
            background: #fafbfc;
        }
        
        /* Post Header Layout */
        .post-header {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .post-avatar {
            flex-shrink: 0;
        }
        
        .post-info {
            flex: 1;
            min-width: 0;
        }
        
        .post-author {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .author-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .post-time {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 0;
        }
        
        /* Avatar Circles */
        .avatar-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .avatar-initials {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .avatar-university {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
            color: white;
            font-size: 1.3rem;
        }
        
        /* Post Content */
        .user-post-data > .mt-3 {
            margin-top: 15px;
        }
        
        .user-post-data p {
            color: #495057;
            line-height: 1.6;
            margin: 0;
        }
        
        .user-post img {
            border-radius: 8px;
            margin-top: 15px;
            transition: transform 0.3s ease;
            max-width: 100%;
        }
        
        .user-post img:hover {
            transform: scale(1.02);
        }
        
        /* Verified Badges */
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
            vertical-align: middle;
        }
        
        .uni-badge {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
        }
        
        /* Comment Area */
        .comment-area {
            margin-top: 15px;
            padding-top: 15px;
        }
        
        .comment-area hr {
            border: none;
            border-top: 1px solid var(--border);
            margin: 15px 0;
        }
        
        .like-btn, .comment-toggle-btn {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .like-btn:hover, .comment-toggle-btn:hover {
            color: var(--primary);
        }
        
        .share-block {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .share-block > div {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .share-block > div:hover {
            background: var(--light);
        }
        
        .share-block h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .share-block > div:hover h6 {
            color: var(--primary);
        }
        
        /* Sidebar Widgets */
        .sidebar .card {
            margin-bottom: 0;
        }
        
        .sidebar-item {
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .sidebar-item:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            transform: translateX(3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.12);
            border-left-color: var(--primary);
        }
        
        .sidebar-item-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 12px;
            font-size: 1rem;
            line-height: 1.4;
        }
        
        .sidebar-item-meta {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 2;
        }
        
        .sidebar-item-meta i {
            color: var(--primary);
            width: 20px;
            text-align: center;
            display: inline-block;
        }
        
        .sidebar-item-meta br + i {
            margin-top: 2px;
        }
        
        /* Comments Section */
        .comments-list {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }
        
        .comment-item {
            padding: 12px;
            background: var(--light);
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .comment-text {
            margin-top: 15px;
        }
        
        .comment-text input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border);
            border-radius: 25px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .comment-text input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.875rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .empty-state p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        /* Load More Button */
        .load-more-wrapper {
            text-align: center;
            padding: 20px 0;
        }
        
        .load-more-btn {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .load-more-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        /* Utility Classes */
        .d-flex {
            display: flex;
        }
        
        .justify-content-between {
            justify-content: space-between;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        .w-100 {
            width: 100%;
        }
        
        .me-2 {
            margin-right: 0.5rem;
        }
        
        .me-3 {
            margin-right: 1rem;
        }
        
        .mb-0 {
            margin-bottom: 0;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .ms-1 {
            margin-left: 0.25rem;
        }
        
        .ms-2 {
            margin-left: 0.5rem;
        }
        
        .img-fluid {
            max-width: 100%;
            height: auto;
        }
        
        .rounded {
            border-radius: 8px;
        }
        
        .position-relative {
            position: relative;
        }
        
        .d-inline-block {
            display: inline-block;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            
            nav {
                padding: 10px 0 !important;
            }
            
            nav > div {
                flex-wrap: wrap;
                padding: 0 15px !important;
            }
            
            nav ul {
                order: 3;
                width: 100%;
                justify-content: center;
                gap: 15px !important;
                margin-top: 10px;
                flex-wrap: wrap;
            }
            
            #content-page {
                padding: 20px 15px;
            }
            
            .sidebar {
                grid-template-columns: 1fr;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .user-post-data {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div>
        <!-- Top Navigation Bar -->
        <nav style="background:#fff;padding:15px 0;box-shadow:0 2px 8px rgba(0,0,0,0.1);position:sticky;top:0;z-index:1000;">
            <div style="max-width:1400px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;gap:20px;">
                <a href="homepage.php" style="font-size:1.5rem;font-weight:700;color:#667eea;text-decoration:none;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-link"></i>
                    ReConnect
                </a>
                
                <!-- Search Bar -->
                <div style="flex: 1; max-width: 500px; position: relative;">
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; color: #666; z-index: 1;"></i>
                        <input type="text" id="globalSearch" placeholder="Search users, products, events, jobs..." autocomplete="off" 
                               style="width: 100%; padding: 10px 15px 10px 45px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 0.95rem; outline: none; transition: all 0.3s;">
                    </div>
                    <div id="searchResults" style="display: none; position: absolute; top: calc(100% + 10px); left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-height: 400px; overflow-y: auto; z-index: 1000;"></div>
                </div>
                
                <!-- Navigation Menu -->
                <ul style="display:flex;gap:25px;list-style:none;margin:0;padding:0;align-items:center;">
                    <li><a href="homepage.php" style="text-decoration:none;color:#667eea;font-weight:600;"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="connections.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-user-friends"></i> Connections</a></li>
                    <li><a href="groups.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-users"></i> Groups</a></li>
                    <li><a href="events.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-calendar"></i> Events</a></li>
                    <li><a href="marketplace.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-store"></i> Marketplace</a></li>
                    <li><a href="jobs.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-briefcase"></i> Jobs</a></li>
                    <li><a href="dashboard.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-user"></i> Profile</a></li>
                    <li>
                        <form method="post" action="../actions/logout_user_action.php" style="margin:0">
                            <button type="submit" style="background:#dc3545;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- ============================================
             MAIN CONTENT AREA
             ============================================ -->
        <div id="content-page">
            <div class="content-wrapper">
                <!-- ============================================
                     LEFT: FEED SECTION
                     ============================================ -->
                <div class="feed-section">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-rss"></i>Community Feed
                            </h4>
                        </div>
                        
                        <div class="card-body">
                            <div id="postsContainer">
                                <?php if (!empty($allPosts)): ?>
                                    <?php foreach ($allPosts as $post): ?>
                                        <?php include 'includes/post_item.php'; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-comments"></i>
                                        <p>No posts yet. Be the first to share something!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Load More Button -->
                            <?php if (count($allPosts) >= 10): ?>
                                <div class="load-more-wrapper">
                                    <button id="loadMoreBtn" class="load-more-btn" data-offset="<?php echo count($allPosts); ?>">
                                        <i class="fas fa-sync-alt"></i> Load More Posts
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- ============================================
                     RIGHT: SIDEBAR SECTION
                     ============================================ -->
                <div class="sidebar">
                    <!-- Upcoming Events Widget -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-calendar-alt"></i>Upcoming Events
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($upcomingEvents)): ?>
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <div class="sidebar-item" onclick="window.location.href='events.php'">
                                        <div class="sidebar-item-title">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </div>
                                        <div class="sidebar-item-meta">
                                            <i class="far fa-calendar"></i> 
                                            <?php echo date('M j, Y', strtotime($event['start_datetime'])); ?>
                                            <br>
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($event['location']); ?>
                                            <?php if ($event['attendee_count'] > 0): ?>
                                                <br>
                                                <i class="fas fa-users"></i> 
                                                <?php echo $event['attendee_count']; ?> attending
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar"></i>
                                    <p>No upcoming events</p>
                                </div>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="events.php" class="btn btn-primary btn-sm">
                                    View All Events <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Job Opportunities Widget -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-briefcase"></i>Job Opportunities
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($jobListings)): ?>
                                <?php foreach ($jobListings as $job): ?>
                                    <div class="sidebar-item" onclick="window.location.href='jobs.php'">
                                        <div class="sidebar-item-title">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </div>
                                        <div class="sidebar-item-meta">
                                            <i class="fas fa-building"></i> 
                                            <?php echo htmlspecialchars($job['company']); ?>
                                            <br>
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($job['location']); ?>
                                            <br>
                                            <i class="fas fa-briefcase"></i> 
                                            <?php echo htmlspecialchars($job['job_type']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-briefcase"></i>
                                    <p>No job postings</p>
                                </div>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="jobs.php" class="btn btn-primary btn-sm">
                                    View All Jobs <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Marketplace Widget -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-store"></i>Marketplace
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($marketplaceItems)): ?>
                                <?php foreach ($marketplaceItems as $item): ?>
                                    <div class="sidebar-item" onclick="window.location.href='marketplace.php'">
                                        <div class="sidebar-item-title">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </div>
                                        <div class="sidebar-item-meta">
                                            <i class="fas fa-tag"></i> 
                                            GH₵<?php echo number_format($item['price'], 2); ?>
                                            <br>
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <p>No items available</p>
                                </div>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="marketplace.php" class="btn btn-primary btn-sm">
                                    Visit Marketplace <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script src="js/libs.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/homepage.js"></script>
    
    <!-- Global Search Functionality -->
    <script>
    (function() {
        let searchTimeout;
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('searchResults');

        if (searchInput && searchResults) {
            // Focus styling
            searchInput.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
                this.style.boxShadow = '0 2px 8px rgba(102, 126, 234, 0.2)';
            });
            
            searchInput.addEventListener('blur', function() {
                this.style.borderColor = '#e0e0e0';
                this.style.boxShadow = 'none';
                // Delay hiding results to allow clicks
                setTimeout(() => {
                    if (!searchResults.matches(':hover')) {
                        searchResults.style.display = 'none';
                    }
                }, 200);
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    searchResults.innerHTML = '';
                    return;
                }
                
                // Show loading
                searchResults.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#666;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:10px;"></i><p>Searching...</p></div>';
                searchResults.style.display = 'block';
                
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });
        }

        async function performSearch(query) {
            try {
                const response = await fetch(`../actions/global_search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                displaySearchResults(data);
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#666;"><i class="fas fa-exclamation-circle" style="font-size:2rem;margin-bottom:10px;"></i><p>Search failed</p></div>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function displaySearchResults(data) {
            const hasResults = Object.values(data).some(arr => arr && arr.length > 0);
            
            if (!hasResults) {
                searchResults.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#666;"><i class="fas fa-search" style="font-size:2rem;margin-bottom:10px;opacity:0.3;"></i><p>No results found</p></div>';
                searchResults.style.display = 'block';
                return;
            }
            
            let html = '';
            
            // Users
            if (data.users && data.users.length > 0) {
                html += '<div style="padding:15px 20px;">';
                html += '<div style="font-weight:700;font-size:0.85rem;color:#666;text-transform:uppercase;margin-bottom:10px;"><i class="fas fa-users"></i> People</div>';
                data.users.forEach(user => {
                    const initials = user.name.split(' ').map(n => n[0]).join('');
                    const photoHtml = user.photo 
                        ? `<img src="${user.photo}" alt="${escapeHtml(user.name)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` 
                        : initials;
                    const verifiedBadge = user.is_verified 
                        ? '<span style="display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:2px 8px;border-radius:10px;font-size:0.7rem;font-weight:600;margin-left:6px;"><i class="fas fa-check-circle"></i> Verified</span>' 
                        : '';
                    html += `
                        <a href="${user.url}" style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:8px;cursor:pointer;transition:background 0.2s;text-decoration:none;color:inherit;" 
                           onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                            <div style="width:45px;height:45px;border-radius:50%;background:#667eea;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;">${photoHtml}</div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:600;font-size:0.95rem;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(user.name)}${verifiedBadge}</div>
                                <div style="font-size:0.85rem;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(user.email)}</div>
                            </div>
                        </a>
                    `;
                });
                html += '</div>';
            }
            
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
        }
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    })();
    </script>
</body>
</html>

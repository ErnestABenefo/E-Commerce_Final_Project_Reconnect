<?php
// Homepage - Public feed showing all posts from all users
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();
$conn = $db->db_conn();

// Helper to safely fetch one row
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

// Helper to fetch all rows
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

// Get current user info
$user = fetch_one($conn, "SELECT first_name, last_name, profile_photo FROM Users WHERE user_id = ?", "i", [$user_id]);
$displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';

// Check if user is verified alumni
$is_verified = false;
$verification_check = fetch_one($conn, "SELECT verification_id FROM AlumniVerification WHERE user_id = ? AND verification_status = 'approved' LIMIT 1", "i", [$user_id]);
if ($verification_check) {
    $is_verified = true;
}

// Get all posts from all users and universities (limit 10 initially)
$allPosts = [];
$sql = "
    (SELECT 
        p.post_id, 
        p.content, 
        p.image_url, 
        p.post_type, 
        p.created_at, 
        p.creator_type,
        u.user_id as creator_id,
        u.first_name, 
        u.last_name,
        u.profile_photo,
        NULL as university_id,
        NULL as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked,
        (SELECT verification_id FROM AlumniVerification WHERE user_id = u.user_id AND verification_status = 'approved' LIMIT 1) as is_verified
    FROM Posts p 
    JOIN Users u ON p.user_id = u.user_id 
    WHERE p.creator_type = 'user')
    
    UNION ALL
    
    (SELECT 
        p.post_id, 
        p.content, 
        p.image_url, 
        p.post_type, 
        p.created_at, 
        p.creator_type,
        NULL as creator_id,
        NULL as first_name,
        NULL as last_name,
        NULL as profile_photo,
        uni.university_id,
        uni.name as university_name,
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
    if ($row['creator_type'] === 'user') {
        $row['creator_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    } else {
        $row['creator_name'] = $row['university_name'];
    }
    $allPosts[] = $row;
}
$stmt->close();

// Get upcoming events (limit 5)
$upcomingEvents = fetch_all($conn, "
    SELECT e.event_id, e.title, e.description, e.start_datetime, e.location, e.event_image,
           u.first_name, u.last_name,
           (SELECT COUNT(*) FROM EventAttendees WHERE event_id = e.event_id) as attendee_count
    FROM Events e 
    LEFT JOIN Users u ON e.host_user_id = u.user_id
    WHERE e.start_datetime >= NOW() 
    ORDER BY e.start_datetime ASC 
    LIMIT 5
");

// Get latest marketplace items (limit 5)
$marketplaceItems = fetch_all($conn, "
    SELECT m.item_id, m.title, m.description, m.price, m.image_url, m.created_at,
           u.first_name, u.last_name
    FROM MarketplaceItems m
    LEFT JOIN Users u ON m.user_id = u.user_id
    WHERE m.status = 'available' 
    ORDER BY m.created_at DESC 
    LIMIT 5
");

// Get latest job listings (limit 5)
$jobListings = fetch_all($conn, "
    SELECT job_id, title, company, location, job_type, salary_range, created_at 
    FROM JobListings 
    WHERE status = 'open'
    ORDER BY created_at DESC 
    LIMIT 5
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home - ReConnect</title>
    <link rel="stylesheet" href="css/libs.min.css">
    <link rel="stylesheet" href="css/socialv.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        /* Custom overrides for ReConnect branding */
        :root {
            --bs-primary: #667eea;
            --bs-primary-rgb: 102, 126, 234;
        }

        body {
            background: #f8f9fa;
            overflow-x: hidden;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8e 100%);
        }

        /* Header Navigation Styling */
        .iq-top-navbar {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            padding: 12px 0;
        }

        .iq-navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-pills .nav-link {
            border-radius: 10px;
            padding: 10px 18px;
            color: #6c757d;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 4px;
        }

        .nav-pills .nav-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        /* Main Content Area */
        #content-page {
            padding-top: 90px;
            min-height: 100vh;
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
        }

        .card-title {
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
            font-size: 1.2rem;
        }

        .card-body {
            padding: 20px;
        }

        /* Post Styles */
        .user-post-data {
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 15px;
        }

        .user-post-data .rounded-circle {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border: 2px solid #667eea;
        }

        .user-post-data h5 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .user-post-data .text-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 6px;
            vertical-align: middle;
        }

        .uni-badge {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
        }

        .user-post img {
            border-radius: 12px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .user-post img:hover {
            transform: scale(1.02);
        }

        /* Comment Area */
        .comment-area {
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .like-block {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .like-data img {
            width: 24px;
            height: 24px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .like-data img:hover {
            transform: scale(1.2);
        }

        .total-like-block,
        .total-comment-block {
            color: #6c757d;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.3s;
        }

        .total-like-block:hover,
        .total-comment-block:hover {
            color: #667eea;
        }

        /* Comment Actions */
        .share-block {
            display: flex;
            gap: 25px;
            padding: 15px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
            margin: 15px 0;
        }

        .share-block .d-flex {
            cursor: pointer;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .share-block .d-flex:hover {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            transform: translateY(-2px);
        }

        .share-block h6 {
            margin: 0;
            font-weight: 600;
            color: #6c757d;
            transition: color 0.3s;
        }

        .share-block .d-flex:hover h6 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Comment Form */
        .comment-text .form-control {
            border-radius: 25px;
            border: 1px solid #e0e0e0;
            padding: 10px 20px;
            transition: all 0.3s;
        }

        .comment-text .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        /* Comments Display */
        .comment-item {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-author {
            font-weight: 600;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .comment-time {
            color: #999;
            font-size: 0.8rem;
            font-weight: 400;
            margin-left: 8px;
        }

        .comment-text-display {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-left: 22px;
        }

        /* Sidebar Cards */
        .sidebar-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .sidebar-card h4 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-item {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #f0f0f0;
        }

        .sidebar-item:hover {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            border-color: #667eea;
            transform: translateX(5px);
        }

        .sidebar-item-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .sidebar-item-meta {
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Load More Button */
        .load-more-wrapper {
            text-align: center;
            padding: 20px 0;
        }

        .load-more-btn {
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            padding: 12px 40px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .load-more-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            #content-page {
                padding-top: 80px;
            }

            .nav-pills {
                flex-wrap: wrap;
                gap: 8px;
            }

            .nav-pills .nav-link {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
        }

        @media (max-width: 768px) {
            .iq-navbar-custom {
                font-size: 1.1rem;
                padding: 8px 16px;
            }

            .card-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Wrapper Start -->
    <div class="wrapper">
        <!-- Top Header -->
        <div class="iq-top-navbar">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-between">
                    <div class="col-sm-3">
                        <div class="iq-navbar-custom">
                            <i class="fas fa-link"></i> ReConnect
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <ul class="nav nav-pills justify-content-center">
                            <li class="nav-item">
                                <a class="nav-link active" href="homepage.php">
                                    <i class="fas fa-home"></i> Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="connections.php">
                                    <i class="fas fa-users"></i> Connections
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="groups.php">
                                    <i class="fas fa-users-cog"></i> Groups
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="events.php">
                                    <i class="fas fa-calendar"></i> Events
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="marketplace.php">
                                    <i class="fas fa-store"></i> Marketplace
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="jobs.php">
                                    <i class="fas fa-briefcase"></i> Jobs
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-3 text-end">
                        <form method="post" action="../actions/logout_user_action.php" style="display:inline;">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div id="content-page" class="content-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 row m-0 p-0">
                        <div class="col-sm-12">
                            <div class="card card-block card-stretch card-height">
                                <div class="card-header d-flex justify-content-between">
                                    <div class="header-title">
                                        <h4 class="card-title"><i class="fas fa-rss me-2"></i>Community Feed</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="postsContainer">
                    <?php if (!empty($allPosts)): ?>
                        <?php foreach ($allPosts as $post): ?>
                            <div class="user-post-data" data-post-id="<?php echo $post['post_id']; ?>">
                                <div class="d-flex justify-content-between">
                                    <div class="me-3">
                                        <?php if ($post['creator_type'] === 'user' && !empty($post['profile_photo'])): ?>
                                            <img class="rounded-circle img-fluid" src="<?php echo htmlspecialchars($post['profile_photo']); ?>" alt="Profile" style="width:48px;height:48px;object-fit:cover;">
                                        <?php elseif ($post['creator_type'] === 'user'): ?>
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-weight:700;">
                                                <?php
                                                $initials = '';
                                                if (!empty($post['first_name'])) $initials .= strtoupper($post['first_name'][0]);
                                                if (!empty($post['last_name'])) $initials .= strtoupper($post['last_name'][0]);
                                                echo $initials;
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:linear-gradient(135deg,#ff9800 0%,#ff5722 100%);color:white;font-size:1.2rem;">
                                                <i class="fas fa-university"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-0 d-inline-block">
                                                    <?php echo htmlspecialchars($post['creator_name']); ?>
                                                    <?php if ($post['creator_type'] === 'university'): ?>
                                                        <span class="verified-badge uni-badge">
                                                            <i class="fas fa-check-circle"></i> University
                                                        </span>
                                                    <?php elseif ($post['is_verified']): ?>
                                                        <span class="verified-badge">
                                                            <i class="fas fa-check-circle"></i> Verified Alumni
                                                        </span>
                                                    <?php endif; ?>
                                                </h5>
                                                <p class="mb-0 text-primary">
                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Post Content -->
                                <?php if (!empty($post['content'])): ?>
                                    <div class="mt-3">
                                        <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Post Image -->
                                <?php if (!empty($post['image_url'])): ?>
                                    <div class="user-post mt-3">
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                                             alt="Post image" 
                                             class="img-fluid rounded w-100"
                                             onclick="window.open(this.src, '_blank')"
                                             style="cursor:pointer;">
                                    </div>
                                <?php endif; ?>

                                <!-- Post Actions -->
                                <div class="comment-area mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="like-block position-relative d-flex align-items-center">
                                            <div class="total-like-block ms-2 me-3">
                                                <span class="like-btn" data-post-id="<?php echo $post['post_id']; ?>" style="cursor:pointer;">
                                                    <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart" style="color:<?php echo $post['user_liked'] ? '#e74c3c' : '#6c757d'; ?>;"></i>
                                                    <span class="like-count"><?php echo $post['like_count']; ?></span> Likes
                                                </span>
                                            </div>
                                        </div>
                                        <div class="total-comment-block">
                                            <span class="comment-toggle-btn" data-post-id="<?php echo $post['post_id']; ?>" style="cursor:pointer;">
                                                <i class="far fa-comment"></i>
                                                <span class="comment-count"><?php echo $post['comment_count']; ?></span> Comments
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="share-block d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center feather-icon like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                            <i class="<?php echo $post['user_liked'] ? 'fas' : 'far'; ?> fa-heart me-2" style="font-size:1.2rem;color:<?php echo $post['user_liked'] ? '#e74c3c' : '#6c757d'; ?>;"></i>
                                            <h6 class="mb-0"><?php echo $post['user_liked'] ? 'Liked' : 'Like'; ?></h6>
                                        </div>
                                        <div class="d-flex align-items-center feather-icon comment-toggle-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                            <i class="far fa-comment me-2" style="font-size:1.2rem;"></i>
                                            <h6 class="mb-0">Comment</h6>
                                        </div>
                                        <div class="d-flex align-items-center feather-icon">
                                            <i class="fas fa-share me-2" style="font-size:1.2rem;"></i>
                                            <h6 class="mb-0">Share</h6>
                                        </div>
                                    </div>
                                    
                                    <!-- Comments Section -->
                                    <div class="comments-section mt-3" data-post-id="<?php echo $post['post_id']; ?>" style="display:none;">
                                        <div class="comments-list mb-3"></div>
                                        <form class="comment-text d-flex align-items-center mt-3">
                                            <input type="text" class="form-control rounded comment-input" placeholder="Write a comment..." data-post-id="<?php echo $post['post_id']; ?>">
                                            <button type="button" class="btn btn-primary ms-2 submit-comment-btn" data-post-id="<?php echo $post['post_id']; ?>">Post</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php if ($post !== end($allPosts)): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No posts yet. Be the first to share something!</p>
                        </div>
                    <?php endif; ?>
                                    </div>
                                    
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
                        
                        <!-- Sidebar Section -->
                        <div class="col-lg-4">
                            <!-- Upcoming Events -->
                            <div class="card card-block card-stretch card-height">
                                <div class="card-header d-flex justify-content-between">
                                    <div class="header-title">
                                        <h4 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Upcoming Events</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                <?php if (!empty($upcomingEvents)): ?>
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="sidebar-item" onclick="window.location.href='events.php'" style="cursor:pointer;">
                            <div class="sidebar-item-title">
                                <?php echo htmlspecialchars($event['title']); ?>
                            </div>
                            <div class="sidebar-item-meta">
                                <i class="far fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($event['start_datetime'])); ?>
                                <br>
                                <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($event['location']); ?>
                                <?php if ($event['attendee_count'] > 0): ?>
                                    <br>
                                    <i class="fas fa-users me-1"></i> <?php echo $event['attendee_count']; ?> attending
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No upcoming events</p>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="events.php" class="btn btn-primary btn-sm">View All Events <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                                </div>
                            </div>

                            <!-- Latest Jobs -->
                            <div class="card card-block card-stretch card-height">
                                <div class="card-header d-flex justify-content-between">
                                    <div class="header-title">
                                        <h4 class="card-title"><i class="fas fa-briefcase me-2"></i>Job Opportunities</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                <?php if (!empty($jobListings)): ?>
                    <?php foreach ($jobListings as $job): ?>
                        <div class="sidebar-item" onclick="window.location.href='jobs.php'" style="cursor:pointer;">
                            <div class="sidebar-item-title">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </div>
                            <div class="sidebar-item-meta">
                                <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($job['company']); ?>
                                <br>
                                <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($job['location']); ?>
                                <?php if (!empty($job['salary_range'])): ?>
                                    <br>
                                    <i class="fas fa-money-bill-wave me-1"></i> <?php echo htmlspecialchars($job['salary_range']); ?>
                                <?php endif; ?>
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
                    <a href="jobs.php" class="btn btn-primary btn-sm">View All Jobs <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                                </div>
                            </div>

                            <!-- Marketplace -->
                            <div class="card card-block card-stretch card-height">
                                <div class="card-header d-flex justify-content-between">
                                    <div class="header-title">
                                        <h4 class="card-title"><i class="fas fa-store me-2"></i>Marketplace</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                <?php if (!empty($marketplaceItems)): ?>
                    <?php foreach ($marketplaceItems as $item): ?>
                        <div class="sidebar-item" onclick="window.location.href='marketplace.php'" style="cursor:pointer;">
                            <div class="sidebar-item-title">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                            <div class="sidebar-item-meta">
                                <i class="fas fa-tag me-1"></i> GH₵<?php echo number_format($item['price'], 2); ?>
                                <br>
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
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
                    <a href="marketplace.php" class="btn btn-primary btn-sm">Visit Marketplace <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Wrapper End -->

    <script>
        // Utility function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // ==================== LIKE FUNCTIONALITY ====================
        document.addEventListener('click', async function(e) {
            const likeBtn = e.target.closest('.like-btn');
            if (!likeBtn) return;

            const postId = likeBtn.dataset.postId;
            const icon = likeBtn.querySelector('i');
            const countSpan = likeBtn.querySelector('.like-count');

            try {
                const response = await fetch('../actions/post_like_action.php?action=toggle', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `post_id=${postId}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    countSpan.textContent = data.like_count;

                    if (data.action === 'liked') {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        likeBtn.classList.add('liked');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        likeBtn.classList.remove('liked');
                    }
                }
            } catch (error) {
                console.error('Error toggling like:', error);
            }
        });

        // ==================== COMMENT FUNCTIONALITY ====================
        // Toggle comments section
        document.addEventListener('click', async function(e) {
            const toggleBtn = e.target.closest('.comment-toggle-btn');
            if (!toggleBtn) return;

            const postId = toggleBtn.dataset.postId;
            const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);

            if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
                commentsSection.style.display = 'block';
                await loadComments(postId);
            } else {
                commentsSection.style.display = 'none';
            }
        });

        // Submit comment
        document.addEventListener('click', async function(e) {
            const submitBtn = e.target.closest('.submit-comment-btn');
            if (!submitBtn) return;

            const postId = submitBtn.dataset.postId;
            const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
            const input = commentsSection.querySelector('.comment-input');
            const comment = input.value.trim();

            if (!comment) {
                alert('Please enter a comment');
                return;
            }

            submitBtn.disabled = true;

            try {
                const response = await fetch('../actions/post_comment_action.php?action=create', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    input.value = '';
                    await loadComments(postId);

                    // Update comment count
                    const countSpan = document.querySelector(`.comment-toggle-btn[data-post-id="${postId}"] .comment-count`);
                    if (countSpan) {
                        const currentCount = parseInt(countSpan.textContent) || 0;
                        countSpan.textContent = currentCount + 1;
                    }
                } else {
                    alert(data.message || 'Failed to add comment');
                }
            } catch (error) {
                console.error('Error adding comment:', error);
                alert('An error occurred while adding comment');
            }

            submitBtn.disabled = false;
        });

        // Load comments for a post
        async function loadComments(postId) {
            try {
                const response = await fetch(`../actions/post_comment_action.php?action=get&post_id=${postId}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const commentsList = document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`);

                    if (data.comments.length === 0) {
                        commentsList.innerHTML = '<div style="color:#999;font-size:14px;padding:10px 0;text-align:center;">No comments yet. Be the first to comment!</div>';
                    } else {
                        let html = '';
                        data.comments.forEach(comment => {
                            html += `
                                <div class="comment-item">
                                    <div class="comment-author">
                                        <i class="fas fa-user me-1" style="color:#667eea;font-size:12px;"></i>
                                        ${escapeHtml(comment.user_name)}
                                        <span class="comment-time">${comment.created_at}</span>
                                    </div>
                                    <div class="comment-text-display">${escapeHtml(comment.comment)}</div>
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

        // ==================== LOAD MORE POSTS ====================
        document.getElementById('loadMoreBtn')?.addEventListener('click', async function() {
            const btn = this;
            let offset = parseInt(btn.dataset.offset || '0');
            const limit = 10;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            try {
                const response = await fetch(`../actions/load_all_posts_action.php?offset=${offset}&limit=${limit}`);
                const data = await response.json();

                if (data.status === 'success' && data.posts.length > 0) {
                    const container = document.getElementById('postsContainer');

                    data.posts.forEach(post => {
                        const postDiv = document.createElement('div');
                        postDiv.className = 'user-post-data';
                        postDiv.dataset.postId = post.post_id;

                        let avatarContent = '';
                        if (post.creator_type === 'user') {
                            if (post.profile_photo) {
                                avatarContent = `<img class="rounded-circle img-fluid" src="${escapeHtml(post.profile_photo)}" alt="Profile" style="width:48px;height:48px;object-fit:cover;">`;
                            } else {
                                const initials = (post.first_name ? post.first_name[0] : '') + (post.last_name ? post.last_name[0] : '');
                                avatarContent = `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-weight:700;">${initials.toUpperCase()}</div>`;
                            }
                        } else {
                            avatarContent = `<div class="rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:linear-gradient(135deg,#ff9800 0%,#ff5722 100%);color:white;font-size:1.2rem;"><i class="fas fa-university"></i></div>`;
                        }

                        let verifiedBadge = '';
                        if (post.creator_type === 'university') {
                            verifiedBadge = '<span class="verified-badge uni-badge"><i class="fas fa-check-circle"></i> University</span>';
                        } else if (post.is_verified) {
                            verifiedBadge = '<span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Alumni</span>';
                        }

                        let imageHtml = '';
                        if (post.image_url) {
                            imageHtml = `<div class="user-post mt-3"><img src="${escapeHtml(post.image_url)}" alt="Post image" class="img-fluid rounded w-100" onclick="window.open(this.src, '_blank')" style="cursor:pointer;"></div>`;
                        }

                        const creatorName = post.creator_type === 'user' 
                            ? `${post.first_name} ${post.last_name}` 
                            : post.university_name;

                        postDiv.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div class="me-3">${avatarContent}</div>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0 d-inline-block">
                                                ${escapeHtml(creatorName)}
                                                ${verifiedBadge}
                                            </h5>
                                            <p class="mb-0 text-primary">${post.created_at}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ${post.content ? `<div class="mt-3"><p>${escapeHtml(post.content).replace(/\n/g, '<br>')}</p></div>` : ''}
                            ${imageHtml}
                            <div class="comment-area mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="like-block position-relative d-flex align-items-center">
                                        <div class="total-like-block ms-2 me-3">
                                            <span class="like-btn" data-post-id="${post.post_id}" style="cursor:pointer;">
                                                <i class="${post.user_liked ? 'fas' : 'far'} fa-heart" style="color:${post.user_liked ? '#e74c3c' : '#6c757d'};"></i>
                                                <span class="like-count">${post.like_count}</span> Likes
                                            </span>
                                        </div>
                                    </div>
                                    <div class="total-comment-block">
                                        <span class="comment-toggle-btn" data-post-id="${post.post_id}" style="cursor:pointer;">
                                            <i class="far fa-comment"></i>
                                            <span class="comment-count">${post.comment_count}</span> Comments
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <div class="share-block d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center feather-icon like-btn" data-post-id="${post.post_id}">
                                        <i class="${post.user_liked ? 'fas' : 'far'} fa-heart me-2" style="font-size:1.2rem;color:${post.user_liked ? '#e74c3c' : '#6c757d'};"></i>
                                        <h6 class="mb-0">${post.user_liked ? 'Liked' : 'Like'}</h6>
                                    </div>
                                    <div class="d-flex align-items-center feather-icon comment-toggle-btn" data-post-id="${post.post_id}">
                                        <i class="far fa-comment me-2" style="font-size:1.2rem;"></i>
                                        <h6 class="mb-0">Comment</h6>
                                    </div>
                                    <div class="d-flex align-items-center feather-icon">
                                        <i class="fas fa-share me-2" style="font-size:1.2rem;"></i>
                                        <h6 class="mb-0">Share</h6>
                                    </div>
                                </div>
                                <div class="comments-section mt-3" data-post-id="${post.post_id}" style="display:none;">
                                    <div class="comments-list mb-3"></div>
                                    <form class="comment-text d-flex align-items-center mt-3">
                                        <input type="text" class="form-control rounded comment-input" placeholder="Write a comment..." data-post-id="${post.post_id}">
                                        <button type="button" class="btn btn-primary ms-2 submit-comment-btn" data-post-id="${post.post_id}">Post</button>
                                    </form>
                                </div>
                            </div>
                        `;

                        const hr = document.createElement('hr');
                        container.appendChild(postDiv);
                        container.appendChild(hr);
                    });

                    offset += data.posts.length;
                    btn.dataset.offset = offset;

                    if (data.posts.length < limit) {
                        btn.style.display = 'none';
                    } else {
                        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Load More Posts';
                    }
                } else {
                    btn.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading more posts:', error);
                alert('Failed to load more posts');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Load More Posts';
        });
    </script>

    <!-- Backend Bundle JavaScript -->
    <script src="js/libs.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>

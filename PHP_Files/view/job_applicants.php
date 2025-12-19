<?php
session_start();
require_once('../settings/db_class.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../loginPage.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    header("Location: jobs.php");
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get job details and verify ownership
$job_query = "SELECT * FROM JobListings WHERE job_id = ? AND posted_by = ?";
$stmt = $conn->prepare($job_query);
$stmt->bind_param('ii', $job_id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) {
    $_SESSION['error_message'] = 'Job not found or you do not have permission to view applicants.';
    header("Location: jobs.php");
    exit();
}

// Get all applicants for this job
$applicants_query = "SELECT ja.*, u.first_name, u.last_name, u.email, u.phone, u.profile_photo, u.bio
                     FROM JobApplications ja
                     JOIN Users u ON ja.user_id = u.user_id
                     WHERE ja.job_id = ?
                     ORDER BY ja.submitted_at DESC";
$stmt = $conn->prepare($applicants_query);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$applicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applicants - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
            padding-top: 80px;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 15px 0;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-menu {
            display: flex;
            gap: 25px;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .navbar-menu a {
            text-decoration: none;
            color: #555;
            font-weight: 600;
            transition: color 0.3s;
        }

        .navbar-menu a:hover {
            color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }

        .job-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .job-info-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 20px;
            color: #666;
            font-size: 0.95rem;
        }

        .applicants-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .applicants-count-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .applicant-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .applicant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .applicant-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .applicant-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }

        .applicant-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .applicant-info {
            flex: 1;
        }

        .applicant-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .applicant-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .applicant-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .applicant-bio {
            color: #555;
            line-height: 1.6;
            margin-top: 10px;
        }

        .cover-letter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .cover-letter-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cover-letter-text {
            color: #555;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .applicant-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .applied-date {
            color: #999;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .applicant-header {
                flex-direction: column;
            }

            .applicant-actions {
                flex-direction: column;
            }

            .navbar-menu {
                display: none;
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
                <li><a href="connections.php"><i class="fas fa-user-friends"></i> Connections</a></li>
                <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
                <li>
                    <form method="post" action="../actions/logout_user_action.php" style="margin:0">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Job Applicants</h1>
            <div class="job-info">
                <div style="font-weight:700; font-size:1.2rem; color:#333; margin-bottom:8px;">
                    <?php echo htmlspecialchars($job['title']); ?>
                </div>
                <div class="job-info-item">
                    <i class="fas fa-building"></i>
                    <span><?php echo htmlspecialchars($job['company']); ?></span>
                </div>
                <div class="job-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                </div>
                <div class="job-info-item">
                    <i class="fas fa-briefcase"></i>
                    <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                </div>
            </div>
            <div style="margin-top:15px;">
                <a href="jobs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Jobs
                </a>
                <a href="manage_jobs.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Manage Jobs
                </a>
            </div>
        </div>

        <div class="applicants-count">
            <div class="applicants-count-number"><?php echo count($applicants); ?></div>
            <div>Total Applications Received</div>
        </div>

        <?php if (count($applicants) > 0): ?>
            <?php foreach ($applicants as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-header">
                        <div class="applicant-avatar">
                            <?php if ($applicant['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($applicant['profile_photo']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($applicant['first_name'], 0, 1) . substr($applicant['last_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="applicant-info">
                            <div class="applicant-name"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></div>
                            <div class="applicant-meta">
                                <div class="applicant-meta-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($applicant['email']); ?></span>
                                </div>
                                <?php if ($applicant['phone']): ?>
                                    <div class="applicant-meta-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($applicant['phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($applicant['bio']): ?>
                                <div class="applicant-bio"><?php echo nl2br(htmlspecialchars($applicant['bio'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($applicant['cover_letter']): ?>
                        <div class="cover-letter-section">
                            <div class="cover-letter-title">
                                <i class="fas fa-file-alt"></i> Cover Letter
                            </div>
                            <div class="cover-letter-text"><?php echo nl2br(htmlspecialchars($applicant['cover_letter'])); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="applicant-actions">
                        <?php if ($applicant['cv_file_path']): ?>
                            <a href="<?php echo htmlspecialchars($applicant['cv_file_path']); ?>" download class="btn btn-success">
                                <i class="fas fa-download"></i> Download CV
                            </a>
                        <?php endif; ?>
                        <a href="user_profile.php?id=<?php echo $applicant['user_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($applicant['email']); ?>" class="btn btn-secondary">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                        <div class="applied-date" style="margin-left:auto;">
                            <i class="far fa-clock"></i>
                            Applied <?php echo date('M j, Y \a\t g:i A', strtotime($applicant['submitted_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Applications Yet</h3>
                <p>You haven't received any applications for this job listing yet.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

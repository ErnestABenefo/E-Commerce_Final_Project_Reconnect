<?php
session_start();
require_once('../settings/db_class.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../loginPage.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = new db_connection();
$conn = $db->db_conn();

// Get all job listings
$jobs_query = "SELECT j.*, u.first_name, u.last_name, u.profile_photo,
               (SELECT COUNT(*) FROM JobApplications WHERE job_id = j.job_id) as application_count,
               (SELECT COUNT(*) FROM JobApplications WHERE job_id = j.job_id AND user_id = ?) as user_applied
               FROM JobListings j
               JOIN Users u ON j.posted_by = u.user_id
               ORDER BY j.created_at DESC";
$stmt = $conn->prepare($jobs_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Opportunities - ReConnect</title>
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

        /* Navbar */
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

        .navbar-menu a:hover,
        .navbar-menu a.active {
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
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        /* Container */
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Job Cards */
        .jobs-grid {
            display: grid;
            gap: 20px;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            gap: 20px;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .job-icon {
            width: 70px;
            height: 70px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .job-content {
            flex: 1;
            min-width: 0;
        }

        .job-header {
            margin-bottom: 12px;
        }

        .job-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .job-company {
            color: #667eea;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .job-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
            position: relative;
        }

        .job-description.collapsed {
            max-height: 4.8em;
            overflow: hidden;
        }

        .job-description.expanded {
            max-height: none;
        }

        .read-more-btn {
            color: #667eea;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 5px 0;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }

        .read-more-btn:hover {
            color: #5568d3;
            text-decoration: underline;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .job-poster {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .poster-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            overflow: hidden;
        }

        .poster-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .poster-info {
            font-size: 0.9rem;
        }

        .poster-name {
            font-weight: 600;
            color: #333;
        }

        .posted-date {
            color: #999;
            font-size: 0.85rem;
        }

        .job-actions {
            display: flex;
            gap: 10px;
        }

        .application-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .applied-badge {
            background: #fff3e0;
            color: #e65100;
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

        .empty-state p {
            color: #999;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .job-card {
                flex-direction: column;
            }

            .job-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .navbar-menu {
                display: none;
            }
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
                <li><a href="connections.php"><i class="fas fa-user-friends"></i> Connections</a></li>
                <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php" class="active"><i class="fas fa-briefcase"></i> Jobs</a></li>
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
            <h1><i class="fas fa-briefcase"></i> Job Opportunities</h1>
            <a href="manage_jobs.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Post a Job
            </a>
        </div>

        <div class="jobs-grid">
            <?php if (count($jobs) > 0): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="job-content">
                            <div class="job-header">
                                <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
                            </div>

                            <div class="job-meta">
                                <div class="job-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                </div>
                                <div class="job-meta-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                            </div>

                            <div class="job-description <?php echo strlen($job['description']) > 250 ? 'collapsed' : ''; ?>" id="desc-<?php echo $job['job_id']; ?>">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                            <?php if (strlen($job['description']) > 250): ?>
                                <button class="read-more-btn" onclick="toggleDescription(<?php echo $job['job_id']; ?>)" id="toggle-<?php echo $job['job_id']; ?>">
                                    <span>Read More</span> <i class="fas fa-chevron-down"></i>
                                </button>
                            <?php endif; ?>

                            <div class="job-footer">
                                <div class="job-poster">
                                    <div class="poster-avatar">
                                        <?php if ($job['profile_photo']): ?>
                                            <img src="<?php echo htmlspecialchars($job['profile_photo']); ?>" alt="Profile">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($job['first_name'], 0, 1) . substr($job['last_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="poster-info">
                                        <div class="poster-name"><?php echo htmlspecialchars($job['first_name'] . ' ' . $job['last_name']); ?></div>
                                        <div class="posted-date">
                                            <i class="far fa-clock"></i> <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="job-actions">
                                    <?php if ($job['user_applied'] > 0): ?>
                                        <span class="application-badge applied-badge">
                                            <i class="fas fa-check-circle"></i> Applied
                                        </span>
                                    <?php elseif ($job['posted_by'] == $user_id): ?>
                                        <span class="application-badge">
                                            <i class="fas fa-user"></i> <?php echo $job['application_count']; ?> Applications
                                        </span>
                                        <a href="job_applicants.php?job_id=<?php echo $job['job_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-users"></i> View Applicants
                                        </a>
                                        <a href="manage_jobs.php" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Manage
                                        </a>
                                    <?php else: ?>
                                        <button onclick="openApplicationModal(<?php echo $job['job_id']; ?>, '<?php echo htmlspecialchars($job['title'], ENT_QUOTES); ?>')" class="btn btn-success">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Job Opportunities Yet</h3>
                    <p>Be the first to post a job opportunity!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Application Modal -->
    <div id="applicationModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:15px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0; color:#333;"><i class="fas fa-file-upload"></i> Apply for Job</h2>
                <button onclick="closeApplicationModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#999;">&times;</button>
            </div>
            
            <div style="background:#f8f9fa; padding:15px; border-radius:8px; margin-bottom:20px;">
                <div style="font-weight:600; color:#333; margin-bottom:5px;" id="modalJobTitle"></div>
                <div style="color:#666; font-size:0.9rem;">Please upload your CV/Resume and optionally add a cover letter</div>
            </div>

            <form id="applicationForm" enctype="multipart/form-data">
                <input type="hidden" id="modalJobId" name="job_id">
                
                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">
                        CV/Resume <span style="color:#dc3545;">*</span>
                    </label>
                    <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required style="width:100%; padding:10px; border:2px solid #e0e0e0; border-radius:8px; font-size:1rem;">
                    <small style="display:block; margin-top:5px; color:#666;">Accepted formats: PDF, DOC, DOCX (Max 5MB)</small>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">
                        Cover Letter (Optional)
                    </label>
                    <textarea name="cover_letter" rows="6" style="width:100%; padding:12px; border:2px solid #e0e0e0; border-radius:8px; font-size:1rem; font-family:inherit; resize:vertical;" placeholder="Explain why you're a great fit for this position..."></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeApplicationModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApplicationModal(jobId, jobTitle) {
            document.getElementById('modalJobId').value = jobId;
            document.getElementById('modalJobTitle').textContent = jobTitle;
            document.getElementById('applicationModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeApplicationModal() {
            document.getElementById('applicationModal').style.display = 'none';
            document.getElementById('applicationForm').reset();
            document.body.style.overflow = 'auto';
        }

        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            fetch('../actions/apply_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Application submitted successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to submit application');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your application');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
            });
        });

        // Close modal when clicking outside
        document.getElementById('applicationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApplicationModal();
            }
        });

        function toggleDescription(jobId) {
            const descElement = document.getElementById('desc-' + jobId);
            const toggleBtn = document.getElementById('toggle-' + jobId);
            
            if (descElement.classList.contains('collapsed')) {
                descElement.classList.remove('collapsed');
                descElement.classList.add('expanded');
                toggleBtn.innerHTML = '<span>Read Less</span> <i class="fas fa-chevron-up"></i>';
            } else {
                descElement.classList.add('collapsed');
                descElement.classList.remove('expanded');
                toggleBtn.innerHTML = '<span>Read More</span> <i class="fas fa-chevron-down"></i>';
            }
        }
    </script>
</body>
</html>

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

// Get user's posted jobs
$jobs_query = "SELECT j.*, 
               (SELECT COUNT(*) FROM JobApplications WHERE job_id = j.job_id) as application_count
               FROM JobListings j
               WHERE j.posted_by = ?
               ORDER BY j.created_at DESC";
$stmt = $conn->prepare($jobs_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$my_jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');

    if (empty($title) || empty($company) || empty($description) || empty($location) || empty($job_type)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO JobListings (posted_by, title, company, description, location, job_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssss', $user_id, $title, $company, $description, $location, $job_type);
        
        if ($stmt->execute()) {
            $message = 'Job posted successfully!';
            $message_type = 'success';
            header('Location: manage_jobs.php');
            exit();
        } else {
            $message = 'Failed to post job. Please try again.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - ReConnect</title>
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
            font-size: 1rem;
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

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 0.9rem;
            padding: 8px 16px;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
            font-size: 0.9rem;
            padding: 8px 16px;
        }

        .btn-warning:hover {
            background: #e0a800;
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

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lead {
            color: #666;
            font-size: 1.05rem;
            margin-bottom: 25px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Form */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #666;
            font-size: 0.9rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Job List Table */
        .jobs-list {
            margin-top: 20px;
        }

        .jobs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .jobs-table th,
        .jobs-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .jobs-table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #333;
        }

        .jobs-table tr:hover {
            background: #f8f9fa;
        }

        .job-title-col {
            font-weight: 600;
            color: #333;
        }

        .job-meta {
            font-size: 0.9rem;
            color: #666;
            margin-top: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .jobs-table {
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
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
        <!-- Post Job Form -->
        <div class="card">
            <h1><i class="fas fa-briefcase"></i> Post a Job</h1>
            <p class="lead">Share job opportunities with the alumni network</p>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="title">Job Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Software Engineer">
                    <small>Enter a clear and descriptive job title</small>
                </div>

                <div class="form-group">
                    <label for="company">Company Name <span class="required">*</span></label>
                    <input type="text" id="company" name="company" required placeholder="e.g., Tech Solutions Inc.">
                    <small>Name of the hiring organization</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" required placeholder="e.g., Accra, Ghana">
                    </div>

                    <div class="form-group">
                        <label for="job_type">Job Type <span class="required">*</span></label>
                        <select id="job_type" name="job_type" required>
                            <option value="">Select job type</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Remote">Remote</option>
                            <option value="Freelance">Freelance</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Job Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required placeholder="Describe the role, requirements, responsibilities, and benefits..."></textarea>
                    <small>Provide detailed information about the position</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Post Job
                </button>
                <a href="jobs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Jobs
                </a>
            </form>
        </div>

        <!-- My Posted Jobs -->
        <div class="card">
            <h1><i class="fas fa-list"></i> My Posted Jobs</h1>
            <p class="lead">Manage your job listings</p>

            <?php if (count($my_jobs) > 0): ?>
                <div class="jobs-list">
                    <table class="jobs-table">
                        <thead>
                            <tr>
                                <th>Job Details</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Applications</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <div class="job-title-col"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="job-meta"><?php echo htmlspecialchars($job['company']); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($job['job_type']); ?></span></td>
                                    <td><?php echo $job['application_count']; ?> applicants</td>
                                    <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick='editJob(<?php echo json_encode($job); ?>)' class="btn btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="deleteJob(<?php echo $job['job_id']; ?>)" class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Posted Yet</h3>
                    <p>You haven't posted any job opportunities yet. Use the form above to create your first listing!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; overflow-y:auto; padding:20px;">
        <div style="background:white; border-radius:15px; max-width:800px; width:100%; max-height:90vh; overflow-y:auto; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,0.3); margin:auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0; color:#333;"><i class="fas fa-edit"></i> Edit Job</h2>
                <button onclick="closeEditModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#999;">&times;</button>
            </div>

            <form id="editJobForm">
                <input type="hidden" id="edit_job_id" name="job_id">
                <input type="hidden" name="action" value="update">

                <div class="form-group">
                    <label for="edit_title">Job Title <span class="required">*</span></label>
                    <input type="text" id="edit_title" name="title" required placeholder="e.g., Software Engineer">
                </div>

                <div class="form-group">
                    <label for="edit_company">Company Name <span class="required">*</span></label>
                    <input type="text" id="edit_company" name="company" required placeholder="e.g., Tech Solutions Inc.">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_location">Location <span class="required">*</span></label>
                        <input type="text" id="edit_location" name="location" required placeholder="e.g., Accra, Ghana">
                    </div>

                    <div class="form-group">
                        <label for="edit_job_type">Job Type <span class="required">*</span></label>
                        <select id="edit_job_type" name="job_type" required>
                            <option value="">Select job type</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                            <option value="Remote">Remote</option>
                            <option value="Freelance">Freelance</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_description">Job Description <span class="required">*</span></label>
                    <textarea id="edit_description" name="description" required placeholder="Describe the role, requirements, responsibilities, and benefits..."></textarea>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editJob(job) {
            document.getElementById('edit_job_id').value = job.job_id;
            document.getElementById('edit_title').value = job.title;
            document.getElementById('edit_company').value = job.company;
            document.getElementById('edit_location').value = job.location;
            document.getElementById('edit_job_type').value = job.job_type;
            document.getElementById('edit_description').value = job.description;
            
            document.getElementById('editModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editJobForm').reset();
            document.body.style.overflow = 'auto';
        }

        document.getElementById('editJobForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            fetch('../actions/update_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Job updated successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to update job');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Job';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the job');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Job';
            });
        });

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        function deleteJob(jobId) {
            if (!confirm('Are you sure you want to delete this job listing?')) {
                return;
            }

            const formData = new FormData();
            formData.append('job_id', jobId);

            fetch('../actions/delete_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Job deleted successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete job');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the job');
            });
        }
    </script>
</body>
</html>

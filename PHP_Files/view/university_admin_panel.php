<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header('Location: ../Log In/login_register.php');
    exit;
}

//require_once '../settings/db_class.php';
require_once '../controllers/university_admin_controller.php';

$current_user_id = $_SESSION['user_id'];
$db_conn = new db_connection();
$conn = $db_conn->db_conn();

// Fetch current user info
$sql = "SELECT first_name, last_name, email FROM Users WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
$current_user_name = $user_result['first_name'] . ' ' . $user_result['last_name'];

// Get universities where user is admin
$admin_universities = get_universities_by_admin_ctr($current_user_id);

// Get selected university from URL
$selected_university_id = isset($_GET['university_id']) ? (int)$_GET['university_id'] : 0;

$university_name = 'Select a University';
$current_admins = array();
$pending_requests = array();

if ($selected_university_id > 0) {
    // Verify user is admin of this university
    if (is_university_admin_ctr($selected_university_id, $current_user_id)) {
        // Get university name
        $sql = "SELECT name FROM University WHERE university_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $selected_university_id);
        $stmt->execute();
        $univ_result = $stmt->get_result()->fetch_assoc();
        $university_name = $univ_result['name'];

        // Get all admins for this university
        $current_admins = get_admins_by_university_ctr($selected_university_id);

        // Get pending requests
        $pending_requests = get_pending_admin_requests_ctr($selected_university_id);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Admin Panel - ReConnect</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        header p {
            color: #666;
            font-size: 14px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .sidebar h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .university-list {
            list-style: none;
        }

        .university-list li {
            margin-bottom: 10px;
        }

        .university-list a {
            display: block;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .university-list a:hover {
            background: #e8e8e8;
            border-left-color: #667eea;
        }

        .university-list a.active {
            background: #667eea;
            color: white;
            border-left-color: #764ba2;
        }

        .content {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .content h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .section {
            margin-bottom: 35px;
        }

        .section-title {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8e8e8;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        .admin-table td {
            padding: 12px;
            border-bottom: 1px solid #e8e8e8;
        }

        .admin-table tr:hover {
            background: #f9f9f9;
        }

        .admin-actions {
            display: flex;
            gap: 8px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }

        .admin-info {
            display: flex;
            align-items: center;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state p {
            margin: 10px 0;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #667eea;
        }

        .loading.show {
            display: block;
        }

        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>🎓 University Admin Panel</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($current_user_name); ?></strong> - Manage university admins</p>
        </header>

        <div class="main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <h3>Your Universities</h3>
                <?php if ($admin_universities && count($admin_universities) > 0): ?>
                    <ul class="university-list">
                        <?php foreach ($admin_universities as $univ): ?>
                            <li>
                                <a href="?university_id=<?php echo $univ['university_id']; ?>" 
                                   class="<?php echo ($univ['university_id'] === $selected_university_id) ? 'active' : ''; ?>">
                                    <strong><?php echo htmlspecialchars($univ['name']); ?></strong>
                                    <small style="display: block; color: #999; margin-top: 4px;">
                                        <?php echo htmlspecialchars($univ['location']); ?>
                                    </small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <p>You are not an admin for any university yet.</p>
                    </div>
                <?php endif; ?>
            </aside>

            <!-- Main Content -->
            <div class="content">
                <?php if ($selected_university_id > 0): ?>
                    <h2>⚙️ Manage Admins - <?php echo htmlspecialchars($university_name); ?></h2>

                    <div id="message" class="message"></div>

                    <!-- Assign New Admin Section -->
                    <div class="section">
                        <div class="section-title">Assign New Admin</div>
                        <form id="assign-admin-form">
                            <div class="form-group">
                                <label for="user-email">User Email</label>
                                <input type="email" id="user-email" name="user_email" required placeholder="Enter user email">
                                <small style="color: #999;">Enter the email of the user you want to make an admin</small>
                            </div>

                            <div class="form-group">
                                <label for="admin-role">Admin Role</label>
                                <select id="admin-role" name="role" required>
                                    <option value="admin">Admin</option>
                                    <option value="moderator">Moderator</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Assign Admin</button>
                        </form>
                        <div class="loading" id="assign-loading">Assigning admin...</div>
                    </div>

                    <!-- Pending Requests Section -->
                    <?php if ($pending_requests && count($pending_requests) > 0): ?>
                        <div class="section">
                            <div class="section-title">Pending Admin Requests (<?php echo count($pending_requests); ?>)</div>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Requested At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_requests as $request): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-info">
                                                    <?php if ($request['profile_photo']): ?>
                                                        <img src="<?php echo htmlspecialchars($request['profile_photo']); ?>" alt="Profile" class="admin-avatar">
                                                    <?php else: ?>
                                                        <div class="admin-avatar" style="background: #667eea; color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                            <?php echo strtoupper(substr($request['first_name'], 0, 1)) . strtoupper(substr($request['last_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                                            <td><span class="status-badge status-pending"><?php echo htmlspecialchars($request['role']); ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <button class="btn btn-primary btn-small approve-btn" data-user-id="<?php echo $request['user_id']; ?>">Approve</button>
                                                    <button class="btn btn-danger btn-small reject-btn" data-user-id="<?php echo $request['user_id']; ?>">Reject</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Current Admins Section -->
                    <div class="section">
                        <div class="section-title">Current Admins (<?php echo count($current_admins); ?>)</div>
                        <?php if ($current_admins && count($current_admins) > 0): ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_admins as $admin): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-info">
                                                    <?php if ($admin['profile_photo']): ?>
                                                        <img src="<?php echo htmlspecialchars($admin['profile_photo']); ?>" alt="Profile" class="admin-avatar">
                                                    <?php else: ?>
                                                        <div class="admin-avatar" style="background: #667eea; color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                            <?php echo strtoupper(substr($admin['first_name'], 0, 1)) . strtoupper(substr($admin['last_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $admin['status']; ?>">
                                                    <?php echo ucfirst($admin['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <?php if ($admin['user_id'] !== $current_user_id): ?>
                                                        <button class="btn btn-danger btn-small revoke-btn" data-user-id="<?php echo $admin['user_id']; ?>">Revoke</button>
                                                    <?php else: ?>
                                                        <span style="color: #999; font-size: 12px;">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No admins assigned yet. Use the form above to assign your first admin.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content Management Tab -->
                    <div class="section" style="margin-top: 40px; border-top: 2px solid #eee; padding-top: 20px;">
                        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                            <button id="post-tab-btn" class="btn btn-primary active" style="background: #2d7ef7; color: white;">📝 Posts</button>
                            <button id="group-tab-btn" class="btn" style="background: #f0f0f0; color: #333;">👥 Groups</button>
                        </div>

                        <!-- Posts Section -->
                        <div id="posts-section" style="display: block;">
                            <div class="section">
                                <div class="section-title">Create University Post</div>
                                <form id="create-post-form">
                                    <div class="form-group">
                                        <label for="post-content">Content</label>
                                        <textarea id="post-content" name="content" required placeholder="Write your announcement or news..." style="width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="post-type">Post Type</label>
                                        <select id="post-type" name="post_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="announcement">Announcement</option>
                                            <option value="news">News</option>
                                            <option value="event">Event</option>
                                            <option value="update">Update</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Publish Post</button>
                                </form>
                            </div>

                            <div class="section">
                                <div class="section-title">University Posts</div>
                                <div id="posts-list" style="max-height: 400px; overflow-y: auto;"></div>
                            </div>
                        </div>

                        <!-- Groups Section -->
                        <div id="groups-section" style="display: none;">
                            <div class="section">
                                <div class="section-title">Create Official Group</div>
                                <form id="create-group-form">
                                    <div class="form-group">
                                        <label for="group-name">Group Name</label>
                                        <input type="text" id="group-name" name="name" required placeholder="E.g., Computer Science Club" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    </div>
                                    <div class="form-group">
                                        <label for="group-description">Description</label>
                                        <textarea id="group-description" name="description" placeholder="Describe the group purpose..." style="width: 100%; height: 80px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="group-type">Group Type</label>
                                        <select id="group-type" name="group_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="official">Official</option>
                                            <option value="department">Department</option>
                                            <option value="special_interest">Special Interest</option>
                                            <option value="alumni">Alumni Network</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Create Group</button>
                                </form>
                            </div>

                            <div class="section">
                                <div class="section-title">University Groups</div>
                                <div id="groups-list" style="max-height: 400px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="empty-state">
                        <p>Select a university from the sidebar to manage admins and content</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const universitId = <?php echo $selected_university_id; ?>;

        // Assign Admin Form
        document.getElementById('assign-admin-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const userEmail = document.getElementById('user-email').value;
            const role = document.getElementById('admin-role').value;

            // First, get user ID by email
            try {
                // Get user by email
                const getUserResponse = await fetch(`../actions/get_user_by_email_action.php?email=${encodeURIComponent(userEmail)}`);
                const userData = await getUserResponse.json();

                if (!userData.user_id) {
                    showMessage('User with this email not found', 'error');
                    return;
                }

                // Now assign admin
                const formData = new FormData();
                formData.append('university_id', universitId);
                formData.append('user_id', userData.user_id);
                formData.append('role', role);

                document.getElementById('assign-loading').classList.add('show');

                const response = await fetch('../actions/assign_university_admin_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                document.getElementById('assign-loading').classList.remove('show');

                if (data.status === 'success') {
                    showMessage('Admin assigned successfully!', 'success');
                    document.getElementById('assign-admin-form').reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage(data.message || 'Failed to assign admin', 'error');
                }
            } catch (error) {
                document.getElementById('assign-loading').classList.remove('show');
                showMessage('Error: ' + error.message, 'error');
            }
        });

        // Revoke Admin Button
        document.querySelectorAll('.revoke-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to revoke this admin?')) return;

                const userId = btn.dataset.userId;
                const formData = new FormData();
                formData.append('university_id', universitId);
                formData.append('user_id', userId);

                try {
                    const response = await fetch('../actions/revoke_university_admin_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showMessage('Admin revoked successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message || 'Failed to revoke admin', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                }
            });
        });

        // Approve Pending Admin
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const userId = btn.dataset.userId;
                const formData = new FormData();
                formData.append('university_id', universitId);
                formData.append('user_id', userId);

                try {
                    const response = await fetch('../actions/approve_university_admin_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showMessage('Admin approved successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message || 'Failed to approve admin', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                }
            });
        });

        // Reject Pending Admin
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to reject this admin request?')) return;

                const userId = btn.dataset.userId;
                const formData = new FormData();
                formData.append('university_id', universitId);
                formData.append('user_id', userId);

                try {
                    const response = await fetch('../actions/revoke_university_admin_action.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showMessage('Admin request rejected!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showMessage(data.message || 'Failed to reject request', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                }
            });
        });

        // Show message helper
        function showMessage(message, type) {
            const messageEl = document.getElementById('message');
            messageEl.textContent = message;
            messageEl.className = `message ${type}`;
            setTimeout(() => {
                messageEl.className = 'message';
            }, 5000);
        }

        // ===== CONTENT MANAGEMENT (Posts & Groups) =====
        
        // Tab switching for content management
        const postTabBtn = document.getElementById('post-tab-btn');
        const groupTabBtn = document.getElementById('group-tab-btn');
        const postsSection = document.getElementById('posts-section');
        const groupsSection = document.getElementById('groups-section');

        if (postTabBtn) {
            postTabBtn.addEventListener('click', () => {
                postsSection.style.display = 'block';
                groupsSection.style.display = 'none';
                postTabBtn.classList.add('active');
                groupTabBtn.classList.remove('active');
            });
        }

        if (groupTabBtn) {
            groupTabBtn.addEventListener('click', () => {
                postsSection.style.display = 'none';
                groupsSection.style.display = 'block';
                postTabBtn.classList.remove('active');
                groupTabBtn.classList.add('active');
            });
        }

        // Create University Post
        const createPostForm = document.getElementById('create-post-form');
        if (createPostForm) {
            createPostForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(createPostForm);
                formData.append('university_id', universitId);
                formData.append('action', 'create');

                try {
                    const response = await fetch('../actions/university_post_action.php?action=create', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        showMessage('Post created successfully!', 'success');
                        createPostForm.reset();
                        loadUniversityPosts();
                    } else {
                        showMessage(data.message || 'Failed to create post', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                }
            });
        }

        // Load and display university posts
        async function loadUniversityPosts() {
            try {
                const response = await fetch(`../actions/university_post_action.php?action=read&university_id=${universitId}&limit=20`);
                const data = await response.json();

                const postsContainer = document.getElementById('posts-list');
                if (!postsContainer) return;

                if (!data || data.status !== 'success' || !data.posts || data.posts.length === 0) {
                    postsContainer.innerHTML = '<p style="color:#999">No posts yet</p>';
                    if (data && data.message) {
                        console.error('Posts load error:', data.message);
                    }
                    return;
                }

                postsContainer.innerHTML = data.posts.map(post => `
                    <div style="background:#f9f9f9; padding:12px; margin:8px 0; border-radius:4px; border-left:3px solid #2d7ef7">
                        <div style="display:flex; justify-content:space-between; align-items:start">
                            <div style="flex:1">
                                <p style="font-size:13px; color:#666; margin:0">${post.post_type}</p>
                                <p style="margin:4px 0">${escapeHtml(post.content)}</p>
                                <p style="font-size:11px; color:#999; margin:4px 0">${new Date(post.created_at).toLocaleDateString()}</p>
                            </div>
                            <button class="edit-post-btn" data-post-id="${post.post_id}" style="background:#2d7ef7; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px">Edit</button>
                            <button class="delete-post-btn" data-post-id="${post.post_id}" style="background:#e74c3c; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:6px">Delete</button>
                        </div>
                    </div>
                `).join('');

                // Attach event listeners to edit/delete buttons
                document.querySelectorAll('.delete-post-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('Delete this post?')) return;
                        const fd = new FormData();
                        fd.append('post_id', btn.dataset.postId);
                        const resp = await fetch('../actions/university_post_action.php?action=delete', {
                            method: 'POST', body: fd
                        });
                        const d = await resp.json();
                        if (d.status === 'success') {
                            showMessage('Post deleted', 'success');
                            loadUniversityPosts();
                        } else {
                            showMessage(d.message || 'Failed', 'error');
                        }
                    });
                });
            } catch (error) {
                console.error(error);
            }
        }

        // Create University Group
        const createGroupForm = document.getElementById('create-group-form');
        if (createGroupForm) {
            createGroupForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(createGroupForm);
                formData.append('university_id', universitId);

                try {
                    const response = await fetch('../actions/university_group_action.php?action=create', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.status === 'success') {
                        showMessage('Group created successfully!', 'success');
                        createGroupForm.reset();
                        loadUniversityGroups();
                    } else {
                        showMessage(data.message || 'Failed to create group', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                }
            });
        }

        // Load and display university groups
        async function loadUniversityGroups() {
            try {
                const response = await fetch(`../actions/university_group_action.php?action=read&university_id=${universitId}&limit=20`);
                const data = await response.json();

                const groupsContainer = document.getElementById('groups-list');
                if (!groupsContainer) return;

                if (!data || data.status !== 'success' || !data.groups || data.groups.length === 0) {
                    groupsContainer.innerHTML = '<p style="color:#999">No groups yet</p>';
                    if (data && data.message) {
                        console.error('Groups load error:', data.message);
                    }
                    return;
                }

                groupsContainer.innerHTML = data.groups.map(group => `
                    <div style="background:#f9f9f9; padding:12px; margin:8px 0; border-radius:4px; border-left:3px solid #27ae60">
                        <div style="display:flex; justify-content:space-between; align-items:start">
                            <div style="flex:1">
                                <p style="font-weight:bold; margin:0">${escapeHtml(group.name)}</p>
                                <p style="font-size:13px; color:#666; margin:4px 0">${escapeHtml(group.description)}</p>
                                <p style="font-size:11px; color:#999; margin:4px 0">${group.member_count} members</p>
                            </div>
                            <button class="edit-group-btn" data-group-id="${group.group_id}" style="background:#2d7ef7; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px">Edit</button>
                            <button class="delete-group-btn" data-group-id="${group.group_id}" style="background:#e74c3c; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:6px">Delete</button>
                        </div>
                    </div>
                `).join('');

                document.querySelectorAll('.delete-group-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('Delete this group?')) return;
                        const fd = new FormData();
                        fd.append('group_id', btn.dataset.groupId);
                        const resp = await fetch('../actions/university_group_action.php?action=delete', {
                            method: 'POST', body: fd
                        });
                        const d = await resp.json();
                        if (d.status === 'success') {
                            showMessage('Group deleted', 'success');
                            loadUniversityGroups();
                        } else {
                            showMessage(d.message || 'Failed', 'error');
                        }
                    });
                });
            } catch (error) {
                console.error(error);
            }
        }

        // Helper to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load posts and groups on page load if a university is selected
        if (universitId > 0) {
            loadUniversityPosts().catch(err => {
                console.error('Failed to load posts:', err);
                const postsContainer = document.getElementById('posts-list');
                if (postsContainer) postsContainer.innerHTML = '<p style="color:red">Error loading posts. Check console.</p>';
            });
            loadUniversityGroups().catch(err => {
                console.error('Failed to load groups:', err);
                const groupsContainer = document.getElementById('groups-list');
                if (groupsContainer) groupsContainer.innerHTML = '<p style="color:red">Error loading groups. Check console.</p>';
            });
        }
    </script>
</body>
</html>

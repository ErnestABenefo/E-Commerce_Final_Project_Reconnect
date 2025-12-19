<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../controllers/university_admin_controller.php';
require_once '../settings/db_class.php';

$current_user = $_SESSION['user_id'] ?? null;
if (!is_global_admin_ctr($current_user)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h2>Access denied</h2><p>You must be a global admin to access this page.</p>';
    exit;
}

// Fetch pending verifications
$db = new db_connection();
$conn = $db->db_conn();

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

$pending_verifications_query = "
    SELECT av.verification_id, av.user_id, u.first_name, u.last_name, u.email, 
           uni.name as university_name, av.graduation_year, av.student_id_number, 
           av.verification_status, av.created_at
    FROM AlumniVerification av
    JOIN Users u ON av.user_id = u.user_id
    JOIN University uni ON av.university_id = uni.university_id
    WHERE av.verification_status = 'pending'
    ORDER BY av.created_at DESC
";
$result = $conn->query($pending_verifications_query);
$pending_verifications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch recent users
$recent_users_query = "SELECT user_id, first_name, last_name, email, created_at FROM Users ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($recent_users_query);
$recent_users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch university admins
$university_admins_query = "
    SELECT ua.ua_id, u.user_id, u.first_name, u.last_name, u.email, 
           uni.name as university_name, ua.role, ua.status
    FROM UniversityAdmins ua
    JOIN Users u ON ua.user_id = u.user_id
    JOIN University uni ON ua.university_id = uni.university_id
    ORDER BY ua.created_at DESC
";
$result = $conn->query($university_admins_query);
$university_admins = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all universities
$universities_query = "SELECT university_id, name, location, university_type, created_at FROM University ORDER BY name";
$result = $conn->query($universities_query);
$universities = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// System statistics with error handling
$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM Users");
$stats['total_users'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM AlumniVerification WHERE verification_status = 'approved'");
$stats['verified_alumni'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM AlumniVerification WHERE verification_status = 'pending'");
$stats['pending_verifications'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM Posts");
$stats['total_posts'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM University");
$stats['total_universities'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM JobListings");
$stats['total_jobs'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM Events");
$stats['total_events'] = $result ? $result->fetch_assoc()['count'] : 0;

$result = $conn->query("SELECT COUNT(*) as count FROM MarketplaceItems");
$stats['total_marketplace_items'] = $result ? $result->fetch_assoc()['count'] : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Global Admin Panel - ReConnect</title>
  <link rel="stylesheet" href="../fontawesome/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; }
    
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .header h1 { font-size: 24px; margin-bottom: 5px; }
    .header p { opacity: 0.9; font-size: 14px; }
    
    .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      text-align: center;
    }
    .stat-card i { font-size: 32px; color: #667eea; margin-bottom: 10px; }
    .stat-card .number { font-size: 32px; font-weight: bold; color: #2c3e50; }
    .stat-card .label { color: #7f8c8d; font-size: 14px; margin-top: 5px; }
    
    .section {
      background: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }
    .section-header h2 { font-size: 20px; color: #2c3e50; }
    
    .tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 25px;
      border-bottom: 2px solid #f0f0f0;
    }
    .tab {
      padding: 12px 24px;
      cursor: pointer;
      border: none;
      background: transparent;
      color: #7f8c8d;
      font-size: 15px;
      font-weight: 500;
      border-bottom: 3px solid transparent;
      transition: all 0.3s;
    }
    .tab:hover { color: #667eea; }
    .tab.active {
      color: #667eea;
      border-bottom-color: #667eea;
    }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    table th, table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #f0f0f0;
    }
    table th {
      background: #f8f9fa;
      font-weight: 600;
      color: #2c3e50;
      font-size: 14px;
    }
    table td { color: #555; font-size: 14px; }
    table tr:hover { background: #f8f9fa; }
    
    .btn {
      padding: 8px 16px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      transition: all 0.3s;
    }
    .btn-primary {
      background: #667eea;
      color: white;
    }
    .btn-primary:hover { background: #5568d3; }
    .btn-success {
      background: #28a745;
      color: white;
    }
    .btn-success:hover { background: #218838; }
    .btn-danger {
      background: #dc3545;
      color: white;
    }
    .btn-danger:hover { background: #c82333; }
    .btn-warning {
      background: #ffc107;
      color: #333;
    }
    .btn-warning:hover { background: #e0a800; }
    
    .badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-pending { background: #fff3cd; color: #856404; }
    .badge-active { background: #d4edda; color: #155724; }
    .badge-approved { background: #d1ecf1; color: #0c5460; }
    .badge-rejected { background: #f8d7da; color: #721c24; }
    
    .empty-state {
      text-align: center;
      padding: 40px;
      color: #7f8c8d;
    }
    .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.3; }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    .modal.active { display: flex; }
    .modal-content {
      background: white;
      border-radius: 12px;
      padding: 30px;
      max-width: 600px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .modal-close {
      font-size: 24px;
      cursor: pointer;
      color: #7f8c8d;
    }
    .modal-close:hover { color: #2c3e50; }
    
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2c3e50;
    }
    .form-group input, .form-group select, .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }
    .form-group textarea { min-height: 100px; resize: vertical; }
    
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
      color: #667eea;
      text-decoration: none;
      white-space: nowrap;
    }
    
    .navbar-brand i { font-size: 1.8rem; }
    
    .navbar-menu {
      display: flex;
      list-style: none;
      gap: 30px;
      align-items: center;
      margin: 0;
      padding: 0;
      margin-left: auto;
    }
    
    .navbar-menu a {
      text-decoration: none;
      color: #2c3e50;
      font-weight: 500;
      transition: color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .navbar-menu a:hover { color: #667eea; }
    .navbar-menu a.active {
      color: #667eea;
      font-weight: 600;
    }
    
    .navbar-menu .btn-nav {
      background: #667eea;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
      font-size: 14px;
    }
    
    .navbar-menu .btn-nav:hover { background: #5568d3; }
    .navbar-menu .btn-nav.danger { background: #dc3545; }
    .navbar-menu .btn-nav.danger:hover { background: #c82333; }
    
    body { padding-top: 70px; }
    .header { margin-top: 0; }
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
      
      <ul class="navbar-menu">
        <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="connections.php"><i class="fas fa-user-friends"></i> Connections</a></li>
        <li><a href="groups.php"><i class="fas fa-users"></i> Groups</a></li>
        <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
        <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
        <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
        <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="global_admin_panel.php" class="active" style="color: #f39c12;"><i class="fas fa-crown"></i> Admin Panel</a></li>
        <li>
          <form method="post" action="../actions/logout_user_action.php" style="margin:0">
            <button type="submit" class="btn-nav danger">
              <i class="fas fa-sign-out-alt"></i> Logout
            </button>
          </form>
        </li>
      </ul>
    </div>
  </nav>
  
  <div class="header">
    <h1><i class="fas fa-crown"></i> Global Admin Panel</h1>
    <p>Complete system administration and management</p>
  </div>
  
  <div class="container">
    <!-- Statistics Dashboard -->
    <div class="stats-grid">
      <div class="stat-card">
        <i class="fas fa-users"></i>
        <div class="number"><?php echo number_format($stats['total_users']); ?></div>
        <div class="label">Total Users</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-user-check"></i>
        <div class="number"><?php echo number_format($stats['verified_alumni']); ?></div>
        <div class="label">Verified Alumni</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-clock"></i>
        <div class="number"><?php echo number_format($stats['pending_verifications']); ?></div>
        <div class="label">Pending Verifications</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-university"></i>
        <div class="number"><?php echo number_format($stats['total_universities']); ?></div>
        <div class="label">Universities</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-comment-alt"></i>
        <div class="number"><?php echo number_format($stats['total_posts']); ?></div>
        <div class="label">Total Posts</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-briefcase"></i>
        <div class="number"><?php echo number_format($stats['total_jobs']); ?></div>
        <div class="label">Job Listings</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-calendar"></i>
        <div class="number"><?php echo number_format($stats['total_events']); ?></div>
        <div class="label">Events</div>
      </div>
      <div class="stat-card">
        <i class="fas fa-shopping-cart"></i>
        <div class="number"><?php echo number_format($stats['total_marketplace_items']); ?></div>
        <div class="label">Marketplace Items</div>
      </div>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="tabs">
      <button class="tab active" onclick="switchTab('verifications')">
        <i class="fas fa-user-check"></i> Alumni Verifications
      </button>
      <button class="tab" onclick="switchTab('admins')">
        <i class="fas fa-user-shield"></i> University Admins
      </button>
      <button class="tab" onclick="switchTab('universities')">
        <i class="fas fa-university"></i> Universities
      </button>
      <button class="tab" onclick="switchTab('users')">
        <i class="fas fa-users"></i> Recent Users
      </button>
      <button class="tab" onclick="switchTab('database')">
        <i class="fas fa-database"></i> Database Manager
      </button>
    </div>
    
    <!-- Alumni Verifications Tab -->
    <div id="verifications" class="tab-content active">
      <div class="section">
        <div class="section-header">
          <h2><i class="fas fa-user-check"></i> Pending Alumni Verifications</h2>
          <span class="badge badge-pending"><?php echo count($pending_verifications); ?> Pending</span>
        </div>
        
        <?php if (empty($pending_verifications)): ?>
          <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>No pending verifications at this time</p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>University</th>
                <th>Student ID</th>
                <th>Graduation Year</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pending_verifications as $verification): ?>
                <tr>
                  <td><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($verification['email']); ?></td>
                  <td><?php echo htmlspecialchars($verification['university_name']); ?></td>
                  <td><?php echo htmlspecialchars($verification['student_id_number']); ?></td>
                  <td><?php echo htmlspecialchars($verification['graduation_year']); ?></td>
                  <td><?php echo date('M d, Y', strtotime($verification['created_at'])); ?></td>
                  <td>
                    <button class="btn btn-success" onclick="approveVerification(<?php echo $verification['verification_id']; ?>)">
                      <i class="fas fa-check"></i> Approve
                    </button>
                    <button class="btn btn-danger" onclick="rejectVerification(<?php echo $verification['verification_id']; ?>)">
                      <i class="fas fa-times"></i> Reject
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- University Admins Tab -->
    <div id="admins" class="tab-content">
      <div class="section">
        <div class="section-header">
          <h2><i class="fas fa-user-shield"></i> University Administrators</h2>
          <button class="btn btn-primary" onclick="openAssignAdminModal()">
            <i class="fas fa-plus"></i> Assign New Admin
          </button>
        </div>
        
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>University</th>
              <th>Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($university_admins as $admin): ?>
              <tr>
                <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                <td><?php echo htmlspecialchars($admin['university_name']); ?></td>
                <td><span class="badge badge-active"><?php echo htmlspecialchars($admin['role']); ?></span></td>
                <td><span class="badge badge-<?php echo $admin['status'] === 'active' ? 'active' : 'rejected'; ?>"><?php echo htmlspecialchars($admin['status']); ?></span></td>
                <td>
                  <?php if ($admin['status'] === 'active'): ?>
                    <button class="btn btn-warning" onclick="suspendAdmin(<?php echo $admin['ua_id']; ?>)">
                      <i class="fas fa-pause"></i> Suspend
                    </button>
                  <?php else: ?>
                    <button class="btn btn-success" onclick="activateAdmin(<?php echo $admin['ua_id']; ?>)">
                      <i class="fas fa-play"></i> Activate
                    </button>
                  <?php endif; ?>
                  <button class="btn btn-danger" onclick="removeAdmin(<?php echo $admin['ua_id']; ?>)">
                    <i class="fas fa-trash"></i> Remove
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Universities Tab -->
    <div id="universities" class="tab-content">
      <div class="section">
        <div class="section-header">
          <h2><i class="fas fa-university"></i> Universities</h2>
          <button class="btn btn-primary" onclick="openAddUniversityModal()">
            <i class="fas fa-plus"></i> Add University
          </button>
        </div>
        
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Location</th>
              <th>Type</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($universities as $uni): ?>
              <tr>
                <td><?php echo htmlspecialchars($uni['university_id']); ?></td>
                <td><?php echo htmlspecialchars($uni['name']); ?></td>
                <td><?php echo htmlspecialchars($uni['location']); ?></td>
                <td><span class="badge badge-active"><?php echo htmlspecialchars($uni['university_type']); ?></span></td>
                <td><?php echo date('M d, Y', strtotime($uni['created_at'])); ?></td>
                <td>
                  <button class="btn btn-primary" onclick="editUniversity(<?php echo $uni['university_id']; ?>)">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Recent Users Tab -->
    <div id="users" class="tab-content">
      <div class="section">
        <div class="section-header">
          <h2><i class="fas fa-users"></i> Recent Users</h2>
          <button class="btn btn-primary" onclick="openUserSearchModal()">
            <i class="fas fa-search"></i> Search All Users
          </button>
        </div>
        
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_users as $user): ?>
              <tr>
                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                <td>
                  <button class="btn btn-primary" onclick="viewUser(<?php echo $user['user_id']; ?>)">
                    <i class="fas fa-eye"></i> View
                  </button>
                  <button class="btn btn-danger" onclick="suspendUser(<?php echo $user['user_id']; ?>)">
                    <i class="fas fa-ban"></i> Suspend
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Database Manager Tab -->
    <div id="database" class="tab-content">
      <div class="section">
        <div class="section-header">
          <h2><i class="fas fa-database"></i> Database Table Manager</h2>
          <p style="color: #7f8c8d; font-size: 14px; margin: 0;">Direct access to database tables for advanced operations</p>
        </div>
        
        <div style="display: flex; gap: 20px;">
          <div style="flex: 0 0 250px;">
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Tables</h3>
            <div id="db-tables-list"></div>
          </div>
          <div style="flex: 1;">
            <div id="db-table-content">
              <div class="empty-state">
                <i class="fas fa-table"></i>
                <p>Select a table to view and manage records</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modals -->
  <div id="assignAdminModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Assign University Admin</h2>
        <span class="modal-close" onclick="closeModal('assignAdminModal')">&times;</span>
      </div>
      <form id="assignAdminForm">
        <div class="form-group">
          <label>User Email</label>
          <input type="email" name="email" required placeholder="Enter user email">
        </div>
        <div class="form-group">
          <label>University</label>
          <select name="university_id" required>
            <option value="">Select University</option>
            <?php foreach ($universities as $uni): ?>
              <option value="<?php echo $uni['university_id']; ?>"><?php echo htmlspecialchars($uni['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role" required>
            <option value="admin">Admin</option>
            <option value="superadmin">Super Admin</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Assign Admin Role</button>
      </form>
    </div>
  </div>
  
  <div id="addUniversityModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add University</h2>
        <span class="modal-close" onclick="closeModal('addUniversityModal')">&times;</span>
      </div>
      <form id="addUniversityForm">
        <div class="form-group">
          <label>University Name *</label>
          <input type="text" name="name" required placeholder="e.g., University of Ghana">
        </div>
        <div class="form-group">
          <label>Location *</label>
          <input type="text" name="location" required placeholder="e.g., Accra, Ghana">
        </div>
        <div class="form-group">
          <label>Website</label>
          <input type="url" name="website" placeholder="https://example.edu.gh">
        </div>
        <div class="form-group">
          <label>Contact Email</label>
          <input type="email" name="contact_email" placeholder="info@university.edu.gh">
        </div>
        <div class="form-group">
          <label>Contact Phone</label>
          <input type="tel" name="contact_phone" placeholder="+233 XX XXX XXXX">
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" rows="2" placeholder="Full postal address"></textarea>
        </div>
        <div class="form-group">
          <label>Established Year</label>
          <input type="number" name="established_year" min="1800" max="2025" placeholder="e.g., 1948">
        </div>
        <div class="form-group">
          <label>University Type *</label>
          <select name="university_type" required>
            <option value="">Select Type</option>
            <option value="public">Public</option>
            <option value="private">Private</option>
            <option value="religious">Religious</option>
            <option value="technical">Technical</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="4" placeholder="Brief description of the university"></textarea>
        </div>
        
        <hr style="margin: 25px 0; border: none; border-top: 2px solid #f0f0f0;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
          <h3 style="margin: 0; color: #2c3e50; font-size: 18px;"><i class="fas fa-building"></i> Academic Departments</h3>
          <button type="button" class="btn btn-primary" onclick="addDepartmentField()" style="padding: 6px 12px; font-size: 13px;">
            <i class="fas fa-plus"></i> Add Department
          </button>
        </div>
        
        <div id="departmentsContainer">
          <div class="department-field" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 12px;">
            <div style="display: flex; gap: 10px; align-items: flex-start;">
              <div style="flex: 1;">
                <label style="font-size: 13px; margin-bottom: 5px; display: block;">Faculty/School</label>
                <input type="text" class="dept-faculty" placeholder="e.g., School of Engineering" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
              </div>
              <div style="flex: 1;">
                <label style="font-size: 13px; margin-bottom: 5px; display: block;">Department Name</label>
                <input type="text" class="dept-name" placeholder="e.g., Computer Science" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
              </div>
              <button type="button" onclick="removeDepartmentField(this)" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-top: 20px; font-size: 13px;">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px; padding: 12px; font-size: 15px;">Add University</button>
      </form>
    </div>
  </div>
  
  <script>
    // Tab switching
    function switchTab(tabName) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      event.target.closest('.tab').classList.add('active');
      document.getElementById(tabName).classList.add('active');
      
      if (tabName === 'database') {
        loadDatabaseTables();
      }
    }
    
    // Modal functions
    function openModal(modalId) {
      document.getElementById(modalId).classList.add('active');
    }
    
    function closeModal(modalId) {
      document.getElementById(modalId).classList.remove('active');
    }
    
    function openAssignAdminModal() {
      openModal('assignAdminModal');
    }
    
    function openAddUniversityModal() {
      openModal('addUniversityModal');
    }
    
    function openUserSearchModal() {
      alert('User search functionality coming soon!');
    }
    
    // Alumni verification functions
    async function approveVerification(verificationId) {
      if (!confirm('Approve this alumni verification?')) return;
      
      try {
        const response = await fetch('../actions/admin/approve_verification.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ verification_id: verificationId })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Verification approved successfully!');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error approving verification: ' + error.message);
      }
    }
    
    async function rejectVerification(verificationId) {
      const reason = prompt('Enter rejection reason:');
      if (!reason) return;
      
      try {
        const response = await fetch('../actions/admin/reject_verification.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ verification_id: verificationId, reason: reason })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Verification rejected.');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error rejecting verification: ' + error.message);
      }
    }
    
    // Admin management functions
    async function suspendAdmin(adminId) {
      if (!confirm('Suspend this university admin?')) return;
      
      try {
        const response = await fetch('../actions/admin/update_admin_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ua_id: adminId, status: 'suspended' })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Admin suspended successfully!');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error suspending admin: ' + error.message);
      }
    }
    
    async function activateAdmin(adminId) {
      if (!confirm('Activate this university admin?')) return;
      
      try {
        const response = await fetch('../actions/admin/update_admin_status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ua_id: adminId, status: 'active' })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Admin activated successfully!');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error activating admin: ' + error.message);
      }
    }
    
    async function removeAdmin(adminId) {
      if (!confirm('Remove this university admin? This action cannot be undone.')) return;
      
      try {
        const response = await fetch('../actions/admin/remove_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ua_id: adminId })
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Admin removed successfully!');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error removing admin: ' + error.message);
      }
    }
    
    // User management functions
    function viewUser(userId) {
      alert('User profile view coming soon! User ID: ' + userId);
    }
    
    async function suspendUser(userId) {
      if (!confirm('Suspend this user account?')) return;
      alert('User suspension functionality coming soon! User ID: ' + userId);
    }
    
    // University management
    function editUniversity(uniId) {
      window.location.href = 'edit_university.php?id=' + uniId;
    }
    
    // Form submissions
    document.getElementById('assignAdminForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData);
      
      try {
        const response = await fetch('../actions/admin/assign_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
          alert('Admin assigned successfully!');
          closeModal('assignAdminModal');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error assigning admin: ' + error.message);
      }
    });
    
    document.getElementById('addUniversityForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData);
      
      // Collect departments
      const departments = [];
      document.querySelectorAll('#departmentsContainer .department-field').forEach(field => {
        const faculty = field.querySelector('.dept-faculty').value.trim();
        const name = field.querySelector('.dept-name').value.trim();
        if (name) { // Only add if department name is provided
          departments.push({ faculty: faculty || null, department_name: name });
        }
      });
      
      data.departments = departments;
      
      try {
        const response = await fetch('../actions/admin/add_university.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
          alert('University added successfully!');
          closeModal('addUniversityModal');
          location.reload();
        } else {
          alert('Error: ' + (result.message || 'Unknown error'));
        }
      } catch (error) {
        alert('Error adding university: ' + error.message);
      }
    });
    
    // Department field management
    function addDepartmentField() {
      const container = document.getElementById('departmentsContainer');
      const fieldHtml = `
        <div class="department-field" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 12px;">
          <div style="display: flex; gap: 10px; align-items: flex-start;">
            <div style="flex: 1;">
              <label style="font-size: 13px; margin-bottom: 5px; display: block;">Faculty/School</label>
              <input type="text" class="dept-faculty" placeholder="e.g., School of Engineering" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
            </div>
            <div style="flex: 1;">
              <label style="font-size: 13px; margin-bottom: 5px; display: block;">Department Name</label>
              <input type="text" class="dept-name" placeholder="e.g., Computer Science" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
            </div>
            <button type="button" onclick="removeDepartmentField(this)" style="background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-top: 20px; font-size: 13px;">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
      container.insertAdjacentHTML('beforeend', fieldHtml);
    }
    
    function removeDepartmentField(button) {
      const container = document.getElementById('departmentsContainer');
      if (container.querySelectorAll('.department-field').length > 1) {
        button.closest('.department-field').remove();
      } else {
        alert('At least one department field is required');
      }
    }
    
    // Database table manager
    async function loadDatabaseTables() {
      try {
        const response = await fetch('../actions/admin/list_tables.php');
        const data = await response.json();
        
        if (data.status === 'error') {
          document.getElementById('db-tables-list').innerHTML = '<p style="color: red;">Error: ' + data.message + '</p>';
          return;
        }
        
        const tables = data.tables || [];
        
        if (tables.length === 0) {
          document.getElementById('db-tables-list').innerHTML = '<p>No tables found</p>';
          return;
        }
        
        const listHtml = tables.map(t => 
          `<div style="padding: 10px; cursor: pointer; border-radius: 6px; margin-bottom: 6px; background: #f8f9fa;" 
                onclick="loadTableData('${t}')" 
                onmouseover="this.style.background='#e9ecef'" 
                onmouseout="this.style.background='#f8f9fa'">${t}</div>`
        ).join('');
        
        document.getElementById('db-tables-list').innerHTML = listHtml;
      } catch (error) {
        console.error('Error loading tables:', error);
        document.getElementById('db-tables-list').innerHTML = '<p style="color: red;">Error loading tables</p>';
      }
    }
    
    async function loadTableData(tableName) {
      try {
        const response = await fetch(`../actions/admin/fetch_rows.php?table=${tableName}`);
        const data = await response.json();
        
        if (!data.columns || !data.rows) {
          document.getElementById('db-table-content').innerHTML = '<p>Error loading table data</p>';
          return;
        }
        
        let html = `<h3 style="margin-bottom: 15px; color: #2c3e50;">${tableName}</h3><div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;"><thead><tr>`;
        data.columns.forEach(col => html += `<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd; background: #f8f9fa; font-weight: 600;">${col}</th>`);
        html += '</tr></thead><tbody>';
        
        data.rows.forEach(row => {
          html += '<tr style="border-bottom: 1px solid #f0f0f0;">';
          data.columns.forEach(col => {
            const value = row[col] !== null && row[col] !== undefined ? String(row[col]) : '<em style="color: #999;">NULL</em>';
            html += `<td style="padding: 12px;">${value}</td>`;
          });
          html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        document.getElementById('db-table-content').innerHTML = html;
      } catch (error) {
        document.getElementById('db-table-content').innerHTML = '<p style="color: red;">Error loading table data</p>';
        console.error('Error:', error);
      }
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
      }
    }
  </script>
</body>
</html>

<?php
// Quick setup script to create test groups and check user data
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../classes/group_class.php';

$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();
$conn = $db->db_conn();

// Get current user info
$stmt = $conn->prepare("SELECT first_name, last_name FROM Users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$userName = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'User';

$message = '';

// Handle auto-enroll action
$gm = new GroupManager();
if (isset($_POST['auto_enroll'])) {
    $enrolled = $gm->autoEnrollUser($user_id);
    if ($enrolled) {
        $message = "<div class='alert alert-success'>✅ Successfully enrolled! <a href='group_setup.php' style='color:#155724;text-decoration:underline'>Refresh to see changes</a></div>";
    } else {
        $message = "<div class='alert alert-warning'>⚠️ Enrollment completed but no new groups were created. Check your verification status and graduation year.</div>";
    }
}

// Handle manual group creation
if (isset($_POST['create_groups'])) {
    $uni_id = (int)$_POST['uni_id'];
    $year = isset($_POST['year']) ? (int)$_POST['year'] : null;
    
    $result_msg = '';
    
    // Create university group
    $uni_group_id = $gm->getOrCreateUniversityGroup($uni_id);
    if ($uni_group_id) {
        $result_msg .= "✅ University group created/found (ID: $uni_group_id)<br>";
        $gm->addUserToGroup($user_id, $uni_group_id);
        $result_msg .= "✅ You were added to the university group<br>";
    }
    
    // Create year group if year provided
    if ($year) {
        $year_group_id = $gm->getOrCreateYearGroup($uni_id, $year);
        if ($year_group_id) {
            $result_msg .= "✅ Year group created/found (ID: $year_group_id)<br>";
            $gm->addUserToGroup($user_id, $year_group_id);
            $result_msg .= "✅ You were added to the year group<br>";
        }
    }
    
    $message = "<div class='alert alert-success'>$result_msg</div>";
}

// Check if user has verified universities
$stmt = $conn->prepare("
    SELECT av.*, u.name as university_name 
    FROM AlumniVerification av 
    JOIN University u ON av.university_id = u.university_id 
    WHERE av.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$verifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check academic profiles
$stmt = $conn->prepare("
    SELECT uap.*, ad.department_name, u.name as university_name 
    FROM UserAcademicProfile uap 
    JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
    JOIN University u ON ad.university_id = u.university_id
    WHERE uap.user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profiles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check existing groups
$stmt = $conn->prepare("
    SELECT g.*, u.name as university_name,
           (SELECT COUNT(*) FROM GroupMembers WHERE group_id = g.group_id) as member_count
    FROM `Groups` g
    LEFT JOIN University u ON g.university_id = u.university_id
    ORDER BY g.created_at DESC
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();
$all_groups = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check user's group memberships
$user_groups = $gm->getUserGroups($user_id);

// Get universities for dropdown
$stmt = $conn->prepare("SELECT university_id, name FROM University ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$universities = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Group Setup & Diagnostics - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root{--primary:#667eea;--danger:#e74c3c;--muted:#666;--success:#27ae60;--warning:#f39c12}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f4f6f8;margin:0;padding:24px}
        .container{max-width:1200px;margin:0 auto}
        .card{background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:20px}
        h1{margin:0 0 10px 0;font-size:1.8rem;color:#333}
        h2{font-size:1.3rem;color:#333;margin:0 0 15px 0;display:flex;align-items:center;gap:10px}
        h3{font-size:1.1rem;color:#555;margin:15px 0 10px 0;padding-bottom:8px;border-bottom:2px solid #e0e0e0}
        .breadcrumb{color:#666;margin-bottom:20px}
        .breadcrumb a{color:var(--primary);text-decoration:none}
        .breadcrumb a:hover{text-decoration:underline}
        .alert{padding:15px;border-radius:6px;margin:15px 0;border-left:4px solid}
        .alert-success{background:#d4edda;color:#155724;border-color:#28a745}
        .alert-warning{background:#fff3cd;color:#856404;border-color:#ffc107}
        .alert-danger{background:#f8d7da;color:#721c24;border-color:#dc3545}
        .alert-info{background:#d1ecf1;color:#0c5460;border-color:#17a2b8}
        .btn{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;transition:all 0.2s}
        .btn:hover{background:#5568d3;transform:translateY(-1px)}
        .btn-success{background:var(--success)}
        .btn-success:hover{background:#229954}
        .btn-warning{background:var(--warning)}
        .btn-warning:hover{background:#e67e22}
        table{width:100%;border-collapse:collapse;margin:10px 0}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #e0e0e0}
        th{background:#f8f9fa;font-weight:600;color:#333;font-size:14px}
        td{color:#555}
        .badge{display:inline-block;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600}
        .badge.pending{background:#fff3cd;color:#856404}
        .badge.approved{background:#d4edda;color:#155724}
        .badge.rejected{background:#f8d7da;color:#721c24}
        .status-icon{font-size:1.2rem;margin-right:8px}
        .empty-state{text-align:center;padding:40px;color:#999}
        .empty-state i{font-size:3rem;opacity:0.3;margin-bottom:15px;display:block}
        .form-group{margin-bottom:15px}
        input,select{padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-right:10px}
        select{min-width:200px}
        input[type="number"]{width:150px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin:20px 0}
        .stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:20px;border-radius:8px}
        .stat-card.success{background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%)}
        .stat-card.warning{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%)}
        .stat-card .number{font-size:2.5rem;font-weight:700;margin:10px 0}
        .stat-card .label{font-size:0.9rem;opacity:0.9}
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a> / 
            <a href="alumni_verification.php">Alumni Verification</a> / 
            <a href="academic_profile.php">Academic Profile</a> / 
            Group Setup
        </div>
        
        <div class="card">
            <h1><i class="fas fa-cogs"></i> Group Setup & Diagnostics</h1>
            <p style="color:#666">User: <strong><?php echo htmlspecialchars($userName); ?></strong></p>
        </div>
        
        <?php echo $message; ?>
        
        <!-- Quick Stats -->
        <div class="grid">
            <div class="stat-card">
                <div class="label"><i class="fas fa-graduation-cap"></i> Alumni Verifications</div>
                <div class="number"><?php echo count($verifications); ?></div>
            </div>
            <div class="stat-card success">
                <div class="label"><i class="fas fa-university"></i> Academic Profiles</div>
                <div class="number"><?php echo count($profiles); ?></div>
            </div>
            <div class="stat-card warning">
                <div class="label"><i class="fas fa-users"></i> Your Groups</div>
                <div class="number"><?php echo count($user_groups); ?></div>
            </div>
        </div>
        
        <!-- Alumni Verifications -->
        <div class="card">
            <h2><i class="fas fa-graduation-cap"></i> Alumni Verifications</h2>
            <?php if (empty($verifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p><strong>No alumni verifications found</strong></p>
                    <p>You need to be verified with a university first.</p>
                    <a href="alumni_verification.php" class="btn btn-success">Add Verification</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>University</th>
                            <th>Graduation Year</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($verifications as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['university_name']); ?></td>
                                <td><?php echo $v['graduation_year'] ?: '<em>Not set</em>'; ?></td>
                                <td>
                                    <span class="badge <?php echo $v['verification_status']; ?>">
                                        <?php echo ucfirst($v['verification_status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Academic Profiles -->
        <div class="card">
            <h2><i class="fas fa-university"></i> Academic Profiles</h2>
            <?php if (empty($profiles)): ?>
                <div class="alert alert-warning">
                    ⚠️ No academic profiles found. Add one to set your graduation year for year groups.
                    <a href="academic_profile.php" style="color:#856404;text-decoration:underline">Add Profile</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>University</th>
                            <th>Department</th>
                            <th>Graduation Year</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['university_name']); ?></td>
                                <td><?php echo htmlspecialchars($p['department_name']); ?></td>
                                <td><?php echo $p['graduation_year'] ?: '<em>Not set</em>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Your Group Memberships -->
        <div class="card">
            <h2><i class="fas fa-users"></i> Your Group Memberships</h2>
            <?php if (empty($user_groups)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p><strong>You are not a member of any groups</strong></p>
                    <p>Use the auto-enrollment button below to join groups based on your verifications.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Type</th>
                            <th>Members</th>
                            <th>Messages</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_groups as $g): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($g['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($g['group_type']); ?></td>
                                <td><?php echo $g['member_count']; ?></td>
                                <td><?php echo $g['message_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            
            <?php if (!empty($verifications)): ?>
                <?php
                $approved = array_filter($verifications, function($v) { return $v['verification_status'] === 'approved'; });
                ?>
                
                <?php if (!empty($approved)): ?>
                    <div style="margin:15px 0">
                        <p style="color:#666;margin-bottom:10px">
                            <i class="fas fa-info-circle"></i> Auto-enrollment will create and join you to university and year groups based on your approved verifications.
                        </p>
                        <form method="POST" style="display:inline">
                            <button type="submit" name="auto_enroll" class="btn btn-success">
                                <i class="fas fa-rocket"></i> Auto-Enroll Me in Groups
                            </button>
                        </form>
                        <a href="groups.php" class="btn" style="text-decoration:none">
                            <i class="fas fa-comments"></i> View My Groups
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        ⚠️ Your verification must be approved before you can join groups. Please wait for admin approval.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-danger">
                    ❌ No verifications found. Please get verified with a university first.
                    <a href="alumni_verification.php" style="color:#721c24;text-decoration:underline">Add Verification</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Manual Group Creation -->
        <div class="card">
            <h2><i class="fas fa-wrench"></i> Manual Group Creation (For Testing)</h2>
            <p style="color:#666;margin-bottom:15px">Create groups manually for testing purposes.</p>
            
            <form method="POST">
                <div class="form-group">
                    <select name="uni_id" required>
                        <option value="">-- Select University --</option>
                        <?php foreach ($universities as $uni): ?>
                            <option value="<?php echo $uni['university_id']; ?>">
                                <?php echo htmlspecialchars($uni['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="number" name="year" placeholder="Year (e.g., 2024)" min="1950" max="2050">
                    
                    <button type="submit" name="create_groups" class="btn">
                        <i class="fas fa-plus"></i> Create Groups
                    </button>
                </div>
            </form>
        </div>
        
        <!-- All Groups -->
        <div class="card">
            <h2><i class="fas fa-list"></i> All Groups in Database</h2>
            <?php if (empty($all_groups)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No groups exist yet.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>University</th>
                            <th>Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_groups as $g): ?>
                            <tr>
                                <td><?php echo $g['group_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($g['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($g['group_type']); ?></td>
                                <td><?php echo htmlspecialchars($g['university_name'] ?: 'N/A'); ?></td>
                                <td><?php echo $g['member_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

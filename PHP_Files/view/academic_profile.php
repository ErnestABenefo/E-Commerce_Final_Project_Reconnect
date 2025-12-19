<?php
// Academic Profile Manager
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];
$db = new db_connection();
$conn = $db->db_conn();

// Get current user
$stmt = $conn->prepare("SELECT first_name, last_name FROM Users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$userName = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'User';

// Get user's university from their existing academic profile (to ensure consistency)
$user_university = null;
$stmt = $conn->prepare("
    SELECT DISTINCT ad.university_id 
    FROM UserAcademicProfile uap
    JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
    WHERE uap.user_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_university = (int)$row['university_id'];
}
$stmt->close();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_profile'])) {
        $dept_id = (int)$_POST['department_id'];
        $grad_year = !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null;
        $degree = $_POST['degree'];
        
        $stmt = $conn->prepare("INSERT INTO UserAcademicProfile (user_id, department_id, graduation_year, degree) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiis', $user_id, $dept_id, $grad_year, $degree);
        
        if ($stmt->execute()) {
            $message = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0'>✅ Academic profile created successfully!</div>";
        } else {
            $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_profile'])) {
        $profile_id = (int)$_POST['profile_id'];
        $stmt = $conn->prepare("DELETE FROM UserAcademicProfile WHERE profile_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $profile_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "<div style='background:#fff3cd;color:#856404;padding:15px;border-radius:5px;margin:10px 0'>🗑️ Profile deleted</div>";
    }
}

// Get departments - filter by user's university if they have one
$departments = [];
if ($user_university) {
    // User has a verified university - only show departments from that university
    $stmt = $conn->prepare("
        SELECT ad.department_id, ad.department_name, ad.faculty, u.name as university_name, u.university_id
        FROM AcademicDepartment ad
        JOIN University u ON ad.university_id = u.university_id
        WHERE ad.university_id = ?
        ORDER BY ad.department_name
    ");
    $stmt->bind_param('i', $user_university);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    $stmt->close();
} else {
    // No verified university - show all departments grouped by university
    $result = $conn->query("
        SELECT ad.department_id, ad.department_name, ad.faculty, u.name as university_name, u.university_id
        FROM AcademicDepartment ad
        JOIN University u ON ad.university_id = u.university_id
        ORDER BY u.name, ad.department_name
    ");
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get user's academic profiles
$stmt = $conn->prepare("
    SELECT uap.*, ad.department_name, ad.faculty, u.name as university_name
    FROM UserAcademicProfile uap
    JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
    JOIN University u ON ad.university_id = u.university_id
    WHERE uap.user_id = ?
    ORDER BY uap.graduation_year DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profiles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Academic Profile Manager</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f4f6f8;margin:0;padding:20px}
        .container{max-width:1000px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
        h1{color:#333;margin:0 0 10px 0}
        .breadcrumb{color:#666;margin-bottom:20px}
        .breadcrumb a{color:#667eea;text-decoration:none}
        .section{margin:30px 0;padding:20px;background:#f8f9fa;border-radius:6px}
        .form-group{margin-bottom:15px}
        label{display:block;font-weight:600;margin-bottom:5px;color:#333}
        input,select{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px}
        button{background:#667eea;color:#fff;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:600;font-size:14px}
        button:hover{background:#5568d3}
        button.danger{background:#e74c3c}
        button.danger:hover{background:#c0392b}
        button.success{background:#27ae60}
        button.success:hover{background:#229954}
        table{width:100%;border-collapse:collapse;margin-top:15px}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}
        th{background:#f8f9fa;font-weight:600;color:#333}
        .info-box{background:#e3f2fd;padding:15px;border-left:4px solid #2196f3;margin:20px 0;border-radius:4px}
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a> / 
            Academic Profile Manager
        </div>
        
        <h1><i class="fas fa-university"></i> Academic Profile Manager</h1>
        <p style="color:#666">Managing academic profiles for: <strong><?php echo htmlspecialchars($userName); ?></strong></p>
        
        <?php echo $message; ?>
        
        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Important:</strong> 
            Your graduation year determines which year groups you'll be added to. Make sure to set it correctly!
        </div>
        
        <?php if ($user_university): ?>
        <div class="info-box" style="background:#fff3cd;border-left-color:#ffc107">
            <strong><i class="fas fa-university"></i> Note:</strong> 
            Departments shown are from your registered university. All your academic profiles must be from the same university.
        </div>
        <?php endif; ?>
        
        <!-- Add New Profile -->
        <div class="section">
            <h2 style="margin-top:0">Add Academic Profile</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Department *</label>
                    <select name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php 
                        if ($user_university) {
                            // Single university - no need for optgroups
                            foreach ($departments as $dept): 
                        ?>
                            <option value="<?php echo $dept['department_id']; ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                <?php echo $dept['faculty'] ? ' - ' . htmlspecialchars($dept['faculty']) : ''; ?>
                            </option>
                        <?php 
                            endforeach;
                        } else {
                            // Multiple universities - use optgroups
                            $current_uni = '';
                            foreach ($departments as $dept): 
                                if ($current_uni !== $dept['university_name']) {
                                    if ($current_uni !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($dept['university_name']) . '">';
                                    $current_uni = $dept['university_name'];
                                }
                        ?>
                            <option value="<?php echo $dept['department_id']; ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                                <?php echo $dept['faculty'] ? ' - ' . htmlspecialchars($dept['faculty']) : ''; ?>
                            </option>
                        <?php 
                            endforeach; 
                            if ($current_uni !== '') echo '</optgroup>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Graduation Year * (Important for year groups!)</label>
                    <input type="number" name="graduation_year" min="1950" max="2050" placeholder="e.g., 2024" required>
                </div>
                
                <div class="form-group">
                    <label>Degree (Optional)</label>
                    <input type="text" name="degree" placeholder="e.g., Bachelor of Science">
                </div>
                
                <button type="submit" name="add_profile" class="success">
                    <i class="fas fa-plus"></i> Add Profile
                </button>
            </form>
        </div>
        
        <!-- Existing Profiles -->
        <div class="section">
            <h2 style="margin-top:0">Your Academic Profiles</h2>
            
            <?php if (empty($profiles)): ?>
                <p style="color:#999;text-align:center;padding:40px">
                    <i class="fas fa-inbox" style="font-size:3rem;opacity:0.3"></i><br><br>
                    No academic profiles yet. Add one above to get started.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>University</th>
                            <th>Department</th>
                            <th>Faculty</th>
                            <th>Graduation Year</th>
                            <th>Degree</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $profile): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($profile['university_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($profile['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($profile['faculty'] ?: '-'); ?></td>
                                <td>
                                    <strong style="color:#667eea">
                                        <?php echo $profile['graduation_year'] ?: '<em style="color:#999">Not set</em>'; ?>
                                    </strong>
                                </td>
                                <td><?php echo htmlspecialchars($profile['degree'] ?: '-'); ?></td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this profile?')">
                                        <input type="hidden" name="profile_id" value="<?php echo $profile['profile_id']; ?>">
                                        <button type="submit" name="delete_profile" class="danger" style="padding:6px 12px;font-size:12px">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Quick Create Department (if needed) -->
        <details style="margin-top:20px">
            <summary style="cursor:pointer;font-weight:600;padding:10px;background:#fff3cd;border-radius:4px">
                <i class="fas fa-plus-circle"></i> Don't see your department? Create it here
            </summary>
            <div style="padding:20px;background:#fff9e6;margin-top:10px;border-radius:4px">
                <form method="POST" action="create_department.php" target="_blank">
                    <p><strong>This will open a new page to create a department.</strong></p>
                    <button type="submit" style="background:#f57c00">
                        <i class="fas fa-external-link-alt"></i> Open Department Creator
                    </button>
                </form>
            </div>
        </details>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px solid #eee;text-align:center">
            <p style="color:#666">After adding profiles, go to:</p>
            <a href="alumni_verification.php" style="display:inline-block;background:#667eea;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;margin:5px">
                <i class="fas fa-graduation-cap"></i> Manage Verifications
            </a>
            <a href="group_setup.php" style="display:inline-block;background:#27ae60;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;margin:5px">
                <i class="fas fa-users"></i> Setup Groups
            </a>
        </div>
    </div>
</body>
</html>

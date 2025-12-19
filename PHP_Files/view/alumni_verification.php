<?php
// Quick Alumni Verification Management
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

// Get user's university and graduation year from their academic profile
$user_university = null;
$user_grad_year = null;
$stmt = $conn->prepare("
    SELECT ad.university_id, uap.graduation_year
    FROM UserAcademicProfile uap
    JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
    WHERE uap.user_id = ?
    ORDER BY uap.graduation_year DESC
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_university = (int)$row['university_id'];
    $user_grad_year = $row['graduation_year'] ? (int)$row['graduation_year'] : null;
}
$stmt->close();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_verification'])) {
        $uni_id = (int)$_POST['university_id'];
        $grad_year = !empty($_POST['graduation_year']) ? (int)$_POST['graduation_year'] : null;
        $student_id = !empty($_POST['student_id_number']) ? $_POST['student_id_number'] : null;
        $status = 'pending'; // Always set to pending for admin approval
        
        $stmt = $conn->prepare("INSERT INTO AlumniVerification (user_id, university_id, graduation_year, student_id_number, verification_status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iiiss', $user_id, $uni_id, $grad_year, $student_id, $status);
        
        if ($stmt->execute()) {
            $message = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0'>✅ Verification request submitted! Awaiting admin approval.</div>";
        } else {
            $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } elseif (isset($_POST['delete_verification'])) {
        $ver_id = (int)$_POST['verification_id'];
        $stmt = $conn->prepare("DELETE FROM AlumniVerification WHERE verification_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $ver_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "<div style='background:#fff3cd;color:#856404;padding:15px;border-radius:5px;margin:10px 0'>🗑️ Verification deleted</div>";
    }
}

// Get all universities
$universities = [];
$result = $conn->query("SELECT university_id, name, location FROM University ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $universities[] = $row;
}

// Get user's verifications
$stmt = $conn->prepare("
    SELECT av.*, u.name as university_name, u.location 
    FROM AlumniVerification av 
    JOIN University u ON av.university_id = u.university_id 
    WHERE av.user_id = ?
    ORDER BY av.verification_id DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$verifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alumni Verification Manager</title>
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
        .badge{display:inline-block;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600}
        .badge.pending{background:#fff3cd;color:#856404}
        .badge.approved{background:#d4edda;color:#155724}
        .badge.rejected{background:#f8d7da;color:#721c24}
        .actions{display:flex;gap:8px}
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a> / 
            Alumni Verification Manager
        </div>
        
        <h1><i class="fas fa-graduation-cap"></i> Alumni Verification Manager</h1>
        <p style="color:#666">Managing verifications for: <strong><?php echo htmlspecialchars($userName); ?></strong></p>
        
        <?php echo $message; ?>
        
        <!-- Add New Verification -->
        <div class="section">
            <h2 style="margin-top:0">Add New Verification</h2>
            <?php if ($user_university): ?>
            <div style="background:#e3f2fd;padding:15px;border-left:4px solid #2196f3;margin-bottom:20px;border-radius:4px">
                <strong><i class="fas fa-info-circle"></i> Auto-filled:</strong> 
                Your university and graduation year have been automatically selected from your academic profile.
            </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>University *</label>
                    <select name="university_id" required>
                        <option value="">-- Select University --</option>
                        <?php foreach ($universities as $uni): ?>
                            <option value="<?php echo $uni['university_id']; ?>"
                                <?php echo ($user_university && $uni['university_id'] == $user_university) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uni['name']); ?> 
                                <?php echo $uni['location'] ? '(' . htmlspecialchars($uni['location']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Graduation Year (Optional)</label>
                    <input type="number" name="graduation_year" min="1950" max="2050" 
                           placeholder="e.g., 2020" 
                           value="<?php echo $user_grad_year ? htmlspecialchars($user_grad_year) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Student ID Number (Optional)</label>
                    <input type="text" name="student_id_number" placeholder="Your student ID">
                </div>
                
                <button type="submit" name="add_verification" class="success">
                    <i class="fas fa-plus"></i> Submit Verification Request
                </button>
                <p style="color:#666;margin-top:10px;font-size:0.9rem">
                    <i class="fas fa-info-circle"></i> Your request will be sent to university administrators for approval.
                </p>
            </form>
        </div>
        
        <!-- Existing Verifications -->
        <div class="section">
            <h2 style="margin-top:0">Your Verifications</h2>
            
            <?php if (empty($verifications)): ?>
                <p style="color:#999;text-align:center;padding:40px">
                    <i class="fas fa-inbox" style="font-size:3rem;opacity:0.3"></i><br><br>
                    No verifications yet. Add one above to get started.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>University</th>
                            <th>Graduation Year</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($verifications as $ver): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ver['university_name']); ?></strong><br>
                                    <small style="color:#666"><?php echo htmlspecialchars($ver['location'] ?: ''); ?></small>
                                </td>
                                <td><?php echo $ver['graduation_year'] ?: '<em style="color:#999">Not set</em>'; ?></td>
                                <td><?php echo htmlspecialchars($ver['student_id_number'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $ver['verification_status']; ?>">
                                        <?php echo ucfirst($ver['verification_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($ver['verified_at']) ? date('M j, Y', strtotime($ver['verified_at'])) : '<em style="color:#999">Pending</em>'; ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this verification request?')">
                                            <input type="hidden" name="verification_id" value="<?php echo $ver['verification_id']; ?>">
                                            <button type="submit" name="delete_verification" class="danger" style="padding:6px 12px;font-size:12px">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="margin-top:30px;padding-top:20px;border-top:2px solid #eee;text-align:center">
            <p style="color:#666">After adding verifications, go to:</p>
            <a href="academic_profile.php" style="display:inline-block;background:#667eea;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;margin:5px">
                <i class="fas fa-university"></i> Manage Academic Profile
            </a>
            <a href="group_setup.php" style="display:inline-block;background:#27ae60;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;margin:5px">
                <i class="fas fa-users"></i> Setup Groups
            </a>
        </div>
    </div>
</body>
</html>

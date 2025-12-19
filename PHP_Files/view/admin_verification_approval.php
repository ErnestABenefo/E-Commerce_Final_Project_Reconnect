<?php
// Admin Verification Approval Page
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];
$db = new db_connection();
$conn = $db->db_conn();

// Check if user is admin (you can modify this logic based on your admin system)
// For now, checking if user is university admin
$is_admin = false;
$stmt = $conn->prepare("SELECT COUNT(*) as is_admin FROM UniversityAdmins WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_check = $result->fetch_assoc();
    $stmt->close();
    $is_admin = $admin_check['is_admin'] > 0;
} else {
    // UniversityAdmin table doesn't exist - check if user created any university
    $stmt = $conn->prepare("SELECT COUNT(*) as is_creator FROM University WHERE created_by = ?");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $creator_check = $result->fetch_assoc();
        $stmt->close();
        $is_admin = $creator_check && $creator_check['is_creator'] > 0;
    }
}

if (!$is_admin) {
    die("<h1>Access Denied</h1><p>You must be an administrator to access this page.</p><p><a href='dashboard.php'>Back to Dashboard</a></p>");
}

// Get admin's universities
$admin_universities = [];
$stmt = $conn->prepare("SELECT university_id FROM UniversityAdmins WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admin_universities[] = $row['university_id'];
    }
    $stmt->close();
} else {
    // If UniversityAdmin doesn't exist, get universities created by this user
    $stmt = $conn->prepare("SELECT university_id FROM University WHERE created_by = ?");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $admin_universities[] = $row['university_id'];
        }
        $stmt->close();
    } else {
        // If no created_by column, show all universities (fallback)
        $result = $conn->query("SELECT university_id FROM University");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $admin_universities[] = $row['university_id'];
            }
        }
    }
}

// Handle approval/rejection actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_verification'])) {
        $ver_id = (int)$_POST['verification_id'];
        
        // Debug: Check if we have the verification ID
        if ($ver_id <= 0) {
            $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: Invalid verification ID</div>";
        } else {
            // First check if the verification exists
            $check_stmt = $conn->prepare("SELECT verification_status FROM AlumniVerification WHERE verification_id = ?");
            $check_stmt->bind_param('i', $ver_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $existing = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if (!$existing) {
                $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: Verification ID $ver_id not found in database</div>";
            } elseif ($existing['verification_status'] === 'approved') {
                $message = "<div style='background:#fff3cd;color:#856404;padding:15px;border-radius:5px;margin:10px 0'>⚠️ This verification is already approved</div>";
            } else {
                // Now update it
                $stmt = $conn->prepare("UPDATE AlumniVerification SET verification_status = 'approved', verified_at = NOW() WHERE verification_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $ver_id);
                    if ($stmt->execute()) {
                        $affected = $stmt->affected_rows;
                        $stmt->close();
                        if ($affected > 0) {
                            // Redirect to prevent form resubmission and show updated data
                            header('Location: admin_verification_approval.php?approved=1&id=' . $ver_id);
                            exit();
                        } else {
                            $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: No rows were updated (ID: $ver_id)</div>";
                        }
                    } else {
                        $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ SQL Error: " . $stmt->error . "</div>";
                        $stmt->close();
                    }
                } else {
                    $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Database prepare error: " . $conn->error . "</div>";
                }
            }
        }
    } elseif (isset($_POST['reject_verification'])) {
        $ver_id = (int)$_POST['verification_id'];
        $stmt = $conn->prepare("UPDATE AlumniVerification SET verification_status = 'rejected' WHERE verification_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $ver_id);
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                $stmt->close();
                if ($affected > 0) {
                    header('Location: admin_verification_approval.php?rejected=1');
                    exit();
                } else {
                    $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: Verification not found</div>";
                }
            } else {
                $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Error: " . $stmt->error . "</div>";
                $stmt->close();
            }
        } else {
            $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Database error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST['bulk_approve'])) {
        if (!empty($_POST['selected_verifications'])) {
            $selected = json_decode($_POST['selected_verifications'], true);
            if (is_array($selected) && count($selected) > 0) {
                $placeholders = implode(',', array_fill(0, count($selected), '?'));
                $stmt = $conn->prepare("UPDATE AlumniVerification SET verification_status = 'approved', verified_at = NOW() WHERE verification_id IN ($placeholders)");
                $types = str_repeat('i', count($selected));
                $stmt->bind_param($types, ...$selected);
                $stmt->execute();
                $count = $stmt->affected_rows;
                $stmt->close();
                $message = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0'>✅ Approved $count verification(s)</div>";
            }
        }
    }
}

// Get current user info
$stmt = $conn->prepare("SELECT first_name, last_name FROM Users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$userName = $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Admin';

// Show success messages from redirects
if (isset($_GET['approved'])) {
    $message = "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:10px 0'>✅ Verification approved successfully!</div>";
} elseif (isset($_GET['rejected'])) {
    $message = "<div style='background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin:10px 0'>❌ Verification rejected</div>";
}

// Get pending verifications for admin's universities
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$allowed_filters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $allowed_filters)) $filter = 'pending';

if (empty($admin_universities)) {
    $verifications = [];
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
} else {
    $uni_placeholders = implode(',', array_fill(0, count($admin_universities), '?'));
    $sql = "
        SELECT av.verification_id, av.user_id, av.university_id, av.graduation_year, 
               av.student_id_number, av.verification_status, av.verified_at,
               u.first_name, u.last_name, u.email, u.phone,
               uni.name as university_name, uni.location
        FROM AlumniVerification av
        JOIN Users u ON av.user_id = u.user_id
        JOIN University uni ON av.university_id = uni.university_id
        WHERE av.university_id IN ($uni_placeholders)
    ";

    if ($filter !== 'all') {
        $sql .= " AND av.verification_status = ?";
    }

    $sql .= " ORDER BY av.verification_id DESC";

    $stmt = $conn->prepare($sql);

    if ($filter !== 'all') {
        $types = str_repeat('i', count($admin_universities)) . 's';
        $params = array_merge($admin_universities, [$filter]);
        $stmt->bind_param($types, ...$params);
    } else {
        $types = str_repeat('i', count($admin_universities));
        $stmt->bind_param($types, ...$admin_universities);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $verifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get statistics
    $stmt = $conn->prepare("SELECT verification_status, COUNT(*) as count FROM AlumniVerification WHERE university_id IN ($uni_placeholders) GROUP BY verification_status");
    $types = str_repeat('i', count($admin_universities));
    $stmt->bind_param($types, ...$admin_universities);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    while ($row = $result->fetch_assoc()) {
        $stats[$row['verification_status']] = $row['count'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verification Approval - Admin</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f4f6f8;margin:0;padding:20px}
        .container{max-width:1400px;margin:0 auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
        h1{color:#333;margin:0 0 10px 0}
        .breadcrumb{color:#666;margin-bottom:20px}
        .breadcrumb a{color:#667eea;text-decoration:none}
        .stats{display:flex;gap:20px;margin:20px 0;flex-wrap:wrap}
        .stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:20px;border-radius:8px;flex:1;min-width:200px}
        .stat-card.pending{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%)}
        .stat-card.approved{background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)}
        .stat-card.rejected{background:linear-gradient(135deg,#fa709a 0%,#fee140 100%)}
        .stat-card .number{font-size:2.5rem;font-weight:700;margin:10px 0}
        .stat-card .label{font-size:0.9rem;opacity:0.9}
        .filters{margin:20px 0;padding:15px;background:#f8f9fa;border-radius:6px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .filter-btn{padding:8px 16px;border:2px solid transparent;background:#fff;border-radius:6px;cursor:pointer;font-weight:600;text-decoration:none;color:#333;transition:all 0.2s}
        .filter-btn:hover{background:#667eea;color:#fff}
        .filter-btn.active{background:#667eea;color:#fff;border-color:#667eea}
        table{width:100%;border-collapse:collapse;margin-top:15px}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}
        th{background:#f8f9fa;font-weight:600;color:#333;position:sticky;top:0}
        .badge{display:inline-block;padding:4px 10px;border-radius:4px;font-size:12px;font-weight:600}
        .badge.pending{background:#fff3cd;color:#856404}
        .badge.approved{background:#d4edda;color:#155724}
        .badge.rejected{background:#f8d7da;color:#721c24}
        .user-info{display:flex;align-items:center;gap:10px}
        .user-avatar{width:40px;height:40px;border-radius:50%;background:#667eea;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700}
        .actions{display:flex;gap:8px}
        button{border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-weight:600;font-size:13px;transition:all 0.2s}
        button.approve{background:#27ae60;color:#fff}
        button.approve:hover{background:#229954}
        button.reject{background:#e74c3c;color:#fff}
        button.reject:hover{background:#c0392b}
        button.view{background:#667eea;color:#fff}
        button.view:hover{background:#5568d3}
        .bulk-actions{padding:15px;background:#fff3cd;border-radius:6px;margin:20px 0;display:none}
        .bulk-actions.show{display:block}
        .empty-state{text-align:center;padding:60px 20px;color:#999}
        .empty-state i{font-size:4rem;opacity:0.3;margin-bottom:20px}
        .detail-modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center}
        .detail-modal.show{display:flex}
        .modal-content{background:#fff;padding:30px;border-radius:12px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto}
        .modal-close{float:right;font-size:1.5rem;cursor:pointer;color:#999}
        .info-row{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #eee}
        .info-row label{font-weight:600;color:#666}
        .info-row value{color:#333}
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a> / Admin Verification Approval
        </div>
        
        <h1><i class="fas fa-user-check"></i> Alumni Verification Approval</h1>
        <p style="color:#666">Administrator: <strong><?php echo htmlspecialchars($userName); ?></strong></p>
        
        <?php echo $message; ?>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card pending">
                <div class="label"><i class="fas fa-clock"></i> Pending Review</div>
                <div class="number"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card approved">
                <div class="label"><i class="fas fa-check-circle"></i> Approved</div>
                <div class="number"><?php echo $stats['approved']; ?></div>
            </div>
            <div class="stat-card rejected">
                <div class="label"><i class="fas fa-times-circle"></i> Rejected</div>
                <div class="number"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <strong>Filter:</strong>
            <a href="?status=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> Pending (<?php echo $stats['pending']; ?>)
            </a>
            <a href="?status=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Approved (<?php echo $stats['approved']; ?>)
            </a>
            <a href="?status=rejected" class="filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                <i class="fas fa-times"></i> Rejected (<?php echo $stats['rejected']; ?>)
            </a>
            <a href="?status=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All
            </a>
        </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST">
                <strong><span id="selectedCount">0</span> verification(s) selected</strong>
                <button type="submit" name="bulk_approve" class="approve" onclick="return confirm('Approve all selected verifications?')">
                    <i class="fas fa-check"></i> Approve Selected
                </button>
                <button type="button" onclick="clearSelection()" style="background:#6c757d;color:#fff">
                    <i class="fas fa-times"></i> Clear
                </button>
                <input type="hidden" name="selected_verifications" id="selectedInput">
            </form>
        </div>
        
        <!-- Verifications Table -->
        <?php if (empty($verifications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No verifications found</h3>
                <p>There are no <?php echo $filter === 'all' ? '' : $filter; ?> verification requests at this time.</p>
            </div>
        <?php else: ?>
            <form id="verificationsForm">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>User</th>
                            <th>University</th>
                            <th>Graduation Year</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Verified At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($verifications as $ver): ?>
                            <tr>
                                <td><input type="checkbox" class="verify-checkbox" value="<?php echo $ver['verification_id']; ?>"></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($ver['first_name'], 0, 1) . substr($ver['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($ver['first_name'] . ' ' . $ver['last_name']); ?></strong><br>
                                            <small style="color:#666"><?php echo htmlspecialchars($ver['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
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
                                <td><?php echo $ver['verified_at'] ? date('M j, Y', strtotime($ver['verified_at'])) : '<em style="color:#999">-</em>'; ?></td>
                                <td>
                                    <div class="actions">
                                        <button type="button" class="view" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($ver)); ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($ver['verification_status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="verification_id" value="<?php echo $ver['verification_id']; ?>">
                                                <button type="submit" name="approve_verification" class="approve" onclick="return confirm('Approve this verification?')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="verification_id" value="<?php echo $ver['verification_id']; ?>">
                                                <button type="submit" name="reject_verification" class="reject" onclick="return confirm('Reject this verification?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Detail Modal -->
    <div class="detail-modal" id="detailModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 style="margin-top:0"><i class="fas fa-user"></i> Verification Details</h2>
            <div id="modalBody"></div>
        </div>
    </div>
    
    <script>
        // Select all checkbox
        document.getElementById('selectAll')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.verify-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
        
        // Individual checkboxes
        document.querySelectorAll('.verify-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });
        
        function updateBulkActions() {
            const checked = document.querySelectorAll('.verify-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const selectedInput = document.getElementById('selectedInput');
            
            if (checked.length > 0) {
                bulkActions.classList.add('show');
                selectedCount.textContent = checked.length;
                const ids = Array.from(checked).map(cb => cb.value);
                selectedInput.value = JSON.stringify(ids);
            } else {
                bulkActions.classList.remove('show');
            }
        }
        
        function clearSelection() {
            document.querySelectorAll('.verify-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }
        
        function viewDetails(data) {
            const modal = document.getElementById('detailModal');
            const body = document.getElementById('modalBody');
            
            body.innerHTML = `
                <div class="info-row">
                    <label>User Name:</label>
                    <value>${data.first_name} ${data.last_name}</value>
                </div>
                <div class="info-row">
                    <label>Email:</label>
                    <value>${data.email}</value>
                </div>
                <div class="info-row">
                    <label>Phone:</label>
                    <value>${data.phone || 'Not provided'}</value>
                </div>
                <div class="info-row">
                    <label>University:</label>
                    <value>${data.university_name}</value>
                </div>
                <div class="info-row">
                    <label>Location:</label>
                    <value>${data.location || 'Not specified'}</value>
                </div>
                <div class="info-row">
                    <label>Graduation Year:</label>
                    <value>${data.graduation_year || 'Not set'}</value>
                </div>
                <div class="info-row">
                    <label>Student ID:</label>
                    <value>${data.student_id_number || 'Not provided'}</value>
                </div>
                <div class="info-row">
                    <label>Status:</label>
                    <value><span class="badge ${data.verification_status}">${data.verification_status.toUpperCase()}</span></value>
                </div>
                <div class="info-row">
                    <label>Verified At:</label>
                    <value>${data.verified_at || 'Pending'}</value>
                </div>
            `;
            
            modal.classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }
        
        // Close modal on outside click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>

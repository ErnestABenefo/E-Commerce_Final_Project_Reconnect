<?php
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../controllers/university_admin_controller.php';
require_once '../../settings/db_class.php';

// Clear any accidental output
ob_clean();
header('Content-Type: application/json');

// Check global admin permission
$current_user = $_SESSION['user_id'] ?? null;
if (!is_global_admin_ctr($current_user)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$ua_id = $input['ua_id'] ?? null;
$status = $input['status'] ?? null;

if (!$ua_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate status
if (!in_array($status, ['active', 'suspended', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Update admin status
    $stmt = $conn->prepare("UPDATE UniversityAdmins SET status = ?, updated_at = NOW() WHERE ua_id = ?");
    $stmt->bind_param('si', $status, $ua_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update admin status']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

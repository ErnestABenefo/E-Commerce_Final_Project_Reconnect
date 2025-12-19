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

if (!$ua_id) {
    echo json_encode(['success' => false, 'message' => 'Missing admin ID']);
    exit;
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Delete admin record
    $stmt = $conn->prepare("DELETE FROM UniversityAdmins WHERE ua_id = ?");
    $stmt->bind_param('i', $ua_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove admin']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

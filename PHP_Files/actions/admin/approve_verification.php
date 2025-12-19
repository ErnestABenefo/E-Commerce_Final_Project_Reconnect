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
$verification_id = $input['verification_id'] ?? null;

if (!$verification_id) {
    echo json_encode(['success' => false, 'message' => 'Missing verification ID']);
    exit;
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Update verification status
    $stmt = $conn->prepare("UPDATE AlumniVerification SET verification_status = 'approved', updated_at = NOW() WHERE verification_id = ?");
    $stmt->bind_param('i', $verification_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Verification approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve verification']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

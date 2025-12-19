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
$email = $input['email'] ?? null;
$university_id = $input['university_id'] ?? null;
$role = $input['role'] ?? 'admin';

if (!$email || !$university_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate role
if (!in_array($role, ['admin', 'superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Find user by email
    $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found with this email']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    $stmt->close();
    
    // Check if already admin for this university
    $stmt = $conn->prepare("SELECT ua_id FROM UniversityAdmins WHERE user_id = ? AND university_id = ?");
    $stmt->bind_param('ii', $user_id, $university_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'User is already an admin for this university']);
        exit;
    }
    $stmt->close();
    
    // Assign admin role
    $stmt = $conn->prepare("INSERT INTO UniversityAdmins (user_id, university_id, role, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param('iis', $user_id, $university_id, $role);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin assigned successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to assign admin role']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

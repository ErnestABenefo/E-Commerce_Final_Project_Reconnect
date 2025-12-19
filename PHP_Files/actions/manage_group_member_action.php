<?php
// Manage Group Member Actions (Promote to Admin / Remove from Group)
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

header('Content-Type: application/json');

// Get action type
$action = $_POST['action'] ?? '';
$group_id = (int)($_POST['group_id'] ?? 0);
$target_user_id = (int)($_POST['target_user_id'] ?? 0);
$current_user_id = (int)$_SESSION['user_id'];

if (!$group_id || !$target_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Check if current user is an admin of this group
$admin_check = $conn->prepare("SELECT is_admin FROM GroupMembers WHERE group_id = ? AND user_id = ?");
$admin_check->bind_param("ii", $group_id, $current_user_id);
$admin_check->execute();
$admin_result = $admin_check->get_result();
$admin_data = $admin_result->fetch_assoc();
$admin_check->close();

if (!$admin_data || (int)$admin_data['is_admin'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'You do not have permission to perform this action']);
    exit();
}

// Prevent users from removing themselves
if ($action === 'remove' && $target_user_id === $current_user_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot remove yourself from the group']);
    exit();
}

// Handle different actions
switch ($action) {
    case 'promote':
        // Promote user to admin
        $stmt = $conn->prepare("UPDATE GroupMembers SET is_admin = 1 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'success', 'message' => 'User promoted to admin successfully']);
        } else {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'error', 'message' => 'Failed to promote user or user already an admin']);
        }
        break;
        
    case 'demote':
        // Demote user from admin (optional feature)
        $stmt = $conn->prepare("UPDATE GroupMembers SET is_admin = 0 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'success', 'message' => 'Admin privileges removed successfully']);
        } else {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'error', 'message' => 'Failed to demote user or user is not an admin']);
        }
        break;
        
    case 'remove':
        // Remove user from group
        $stmt = $conn->prepare("DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'success', 'message' => 'User removed from group successfully']);
        } else {
            $stmt->close();
            $conn->close();
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove user from group']);
        }
        break;
        
    default:
        $conn->close();
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

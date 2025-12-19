<?php
// Action to get user's groups
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/group_controller.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

try {
    $groups = get_user_groups_ctr($user_id);
    
    echo json_encode([
        'status' => 'success',
        'groups' => $groups
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load groups: ' . $e->getMessage()
    ]);
}
?>

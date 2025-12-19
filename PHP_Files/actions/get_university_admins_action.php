<?php

header('Content-Type: application/json');

require_once '../controllers/university_admin_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request method'));
    exit;
}

// Check session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
        echo json_encode(array('status' => 'error', 'message' => 'User not authenticated'));
        exit;
    }
}

// Get university_id parameter
$university_id = isset($_GET['university_id']) ? (int)$_GET['university_id'] : 0;

// Validate input
if ($university_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid university ID'));
    exit;
}

// Check if current user is admin
$current_user_id = $_SESSION['user_id'];
if (!is_university_admin_ctr($university_id, $current_user_id)) {
    echo json_encode(array('status' => 'error', 'message' => 'You do not have permission to view admins'));
    exit;
}

// Get all admins for the university
$admins = get_admins_by_university_ctr($university_id);

if ($admins === false) {
    echo json_encode(array('status' => 'error', 'message' => 'Failed to retrieve admins'));
} else {
    echo json_encode(array(
        'status' => 'success',
        'admins' => $admins,
        'count' => count($admins)
    ));
}

exit;

?>

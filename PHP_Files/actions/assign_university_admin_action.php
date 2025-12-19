<?php

header('Content-Type: application/json');

require_once '../controllers/university_admin_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Get required fields
$university_id = isset($_POST['university_id']) ? (int)$_POST['university_id'] : 0;
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$role = isset($_POST['role']) ? sanitize_admin_input_ctr($_POST['role']) : 'admin';
$current_user_id = $_SESSION['user_id'];

// Validate inputs
if ($university_id <= 0 || $user_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid university or user ID'));
    exit;
}

if ($user_id === $current_user_id) {
    echo json_encode(array('status' => 'error', 'message' => 'You cannot assign yourself as admin'));
    exit;
}

// Assign admin
$result = assign_university_admin_ctr($university_id, $user_id, $role, $current_user_id);

if ($result['success']) {
    echo json_encode(array(
        'status' => 'success',
        'message' => $result['message'],
        'admin_id' => $result['admin_id']
    ));
} else {
    echo json_encode(array(
        'status' => 'error',
        'message' => $result['message']
    ));
}

exit;

?>

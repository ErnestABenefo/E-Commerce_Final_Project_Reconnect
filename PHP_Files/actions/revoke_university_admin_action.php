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
$current_user_id = $_SESSION['user_id'];

// Validate inputs
if ($university_id <= 0 || $user_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid university or user ID'));
    exit;
}

// Revoke admin
$result = revoke_university_admin_ctr($university_id, $user_id, $current_user_id);

if ($result['success']) {
    echo json_encode(array(
        'status' => 'success',
        'message' => $result['message']
    ));
} else {
    echo json_encode(array(
        'status' => 'error',
        'message' => $result['message']
    ));
}

exit;

?>

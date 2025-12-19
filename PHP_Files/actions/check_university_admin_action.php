<?php

header('Content-Type: application/json');

require_once '../controllers/university_admin_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Get parameters
$university_id = isset($_GET['university_id']) || isset($_POST['university_id']) 
    ? (int)(isset($_GET['university_id']) ? $_GET['university_id'] : $_POST['university_id'])
    : 0;
$user_id = isset($_GET['user_id']) || isset($_POST['user_id']) 
    ? (int)(isset($_GET['user_id']) ? $_GET['user_id'] : $_POST['user_id'])
    : 0;

// If not specified, use current user
if ($user_id <= 0) {
    $user_id = $_SESSION['user_id'];
}

// Validate inputs
if ($university_id <= 0) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid university ID'));
    exit;
}

// Check admin status
$is_admin = is_university_admin_ctr($university_id, $user_id);
$admin_info = get_admin_info_ctr($university_id, $user_id);

echo json_encode(array(
    'status' => 'success',
    'is_admin' => $is_admin,
    'admin_info' => $admin_info ? $admin_info : null
));

exit;

?>

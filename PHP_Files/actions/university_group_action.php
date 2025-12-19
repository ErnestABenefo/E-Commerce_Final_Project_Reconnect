<?php

header('Content-Type: application/json');
session_start();

// Set error handler to catch any PHP errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $response;
    $response['status'] = 'error';
    $response['message'] = "PHP Error: $errstr (in $errfile:$errline)";
    echo json_encode($response);
    exit();
});

// Catch exceptions
set_exception_handler(function($exception) {
    global $response;
    $response['status'] = 'error';
    $response['message'] = "Exception: " . $exception->getMessage();
    echo json_encode($response);
    exit();
});

$response = array();

require_once '../controllers/university_content_controller.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'error';
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'read';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a university group
    $university_id = isset($_POST['university_id']) ? (int)$_POST['university_id'] : null;
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $group_type = isset($_POST['group_type']) ? $_POST['group_type'] : 'official';

    if (!$university_id) {
        $response['status'] = 'error';
        $response['message'] = 'University ID is required';
        echo json_encode($response);
        exit();
    }

    $result = create_university_group_ctr($university_id, $user_id, $name, $description, $group_type);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Group created successfully';
        $response['group_id'] = $result;
    }
} elseif ($action === 'read' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get groups for a university
    $university_id = isset($_GET['university_id']) ? (int)$_GET['university_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if (!$university_id) {
        $response['status'] = 'error';
        $response['message'] = 'University ID is required';
        echo json_encode($response);
        exit();
    }

    $groups = get_university_groups_ctr($university_id, $limit, $offset);
    $response['status'] = 'success';
    $response['groups'] = $groups;
    $response['count'] = count($groups);
} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update a university group
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $group_type = isset($_POST['group_type']) ? $_POST['group_type'] : 'official';

    if (!$group_id) {
        $response['status'] = 'error';
        $response['message'] = 'Group ID is required';
        echo json_encode($response);
        exit();
    }

    $result = update_university_group_ctr($group_id, $user_id, $name, $description, $group_type);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Group updated successfully';
    }
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete a university group
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;

    if (!$group_id) {
        $response['status'] = 'error';
        $response['message'] = 'Group ID is required';
        echo json_encode($response);
        exit();
    }

    $result = delete_university_group_ctr($group_id, $user_id);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Group deleted successfully';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid action or request method';
}

echo json_encode($response);
exit();

?>

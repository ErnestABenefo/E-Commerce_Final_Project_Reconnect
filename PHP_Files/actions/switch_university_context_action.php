<?php

header('Content-Type: application/json');

// Catch any errors and return as JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $response = [
        'status' => 'error',
        'message' => "PHP Error: $errstr in $errfile line $errline"
    ];
    echo json_encode($response);
    exit();
});

session_start();

$response = array();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['status'] = 'error';
    $response['message'] = 'You must be logged in to perform this action';
    echo json_encode($response);
    exit();
}

require_once __DIR__ . '/../controllers/user_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Check required action parameter
if (!isset($_POST['action']) || empty($_POST['action'])) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required field: action';
    echo json_encode($response);
    exit();
}

$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

// Handle different actions
if ($action === 'switch_to_university') {
    // Validate university_id
    if (!isset($_POST['university_id']) || !is_numeric($_POST['university_id'])) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid or missing university_id';
        echo json_encode($response);
        exit();
    }
    
    $university_id = (int)$_POST['university_id'];
    
    // Attempt to switch context
    if (switch_to_university_context_ctr($university_id)) {
        $response['status'] = 'success';
        $response['message'] = 'Successfully switched to university context';
        $response['context'] = [
            'acting_as_university' => true,
            'university_id' => $_SESSION['active_university_id'],
            'university_name' => $_SESSION['active_university_name']
        ];
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to switch to university context. You may not have permission to manage this university.';
    }
    
} elseif ($action === 'switch_to_personal') {
    // Switch back to personal context
    if (switch_to_personal_context_ctr()) {
        $response['status'] = 'success';
        $response['message'] = 'Successfully switched to personal context';
        $response['context'] = [
            'acting_as_university' => false,
            'university_id' => null,
            'university_name' => null
        ];
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to switch to personal context';
    }
    
} elseif ($action === 'get_my_universities') {
    // Get list of universities the user can manage
    $universities = get_user_universities_ctr($user_id);
    
    if ($universities && count($universities) > 0) {
        $response['status'] = 'success';
        $response['universities'] = $universities;
        $response['current_context'] = [
            'acting_as_university' => is_acting_as_university_ctr(),
            'active_university_id' => $_SESSION['active_university_id'] ?? null,
            'active_university_name' => $_SESSION['active_university_name'] ?? null
        ];
    } else {
        $response['status'] = 'success';
        $response['universities'] = [];
        $response['message'] = 'You are not an admin of any universities';
    }
    
} elseif ($action === 'get_current_context') {
    // Return current context information
    $response['status'] = 'success';
    $response['context'] = [
        'acting_as_university' => is_acting_as_university_ctr(),
        'active_university_id' => $_SESSION['active_university_id'] ?? null,
        'active_university_name' => $_SESSION['active_university_name'] ?? null,
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    ];
    
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid action specified';
}

echo json_encode($response);
exit();

?>

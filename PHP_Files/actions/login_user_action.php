<?php

header('Content-Type: application/json');

session_start();

$response = array();

// Check if the user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['status'] = 'error';
    $response['message'] = 'You are already logged in';
    // Build a redirect path that includes the project folder (handles spaces/locations)
    $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $response['redirect'] = $basePath . '/view/homepage.php';
    echo json_encode($response);
    exit();
}

require_once '../controllers/user_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Check if all required fields are present
$required_fields = array('email', 'password');
$missing_fields = array();

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required fields: ' . implode(', ', $missing_fields);
    echo json_encode($response);
    exit();
}

// Check if account is locked due to failed attempts
if (isset($_SESSION['login_locked_until'])) {
    if (time() < $_SESSION['login_locked_until']) {
        $remaining_time = ceil(($_SESSION['login_locked_until'] - time()) / 60);
        $response['status'] = 'error';
        $response['message'] = "Account temporarily locked. Please try again in {$remaining_time} minutes.";
        echo json_encode($response);
        exit();
    } else {
        // Lock period expired, reset
        unset($_SESSION['login_locked_until']);
        unset($_SESSION['failed_login_attempts']);
    }
}

// Sanitize email input
$email = sanitize_input_ctr($_POST['email']);
$password = $_POST['password']; // Don't sanitize password

// Validate email format
if (!validate_email_ctr($email)) {
    $response['status'] = 'error';
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit();
}

// Attempt to login
$login_result = login_user_ctr($email, $password);

if ($login_result) {
    // Login successful - Reset failed attempts
    unset($_SESSION['failed_login_attempts']);
    unset($_SESSION['login_locked_until']);
    
    $response['status'] = 'success';
    $response['message'] = 'Login successful! Redirecting...';
    $response['user_id'] = $_SESSION['user_id'];
    $response['user_name'] = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    // Build a redirect path that includes the project folder (handles spaces/locations)
    $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
    $response['redirect'] = $basePath . '/view/homepage.php';
    
    // Optional: Set remember me cookie
    if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'true') {
        // Set a cookie that expires in 30 days
        setcookie('remember_user', $email, time() + (30 * 24 * 60 * 60), '/');
    }
} else {
    // Login failed
    $response['status'] = 'error';
    $response['message'] = 'Invalid email or password. Please try again.';
    
    // Track failed login attempts
    if (!isset($_SESSION['failed_login_attempts'])) {
        $_SESSION['failed_login_attempts'] = 0;
    }
    $_SESSION['failed_login_attempts']++;
    
    // Lock account after 5 failed attempts
    if ($_SESSION['failed_login_attempts'] >= 5) {
        $response['message'] = 'Too many failed login attempts. Account locked for 15 minutes.';
        $_SESSION['login_locked_until'] = time() + (15 * 60); // Lock for 15 minutes
    } else {
        $remaining_attempts = 5 - $_SESSION['failed_login_attempts'];
        $response['remaining_attempts'] = $remaining_attempts;
        $response['message'] = "Invalid email or password. {$remaining_attempts} attempts remaining.";
    }
}

echo json_encode($response);
exit();

?>

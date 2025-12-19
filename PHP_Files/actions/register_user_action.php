<?php

header('Content-Type: application/json');

session_start();

$response = array();

// Check if the user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['status'] = 'error';
    $response['message'] = 'You are already logged in';
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
$required_fields = array('first_name', 'last_name', 'email', 'password');
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

// Sanitize inputs
$first_name = sanitize_input_ctr($_POST['first_name']);
$last_name = sanitize_input_ctr($_POST['last_name']);
$email = sanitize_input_ctr($_POST['email']);
$password = $_POST['password']; // Don't sanitize password
$phone = isset($_POST['phone']) ? sanitize_input_ctr($_POST['phone']) : null;
$profile_photo = isset($_POST['profile_photo']) ? sanitize_input_ctr($_POST['profile_photo']) : null;
$bio = isset($_POST['bio']) ? sanitize_input_ctr($_POST['bio']) : null;

// Validate email format
if (!validate_email_ctr($email)) {
    $response['status'] = 'error';
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit();
}

// Check if email already exists
if (email_exists_ctr($email)) {
    $response['status'] = 'error';
    $response['message'] = 'Email already exists. Please use a different email or login.';
    echo json_encode($response);
    exit();
}

// Validate password strength
$password_validation = validate_password_ctr($password);
if (!$password_validation['valid']) {
    $response['status'] = 'error';
    $response['message'] = $password_validation['message'];
    echo json_encode($response);
    exit();
}

// Validate password confirmation if provided
if (isset($_POST['confirm_password'])) {
    if ($password !== $_POST['confirm_password']) {
        $response['status'] = 'error';
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit();
    }
}

// Validate name lengths
if (strlen($first_name) < 2 || strlen($last_name) < 2) {
    $response['status'] = 'error';
    $response['message'] = 'First name and last name must be at least 2 characters long';
    echo json_encode($response);
    exit();
}

// Validate phone number format if provided
if ($phone && !empty($phone)) {
    // Basic phone validation (you can customize this)
    if (!preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid phone number format';
        echo json_encode($response);
        exit();
    }
}

// Register the user
$user_id = register_user_ctr($first_name, $last_name, $email, $password, $phone, $profile_photo, $bio);

if ($user_id) {
    // Registration successful
    $response['status'] = 'success';
    $response['message'] = 'Registration successful! You can now login.';
    $response['user_id'] = $user_id;
    
    // Optional: Auto-login the user after registration
    if (isset($_POST['auto_login']) && $_POST['auto_login'] === 'true') {
        if (login_user_ctr($email, $password)) {
            $response['message'] = 'Registration successful! You are now logged in.';
            $response['auto_logged_in'] = true;
            // Build a redirect path that includes the project folder (handles spaces/locations)
            $basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
            $response['redirect'] = $basePath . '/view/dashboard.php';
        } else {
            // Auto-login attempted but failed
            $response['auto_logged_in'] = false;
        }
    }
} else {
    // Registration failed
    $response['status'] = 'error';
    $response['message'] = 'Failed to register. Please try again later.';
}

echo json_encode($response);
exit();

?>

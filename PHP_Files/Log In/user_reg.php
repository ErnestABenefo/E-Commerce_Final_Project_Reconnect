<?php
// Suppress all output and errors before JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

session_start();

$response = array();

// If not POST, return an informative error (this file is an API endpoint)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method. Use POST to register.';
    echo json_encode($response);
    exit();
}

// Include the user controller which provides the helper functions and register_user_ctr
try {
    require_once '../controllers/user_controller.php';
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'System error: Unable to load required files';
    echo json_encode($response);
    exit();
}

// Check required fields
$required_fields = array('first_name', 'last_name', 'email', 'password');
$missing_fields = array();

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required fields: ' . implode(', ', $missing_fields);
    echo json_encode($response);
    exit();
}

// Sanitize inputs using controller helper
$first_name = sanitize_input_ctr($_POST['first_name']);
$last_name = sanitize_input_ctr($_POST['last_name']);
$email = sanitize_input_ctr($_POST['email']);
$password = isset($_POST['password']) ? $_POST['password'] : '';
$phone = isset($_POST['phone']) ? sanitize_input_ctr($_POST['phone']) : null;
$profile_photo = isset($_POST['profile_photo']) ? sanitize_input_ctr($_POST['profile_photo']) : null;
$bio = isset($_POST['bio']) ? sanitize_input_ctr($_POST['bio']) : null;
$year_group = isset($_POST['year_group']) ? sanitize_input_ctr($_POST['year_group']) : null;
$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
$major = isset($_POST['major']) ? sanitize_input_ctr($_POST['major']) : null;

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

// Confirm password if provided
if (isset($_POST['confirm_password'])) {
    if ($password !== $_POST['confirm_password']) {
        $response['status'] = 'error';
        $response['message'] = 'Passwords do not match';
        echo json_encode($response);
        exit();
    }
}

// Basic phone validation if provided
if ($phone && !empty($phone)) {
    if (!preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
        $response['status'] = 'error';
        $response['message'] = 'Invalid phone number format';
        echo json_encode($response);
        exit();
    }
}

// Register the user
try {
    $user_id = register_user_ctr($first_name, $last_name, $email, $password, $phone, $profile_photo, $bio, $year_group);
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Database error during registration';
    $response['debug'] = $e->getMessage(); // Include error details for debugging
    echo json_encode($response);
    exit();
}

if (!$user_id || !is_numeric($user_id)) {
    $response['status'] = 'error';
    $response['message'] = 'Failed to register. Database connection or query error.';
    echo json_encode($response);
    exit();
}

// User registered successfully
if ($user_id && is_numeric($user_id)) {
    // If department_id and major provided, create academic profile
    if ($department_id && $major) {
        try {
            require_once '../controllers/general_controller.php';
            // Pass year_group as graduation_year to the academic profile
            $profile_result = create_academic_profile_ctr($user_id, $department_id, $major, $year_group);
        } catch (Exception $e) {
            // Continue even if profile creation fails - user is registered
        }
    }

    $response['status'] = 'success';
    $response['message'] = 'Registration successful!';
    $response['user_id'] = $user_id;

    // Auto-login support (default to true if not specified)
    $auto_login = isset($_POST['auto_login']) ? $_POST['auto_login'] === 'true' : true;
    
    if ($auto_login) {
        // Give a small delay for database to commit
        usleep(100000); // 0.1 seconds
        
        if (login_user_ctr($email, $password)) {
            $response['auto_logged_in'] = true;
            // Redirect to homepage after auto-login
            $response['redirect'] = '../view/homepage.php';
        } else {
            // Login failed, but registration succeeded
            $response['auto_logged_in'] = false;
            $response['message'] = 'Registration successful! Please log in.';
            $response['redirect'] = '../../index.php';
        }
    } else {
        $response['auto_logged_in'] = false;
        // Redirect to root index.php for manual login (two levels up from Log In/)
        $response['redirect'] = '../../index.php';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to register. Please try again later.';
}

echo json_encode($response);
exit();

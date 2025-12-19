<?php
/**
 * Process Login
 * Handles user authentication
 */

session_start();
require_once('../config/database.php');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please enter both email and password.';
        header('Location: login_register.php?error=' . urlencode($response['message']));
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        header('Location: login_register.php?error=' . urlencode($response['message']));
        exit;
    }
    
    // Create database connection
    $conn = getDatabaseConnection();
    
    if (!$conn) {
        $response['message'] = 'Database connection failed.';
        header('Location: login_register.php?error=' . urlencode($response['message']));
        exit;
    }
    
    try {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("
            SELECT 
                user_id, 
                first_name, 
                last_name, 
                email, 
                password_hash,
                profile_photo,
                created_at
            FROM Users 
            WHERE email = ?
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Password is correct - create session
                $_SESSION['id'] = $user['user_id'];
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                $_SESSION['login_time'] = time();
                
                // Update last login time (optional - add last_login column to Users table if needed)
                // $updateStmt = $conn->prepare("UPDATE Users SET last_login = NOW() WHERE user_id = ?");
                // $updateStmt->bind_param("i", $user['user_id']);
                // $updateStmt->execute();
                
                $stmt->close();
                closeDatabaseConnection($conn);
                
                // Redirect to dashboard
                header('Location: ../dashboard/index.php');
                exit;
                
            } else {
                // Invalid password
                $response['message'] = 'Invalid email or password.';
                $stmt->close();
                closeDatabaseConnection($conn);
                header('Location: login_register.php?error=' . urlencode($response['message']));
                exit;
            }
        } else {
            // User not found
            $response['message'] = 'Invalid email or password.';
            $stmt->close();
            closeDatabaseConnection($conn);
            header('Location: login_register.php?error=' . urlencode($response['message']));
            exit;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'An error occurred. Please try again.';
        error_log("Login error: " . $e->getMessage());
        closeDatabaseConnection($conn);
        header('Location: login_register.php?error=' . urlencode($response['message']));
        exit;
    }
    
} else {
    // Invalid request method
    header('Location: login_register.php');
    exit;
}
?>
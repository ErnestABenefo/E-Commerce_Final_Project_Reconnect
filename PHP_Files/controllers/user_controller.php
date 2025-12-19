<?php

require_once '../classes/user_class.php';

/**
 * User Controller - Functions to handle user operations
 * Acts as an intermediary between forms and the User class
 */

// ==================== CREATE OPERATIONS ====================

/**
 * Register a new user
 * @param string $first_name User's first name
 * @param string $last_name User's last name
 * @param string $email User's email address
 * @param string $password User's password
 * @param string $phone User's phone number (optional)
 * @param string $profile_photo Path to profile photo (optional)
 * @param string $bio User biography (optional)
 * @param string $year_group User's year group (optional)
 * @return int|bool User ID on success, false on failure
 */
function register_user_ctr($first_name, $last_name, $email, $password, $phone = null, $profile_photo = null, $bio = null, $year_group = null)
{
    $user = new User();
    $user_id = $user->createUser($first_name, $last_name, $email, $password, $phone, $profile_photo, $bio, $year_group);
    if ($user_id) {
        return $user_id;
    }
    return false;
}

// ==================== READ OPERATIONS ====================

/**
 * Get user by email
 * @param string $email User's email address
 * @return array|bool User data on success, false on failure
 */
function get_user_by_email_ctr($email)
{
    $user = new User();
    return $user->getUserByEmail($email);
}

/**
 * Get user by ID
 * @param int $user_id User ID
 * @return array|bool User data on success, false on failure
 */
function get_user_by_id_ctr($user_id)
{
    $user = new User();
    return $user->getUserById($user_id);
}

/**
 * Get all users
 * @return array|bool Array of all users on success, false on failure
 */
function get_all_users_ctr()
{
    $user = new User();
    return $user->getAllUsers();
}

/**
 * Search users by name
 * @param string $search_term Search term for first or last name
 * @return array|bool Array of matching users on success, false on failure
 */
function search_users_by_name_ctr($search_term)
{
    $user = new User();
    return $user->searchUsersByName($search_term);
}

/**
 * Get user's full name
 * @param int $user_id User ID
 * @return string|bool Full name on success, false on failure
 */
function get_user_full_name_ctr($user_id)
{
    $user = new User();
    return $user->getFullName($user_id);
}

/**
 * Get recently registered users
 * @param int $limit Number of users to retrieve
 * @return array|bool Array of recent users on success, false on failure
 */
function get_recent_users_ctr($limit = 10)
{
    $user = new User();
    return $user->getRecentUsers($limit);
}

/**
 * Get total number of users
 * @return int Number of users
 */
function get_total_users_ctr()
{
    $user = new User();
    return $user->getTotalUsers();
}

// ==================== UPDATE OPERATIONS ====================

/**
 * Update user information
 * @param int $user_id User ID
 * @param string $first_name User's first name
 * @param string $last_name User's last name
 * @param string $email User's email address
 * @param string $phone User's phone number (optional)
 * @param string $profile_photo Path to profile photo (optional)
 * @param string $bio User biography (optional)
 * @return bool True on success, false on failure
 */
function update_user_ctr($user_id, $first_name, $last_name, $email, $phone = null, $profile_photo = null, $bio = null)
{
    $user = new User();
    return $user->updateUser($user_id, $first_name, $last_name, $email, $phone, $profile_photo, $bio);
}

/**
 * Update user password
 * @param int $user_id User ID
 * @param string $new_password New password
 * @return bool True on success, false on failure
 */
function update_user_password_ctr($user_id, $new_password)
{
    $user = new User();
    return $user->updatePassword($user_id, $new_password);
}

/**
 * Update profile photo
 * @param int $user_id User ID
 * @param string $profile_photo Path to new profile photo
 * @return bool True on success, false on failure
 */
function update_profile_photo_ctr($user_id, $profile_photo)
{
    $user = new User();
    return $user->updateProfilePhoto($user_id, $profile_photo);
}

/**
 * Update user bio
 * @param int $user_id User ID
 * @param string $bio New biography
 * @return bool True on success, false on failure
 */
function update_user_bio_ctr($user_id, $bio)
{
    $user = new User();
    return $user->updateBio($user_id, $bio);
}

// ==================== DELETE OPERATIONS ====================

/**
 * Delete user
 * @param int $user_id User ID
 * @return bool True on success, false on failure
 */
function delete_user_ctr($user_id)
{
    $user = new User();
    return $user->deleteUser($user_id);
}

// ==================== AUTHENTICATION OPERATIONS ====================

/**
 * Verify user login credentials
 * @param string $email User's email address
 * @param string $password User's password
 * @return array|bool User data on success, false on failure
 */
function verify_login_ctr($email, $password)
{
    $user = new User();
    return $user->verifyLogin($email, $password);
}

/**
 * Login user and create session
 * @param string $email User's email address
 * @param string $password User's password
 * @return bool True on success, false on failure
 */
function login_user_ctr($email, $password)
{
    $user = new User();
    $user_data = $user->verifyLogin($email, $password);
    
    if ($user_data) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store user data in session
        $_SESSION['user_id'] = $user_data['user_id'];
        $_SESSION['first_name'] = $user_data['first_name'];
        $_SESSION['last_name'] = $user_data['last_name'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['logged_in'] = true;
        
        // Initialize university context as personal (not acting as university)
        $_SESSION['acting_as_university'] = false;
        $_SESSION['active_university_id'] = null;
        $_SESSION['active_university_name'] = null;
        
        return true;
    }
    return false;
}

/**
 * Logout user and destroy session
 * @return bool True on success
 */
function logout_user_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    return true;
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function is_user_logged_in_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current logged-in user ID
 * @return int|bool User ID if logged in, false otherwise
 */
function get_logged_in_user_id_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : false;
}

// ==================== VALIDATION OPERATIONS ====================

/**
 * Check if email already exists
 * @param string $email Email to check
 * @param int $exclude_user_id Optional user ID to exclude from check
 * @return bool True if email exists, false otherwise
 */
function email_exists_ctr($email, $exclude_user_id = null)
{
    $user = new User();
    return $user->emailExists($email, $exclude_user_id);
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validate_email_ctr($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function validate_password_ctr($password)
{
    $result = array('valid' => true, 'message' => '');
    
    // Check minimum length
    if (strlen($password) < 8) {
        $result['valid'] = false;
        $result['message'] = 'Password must be at least 8 characters long';
        return $result;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one uppercase letter';
        return $result;
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one lowercase letter';
        return $result;
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = 'Password must contain at least one number';
        return $result;
    }
    
    return $result;
}

/**
 * Sanitize user input
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize_input_ctr($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ==================== UNIVERSITY CONTEXT OPERATIONS ====================

/**
 * Get all universities that the user is an admin of
 * @param int $user_id User ID
 * @return array|bool Array of universities on success, false on failure
 */
function get_user_universities_ctr($user_id)
{
    require_once __DIR__ . '/../classes/university_admin_class.php';
    $admin = new UniversityAdmin();
    return $admin->getUniversitiesByAdmin($user_id);
}

/**
 * Check if user can act as a specific university
 * @param int $user_id User ID
 * @param int $university_id University ID
 * @return bool True if user is an active admin of the university
 */
function can_act_as_university_ctr($user_id, $university_id)
{
    require_once __DIR__ . '/../classes/university_admin_class.php';
    $admin = new UniversityAdmin();
    return $admin->isAdmin($university_id, $user_id); // Only returns true if status is 'active'
}

/**
 * Switch user context to act as a university
 * @param int $university_id University ID to switch to
 * @return bool True on success, false on failure
 */
function switch_to_university_context_ctr($university_id)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Verify user is an admin of this university
    if (!can_act_as_university_ctr($user_id, $university_id)) {
        return false;
    }
    
    // Get university details
    require_once __DIR__ . '/general_controller.php';
    $university = get_university_by_id_ctr($university_id);
    
    if (!$university) {
        return false;
    }
    
    // Set session variables for university context
    $_SESSION['acting_as_university'] = true;
    $_SESSION['active_university_id'] = $university_id;
    $_SESSION['active_university_name'] = $university['name'];
    $_SESSION['original_user_id'] = $user_id; // Track who is acting as university
    
    return true;
}

/**
 * Switch user context back to personal account
 * @return bool True on success
 */
function switch_to_personal_context_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear university context variables
    $_SESSION['acting_as_university'] = false;
    $_SESSION['active_university_id'] = null;
    $_SESSION['active_university_name'] = null;
    unset($_SESSION['original_user_id']);
    
    return true;
}

/**
 * Check if user is currently acting as a university
 * @return bool True if acting as university, false otherwise
 */
function is_acting_as_university_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true;
}

/**
 * Get active university context information
 * @return array|bool Array with university_id and name if acting as university, false otherwise
 */
function get_active_university_context_ctr()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!is_acting_as_university_ctr()) {
        return false;
    }
    
    return [
        'university_id' => $_SESSION['active_university_id'],
        'name' => $_SESSION['active_university_name'],
        'acting_user_id' => $_SESSION['original_user_id'] ?? $_SESSION['user_id']
    ];
}

?>
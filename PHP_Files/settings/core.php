<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For header redirection
ob_start();

// Database configuration
require_once('../settings/db_class.php');

/**
 * Function to get user ID from session
 * 
 * @return int|null Returns user ID if logged in, null otherwise
 */
function getUserID() {
    return isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
}

/**
 * Function to get current user's complete information
 * 
 * @param mysqli $conn Database connection
 * @return array|null Returns user data array or null if not found
 */
function getCurrentUser($conn) {
    $user_id = getUserID();
    
    if (!$user_id) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            user_id, 
            first_name, 
            last_name, 
            email, 
            phone, 
            profile_photo, 
            bio, 
            created_at 
        FROM Users 
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Function to check if user is verified alumni
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user is verified alumni, false otherwise
 */
function isVerifiedAlumni($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM AlumniVerification 
        WHERE user_id = ? AND verification_status = 'approved'
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Function to check if user is a mentor
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user is an active mentor, false otherwise
 */
function isMentor($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM MentorshipPrograms 
        WHERE mentor_id = ? AND status IN ('active', 'pending')
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Function to check if user is a business owner
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user owns a business, false otherwise
 */
function isBusinessOwner($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM Businesses 
        WHERE owner_user_id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Function to check if user is a verified business owner
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user owns a verified business, false otherwise
 */
function isVerifiedBusinessOwner($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM Businesses 
        WHERE owner_user_id = ? AND verified = 1
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Function to check if user has posted jobs
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user has posted jobs, false otherwise
 */
function isJobPoster($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM JobListings 
        WHERE posted_by = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Function to get user's role(s)
 * Returns an array of roles the user has
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return array Returns array of user roles
 */
function getUserRoles($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return [];
    }
    
    $roles = ['user']; // Everyone is at least a basic user
    
    if (isVerifiedAlumni($conn, $user_id)) {
        $roles[] = 'verified_alumni';
    }
    
    if (isMentor($conn, $user_id)) {
        $roles[] = 'mentor';
    }
    
    if (isBusinessOwner($conn, $user_id)) {
        $roles[] = 'business_owner';
    }
    
    if (isVerifiedBusinessOwner($conn, $user_id)) {
        $roles[] = 'verified_business_owner';
    }
    
    if (isJobPoster($conn, $user_id)) {
        $roles[] = 'job_poster';
    }
    
    return $roles;
}

/**
 * Function to check if user has a specific role
 * 
 * @param mysqli $conn Database connection
 * @param string $role Role to check for
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user has the role, false otherwise
 */
function hasRole($conn, $role, $user_id = null) {
    $roles = getUserRoles($conn, $user_id);
    return in_array($role, $roles);
}

/**
 * Function to require a specific role
 * Redirects to unauthorized page if user doesn't have the required role
 * 
 * @param mysqli $conn Database connection
 * @param string $role Required role
 * @param string $redirect_url URL to redirect to if unauthorized (default: unauthorized.php)
 */
function requireRole($conn, $role, $redirect_url = '../error/unauthorized.php') {
    if (!hasRole($conn, $role)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Function to require any of multiple roles
 * 
 * @param mysqli $conn Database connection
 * @param array $roles Array of acceptable roles
 * @param string $redirect_url URL to redirect to if unauthorized
 */
function requireAnyRole($conn, $roles, $redirect_url = '../errors/unauthorized.php') {
    $userRoles = getUserRoles($conn);
    $hasAnyRole = false;
    
    foreach ($roles as $role) {
        if (in_array($role, $userRoles)) {
            $hasAnyRole = true;
            break;
        }
    }
    
    if (!$hasAnyRole) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Function to get user's university information
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return array|null Returns university data or null if not found
 */
function getUserUniversity($conn, $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.university_id,
            u.name,
            u.location,
            av.graduation_year,
            av.verification_status
        FROM University u
        INNER JOIN AlumniVerification av ON u.university_id = av.university_id
        WHERE av.user_id = ?
        ORDER BY av.verified_at DESC
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $university = $result->fetch_assoc();
    $stmt->close();
    
    return $university;
}

/**
 * Function to check if user owns a specific resource
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $id_column ID column name
 * @param int $resource_id Resource ID
 * @param string $owner_column Owner column name (default: user_id)
 * @param int $user_id User ID to check (optional, uses session if not provided)
 * @return bool Returns true if user owns the resource, false otherwise
 */
function ownsResource($conn, $table, $id_column, $resource_id, $owner_column = 'user_id', $user_id = null) {
    if ($user_id === null) {
        $user_id = getUserID();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Sanitize table and column names to prevent SQL injection
    $allowed_tables = [
        'Posts', 'PostComments', 'MarketplaceItems', 'Orders', 
        'Projects', 'Events', 'JobListings', 'Businesses'
    ];
    
    if (!in_array($table, $allowed_tables)) {
        return false;
    }
    
    $query = "SELECT COUNT(*) as count FROM $table WHERE $id_column = ? AND $owner_column = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $resource_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] > 0;
}

/**
 * Store user ID in session after login
 * 
 * @param int $user_id User ID to store
 * @param array $additional_data Additional session data to store
 */
function setUserSession($user_id, $additional_data = []) {
    $_SESSION['id'] = $user_id;
    $_SESSION['user_id'] = $user_id; // Alternative key
    $_SESSION['login_time'] = time();
    
    foreach ($additional_data as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

/**
 * Clear user session on logout
 */
function clearUserSession() {
    session_unset();
    session_destroy();
}

/**
 * Check if session is still valid (optional timeout check)
 * 
 * @param int $timeout Session timeout in seconds (default: 3600 = 1 hour)
 * @return bool Returns true if session is valid, false otherwise
 */
function isSessionValid($timeout = 3600) {
    if (!isset($_SESSION['id'])) {
        return false;
    }
    
    if (isset($_SESSION['login_time'])) {
        $elapsed_time = time() - $_SESSION['login_time'];
        if ($elapsed_time > $timeout) {
            clearUserSession();
            return false;
        }
        // Update last activity time
        $_SESSION['login_time'] = time();
    }
    
    return true;
}

?>
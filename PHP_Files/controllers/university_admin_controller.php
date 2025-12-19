<?php

require_once __DIR__ . '/../classes/university_admin_class.php';

/**
 * University Admin Controller - Functions to handle university admin operations
 * Acts as an intermediary between forms and the UniversityAdmin class
 */

// ==================== SANITIZATION & VALIDATION ====================

/**
 * Sanitize admin input
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize_admin_input_ctr($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate role
 * @param string $role Role to validate
 * @return bool True if valid, false otherwise
 */
function validate_admin_role_ctr($role)
{
    $allowed_roles = array('admin', 'superadmin', 'moderator');
    return in_array($role, $allowed_roles);
}

// ==================== ASSIGN & REVOKE ====================

/**
 * Assign a user as admin for a university
 * Validates that current user is an admin before allowing assignment
 * @param int $university_id University ID
 * @param int $user_id User ID to assign as admin
 * @param string $role Admin role (default: 'admin')
 * @param int $current_user_id Current logged-in user ID (who is performing the assignment)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function assign_university_admin_ctr($university_id, $user_id, $role = 'admin', $current_user_id = null)
{
    $result = array('success' => false, 'message' => '', 'admin_id' => null);

    // Validate role
    if (!validate_admin_role_ctr($role)) {
        $result['message'] = 'Invalid admin role';
        return $result;
    }

    // Validate that current user is admin (permission check)
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to assign admins for this university';
        return $result;
    }

    // Check if user already assigned
    $admin = new UniversityAdmin();
    if ($admin->isAdminAnyStatus($university_id, $user_id)) {
        $result['message'] = 'User is already assigned as admin for this university';
        return $result;
    }

    // Assign admin
    $admin_id = $admin->assignAdmin($university_id, $user_id, $role, $current_user_id);
    if ($admin_id) {
        $result['success'] = true;
        $result['message'] = 'Admin assigned successfully';
        $result['admin_id'] = $admin_id;
    } else {
        $result['message'] = 'Failed to assign admin';
    }

    return $result;
}

/**
 * Revoke admin status from a user
 * @param int $university_id University ID
 * @param int $user_id User ID to revoke admin from
 * @param int $current_user_id Current logged-in user ID (who is performing the revocation)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function revoke_university_admin_ctr($university_id, $user_id, $current_user_id = null)
{
    $result = array('success' => false, 'message' => '');

    // Validate permission
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to revoke admins for this university';
        return $result;
    }

    // Cannot revoke self
    if ($current_user_id && $current_user_id === $user_id) {
        $result['message'] = 'You cannot revoke your own admin status';
        return $result;
    }

    $admin = new UniversityAdmin();
    if ($admin->revokeAdmin($university_id, $user_id)) {
        $result['success'] = true;
        $result['message'] = 'Admin revoked successfully';
    } else {
        $result['message'] = 'Failed to revoke admin';
    }

    return $result;
}

// ==================== PERMISSION CHECKS ====================

/**
 * Check if a user is an active admin for a university
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @return bool True if admin and active, false otherwise
 */
function is_university_admin_ctr($university_id, $user_id)
{
    $admin = new UniversityAdmin();
    return $admin->isAdmin($university_id, $user_id);
}

/**
 * Check if user is admin (any status)
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @return bool True if admin in any status, false otherwise
 */
function is_university_admin_any_status_ctr($university_id, $user_id)
{
    $admin = new UniversityAdmin();
    return $admin->isAdminAnyStatus($university_id, $user_id);
}

// ==================== RETRIEVE DATA ====================

/**
 * Get admin info for a user and university
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @return array|bool Admin data on success, false on failure
 */
function get_admin_info_ctr($university_id, $user_id)
{
    $admin = new UniversityAdmin();
    return $admin->getAdminInfo($university_id, $user_id);
}

/**
 * Get all admins for a university
 * @param int $university_id University ID
 * @return array|bool Array of admins on success, false on failure
 */
function get_admins_by_university_ctr($university_id)
{
    $admin = new UniversityAdmin();
    return $admin->getAdminsByUniversity($university_id);
}

/**
 * Get all universities where user is an active admin
 * @param int $user_id User ID
 * @return array|bool Array of universities on success, false on failure
 */
function get_universities_by_admin_ctr($user_id)
{
    $admin = new UniversityAdmin();
    return $admin->getUniversitiesByAdmin($user_id);
}

/**
 * Get admin count for a university
 * @param int $university_id University ID
 * @return int Number of active admins
 */
function get_admin_count_by_university_ctr($university_id)
{
    $admin = new UniversityAdmin();
    return $admin->getAdminCountByUniversity($university_id);
}

// ==================== ROLE MANAGEMENT ====================

/**
 * Change admin role
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @param string $new_role New role
 * @param int $current_user_id Current logged-in user (permission check)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function change_admin_role_ctr($university_id, $user_id, $new_role, $current_user_id = null)
{
    $result = array('success' => false, 'message' => '');

    // Validate role
    if (!validate_admin_role_ctr($new_role)) {
        $result['message'] = 'Invalid admin role';
        return $result;
    }

    // Validate permission
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to change admin roles for this university';
        return $result;
    }

    $admin = new UniversityAdmin();
    if ($admin->changeAdminRole($university_id, $user_id, $new_role)) {
        $result['success'] = true;
        $result['message'] = 'Admin role changed successfully';
    } else {
        $result['message'] = 'Failed to change admin role';
    }

    return $result;
}

// ==================== STATUS MANAGEMENT ====================

/**
 * Suspend an admin
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @param int $current_user_id Current logged-in user (permission check)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function suspend_university_admin_ctr($university_id, $user_id, $current_user_id = null)
{
    $result = array('success' => false, 'message' => '');

    // Validate permission
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to manage admins for this university';
        return $result;
    }

    $admin = new UniversityAdmin();
    if ($admin->suspendAdmin($university_id, $user_id)) {
        $result['success'] = true;
        $result['message'] = 'Admin suspended successfully';
    } else {
        $result['message'] = 'Failed to suspend admin';
    }

    return $result;
}

/**
 * Activate a suspended admin
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @param int $current_user_id Current logged-in user (permission check)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function activate_university_admin_ctr($university_id, $user_id, $current_user_id = null)
{
    $result = array('success' => false, 'message' => '');

    // Validate permission
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to manage admins for this university';
        return $result;
    }

    $admin = new UniversityAdmin();
    if ($admin->activateAdmin($university_id, $user_id)) {
        $result['success'] = true;
        $result['message'] = 'Admin activated successfully';
    } else {
        $result['message'] = 'Failed to activate admin';
    }

    return $result;
}

// ==================== APPROVAL WORKFLOW ====================

/**
 * Approve a pending admin request
 * @param int $university_id University ID
 * @param int $user_id User ID
 * @param int $current_user_id Current logged-in user (permission check)
 * @return array Array with 'success' (bool) and 'message' (string)
 */
function approve_pending_admin_ctr($university_id, $user_id, $current_user_id = null)
{
    $result = array('success' => false, 'message' => '');

    // Validate permission
    if ($current_user_id && !is_university_admin_ctr($university_id, $current_user_id)) {
        $result['message'] = 'You do not have permission to approve admins for this university';
        return $result;
    }

    $admin = new UniversityAdmin();
    if ($admin->approvePendingAdmin($university_id, $user_id)) {
        $result['success'] = true;
        $result['message'] = 'Admin request approved successfully';
    } else {
        $result['message'] = 'Failed to approve admin request';
    }

    return $result;
}

/**
 * Get all pending admin requests for a university
 * @param int $university_id University ID
 * @return array|bool Array of pending requests on success, false on failure
 */
function get_pending_admin_requests_ctr($university_id)
{
    $admin = new UniversityAdmin();
    return $admin->getPendingAdminRequests($university_id);
}

// ==================== GLOBAL ADMIN HELPERS ====================

/**
 * Check if a user is a global admin.
 * Criteria:
 *  - If the user has a 'superadmin' role in any UniversityAdmins record, or
 *  - If the user_id is 1 (fallback superuser).
 * @param int $user_id User ID
 * @return bool True if the user is a global admin
 */
function is_global_admin_ctr($user_id)
{
    // quick fallback superuser
    if (!$user_id) return false;
    if ((int)$user_id === 1) return true;

    $admin = new UniversityAdmin();
    // check by querying universities where role = 'superadmin'
    $universities = $admin->getUniversitiesByAdmin($user_id);
    if ($universities && is_array($universities)) {
        foreach ($universities as $u) {
            if (isset($u['role']) && $u['role'] === 'superadmin') {
                return true;
            }
        }
    }

    return false;
}

/**
 * Get users who are global admins (role = 'superadmin')
 * @return array|bool Array of user rows or false
 */
function get_global_admin_users_ctr()
{
    $db = new db_connection();
    $conn = $db->db_conn();
    $sql = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email
            FROM UniversityAdmins ua
            JOIN Users u ON ua.user_id = u.user_id
            WHERE ua.role = 'superadmin'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

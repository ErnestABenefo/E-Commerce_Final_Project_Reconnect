<?php

require_once __DIR__ . '/general_class.php';

/**
 * UniversityAdmin Class - Handles university admin operations
 * Manages relationships between Users and Universities for admin roles
 */

class UniversityAdmin
{
    private $db;

    public function __construct()
    {
        $db_conn = new db_connection();
        $this->db = $db_conn->db_conn();
    }

    /**
     * Assign a user as admin for a university
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @param string $role Admin role (default: 'admin')
     * @param int $created_by User ID of admin performing this action
     * @return int|bool Admin assignment ID on success, false on failure
     */
    public function assignAdmin($university_id, $user_id, $role = 'admin', $created_by = null)
    {
        // Check if already assigned
        if ($this->isAdmin($university_id, $user_id)) {
            return false;
        }

        $sql = "INSERT INTO UniversityAdmins (university_id, user_id, role, created_by, status) 
                VALUES (?, ?, ?, ?, 'active')";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("iisi", $university_id, $user_id, $role, $created_by);
            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
        }
        return false;
    }

    /**
     * Revoke admin status from a user
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function revokeAdmin($university_id, $user_id)
    {
        $sql = "DELETE FROM UniversityAdmins 
                WHERE university_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Suspend an admin (soft delete)
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function suspendAdmin($university_id, $user_id)
    {
        $sql = "UPDATE UniversityAdmins 
                SET status = 'suspended' 
                WHERE university_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Activate a suspended admin
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function activateAdmin($university_id, $user_id)
    {
        $sql = "UPDATE UniversityAdmins 
                SET status = 'active' 
                WHERE university_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Check if a user is an active admin for a university
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True if admin and active, false otherwise
     */
    public function isAdmin($university_id, $user_id)
    {
        $sql = "SELECT 1 FROM UniversityAdmins 
                WHERE university_id = ? AND user_id = ? AND status = 'active' 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }
        return false;
    }

    /**
     * Check if a user is admin (pending, active, or suspended)
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True if admin in any status, false otherwise
     */
    public function isAdminAnyStatus($university_id, $user_id)
    {
        $sql = "SELECT 1 FROM UniversityAdmins 
                WHERE university_id = ? AND user_id = ? 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }
        return false;
    }

    /**
     * Get admin info for a user and university
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return array|bool Admin data on success, false on failure
     */
    public function getAdminInfo($university_id, $user_id)
    {
        $sql = "SELECT ua.ua_id, ua.role, ua.status, ua.created_at, ua.updated_at,
                       u.first_name, u.last_name, u.email
                FROM UniversityAdmins ua
                JOIN Users u ON ua.user_id = u.user_id
                WHERE ua.university_id = ? AND ua.user_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc() : false;
        }
        return false;
    }

    /**
     * Get all admins for a university
     * @param int $university_id University ID
     * @return array|bool Array of admins on success, false on failure
     */
    public function getAdminsByUniversity($university_id)
    {
        $sql = "SELECT ua.ua_id, ua.user_id, ua.role, ua.status, ua.created_at, ua.updated_at,
                       u.first_name, u.last_name, u.email, u.profile_photo
                FROM UniversityAdmins ua
                JOIN Users u ON ua.user_id = u.user_id
                WHERE ua.university_id = ?
                ORDER BY ua.created_at DESC";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $university_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }

    /**
     * Get all universities where user is an active admin
     * @param int $user_id User ID
     * @return array|bool Array of universities on success, false on failure
     */
    public function getUniversitiesByAdmin($user_id)
    {
        $sql = "SELECT ua.ua_id, ua.role, ua.status, ua.created_at,
                       u.university_id, u.name, u.location
                FROM UniversityAdmins ua
                JOIN University u ON ua.university_id = u.university_id
                WHERE ua.user_id = ? AND ua.status = 'active'
                ORDER BY u.name ASC";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }

    /**
     * Change admin role
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @param string $new_role New role
     * @return bool True on success, false on failure
     */
    public function changeAdminRole($university_id, $user_id, $new_role)
    {
        $sql = "UPDATE UniversityAdmins 
                SET role = ?, updated_at = CURRENT_TIMESTAMP
                WHERE university_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sii", $new_role, $university_id, $user_id);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get count of active admins for a university
     * @param int $university_id University ID
     * @return int Number of active admins
     */
    public function getAdminCountByUniversity($university_id)
    {
        $sql = "SELECT COUNT(*) as count FROM UniversityAdmins 
                WHERE university_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $university_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['count'] ?? 0;
        }
        return 0;
    }

    /**
     * Approve a pending admin request
     * @param int $university_id University ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function approvePendingAdmin($university_id, $user_id)
    {
        $sql = "UPDATE UniversityAdmins 
                SET status = 'active', updated_at = CURRENT_TIMESTAMP
                WHERE university_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $university_id, $user_id);
            return $stmt->execute();
        }
        return false;
    }

    /**
     * Get all pending admin requests
     * @param int $university_id University ID
     * @return array|bool Array of pending requests on success, false on failure
     */
    public function getPendingAdminRequests($university_id)
    {
        $sql = "SELECT ua.ua_id, ua.user_id, ua.role, ua.created_at,
                       u.first_name, u.last_name, u.email, u.profile_photo
                FROM UniversityAdmins ua
                JOIN Users u ON ua.user_id = u.user_id
                WHERE ua.university_id = ? AND ua.status = 'pending'
                ORDER BY ua.created_at ASC";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $university_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return false;
    }
}

?>

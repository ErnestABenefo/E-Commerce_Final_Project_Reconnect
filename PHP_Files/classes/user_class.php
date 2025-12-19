<?php

require_once '../settings/db_class.php';

/**
 * User Class - CRUD operations for Users table
 * @author Your Name
 * @version 1.0
 */
class User extends db_connection
{
    private $user_id;
    private $first_name;
    private $last_name;
    private $email;
    private $phone;
    private $password_hash;
    private $profile_photo;
    private $bio;
    private $created_at;

    /**
     * Constructor - Initialize database connection and optionally load user
     * @param int $user_id Optional user ID to load
     */
    public function __construct($user_id = null)
    {
        parent::db_connect();
        if ($user_id) {
            $this->user_id = $user_id;
            $this->loadUser();
        }
    }

    /**
     * Load user data from database
     * @param int $user_id Optional user ID to load
     * @return bool True on success, false on failure
     */
    private function loadUser($user_id = null)
    {
        if ($user_id) {
            $this->user_id = $user_id;
        }
        if (!$this->user_id) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $this->first_name = $result['first_name'];
            $this->last_name = $result['last_name'];
            $this->email = $result['email'];
            $this->phone = $result['phone'];
            $this->password_hash = $result['password_hash'];
            $this->profile_photo = $result['profile_photo'];
            $this->bio = $result['bio'];
            $this->created_at = $result['created_at'];
            return true;
        }
        return false;
    }

    /**
     * CREATE - Add new user to database
     * @param string $first_name User's first name
     * @param string $last_name User's last name
     * @param string $email User's email address
     * @param string $password User's password (will be hashed)
     * @param string $phone User's phone number (optional)
     * @param string $profile_photo Path to profile photo (optional)
     * @param string $bio User biography (optional)
     * @param string $year_group User's year group (optional) - Note: stored separately in UserAcademicProfile
     * @return int|bool User ID on success, false on failure
     */
    public function createUser($first_name, $last_name, $email, $password, $phone = null, $profile_photo = null, $bio = null, $year_group = null)
    {
        // Check database connection
        if (!$this->db) {
            error_log("User createUser: Database connection is null");
            return false;
        }
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL statement (year_group is stored in UserAcademicProfile, not Users table)
        $stmt = $this->db->prepare(
            "INSERT INTO Users (first_name, last_name, email, phone, password_hash, profile_photo, bio) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            error_log("User createUser: Failed to prepare statement - " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param(
            "sssssss", 
            $first_name, 
            $last_name, 
            $email, 
            $phone, 
            $hashed_password, 
            $profile_photo, 
            $bio
        );
        
        if ($stmt->execute()) {
            $this->user_id = $this->db->insert_id;
            return $this->user_id;
        }
        
        error_log("User createUser: Failed to execute - " . $stmt->error);
        return false;
    }

    /**
     * READ - Get user by ID
     * @param int $user_id User ID
     * @return array|bool User data array on success, false on failure
     */
    public function getUserById($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * READ - Get user by email
     * @param string $email User's email address
     * @return array|bool User data array on success, false on failure
     */
    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * READ - Get all users
     * @return array|bool Array of all users on success, false on failure
     */
    public function getAllUsers()
    {
        $sql = "SELECT * FROM Users ORDER BY created_at DESC";
        return $this->db_fetch_all($sql);
    }

    /**
     * READ - Search users by name
     * @param string $search_term Search term for first or last name
     * @return array|bool Array of matching users on success, false on failure
     */
    public function searchUsersByName($search_term)
    {
        $search_pattern = "%{$search_term}%";
        $stmt = $this->db->prepare(
            "SELECT * FROM Users 
             WHERE first_name LIKE ? OR last_name LIKE ? 
             ORDER BY first_name, last_name"
        );
        $stmt->bind_param("ss", $search_pattern, $search_pattern);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * UPDATE - Update user information
     * @param int $user_id User ID
     * @param string $first_name User's first name
     * @param string $last_name User's last name
     * @param string $email User's email address
     * @param string $phone User's phone number (optional)
     * @param string $profile_photo Path to profile photo (optional)
     * @param string $bio User biography (optional)
     * @return bool True on success, false on failure
     */
    public function updateUser($user_id, $first_name, $last_name, $email, $phone = null, $profile_photo = null, $bio = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE Users 
             SET first_name = ?, last_name = ?, email = ?, phone = ?, profile_photo = ?, bio = ? 
             WHERE user_id = ?"
        );
        
        $stmt->bind_param(
            "ssssssi", 
            $first_name, 
            $last_name, 
            $email, 
            $phone, 
            $profile_photo, 
            $bio, 
            $user_id
        );
        
        return $stmt->execute();
    }

    /**
     * UPDATE - Update user password
     * @param int $user_id User ID
     * @param string $new_password New password (will be hashed)
     * @return bool True on success, false on failure
     */
    public function updatePassword($user_id, $new_password)
    {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("UPDATE Users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        return $stmt->execute();
    }

    /**
     * UPDATE - Update profile photo
     * @param int $user_id User ID
     * @param string $profile_photo Path to new profile photo
     * @return bool True on success, false on failure
     */
    public function updateProfilePhoto($user_id, $profile_photo)
    {
        $stmt = $this->db->prepare("UPDATE Users SET profile_photo = ? WHERE user_id = ?");
        $stmt->bind_param("si", $profile_photo, $user_id);
        
        return $stmt->execute();
    }

    /**
     * UPDATE - Update bio
     * @param int $user_id User ID
     * @param string $bio New biography
     * @return bool True on success, false on failure
     */
    public function updateBio($user_id, $bio)
    {
        $stmt = $this->db->prepare("UPDATE Users SET bio = ? WHERE user_id = ?");
        $stmt->bind_param("si", $bio, $user_id);
        
        return $stmt->execute();
    }

    /**
     * DELETE - Delete user from database
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function deleteUser($user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        
        return $stmt->execute();
    }

    /**
     * AUTHENTICATION - Verify user login credentials
     * @param string $email User's email address
     * @param string $password User's password
     * @return array|bool User data on success, false on failure
     */
    public function verifyLogin($email, $password)
    {
        $user = $this->getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Remove password hash from returned data for security
            unset($user['password_hash']);
            return $user;
        }
        return false;
    }

    /**
     * CHECK - Check if email already exists
     * @param string $email Email address to check
     * @param int $exclude_user_id Optional user ID to exclude from check (for updates)
     * @return bool True if email exists, false otherwise
     */
    public function emailExists($email, $exclude_user_id = null)
    {
        if ($exclude_user_id) {
            $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ? AND user_id != ?");
            $stmt->bind_param("si", $email, $exclude_user_id);
        } else {
            $stmt = $this->db->prepare("SELECT user_id FROM Users WHERE email = ?");
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * GET - Get user's full name
     * @return string Full name or empty string
     */
    public function getFullName()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return '';
    }

    /**
     * GET - Count total users
     * @return int Total number of users
     */
    public function getTotalUsers()
    {
        $sql = "SELECT COUNT(*) as total FROM Users";
        $result = $this->db_fetch_one($sql);
        return $result ? $result['total'] : 0;
    }

    /**
     * GET - Get recently registered users
     * @param int $limit Number of users to retrieve
     * @return array|bool Array of recent users on success, false on failure
     */
    public function getRecentUsers($limit = 10)
    {
        $stmt = $this->db->prepare("SELECT * FROM Users ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Getters for private properties
    public function getUserId() { return $this->user_id; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getEmail() { return $this->email; }
    public function getPhone() { return $this->phone; }
    public function getProfilePhoto() { return $this->profile_photo; }
    public function getBio() { return $this->bio; }
    public function getCreatedAt() { return $this->created_at; }
}

?>
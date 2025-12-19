<?php

require_once '../settings/db_class.php';

/**
 * University Class
 * Handles all university-related database operations
 */
class University
{
    private $db;

    public function __construct()
    {
        $this->db = new db_connection();
    }

    /**
     * Create a new university
     * @param array $data University data
     * @return int|bool University ID on success, false on failure
     */
    public function createUniversity($data)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        // Check if university already exists
        if ($this->universityExists($data['name'])) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $conn = $this->db->db_conn();
        
        // First, check if the new columns exist
        $check_columns = $conn->query("SHOW COLUMNS FROM University LIKE 'website'");
        $has_new_columns = ($check_columns && $check_columns->num_rows > 0);
        
        if ($has_new_columns) {
            // Use new schema with all fields
            $stmt = $conn->prepare("INSERT INTO University (name, location, website, contact_email, contact_phone, address, established_year, university_type, description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $conn->error);
                return false;
            }

            $stmt->bind_param('sssssssssi', 
                $data['name'],
                $data['location'],
                $data['website'],
                $data['contact_email'],
                $data['contact_phone'],
                $data['address'],
                $data['established_year'],
                $data['university_type'],
                $data['description'],
                $created_by
            );
        } else {
            // Use old schema with only name and location
            $stmt = $conn->prepare("INSERT INTO University (name, location) VALUES (?, ?)");
            
            if (!$stmt) {
                error_log("Failed to prepare statement: " . $conn->error);
                return false;
            }

            $stmt->bind_param('ss', $data['name'], $data['location']);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Failed to execute insert: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $university_id = $stmt->insert_id;
        $stmt->close();

        return $result ? $university_id : false;
    }

    /**
     * Get university by ID
     * @param int $university_id University ID
     * @return array|bool University data on success, false on failure
     */
    public function getUniversityById($university_id)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("SELECT * FROM University WHERE university_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $university = $result->fetch_assoc();
        $stmt->close();

        return $university;
    }

    /**
     * Get university by name
     * @param string $name University name
     * @return array|bool University data on success, false on failure
     */
    public function getUniversityByName($name)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("SELECT * FROM University WHERE name = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $university = $result->fetch_assoc();
        $stmt->close();

        return $university;
    }

    /**
     * Get all universities
     * @return array|bool Array of universities on success, false on failure
     */
    public function getAllUniversities()
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $result = $conn->query("SELECT * FROM University ORDER BY name ASC");
        
        if (!$result) {
            return false;
        }

        $universities = $result->fetch_all(MYSQLI_ASSOC);
        return $universities;
    }

    /**
     * Search universities by name or location
     * @param string $search_term Search term
     * @return array|bool Array of matching universities on success, false on failure
     */
    public function searchUniversities($search_term)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $search = '%' . $search_term . '%';
        $stmt = $conn->prepare("SELECT * FROM University WHERE name LIKE ? OR location LIKE ? ORDER BY name ASC");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        $universities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $universities;
    }

    /**
     * Update university information
     * @param int $university_id University ID
     * @param string $name University name
     * @param string $location University location
     * @return bool True on success, false on failure
     */
    public function updateUniversity($university_id, $name, $location)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("UPDATE University SET name = ?, location = ? WHERE university_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssi', $name, $location, $university_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete university
     * @param int $university_id University ID
     * @return bool True on success, false on failure
     */
    public function deleteUniversity($university_id)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("DELETE FROM University WHERE university_id = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $university_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Check if university exists
     * @param string $name University name
     * @return bool True if exists, false otherwise
     */
    public function universityExists($name)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM University WHERE name = ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['count'] > 0;
    }

    /**
     * Get total number of universities
     * @return int Total number of universities
     */
    public function getTotalUniversities()
    {
        if (!$this->db->db_connect()) {
            return 0;
        }

        $conn = $this->db->db_conn();
        $result = $conn->query("SELECT COUNT(*) as count FROM University");
        
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    /**
     * Get paginated universities
     * @param int $limit Number of records per page
     * @param int $offset Starting position
     * @return array|bool Array of universities on success, false on failure
     */
    public function getPaginatedUniversities($limit = 10, $offset = 0)
    {
        if (!$this->db->db_connect()) {
            return false;
        }

        $conn = $this->db->db_conn();
        $stmt = $conn->prepare("SELECT * FROM University ORDER BY name ASC LIMIT ? OFFSET ?");
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $universities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $universities;
    }
}

?>

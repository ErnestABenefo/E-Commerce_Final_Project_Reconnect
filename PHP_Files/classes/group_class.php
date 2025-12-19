<?php
// Group management class
require_once __DIR__ . '/../settings/db_class.php';

class GroupManager extends db_connection {
    
    /**
     * Get or create university group
     * @param int $university_id
     * @return int group_id
     */
    public function getOrCreateUniversityGroup($university_id) {
        $conn = $this->db_conn();
        
        // Check if university group exists (works with or without is_auto_generated column)
        $stmt = $conn->prepare("SELECT group_id FROM `Groups` WHERE university_id = ? AND group_type = 'university' LIMIT 1");
        $stmt->bind_param('i', $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)$row['group_id'];
        }
        $stmt->close();
        
        // Get university name
        $stmt = $conn->prepare("SELECT name FROM University WHERE university_id = ?");
        $stmt->bind_param('i', $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $uni = $result->fetch_assoc();
        $stmt->close();
        
        if (!$uni) return false;
        
        $group_name = $uni['name'] . " Community";
        $description = "Official community group for all " . $uni['name'] . " members";
        
        // Create university group (compatible with existing schema)
        $stmt = $conn->prepare("INSERT INTO `Groups` (university_id, name, description, group_type, created_at) VALUES (?, ?, ?, 'university', NOW())");
        $stmt->bind_param('iss', $university_id, $group_name, $description);
        $stmt->execute();
        $group_id = $stmt->insert_id;
        $stmt->close();
        
        // Create corresponding GroupChat
        $stmt = $conn->prepare("INSERT INTO GroupChats (group_id, created_at) VALUES (?, NOW())");
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $stmt->close();
        
        return $group_id;
    }
    
    /**
     * Get or create year group
     * @param int $university_id
     * @param int $graduation_year
     * @return int group_id
     */
    public function getOrCreateYearGroup($university_id, $graduation_year) {
        $conn = $this->db_conn();
        
        // Check if year group exists
        $stmt = $conn->prepare("SELECT group_id FROM `Groups` WHERE university_id = ? AND group_type = 'year_group' AND name LIKE ? LIMIT 1");
        $year_pattern = "%Class of " . $graduation_year . "%";
        $stmt->bind_param('is', $university_id, $year_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return (int)$row['group_id'];
        }
        $stmt->close();
        
        // Get university name
        $stmt = $conn->prepare("SELECT name FROM University WHERE university_id = ?");
        $stmt->bind_param('i', $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $uni = $result->fetch_assoc();
        $stmt->close();
        
        if (!$uni) return false;
        
        $group_name = $uni['name'] . " - Class of " . $graduation_year;
        $description = "Group for " . $uni['name'] . " alumni graduating in " . $graduation_year;
        
        // Create year group
        $stmt = $conn->prepare("INSERT INTO `Groups` (university_id, name, description, group_type, created_at) VALUES (?, ?, ?, 'year_group', NOW())");
        $stmt->bind_param('iss', $university_id, $group_name, $description);
        $stmt->execute();
        $group_id = $stmt->insert_id;
        $stmt->close();
        
        // Create corresponding GroupChat
        $stmt = $conn->prepare("INSERT INTO GroupChats (group_id, created_at) VALUES (?, NOW())");
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $stmt->close();
        
        return $group_id;
    }
    
    /**
     * Add user to a group
     * @param int $user_id
     * @param int $group_id
     * @param string $role 'admin' or 'member' (ignored in current schema)
     * @return bool
     */
    public function addUserToGroup($user_id, $group_id, $role = 'member') {
        $conn = $this->db_conn();
        
        // Check if already a member
        $stmt = $conn->prepare("SELECT id FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_assoc()) {
            $stmt->close();
            return true; // Already a member
        }
        $stmt->close();
        
        // Add to group (compatible with existing schema)
        $stmt = $conn->prepare("INSERT INTO GroupMembers (group_id, user_id, joined_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('ii', $group_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Auto-enroll user in university and year groups based on their verification
     * @param int $user_id
     * @return bool
     */
    public function autoEnrollUser($user_id) {
        $conn = $this->db_conn();
        
        // Get user's verified universities
        $stmt = $conn->prepare("
            SELECT DISTINCT av.university_id, av.graduation_year 
            FROM AlumniVerification av
            WHERE av.user_id = ? AND av.verification_status = 'approved'
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $enrolled = false;
        while ($row = $result->fetch_assoc()) {
            $university_id = (int)$row['university_id'];
            $graduation_year = $row['graduation_year'];
            
            // Add to university group
            $uni_group_id = $this->getOrCreateUniversityGroup($university_id);
            if ($uni_group_id) {
                $this->addUserToGroup($user_id, $uni_group_id, 'member');
                $enrolled = true;
            }
            
            // Add to year group if graduation year is set
            if ($graduation_year) {
                $year_group_id = $this->getOrCreateYearGroup($university_id, $graduation_year);
                if ($year_group_id) {
                    $this->addUserToGroup($user_id, $year_group_id, 'member');
                    $enrolled = true;
                }
            }
        }
        $stmt->close();
        
        // Also check UserAcademicProfile for graduation year
        $stmt = $conn->prepare("
            SELECT uap.graduation_year, ad.university_id
            FROM UserAcademicProfile uap
            JOIN AcademicDepartment ad ON uap.department_id = ad.department_id
            WHERE uap.user_id = ? AND uap.graduation_year IS NOT NULL
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $university_id = (int)$row['university_id'];
            $graduation_year = $row['graduation_year'];
            
            if ($graduation_year) {
                $year_group_id = $this->getOrCreateYearGroup($university_id, $graduation_year);
                if ($year_group_id) {
                    $this->addUserToGroup($user_id, $year_group_id, 'member');
                    $enrolled = true;
                }
            }
        }
        $stmt->close();
        
        return $enrolled;
    }
    
    /**
     * Get all groups a user is member of
     * @param int $user_id
     * @return array
     */
    public function getUserGroups($user_id) {
        $conn = $this->db_conn();
        
        $stmt = $conn->prepare("
            SELECT g.group_id, g.name, g.description, g.group_type, 
                   gm.joined_at, u.name as university_name,
                   (SELECT COUNT(*) FROM GroupMembers WHERE group_id = g.group_id) as member_count,
                   (SELECT COUNT(*) FROM GroupChatMessages gcm 
                    JOIN GroupChats gc ON gcm.chat_id = gc.chat_id 
                    WHERE gc.group_id = g.group_id) as message_count
            FROM GroupMembers gm
            JOIN `Groups` g ON gm.group_id = g.group_id
            LEFT JOIN University u ON g.university_id = u.university_id
            WHERE gm.user_id = ?
            ORDER BY g.group_type, g.name
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        $stmt->close();
        
        return $groups;
    }
    
    /**
     * Check if user is member of a group
     * @param int $user_id
     * @param int $group_id
     * @return bool
     */
    public function isGroupMember($user_id, $group_id) {
        $conn = $this->db_conn();
        
        $stmt = $conn->prepare("SELECT id FROM GroupMembers WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $group_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_member = $result->num_rows > 0;
        $stmt->close();
        
        return $is_member;
    }
    
    /**
     * Get group details
     * @param int $group_id
     * @return array|null
     */
    public function getGroupDetails($group_id) {
        $conn = $this->db_conn();
        
        $stmt = $conn->prepare("
            SELECT g.*, u.name as university_name,
                   (SELECT COUNT(*) FROM GroupMembers WHERE group_id = g.group_id) as member_count
            FROM `Groups` g
            LEFT JOIN University u ON g.university_id = u.university_id
            WHERE g.group_id = ?
        ");
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $group = $result->fetch_assoc();
        $stmt->close();
        
        return $group;
    }
}
?>

<?php

require_once __DIR__ . '/../settings/db_class.php';

/**
 * UniversityContent Class - CRUD operations for university posts and groups
 * Allows universities (via their admins) to create, read, update, delete content
 */
class UniversityContent extends db_connection
{
    public function __construct()
    {
        parent::db_connect();
    }

    // ==================== UNIVERSITY POSTS ====================

    /**
     * Create a university post
     * @param int $university_id University ID
     * @param string $content Post content
     * @param string $post_type Post type (e.g., 'announcement', 'news', 'event')
     * @return int|bool Post ID on success, false on failure
     */
    public function createUniversityPost($university_id, $content, $post_type = 'announcement')
    {
        $stmt = $this->db->prepare(
            "INSERT INTO Posts (university_id, content, post_type, creator_type) 
             VALUES (?, ?, ?, 'university')"
        );
        $stmt->bind_param("iss", $university_id, $content, $post_type);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Get posts by university
     * @param int $university_id University ID
     * @param int $limit Number of posts to fetch
     * @param int $offset Offset for pagination
     * @return array|bool Array of posts on success, false on failure
     */
    public function getUniversityPosts($university_id, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT p.post_id, p.university_id, p.content, p.post_type, p.created_at,
                    u.name as university_name
             FROM Posts p
             LEFT JOIN University u ON p.university_id = u.university_id
             WHERE p.university_id = ? AND p.creator_type = 'university'
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("iii", $university_id, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get a single university post by ID
     * @param int $post_id Post ID
     * @return array|bool Post data on success, false on failure
     */
    public function getUniversityPost($post_id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM Posts WHERE post_id = ? AND creator_type = 'university'"
        );
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update a university post
     * @param int $post_id Post ID
     * @param string $content New content
     * @param string $post_type New post type
     * @return bool True on success, false on failure
     */
    public function updateUniversityPost($post_id, $content, $post_type)
    {
        $stmt = $this->db->prepare(
            "UPDATE Posts SET content = ?, post_type = ? WHERE post_id = ? AND creator_type = 'university'"
        );
        $stmt->bind_param("ssi", $content, $post_type, $post_id);
        return $stmt->execute();
    }

    /**
     * Delete a university post
     * @param int $post_id Post ID
     * @return bool True on success, false on failure
     */
    public function deleteUniversityPost($post_id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM Posts WHERE post_id = ? AND creator_type = 'university'"
        );
        $stmt->bind_param("i", $post_id);
        return $stmt->execute();
    }

    /**
     * Get total count of university posts
     * @param int $university_id University ID
     * @return int Total count
     */
    public function countUniversityPosts($university_id)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM Posts WHERE university_id = ? AND creator_type = 'university'"
        );
        $stmt->bind_param("i", $university_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    // ==================== UNIVERSITY GROUPS ====================

    /**
     * Create a university group
     * @param int $university_id University ID
     * @param string $name Group name
     * @param string $description Group description
     * @param string $group_type Group type (e.g., 'official', 'department', 'special_interest')
     * @param int $owner_user_id User ID of the admin creating the group
     * @return int|bool Group ID on success, false on failure
     */
    public function createUniversityGroup($university_id, $name, $description, $group_type, $owner_user_id)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `Groups` (university_id, owner_user_id, name, description, group_type, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("iisss", $university_id, $owner_user_id, $name, $description, $group_type);
        
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /**
     * Get groups for a university
     * @param int $university_id University ID
     * @param int $limit Number of groups to fetch
     * @param int $offset Offset for pagination
     * @return array|bool Array of groups on success, false on failure
     */
    public function getUniversityGroups($university_id, $limit = 10, $offset = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT g.group_id, g.university_id, g.owner_user_id, g.name, g.description, g.group_type, g.created_at,
                    u.first_name, u.last_name,
                    COUNT(gm.user_id) as member_count
             FROM `Groups` g
             LEFT JOIN Users u ON g.owner_user_id = u.user_id
             LEFT JOIN GroupMembers gm ON g.group_id = gm.group_id
             WHERE g.university_id = ?
             GROUP BY g.group_id
             ORDER BY g.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("iii", $university_id, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get a single university group by ID
     * @param int $group_id Group ID
     * @return array|bool Group data on success, false on failure
     */
    public function getUniversityGroup($group_id)
    {
        $stmt = $this->db->prepare(
            "SELECT g.*, 
                    COUNT(gm.user_id) as member_count
             FROM `Groups` g
             LEFT JOIN GroupMembers gm ON g.group_id = gm.group_id
             WHERE g.group_id = ? AND g.university_id IS NOT NULL
             GROUP BY g.group_id"
        );
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Update a university group
     * @param int $group_id Group ID
     * @param string $name Group name
     * @param string $description Group description
     * @param string $group_type Group type
     * @return bool True on success, false on failure
     */
    public function updateUniversityGroup($group_id, $name, $description, $group_type)
    {
        $stmt = $this->db->prepare(
            "UPDATE `Groups` SET name = ?, description = ?, group_type = ? WHERE group_id = ? AND university_id IS NOT NULL"
        );
        $stmt->bind_param("sssi", $name, $description, $group_type, $group_id);
        return $stmt->execute();
    }

    /**
     * Delete a university group
     * @param int $group_id Group ID
     * @return bool True on success, false on failure
     */
    public function deleteUniversityGroup($group_id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `Groups` WHERE group_id = ? AND university_id IS NOT NULL"
        );
        $stmt->bind_param("i", $group_id);
        return $stmt->execute();
    }

    /**
     * Get total count of university groups
     * @param int $university_id University ID
     * @return int Total count
     */
    public function countUniversityGroups($university_id)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM `Groups` WHERE university_id = ?"
        );
        $stmt->bind_param("i", $university_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] ?? 0;
    }

    /**
     * Get group members
     * @param int $group_id Group ID
     * @return array|bool Array of members on success, false on failure
     */
    public function getGroupMembers($group_id)
    {
        $stmt = $this->db->prepare(
            "SELECT u.user_id, u.first_name, u.last_name, u.email, gm.joined_at
             FROM GroupMembers gm
             JOIN Users u ON gm.user_id = u.user_id
             WHERE gm.group_id = ?
             ORDER BY gm.joined_at DESC"
        );
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Add user to group
     * @param int $group_id Group ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function addGroupMember($group_id, $user_id)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO GroupMembers (group_id, user_id, joined_at) VALUES (?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE joined_at = NOW()"
        );
        $stmt->bind_param("ii", $group_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Remove user from group
     * @param int $group_id Group ID
     * @param int $user_id User ID
     * @return bool True on success, false on failure
     */
    public function removeGroupMember($group_id, $user_id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM GroupMembers WHERE group_id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $group_id, $user_id);
        return $stmt->execute();
    }
}

?>

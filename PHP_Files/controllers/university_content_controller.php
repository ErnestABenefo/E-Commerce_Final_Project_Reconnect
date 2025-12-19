<?php

require_once __DIR__ . '/../classes/university_content_class.php';
require_once __DIR__ . '/university_admin_controller.php';

/**
 * University Content Controller - wrapper functions for university posts and groups
 * Enforces permission checks: only university admins can create/edit content
 */

// ==================== POSTS ====================

/**
 * Create a university post (admin only)
 * @param int $university_id University ID
 * @param int $user_id User ID of the requesting admin
 * @param string $content Post content
 * @param string $post_type Post type
 * @return int|array Post ID on success, error array on failure
 */
function create_university_post_ctr($university_id, $user_id, $content, $post_type = 'announcement')
{
    // Check if user is admin of this university
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    // Validate inputs
    if (empty(trim($content))) {
        return array('error' => 'Post content cannot be empty');
    }
    if (strlen($content) > 5000) {
        return array('error' => 'Post content exceeds maximum length (5000 characters)');
    }
    if (empty($post_type)) {
        $post_type = 'announcement';
    }

    // Create post
    $content_obj = new UniversityContent();
    $post_id = $content_obj->createUniversityPost($university_id, $content, $post_type);

    if ($post_id) {
        return $post_id;
    }
    return array('error' => 'Failed to create post');
}

/**
 * Get university posts
 * @param int $university_id University ID
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Posts array
 */
function get_university_posts_ctr($university_id, $limit = 10, $offset = 0)
{
    $content_obj = new UniversityContent();
    $posts = $content_obj->getUniversityPosts($university_id, $limit, $offset);
    return $posts ?: array();
}

/**
 * Get a single university post
 * @param int $post_id Post ID
 * @return array|bool Post data or false
 */
function get_university_post_ctr($post_id)
{
    $content_obj = new UniversityContent();
    return $content_obj->getUniversityPost($post_id);
}

/**
 * Update university post (admin only)
 * @param int $post_id Post ID
 * @param int $user_id User ID of the requesting admin
 * @param string $content New content
 * @param string $post_type New post type
 * @return bool True on success, error array on failure
 */
function update_university_post_ctr($post_id, $user_id, $content, $post_type)
{
    // Get post to verify ownership/permission
    $content_obj = new UniversityContent();
    $post = $content_obj->getUniversityPost($post_id);

    if (!$post) {
        return array('error' => 'Post not found');
    }

    $university_id = $post['university_id'];

    // Check if user is admin of the university that owns this post
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    // Validate inputs
    if (empty(trim($content))) {
        return array('error' => 'Post content cannot be empty');
    }
    if (strlen($content) > 5000) {
        return array('error' => 'Post content exceeds maximum length');
    }

    // Update
    if ($content_obj->updateUniversityPost($post_id, $content, $post_type)) {
        return true;
    }
    return array('error' => 'Failed to update post');
}

/**
 * Delete university post (admin only)
 * @param int $post_id Post ID
 * @param int $user_id User ID of the requesting admin
 * @return bool True on success, error array on failure
 */
function delete_university_post_ctr($post_id, $user_id)
{
    // Get post
    $content_obj = new UniversityContent();
    $post = $content_obj->getUniversityPost($post_id);

    if (!$post) {
        return array('error' => 'Post not found');
    }

    $university_id = $post['university_id'];

    // Check if user is admin
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    if ($content_obj->deleteUniversityPost($post_id)) {
        return true;
    }
    return array('error' => 'Failed to delete post');
}

// ==================== GROUPS ====================

/**
 * Create a university group (admin only)
 * @param int $university_id University ID
 * @param int $user_id User ID of the requesting admin
 * @param string $name Group name
 * @param string $description Group description
 * @param string $group_type Group type
 * @return int|array Group ID on success, error array on failure
 */
function create_university_group_ctr($university_id, $user_id, $name, $description, $group_type = 'official')
{
    // Check if user is admin
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    // Validate inputs
    if (empty(trim($name))) {
        return array('error' => 'Group name cannot be empty');
    }
    if (strlen($name) > 255) {
        return array('error' => 'Group name exceeds maximum length');
    }
    if (strlen($description) > 1000) {
        return array('error' => 'Group description exceeds maximum length');
    }

    // Create group
    $content_obj = new UniversityContent();
    $group_id = $content_obj->createUniversityGroup($university_id, $name, $description, $group_type, $user_id);

    if ($group_id) {
        // Add creator as member
        $content_obj->addGroupMember($group_id, $user_id);
        return $group_id;
    }
    return array('error' => 'Failed to create group');
}

/**
 * Get university groups
 * @param int $university_id University ID
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Groups array
 */
function get_university_groups_ctr($university_id, $limit = 10, $offset = 0)
{
    $content_obj = new UniversityContent();
    $groups = $content_obj->getUniversityGroups($university_id, $limit, $offset);
    return $groups ?: array();
}

/**
 * Get a single university group
 * @param int $group_id Group ID
 * @return array|bool Group data or false
 */
function get_university_group_ctr($group_id)
{
    $content_obj = new UniversityContent();
    return $content_obj->getUniversityGroup($group_id);
}

/**
 * Update university group (admin only)
 * @param int $group_id Group ID
 * @param int $user_id User ID of the requesting admin
 * @param string $name New name
 * @param string $description New description
 * @param string $group_type New group type
 * @return bool True on success, error array on failure
 */
function update_university_group_ctr($group_id, $user_id, $name, $description, $group_type)
{
    // Get group
    $content_obj = new UniversityContent();
    $group = $content_obj->getUniversityGroup($group_id);

    if (!$group) {
        return array('error' => 'Group not found');
    }

    $university_id = $group['university_id'];

    // Check if user is admin
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    // Validate
    if (empty(trim($name))) {
        return array('error' => 'Group name cannot be empty');
    }
    if (strlen($name) > 255) {
        return array('error' => 'Group name exceeds maximum length');
    }

    if ($content_obj->updateUniversityGroup($group_id, $name, $description, $group_type)) {
        return true;
    }
    return array('error' => 'Failed to update group');
}

/**
 * Delete university group (admin only)
 * @param int $group_id Group ID
 * @param int $user_id User ID of the requesting admin
 * @return bool True on success, error array on failure
 */
function delete_university_group_ctr($group_id, $user_id)
{
    // Get group
    $content_obj = new UniversityContent();
    $group = $content_obj->getUniversityGroup($group_id);

    if (!$group) {
        return array('error' => 'Group not found');
    }

    $university_id = $group['university_id'];

    // Check if user is admin
    if (!is_university_admin_ctr($university_id, $user_id)) {
        return array('error' => 'Unauthorized: You are not an admin of this university');
    }

    if ($content_obj->deleteUniversityGroup($group_id)) {
        return true;
    }
    return array('error' => 'Failed to delete group');
}

?>

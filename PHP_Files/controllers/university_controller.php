<?php

require_once '../classes/university_class.php';

/**
 * University Controller - Functions to handle university operations
 * Acts as an intermediary between forms and the University class
 */

// ==================== CREATE OPERATIONS ====================

/**
 * Register a new university
 * @param array $data University data
 * @return int|bool University ID on success, false on failure
 */
function register_university_ctr($data)
{
    $university = new University();
    $university_id = $university->createUniversity($data);
    if ($university_id) {
        return $university_id;
    }
    return false;
}

// ==================== READ OPERATIONS ====================

/**
 * Get university by ID
 * @param int $university_id University ID
 * @return array|bool University data on success, false on failure
 */
function get_university_by_id_ctr($university_id)
{
    $university = new University();
    return $university->getUniversityById($university_id);
}

/**
 * Get university by name
 * @param string $name University name
 * @return array|bool University data on success, false on failure
 */
function get_university_by_name_ctr($name)
{
    $university = new University();
    return $university->getUniversityByName($name);
}

/**
 * Get all universities
 * @return array|bool Array of all universities on success, false on failure
 */
function get_all_universities_ctr()
{
    $university = new University();
    return $university->getAllUniversities();
}

/**
 * Search universities by name or location
 * @param string $search_term Search term
 * @return array|bool Array of matching universities on success, false on failure
 */
function search_universities_ctr($search_term)
{
    $university = new University();
    return $university->searchUniversities($search_term);
}

/**
 * Get total number of universities
 * @return int Total number of universities
 */
function get_total_universities_ctr()
{
    $university = new University();
    return $university->getTotalUniversities();
}

/**
 * Get paginated universities
 * @param int $limit Number of records per page
 * @param int $offset Starting position
 * @return array|bool Array of universities on success, false on failure
 */
function get_paginated_universities_ctr($limit = 10, $offset = 0)
{
    $university = new University();
    return $university->getPaginatedUniversities($limit, $offset);
}

// ==================== UPDATE OPERATIONS ====================

/**
 * Update university information
 * @param int $university_id University ID
 * @param string $name University name
 * @param string $location University location
 * @return bool True on success, false on failure
 */
function update_university_ctr($university_id, $name, $location)
{
    $university = new University();
    return $university->updateUniversity($university_id, $name, $location);
}

// ==================== DELETE OPERATIONS ====================

/**
 * Delete university
 * @param int $university_id University ID
 * @return bool True on success, false on failure
 */
function delete_university_ctr($university_id)
{
    $university = new University();
    return $university->deleteUniversity($university_id);
}

// ==================== VALIDATION OPERATIONS ====================

/**
 * Check if university exists
 * @param string $name University name
 * @return bool True if exists, false otherwise
 */
function university_exists_ctr($name)
{
    $university = new University();
    return $university->universityExists($name);
}

/**
 * Validate university name
 * @param string $name University name to validate
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function validate_university_name_ctr($name)
{
    $result = array('valid' => true, 'message' => '');
    
    // Check length
    if (strlen($name) < 2) {
        $result['valid'] = false;
        $result['message'] = 'University name must be at least 2 characters long';
        return $result;
    }
    
    if (strlen($name) > 255) {
        $result['valid'] = false;
        $result['message'] = 'University name must not exceed 255 characters';
        return $result;
    }
    
    return $result;
}

/**
 * Validate university location
 * @param string $location University location to validate
 * @return array Array with 'valid' (bool) and 'message' (string)
 */
function validate_university_location_ctr($location)
{
    $result = array('valid' => true, 'message' => '');
    
    // Check length
    if (strlen($location) < 2) {
        $result['valid'] = false;
        $result['message'] = 'University location must be at least 2 characters long';
        return $result;
    }
    
    if (strlen($location) > 255) {
        $result['valid'] = false;
        $result['message'] = 'University location must not exceed 255 characters';
        return $result;
    }
    
    return $result;
}

/**
 * Sanitize input for university operations
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize_university_input_ctr($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate all university registration fields
 * @param string $name University name
 * @param string $location University location
 * @return array Array with 'valid' (bool) and 'errors' (array of error messages)
 */
function validate_university_registration_ctr($name, $location)
{
    $result = array('valid' => true, 'errors' => array());
    
    // Validate name
    $name_validation = validate_university_name_ctr($name);
    if (!$name_validation['valid']) {
        $result['valid'] = false;
        $result['errors'][] = $name_validation['message'];
    }
    
    // Validate location
    $location_validation = validate_university_location_ctr($location);
    if (!$location_validation['valid']) {
        $result['valid'] = false;
        $result['errors'][] = $location_validation['message'];
    }
    
    // Check if university already exists
    if (university_exists_ctr($name)) {
        $result['valid'] = false;
        $result['errors'][] = 'University already exists. Please use a different name.';
    }
    
    return $result;
}

?>

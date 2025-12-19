<?php

header('Content-Type: application/json');

require_once '../controllers/university_controller.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Check if all required fields are present
$required_fields = array('university_name', 'university_location');
$missing_fields = array();

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required fields: ' . implode(', ', $missing_fields);
    echo json_encode($response);
    exit();
}

// Sanitize inputs using controller helper
$university_data = array(
    'name' => sanitize_university_input_ctr($_POST['university_name']),
    'location' => sanitize_university_input_ctr($_POST['university_location']),
    'website' => isset($_POST['university_website']) ? sanitize_university_input_ctr($_POST['university_website']) : null,
    'contact_email' => isset($_POST['contact_email']) ? sanitize_university_input_ctr($_POST['contact_email']) : null,
    'contact_phone' => isset($_POST['contact_phone']) ? sanitize_university_input_ctr($_POST['contact_phone']) : null,
    'address' => isset($_POST['address']) ? sanitize_university_input_ctr($_POST['address']) : null,
    'established_year' => isset($_POST['established_year']) && !empty($_POST['established_year']) ? (int)$_POST['established_year'] : null,
    'university_type' => isset($_POST['university_type']) && !empty($_POST['university_type']) ? sanitize_university_input_ctr($_POST['university_type']) : null,
    'description' => isset($_POST['description']) ? sanitize_university_input_ctr($_POST['description']) : null
);

// Validate all registration fields using controller
$validation = validate_university_registration_ctr($university_data['name'], $university_data['location']);

if (!$validation['valid']) {
    $response['status'] = 'error';
    $response['message'] = implode('; ', $validation['errors']);
    echo json_encode($response);
    exit();
}

// Register the university using controller
$university_id = register_university_ctr($university_data);

if ($university_id) {
    // If departments were provided, insert them
    if (isset($_POST['departments']) && is_array($_POST['departments'])) {
        require_once '../settings/db_class.php';
        $db = new db_connection();
        $conn = $db->db_conn();
        
        if (!$conn) {
            $response['status'] = 'error';
            $response['message'] = 'Database connection failed for departments';
            echo json_encode($response);
            exit();
        }
        
        $dept_insert_stmt = $conn->prepare("INSERT INTO AcademicDepartment (university_id, faculty, department_name) VALUES (?, ?, ?)");
        
        if (!$dept_insert_stmt) {
            $response['status'] = 'error';
            $response['message'] = 'Failed to prepare department insert: ' . $conn->error;
            echo json_encode($response);
            exit();
        }
        
        foreach ($_POST['departments'] as $dept) {
            $faculty = isset($dept['faculty']) ? sanitize_university_input_ctr($dept['faculty']) : null;
            $dept_name = isset($dept['department_name']) ? sanitize_university_input_ctr($dept['department_name']) : null;
            
            // Only insert if department name is provided
            if (!empty($dept_name)) {
                $dept_insert_stmt->bind_param('iss', $university_id, $faculty, $dept_name);
                if (!$dept_insert_stmt->execute()) {
                    // Don't fail the whole registration if department insert fails
                    error_log("Failed to insert department: " . $dept_insert_stmt->error);
                }
            }
        }
        $dept_insert_stmt->close();
    }
    
    $response['status'] = 'success';
    $response['message'] = 'University registered successfully!';
    $response['university_id'] = $university_id;
    $response['university_name'] = $university_data['name'];
    $response['university_location'] = $university_data['location'];
} else {
    $response['status'] = 'error';
    $response['message'] = 'Failed to register university. Please try again later.';
    
    // Add more detailed error information for debugging
    require_once '../settings/db_class.php';
    $db = new db_connection();
    $conn = $db->db_conn();
    if ($conn && $conn->error) {
        $response['debug_error'] = $conn->error;
    }
}

echo json_encode($response);
exit();

?>

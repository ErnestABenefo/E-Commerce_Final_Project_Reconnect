<?php
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../controllers/university_admin_controller.php';
require_once '../../settings/db_class.php';

// Clear any accidental output
ob_clean();
header('Content-Type: application/json');

// Check global admin permission
$current_user = $_SESSION['user_id'] ?? null;
if (!is_global_admin_ctr($current_user)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? null;
$location = $input['location'] ?? null;
$website = $input['website'] ?? null;
$contact_email = $input['contact_email'] ?? null;
$contact_phone = $input['contact_phone'] ?? null;
$address = $input['address'] ?? null;
$established_year = $input['established_year'] ?? null;
$university_type = $input['university_type'] ?? 'public';
$description = $input['description'] ?? null;
$departments = $input['departments'] ?? [];

if (!$name || !$location) {
    echo json_encode(['success' => false, 'message' => 'Name and location are required']);
    exit;
}

// Validate university type
if (!in_array($university_type, ['public', 'private', 'religious', 'technical', 'other'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid university type']);
    exit;
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if university already exists
    $stmt = $conn->prepare("SELECT university_id FROM University WHERE name = ?");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'University with this name already exists']);
        exit;
    }
    $stmt->close();
    
    // Handle empty values as NULL
    $website = !empty($website) ? $website : null;
    $contact_email = !empty($contact_email) ? $contact_email : null;
    $contact_phone = !empty($contact_phone) ? $contact_phone : null;
    $address = !empty($address) ? $address : null;
    $established_year = !empty($established_year) ? (int)$established_year : null;
    $description = !empty($description) ? $description : null;
    
    // Insert new university with all fields
    $stmt = $conn->prepare(
        "INSERT INTO University (name, location, website, contact_email, contact_phone, address, 
         established_year, university_type, description, created_by) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssissi', $name, $location, $website, $contact_email, $contact_phone, 
                      $address, $established_year, $university_type, $description, $current_user);
    
    if (!$stmt->execute()) {
        $conn->rollback();
        $error = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to add university: ' . $error]);
        exit;
    }
    
    $university_id = $stmt->insert_id;
    $stmt->close();
    
    // Insert departments if provided
    if (!empty($departments) && is_array($departments)) {
        $dept_stmt = $conn->prepare(
            "INSERT INTO AcademicDepartment (university_id, faculty, department_name) VALUES (?, ?, ?)"
        );
        
        $departments_added = 0;
        foreach ($departments as $dept) {
            if (isset($dept['department_name']) && !empty(trim($dept['department_name']))) {
                $faculty = $dept['faculty'] ?? null;
                $dept_name = trim($dept['department_name']);
                
                $dept_stmt->bind_param('iss', $university_id, $faculty, $dept_name);
                if ($dept_stmt->execute()) {
                    $departments_added++;
                }
            }
        }
        $dept_stmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = 'University added successfully';
    if (isset($departments_added) && $departments_added > 0) {
        $message .= ' with ' . $departments_added . ' department(s)';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message, 
        'university_id' => $university_id,
        'departments_added' => $departments_added ?? 0
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

try {
    session_start();
    
    if (!file_exists('../../controllers/university_admin_controller.php')) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Controller file not found']);
        exit;
    }
    
    if (!file_exists('../../settings/db_class.php')) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database class file not found']);
        exit;
    }
    
    require_once '../../controllers/university_admin_controller.php';
    require_once '../../settings/db_class.php';
    
    // Clear any accidental output
    ob_clean();
    header('Content-Type: application/json');
    
    // Check global admin permission
    $current_user = $_SESSION['user_id'] ?? null;
    
    if (!function_exists('is_global_admin_ctr')) {
        echo json_encode(['success' => false, 'message' => 'Admin function not found']);
        exit;
    }
    
    if (!is_global_admin_ctr($current_user)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // Get JSON input
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit;
    }
    
    $university_id = $input['university_id'] ?? null;
    $name = $input['name'] ?? null;
    $location = $input['location'] ?? null;
    $website = $input['website'] ?? null;
    $contact_email = $input['contact_email'] ?? null;
    $contact_phone = $input['contact_phone'] ?? null;
    $address = $input['address'] ?? null;
    $established_year = $input['established_year'] ?? null;
    $university_type = $input['university_type'] ?? null;
    $description = $input['description'] ?? null;
    $departments = $input['departments'] ?? null;
    
    if (!$university_id || !$name || !$location || !$university_type) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate university type
    if (!in_array($university_type, ['public', 'private', 'religious', 'technical', 'other'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid university type']);
        exit;
    }
    
    $db = new db_connection();
    $conn = $db->db_conn();
    
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if university exists
    $stmt = $conn->prepare("SELECT university_id FROM University WHERE university_id = ?");
    if (!$stmt) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database prepare error']);
        exit;
    }
    
    $stmt->bind_param('i', $university_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'University not found']);
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
    
    // Update university
    $stmt = $conn->prepare(
        "UPDATE University SET 
         name = ?, location = ?, website = ?, contact_email = ?, contact_phone = ?, 
         address = ?, established_year = ?, university_type = ?, description = ?
         WHERE university_id = ?"
    );
    
    if (!$stmt) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update statement']);
        exit;
    }
    
    $stmt->bind_param('ssssssissi', $name, $location, $website, $contact_email, $contact_phone,
                      $address, $established_year, $university_type, $description, $university_id);
    
    if (!$stmt->execute()) {
        $conn->rollback();
        $error = $stmt->error;
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update university: ' . $error]);
        exit;
    }
    $stmt->close();
    
    // Handle departments if provided
    $dept_changes = ['updated' => 0, 'added' => 0, 'deleted' => 0];
    
    if ($departments && is_array($departments)) {
        // Delete departments
        if (!empty($departments['deleted']) && is_array($departments['deleted'])) {
            $delete_stmt = $conn->prepare("DELETE FROM AcademicDepartment WHERE department_id = ?");
            foreach ($departments['deleted'] as $dept_id) {
                $delete_stmt->bind_param('i', $dept_id);
                if ($delete_stmt->execute()) {
                    $dept_changes['deleted']++;
                }
            }
            $delete_stmt->close();
        }
        
        // Update existing departments
        if (!empty($departments['existing']) && is_array($departments['existing'])) {
            $update_stmt = $conn->prepare(
                "UPDATE AcademicDepartment SET faculty = ?, department_name = ? WHERE department_id = ?"
            );
            foreach ($departments['existing'] as $dept) {
                if (isset($dept['department_id']) && isset($dept['department_name'])) {
                    $faculty = $dept['faculty'] ?? null;
                    $dept_name = $dept['department_name'];
                    $dept_id = $dept['department_id'];
                    
                    $update_stmt->bind_param('ssi', $faculty, $dept_name, $dept_id);
                    if ($update_stmt->execute()) {
                        $dept_changes['updated']++;
                    }
                }
            }
            $update_stmt->close();
        }
        
        // Add new departments
        if (!empty($departments['new']) && is_array($departments['new'])) {
            $insert_stmt = $conn->prepare(
                "INSERT INTO AcademicDepartment (university_id, faculty, department_name) VALUES (?, ?, ?)"
            );
            foreach ($departments['new'] as $dept) {
                if (isset($dept['department_name']) && !empty(trim($dept['department_name']))) {
                    $faculty = $dept['faculty'] ?? null;
                    $dept_name = trim($dept['department_name']);
                    
                    $insert_stmt->bind_param('iss', $university_id, $faculty, $dept_name);
                    if ($insert_stmt->execute()) {
                        $dept_changes['added']++;
                    }
                }
            }
            $insert_stmt->close();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = 'University updated successfully';
    if ($dept_changes['added'] > 0 || $dept_changes['updated'] > 0 || $dept_changes['deleted'] > 0) {
        $changes = [];
        if ($dept_changes['added'] > 0) $changes[] = $dept_changes['added'] . ' added';
        if ($dept_changes['updated'] > 0) $changes[] = $dept_changes['updated'] . ' updated';
        if ($dept_changes['deleted'] > 0) $changes[] = $dept_changes['deleted'] . ' deleted';
        $message .= '. Departments: ' . implode(', ', $changes);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'department_changes' => $dept_changes
    ]);
    
} catch (Throwable $e) {
    if (isset($conn) && $conn) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackEx) {
            // Ignore rollback errors
        }
    }
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

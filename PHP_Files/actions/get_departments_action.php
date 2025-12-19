<?php
// Fetch departments for a specific university

header('Content-Type: application/json');

require_once __DIR__ . '/../settings/db_class.php';

$response = array();

$university_id = isset($_GET['university_id']) ? (int)$_GET['university_id'] : 0;

if (!$university_id) {
    $response['status'] = 'error';
    $response['message'] = 'University ID is required';
    echo json_encode($response);
    exit();
}

try {
    $db_obj = new db_connection();
    $db_obj->db_connect();
    $db = $db_obj->db;

    $stmt = $db->prepare("SELECT department_id, department_name, faculty FROM AcademicDepartment WHERE university_id = ? ORDER BY department_name ASC");
    $stmt->bind_param("i", $university_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $departments = array();
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    $response['status'] = 'success';
    $response['departments'] = $departments;
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>

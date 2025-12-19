<?php
// Fetch all universities

header('Content-Type: application/json');

require_once __DIR__ . '/../settings/db_class.php';

$response = array();

try {
    $db_obj = new db_connection();
    
    if (!$db_obj->db_connect()) {
        $response['status'] = 'error';
        $response['message'] = 'Database connection failed';
        echo json_encode($response);
        exit();
    }
    
    $db = $db_obj->db;

    $sql = "SELECT university_id, name, location FROM University ORDER BY name ASC";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $universities = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $universities[] = $row;
        }
        $response['status'] = 'success';
        $response['universities'] = $universities;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to fetch universities: ' . mysqli_error($db);
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit();
?>

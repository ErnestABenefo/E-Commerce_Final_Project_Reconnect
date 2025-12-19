<?php
session_start();
header('Content-Type: application/json');

require_once '../../settings/db_class.php';
require_once '../../controllers/university_admin_controller.php';

$current_user = $_SESSION['user_id'] ?? null;
if (!is_global_admin_ctr($current_user)) {
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$db = new db_connection();
$conn = $db->db_conn();

$sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY table_name";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB error preparing statement']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = $row['table_name'];
}

echo json_encode(['status' => 'success', 'tables' => $tables]);
exit;
?>
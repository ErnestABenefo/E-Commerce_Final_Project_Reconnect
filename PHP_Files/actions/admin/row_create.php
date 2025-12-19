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

$data = $_POST;
$table = isset($data['table']) ? trim($data['table']) : '';
if (!$table) {
    echo json_encode(['status'=>'error','message'=>'Table required']);
    exit;
}

// remove table key
unset($data['table']);

$db = new db_connection();
$conn = $db->db_conn();

// validate table
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$check->bind_param('s', $table);
$check->execute();
$cr = $check->get_result()->fetch_assoc();
if (!$cr || (int)$cr['cnt'] === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid table']);
    exit;
}

$columns = array_keys($data);
if (count($columns) === 0) {
    echo json_encode(['status'=>'error','message'=>'No data provided']);
    exit;
}

$cols_sql = implode(',', array_map(function($c) use ($conn){ return "`".$conn->real_escape_string($c)."`"; }, $columns));
$placeholders = implode(',', array_fill(0, count($columns), '?'));

$sql = "INSERT INTO `".$conn->real_escape_string($table)."` ($cols_sql) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status'=>'error','message'=>'DB prepare failed']);
    exit;
}

// bind as strings for simplicity
$types = str_repeat('s', count($columns));
$values = array_values($data);
$stmt->bind_param($types, ...$values);
if ($stmt->execute()) {
    echo json_encode(['status'=>'success','insert_id'=>$conn->insert_id]);
} else {
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
exit;
?>
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
$pk = isset($data['pk']) ? trim($data['pk']) : '';
$pk_val = isset($data['pk_val']) ? $data['pk_val'] : null;

if (!$table || !$pk || $pk_val === null) {
    echo json_encode(['status'=>'error','message'=>'table, pk and pk_val are required']);
    exit;
}

unset($data['table'], $data['pk'], $data['pk_val']);

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

if (count($data) === 0) {
    echo json_encode(['status'=>'error','message'=>'No fields to update']);
    exit;
}

$setParts = [];
$values = [];
foreach ($data as $k => $v) {
    $setParts[] = "`".$conn->real_escape_string($k)."` = ?";
    $values[] = $v;
}
$setSql = implode(', ', $setParts);

$sql = "UPDATE `".$conn->real_escape_string($table)."` SET $setSql WHERE `".$conn->real_escape_string($pk)."` = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['status'=>'error','message'=>'DB prepare failed']); exit; }

$types = str_repeat('s', count($values)) . 's';
$values[] = $pk_val;
$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','affected_rows'=>$stmt->affected_rows]);
} else {
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
exit;
?>
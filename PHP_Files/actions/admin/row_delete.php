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

$table = isset($_POST['table']) ? trim($_POST['table']) : '';
$pk = isset($_POST['pk']) ? trim($_POST['pk']) : '';
$pk_val = isset($_POST['pk_val']) ? $_POST['pk_val'] : null;

if (!$table || !$pk || $pk_val === null) {
    echo json_encode(['status'=>'error','message'=>'table, pk and pk_val are required']);
    exit;
}

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

$sql = "DELETE FROM `".$conn->real_escape_string($table)."` WHERE `".$conn->real_escape_string($pk)."` = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['status'=>'error','message'=>'DB prepare failed']); exit; }

$stmt->bind_param('s', $pk_val);
if ($stmt->execute()) {
    echo json_encode(['status'=>'success','affected_rows'=>$stmt->affected_rows]);
} else {
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
exit;
?>
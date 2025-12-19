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

$table = isset($_GET['table']) ? trim($_GET['table']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if (empty($table)) {
    echo json_encode(['status' => 'error', 'message' => 'Table required']);
    exit;
}

$db = new db_connection();
$conn = $db->db_conn();

// whitelist the table by checking information_schema
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$check->bind_param('s', $table);
$check->execute();
$cr = $check->get_result()->fetch_assoc();
if (!$cr || (int)$cr['cnt'] === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid table']);
    exit;
}

// fetch column metadata
$colSql = "SELECT COLUMN_NAME, COLUMN_KEY FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION";
$colStmt = $conn->prepare($colSql);
$colStmt->bind_param('s', $table);
$colStmt->execute();
$colsRes = $colStmt->get_result();
$columns = [];
$primaryKey = null;
while ($c = $colsRes->fetch_assoc()) {
    $columns[] = $c['COLUMN_NAME'];
    if ($c['COLUMN_KEY'] === 'PRI' && !$primaryKey) $primaryKey = $c['COLUMN_NAME'];
}

// fetch rows
$sql = "SELECT * FROM `" . $conn->real_escape_string($table) . "` LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'DB prepare error']);
    exit;
}
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode(['status' => 'success', 'columns' => $columns, 'primary' => $primaryKey, 'rows' => $rows]);
exit;
?>
<?php
header('Content-Type: application/json');
session_start();

$response = ['status' => 'error', 'posts' => []];

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
if ($limit <= 0) $limit = 6;

require_once __DIR__ . '/../settings/db_class.php';
$db = new db_connection();
$conn = $db->db_conn();

$stmt = $conn->prepare("SELECT p.post_id, p.content, p.created_at, u.first_name, u.last_name FROM Posts p JOIN Users u ON p.user_id = u.user_id ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$posts = [];
while ($row = $res->fetch_assoc()) {
    $posts[] = [
        'post_id' => (int)$row['post_id'],
        'content' => $row['content'],
        'created_at' => date('M j, Y H:i', strtotime($row['created_at'])),
        'user_name' => trim($row['first_name'] . ' ' . $row['last_name'])
    ];
}
$stmt->close();

$response['status'] = 'success';
$response['posts'] = $posts;

echo json_encode($response);
exit();

?>

<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once('../settings/db_class.php');

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$chat_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$chat_user_id) {
    echo json_encode(['error' => 'Invalid user']);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();

    if (!$conn) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    // Get messages between two users (using Messages table, not DirectMessages)
    $query = "SELECT message_id, sender_id, receiver_id, content, sent_at 
              FROM Messages 
              WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
              AND message_id > ?
              ORDER BY sent_at ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['error' => 'Query preparation failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param('iiiii', $user_id, $chat_user_id, $chat_user_id, $user_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load messages']);
}
?>

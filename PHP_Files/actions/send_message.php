<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once('../settings/db_class.php');

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$receiver_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$receiver_id || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

if ($receiver_id === $user_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot send message to yourself']);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();

    if (!$conn) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }

    // Check if users are connected
    $check_query = "SELECT * FROM UserConnections 
                    WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)) 
                    AND status = 'accepted'";
    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query preparation failed']);
        exit();
    }
    $stmt->bind_param('iiii', $user_id, $receiver_id, $receiver_id, $user_id);
    $stmt->execute();
    $is_connected = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$is_connected) {
        echo json_encode(['success' => false, 'error' => 'You must be connected to send messages']);
        exit();
    }

    // Insert message (using Messages table with 'content' column, not DirectMessages with 'message')
    $insert_query = "INSERT INTO Messages (sender_id, receiver_id, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query preparation failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param('iis', $user_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        
        echo json_encode([
            'success' => true, 
            'message_id' => $message_id,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Failed to insert message: ' . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to send message: ' . $e->getMessage()]);
}
?>

<?php
// Action to handle group chat messages
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/group_controller.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'send':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            
            if (!$group_id || !$message) {
                throw new Exception('Group ID and message are required');
            }
            
            $new_message = send_group_message_ctr($user_id, $group_id, $message);
            
            if ($new_message) {
                echo json_encode([
                    'status' => 'success',
                    'message' => $new_message
                ]);
            } else {
                throw new Exception('Failed to send message or you are not a member of this group');
            }
            break;
            
        case 'get':
            $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            if (!$group_id) {
                throw new Exception('Group ID is required');
            }
            
            $messages = get_group_messages_ctr($user_id, $group_id, $limit, $offset);
            
            if ($messages === false) {
                throw new Exception('You are not a member of this group');
            }
            
            echo json_encode([
                'status' => 'success',
                'messages' => $messages
            ]);
            break;
            
        case 'enroll':
            // Auto-enroll user in their university and year groups
            $enrolled = auto_enroll_user_ctr($user_id);
            
            echo json_encode([
                'status' => 'success',
                'enrolled' => $enrolled,
                'message' => $enrolled ? 'Successfully enrolled in groups' : 'No groups to enroll in'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

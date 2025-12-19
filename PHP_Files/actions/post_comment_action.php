<?php

header('Content-Type: application/json');
session_start();

$response = ['status' => 'error', 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'You must be logged in';
    echo json_encode($response);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';
$db = new db_connection();
$conn = $db->db_conn();

$user_id = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a comment to a post
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if (!$post_id) {
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }
    
    if (empty($comment)) {
        $response['message'] = 'Comment cannot be empty';
        echo json_encode($response);
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO PostComments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param('iis', $post_id, $user_id, $comment);
    
    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        
        // Fetch the created comment with user info
        $stmt = $conn->prepare("SELECT c.comment_id, c.comment, c.created_at, u.first_name, u.last_name 
                               FROM PostComments c 
                               JOIN Users u ON c.user_id = u.user_id 
                               WHERE c.comment_id = ?");
        $stmt->bind_param('i', $comment_id);
        $stmt->execute();
        $commentData = $stmt->get_result()->fetch_assoc();
        
        $response['status'] = 'success';
        $response['message'] = 'Comment added';
        $response['comment'] = [
            'comment_id' => (int)$commentData['comment_id'],
            'comment' => $commentData['comment'],
            'created_at' => date('M j, Y H:i', strtotime($commentData['created_at'])),
            'user_name' => trim($commentData['first_name'] . ' ' . $commentData['last_name'])
        ];
    } else {
        $response['message'] = 'Failed to add comment';
    }
    $stmt->close();
    
} elseif ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get comments for a post
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    if (!$post_id) {
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }
    
    // Check if post exists first
    $checkStmt = $conn->prepare("SELECT post_id FROM Posts WHERE post_id = ?");
    $checkStmt->bind_param('i', $post_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows === 0) {
        $response['message'] = 'Post not found';
        echo json_encode($response);
        exit();
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare("SELECT c.comment_id, c.comment, c.created_at, u.first_name, u.last_name, u.profile_photo
                           FROM PostComments c 
                           JOIN Users u ON c.user_id = u.user_id 
                           WHERE c.post_id = ?
                           ORDER BY c.created_at ASC
                           LIMIT ? OFFSET ?");
    $stmt->bind_param('iii', $post_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'comment_id' => (int)$row['comment_id'],
            'comment' => $row['comment'],
            'created_at' => date('M j, Y H:i', strtotime($row['created_at'])),
            'user_name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'profile_photo' => $row['profile_photo']
        ];
    }
    
    $response['status'] = 'success';
    $response['comments'] = $comments;
    $stmt->close();
    
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete a comment (only if user owns it)
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
    
    if (!$comment_id) {
        $response['message'] = 'Comment ID is required';
        echo json_encode($response);
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM PostComments WHERE comment_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $comment_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Comment deleted';
    } else {
        $response['message'] = 'Failed to delete comment or you do not own this comment';
    }
    $stmt->close();
    
} else {
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
exit();

?>

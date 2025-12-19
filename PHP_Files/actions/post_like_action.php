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

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle like on a post
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    if (!$post_id) {
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }
    
    // Check if already liked
    $stmt = $conn->prepare("SELECT like_id FROM PostLikes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike - remove the like
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM PostLikes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param('ii', $post_id, $user_id);
        $stmt->execute();
        
        $response['status'] = 'success';
        $response['action'] = 'unliked';
        $response['message'] = 'Post unliked';
    } else {
        // Like - add the like
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO PostLikes (post_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $post_id, $user_id);
        $stmt->execute();
        
        $response['status'] = 'success';
        $response['action'] = 'liked';
        $response['message'] = 'Post liked';
    }
    
    // Get updated like count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM PostLikes WHERE post_id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $response['like_count'] = (int)$countResult['count'];
    $stmt->close();
    
} elseif ($action === 'count' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get like count for a post
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    
    if (!$post_id) {
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM PostLikes WHERE post_id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Check if current user liked it
    $stmt = $conn->prepare("SELECT like_id FROM PostLikes WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $post_id, $user_id);
    $stmt->execute();
    $userLiked = $stmt->get_result()->num_rows > 0;
    
    $response['status'] = 'success';
    $response['like_count'] = (int)$result['count'];
    $response['user_liked'] = $userLiked;
    $stmt->close();
    
} else {
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
exit();

?>

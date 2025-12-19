<?php
header('Content-Type: application/json');
session_start();

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'You must be logged in to post';
    echo json_encode($response);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Handle image upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['image']['type'];
    
    if (in_array($file_type, $allowed_types)) {
        $upload_dir = __DIR__ . '/../uploads/posts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = 'post_' . time() . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $image_url = '../uploads/posts/' . $file_name;
        }
    }
}

// Content or image must be provided
if ($content === '' && $image_url === null) {
    $response['message'] = 'Post must have content or an image';
    echo json_encode($response);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';
$db = new db_connection();
$conn = $db->db_conn();

// Check if user is acting as a university
$acting_as_university = isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true;
$creator_type = $acting_as_university ? 'university' : 'user';

if ($acting_as_university) {
    // Creating post as university
    $university_id = (int)$_SESSION['active_university_id'];
    $stmt = $conn->prepare("INSERT INTO Posts (university_id, content, image_url, post_type, creator_type, created_at) VALUES (?, ?, ?, 'text', 'university', NOW())");
    $stmt->bind_param('iss', $university_id, $content, $image_url);
} else {
    // Creating post as regular user
    $stmt = $conn->prepare("INSERT INTO Posts (user_id, content, image_url, post_type, creator_type, created_at) VALUES (?, ?, ?, 'text', 'user', NOW())");
    $stmt->bind_param('iss', $user_id, $content, $image_url);
}

$ok = $stmt->execute();
if (!$ok) {
    $response['message'] = 'Failed to save post';
    echo json_encode($response);
    exit();
}
$post_id = $stmt->insert_id;
$stmt->close();

// Fetch saved post with appropriate details
if ($acting_as_university) {
    $stmt = $conn->prepare("SELECT p.post_id, p.content, p.image_url, p.created_at, p.creator_type, uni.name as creator_name 
                           FROM Posts p 
                           JOIN University uni ON p.university_id = uni.university_id 
                           WHERE p.post_id = ? LIMIT 1");
} else {
    $stmt = $conn->prepare("SELECT p.post_id, p.content, p.image_url, p.created_at, p.creator_type, u.first_name, u.last_name 
                           FROM Posts p 
                           JOIN Users u ON p.user_id = u.user_id 
                           WHERE p.post_id = ? LIMIT 1");
}
$stmt->bind_param('i', $post_id);
$stmt->execute();
$res = $stmt->get_result();
$post = $res ? $res->fetch_assoc() : null;
$stmt->close();

if ($post) {
    $response['status'] = 'success';
    $response['message'] = 'Post created';
    
    // Set creator name based on context
    if ($acting_as_university) {
        $creator_name = $post['creator_name'];
    } else {
        $creator_name = trim($post['first_name'] . ' ' . $post['last_name']);
    }
    
    $response['post'] = [
        'post_id' => (int)$post['post_id'],
        'content' => $post['content'],
        'image_url' => $post['image_url'],
        'created_at' => date('M j, Y H:i', strtotime($post['created_at'])),
        'creator_name' => $creator_name,
        'creator_type' => $post['creator_type']
    ];
} else {
    $response['message'] = 'Failed to retrieve created post';
}

echo json_encode($response);
exit();

?>

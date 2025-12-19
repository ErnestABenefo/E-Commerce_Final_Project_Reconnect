<?php
// Action to load all posts from all users and universities
session_start();

require_once __DIR__ . '/../settings/db_class.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$db = new db_connection();
$conn = $db->db_conn();

// Query to get all posts from both users and universities with UNION
$sql = "
    (SELECT 
        p.post_id, 
        p.content, 
        p.image_url, 
        p.post_type, 
        p.created_at, 
        p.creator_type,
        u.first_name, 
        u.last_name,
        u.profile_photo,
        NULL as university_id,
        NULL as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked
    FROM Posts p 
    JOIN Users u ON p.user_id = u.user_id 
    WHERE p.creator_type = 'user')
    
    UNION ALL
    
    (SELECT 
        p.post_id, 
        p.content, 
        p.image_url, 
        p.post_type, 
        p.created_at, 
        p.creator_type,
        NULL as first_name,
        NULL as last_name,
        NULL as profile_photo,
        uni.university_id,
        uni.name as university_name,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id), 0) as like_count,
        COALESCE((SELECT COUNT(*) FROM PostComments WHERE post_id = p.post_id), 0) as comment_count,
        COALESCE((SELECT COUNT(*) FROM PostLikes WHERE post_id = p.post_id AND user_id = ?), 0) as user_liked
    FROM Posts p 
    JOIN University uni ON p.university_id = uni.university_id 
    WHERE p.creator_type = 'university')
    
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $user_id, $user_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    // Format the post data
    if ($row['creator_type'] === 'user') {
        $row['user_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
    } else {
        $row['user_name'] = $row['university_name'];
    }
    
    $posts[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'posts' => $posts,
    'offset' => $offset,
    'limit' => $limit
]);
?>

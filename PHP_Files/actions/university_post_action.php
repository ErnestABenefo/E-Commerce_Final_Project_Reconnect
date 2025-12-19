<?php

header('Content-Type: application/json');
session_start();

$response = array();

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $response;
    $response['status'] = 'error';
    $response['message'] = "PHP Error: " . $errstr . " in " . $errfile . " line " . $errline;
    echo json_encode($response);
    exit();
});

require_once '../controllers/university_content_controller.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    $response['status'] = 'error';
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'read';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a university post
    $university_id = isset($_POST['university_id']) ? (int)$_POST['university_id'] : null;
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : 'announcement';

    if (!$university_id) {
        $response['status'] = 'error';
        $response['message'] = 'University ID is required';
        echo json_encode($response);
        exit();
    }

    $result = create_university_post_ctr($university_id, $user_id, $content, $post_type);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Post created successfully';
        $response['post_id'] = $result;
    }
} elseif ($action === 'read' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get posts for a university
    $university_id = isset($_GET['university_id']) ? (int)$_GET['university_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if (!$university_id) {
        $response['status'] = 'error';
        $response['message'] = 'University ID is required';
        echo json_encode($response);
        exit();
    }

    $posts = get_university_posts_ctr($university_id, $limit, $offset);
    $response['status'] = 'success';
    $response['posts'] = $posts;
    $response['count'] = count($posts);
} elseif ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update a university post
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $post_type = isset($_POST['post_type']) ? $_POST['post_type'] : 'announcement';

    if (!$post_id) {
        $response['status'] = 'error';
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }

    $result = update_university_post_ctr($post_id, $user_id, $content, $post_type);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Post updated successfully';
    }
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete a university post
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;

    if (!$post_id) {
        $response['status'] = 'error';
        $response['message'] = 'Post ID is required';
        echo json_encode($response);
        exit();
    }

    $result = delete_university_post_ctr($post_id, $user_id);

    if (is_array($result) && isset($result['error'])) {
        $response['status'] = 'error';
        $response['message'] = $result['error'];
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Post deleted successfully';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid action or request method';
}

echo json_encode($response);
exit();

?>

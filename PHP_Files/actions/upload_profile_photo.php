<?php
header('Content-Type: application/json');
session_start();

$response = ['status' => 'error', 'message' => ''];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No file uploaded';
    echo json_encode($response);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$file = $_FILES['profile_photo'];
$allowed = ['image/jpeg','image/png','image/gif'];
if (!in_array($file['type'], $allowed)) {
    $response['message'] = 'Invalid file type';
    echo json_encode($response);
    exit();
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeName = 'user_' . $user_id . '_' . time() . '.' . $ext;
$targetDir = __DIR__ . '/../images/user/';
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
$targetPath = $targetDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $response['message'] = 'Failed to move uploaded file';
    echo json_encode($response);
    exit();
}

// Store relative path that will work from view/dashboard.php: ../images/user/filename
$dbPath = '../images/user/' . $safeName;

require_once __DIR__ . '/../settings/db_class.php';
$db = new db_connection();
$conn = $db->db_conn();

$stmt = $conn->prepare("UPDATE Users SET profile_photo = ? WHERE user_id = ?");
$stmt->bind_param('si', $dbPath, $user_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // update session value too
    $_SESSION['profile_photo'] = $dbPath;
    $response['status'] = 'success';
    $response['message'] = 'Profile photo updated';
    $response['url'] = $dbPath;
} else {
    $response['message'] = 'Failed to update database';
}

echo json_encode($response);
exit();

?>

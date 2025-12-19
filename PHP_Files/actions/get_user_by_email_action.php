<?php

header('Content-Type: application/json');

require_once '../settings/db_class.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request method'));
    exit;
}

// Get email parameter
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

// Validate input
if (empty($email)) {
    echo json_encode(array('status' => 'error', 'message' => 'Email is required'));
    exit;
}

// Get database connection
$db_conn = new db_connection();
$conn = $db_conn->db_conn();

// Query for user
$sql = "SELECT user_id, first_name, last_name, email FROM Users WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(array('status' => 'error', 'message' => 'Database error'));
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(array(
        'status' => 'success',
        'user_id' => $user['user_id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email']
    ));
} else {
    echo json_encode(array('status' => 'error', 'message' => 'User not found'));
}

exit;

?>

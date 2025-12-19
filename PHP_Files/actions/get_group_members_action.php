<?php
// Get Group Members Action
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

if (!isset($_GET['group_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Group ID is required']);
    exit();
}

$group_id = (int)$_GET['group_id'];
$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();
$conn = $db->db_conn();

// Check if user is a member of this group and get their admin status
$check_stmt = $conn->prepare("SELECT id, is_admin FROM GroupMembers WHERE group_id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $group_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user_membership = $check_result->fetch_assoc();
$check_stmt->close();

if (!$user_membership) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'You are not a member of this group']);
    exit();
}

$current_user_is_admin = (int)$user_membership['is_admin'] === 1;

// Get all group members with verification status and admin status
$query = "SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.profile_photo,
            u.bio,
            gm.joined_at,
            gm.is_admin,
            (SELECT COUNT(*) FROM AlumniVerification av 
             WHERE av.user_id = u.user_id AND av.verification_status = 'approved') as is_verified
          FROM GroupMembers gm
          JOIN Users u ON gm.user_id = u.user_id
          WHERE gm.group_id = ?
          ORDER BY gm.is_admin DESC, gm.joined_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = [
        'user_id' => $row['user_id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'email' => $row['email'],
        'profile_photo' => $row['profile_photo'],
        'bio' => $row['bio'],
        'joined_at' => $row['joined_at'],
        'is_admin' => (int)$row['is_admin'] === 1,
        'is_verified' => (int)$row['is_verified'] > 0
    ];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'members' => $members,
    'count' => count($members),
    'current_user_is_admin' => $current_user_is_admin
]);

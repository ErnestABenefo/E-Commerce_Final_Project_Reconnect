<?php
session_start();
require_once('../settings/db_class.php');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login_register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    
    $db = new db_connection();
    $conn = $db->db_conn();
    
    $query = "UPDATE Users SET bio = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $bio, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Bio updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update bio. Please try again.';
    }
    
    $stmt->close();
    $conn->close();
}

header('Location: ../view/dashboard.php');
exit();
?>

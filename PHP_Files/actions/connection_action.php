<?php
session_start();
require_once('../settings/db_class.php');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login_register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = isset($_POST['other_user_id']) ? (int)$_POST['other_user_id'] : (isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0);
    $redirect_to = $_POST['redirect_to'] ?? 'user_profile';

    if (!$target_user_id || $target_user_id === $user_id) {
        $_SESSION['error_message'] = 'Invalid user.';
        header('Location: ../view/dashboard.php');
        exit();
    }

    $db = new db_connection();
    $conn = $db->db_conn();

    switch ($action) {
        case 'send':
            // Send connection request
            $check_query = "SELECT * FROM UserConnections 
                           WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('iiii', $user_id, $target_user_id, $target_user_id, $user_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $_SESSION['error_message'] = 'Connection request already exists.';
            } else {
                $insert_query = "INSERT INTO UserConnections (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param('ii', $user_id, $target_user_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'Connection request sent!';
                } else {
                    $_SESSION['error_message'] = 'Failed to send request.';
                }
                $stmt->close();
            }
            break;

        case 'accept':
            // Accept connection request
            $update_query = "UPDATE UserConnections SET status = 'accepted', updated_at = NOW() 
                            WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'pending'";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('ii', $target_user_id, $user_id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['success_message'] = 'Connection accepted!';
            } else {
                $_SESSION['error_message'] = 'Failed to accept connection.';
            }
            $stmt->close();
            break;

        case 'reject':
            // Reject/Remove connection
            $delete_query = "DELETE FROM UserConnections 
                            WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param('iiii', $user_id, $target_user_id, $target_user_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Connection removed.';
            } else {
                $_SESSION['error_message'] = 'Failed to remove connection.';
            }
            $stmt->close();
            break;

        default:
            $_SESSION['error_message'] = 'Invalid action.';
            break;
    }

    $conn->close();
    
    // Redirect based on where the request came from
    if ($redirect_to === 'connections') {
        header('Location: ../view/connections.php');
    } else {
        header('Location: ../view/user_profile.php?id=' . $target_user_id);
    }
    exit();
}

header('Location: ../view/dashboard.php');
exit();
?>

<?php
session_start();
require_once('../settings/core.php');
require_once('../settings/db_class.php');

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_register.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/cart.php");
    exit();
}
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;

// Validate input
if ($cart_id <= 0) {
    $_SESSION['error_message'] = "Invalid cart item.";
    header("Location: ../view/cart.php");
    exit();
}

// Create database connection
$db = new db_connection();

// Verify cart item belongs to user and delete it
$delete_query = "DELETE FROM Cart WHERE cart_id = ? AND user_id = ?";
$stmt = $db->db_conn()->prepare($delete_query);
$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Item removed from cart.";
    } else {
        $_SESSION['error_message'] = "Cart item not found.";
    }
} else {
    $_SESSION['error_message'] = "Failed to remove item from cart.";
}

header("Location: ../view/cart.php");
exit();
?>

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
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate inputs
if ($cart_id <= 0 || $quantity < 1) {
    $_SESSION['error_message'] = "Invalid cart item or quantity.";
    header("Location: ../view/cart.php");
    exit();
}

// Create database connection
$db = new db_connection();

// Verify cart item belongs to user
$check_query = "SELECT cart_id FROM Cart WHERE cart_id = ? AND user_id = ?";
$stmt = $db->db_conn()->prepare($check_query);
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Cart item not found.";
    header("Location: ../view/cart.php");
    exit();
}

// Update quantity
$update_query = "UPDATE Cart SET quantity = ? WHERE cart_id = ?";
$stmt = $db->db_conn()->prepare($update_query);
$stmt->bind_param("ii", $quantity, $cart_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Cart updated successfully!";
} else {
    $_SESSION['error_message'] = "Failed to update cart.";
}

header("Location: ../view/cart.php");
exit();
?>

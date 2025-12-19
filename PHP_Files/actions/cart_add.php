<?php
session_start();

// DEBUGGING - Check session variables
error_log("SESSION DATA: " . print_r($_SESSION, true));
error_log("user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
error_log("logged_in: " . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'NOT SET'));

require_once('../settings/core.php');
require_once('../settings/db_class.php');

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("REDIRECT TO LOGIN: user_id=$user_id, logged_in=" . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'NOT SET'));
    header("Location: ../login_register.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/marketplace.php");
    exit();
}

$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// Validate inputs
if ($item_id <= 0 || $quantity <= 0) {
    $_SESSION['error_message'] = "Invalid item or quantity.";
    header("Location: ../view/marketplace_view.php?id=" . $item_id);
    exit();
}

// Create database connection
$db = new db_connection();

// Check if item exists and is available
$check_item_query = "SELECT item_id, status FROM MarketplaceItems WHERE item_id = ?";
$stmt = $db->db_conn()->prepare($check_item_query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    $_SESSION['error_message'] = "Product not found.";
    header("Location: ../view/marketplace.php");
    exit();
}

if ($item['status'] !== 'available') {
    $_SESSION['error_message'] = "This product is no longer available.";
    header("Location: ../view/marketplace_view.php?id=" . $item_id);
    exit();
}

// Check if item is already in cart
$check_cart_query = "SELECT cart_id, quantity FROM Cart WHERE user_id = ? AND item_id = ?";
$stmt = $db->db_conn()->prepare($check_cart_query);
$stmt->bind_param("ii", $user_id, $item_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_item = $result->fetch_assoc();

if ($cart_item) {
    // Update quantity if already in cart
    $new_quantity = $cart_item['quantity'] + $quantity;
    $update_query = "UPDATE Cart SET quantity = ? WHERE cart_id = ?";
    $stmt = $db->db_conn()->prepare($update_query);
    $stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Cart updated! Quantity increased to " . $new_quantity . ".";
    } else {
        $_SESSION['error_message'] = "Failed to update cart.";
    }
} else {
    // Add new item to cart
    $insert_query = "INSERT INTO Cart (user_id, item_id, quantity) VALUES (?, ?, ?)";
    $stmt = $db->db_conn()->prepare($insert_query);
    $stmt->bind_param("iii", $user_id, $item_id, $quantity);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add product to cart.";
    }
}

// Redirect back to product page or to cart
if (isset($_POST['redirect_to_cart'])) {
    header("Location: ../view/cart.php");
} else {
    header("Location: ../view/marketplace_view.php?id=" . $item_id);
}
exit();
?>

<?php
session_start();

require_once __DIR__ . '/../settings/db_class.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!$user_id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/marketplace.php');
    exit();
}

$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if (!$item_id || !in_array($status, ['available', 'sold', 'removed'])) {
    header('Location: ../view/marketplace.php');
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Verify the user owns this item
$check = $conn->prepare("SELECT seller_id FROM MarketplaceItems WHERE item_id = ?");
$check->bind_param('i', $item_id);
$check->execute();
$result = $check->get_result();
$item = $result->fetch_assoc();
$check->close();

if (!$item || $item['seller_id'] != $user_id) {
    header('Location: ../view/marketplace.php');
    exit();
}

// Update status
$stmt = $conn->prepare("UPDATE MarketplaceItems SET status = ? WHERE item_id = ?");
$stmt->bind_param('si', $status, $item_id);
$stmt->execute();
$stmt->close();

header('Location: ../view/marketplace.php');
exit();
?>

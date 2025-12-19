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

// Get reference from URL
$reference = isset($_GET['reference']) ? $_GET['reference'] : '';

if (empty($reference)) {
    $_SESSION['error_message'] = "Invalid payment reference.";
    header("Location: ../view/cart.php");
    exit();
}

// Your Paystack secret key
$paystack_secret_key = 'sk_test_260b7325626bce45f188b5949a8528744799c902'; // Replace with your Paystack secret key

// Initialize cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $paystack_secret_key,
        "Cache-Control: no-cache",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    $_SESSION['error_message'] = "Payment verification failed. Please contact support.";
    header("Location: ../view/cart.php");
    exit();
}

$result = json_decode($response, true);

// Check if payment was successful
if ($result && $result['status'] === true && $result['data']['status'] === 'success') {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get cart items
        $cart_query = "SELECT c.cart_id, c.item_id, c.quantity, m.price, m.seller_id
                       FROM Cart c
                       INNER JOIN MarketplaceItems m ON c.item_id = m.item_id
                       WHERE c.user_id = ? AND m.status = 'available'";
        
        $stmt = $conn->prepare($cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get payment details
        $amount = $result['data']['amount'] / 100; // Convert from pesewas to cedis
        $payment_date = date('Y-m-d H:i:s');
        
        // Create order record
        $order_query = "INSERT INTO PaymentOrders (user_id, total_amount, payment_reference, payment_status, created_at) 
                       VALUES (?, ?, ?, 'completed', ?)";
        $stmt = $conn->prepare($order_query);
        if (!$stmt) {
            throw new Exception("Order prepare failed: " . $conn->error);
        }
        $stmt->bind_param("idss", $user_id, $amount, $reference, $payment_date);
        $stmt->execute();
        $order_id = $conn->insert_id;
        
        // Create order items and update marketplace items
        foreach ($cart_items as $item) {
            // Insert order item
            $item_total = $item['price'] * $item['quantity'];
            $order_item_query = "INSERT INTO PaymentOrderItems (order_id, item_id, quantity, price, seller_id) 
                                VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($order_item_query);
            if (!$stmt) {
                throw new Exception("OrderItem prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iiidi", $order_id, $item['item_id'], $item['quantity'], $item['price'], $item['seller_id']);
            $stmt->execute();
        }
        
        // Clear user's cart
        $clear_cart_query = "DELETE FROM Cart WHERE user_id = ?";
        $stmt = $conn->prepare($clear_cart_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Payment successful! Your order has been placed.";
        header("Location: ../view/order_success.php?order_id=" . $order_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log the error for debugging
        error_log("Payment verification error: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Order processing failed: " . $e->getMessage() . " Reference: " . $reference;
        header("Location: ../view/cart.php");
        exit();
    }
} else {
    // Payment failed
    $_SESSION['error_message'] = "Payment verification failed. Please try again or contact support.";
    header("Location: ../view/cart.php");
    exit();
}
?>

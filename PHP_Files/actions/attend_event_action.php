<?php
// Attend Event Action - Toggle attendance for events
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Validate event_id
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Event ID is required']);
    exit();
}

$event_id = (int)$_POST['event_id'];

if ($event_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event ID']);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit();
    }
    
    // Check if event exists
    $check_event_query = "SELECT event_id FROM Events WHERE event_id = ?";
    $stmt = $conn->prepare($check_event_query);
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $event_result = $stmt->get_result();
    $stmt->close();
    
    if ($event_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Event not found']);
        $conn->close();
        exit();
    }
    
    // Check if user is already attending
    $check_query = "SELECT attendee_id FROM EventAttendees WHERE event_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $event_id, $user_id);
    $stmt->execute();
    $existing_result = $stmt->get_result();
    $existing = $existing_result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // User is already attending - remove attendance
        $delete_query = "DELETE FROM EventAttendees WHERE event_id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('ii', $event_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Removed from event',
                'is_attending' => false
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove attendance']);
        }
        $stmt->close();
    } else {
        // User is not attending - add attendance
        $insert_query = "INSERT INTO EventAttendees (event_id, user_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('ii', $event_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Added to event',
                'is_attending' => true
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add attendance']);
        }
        $stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Attend Event Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
?>

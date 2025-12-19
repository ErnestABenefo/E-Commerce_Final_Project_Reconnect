<?php
// Create Event Action
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'Unknown error'];

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Validate required fields
$required_fields = ['title', 'description', 'event_type', 'start_datetime', 'location'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
    exit();
}

// Sanitize inputs
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$event_type = trim($_POST['event_type']);
$start_datetime = trim($_POST['start_datetime']);
$location = trim($_POST['location']);

// Validate event type
$valid_types = ['Workshop', 'Seminar', 'Networking', 'Conference', 'Social', 'Other'];
if (!in_array($event_type, $valid_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event type']);
    exit();
}

// Validate datetime format and ensure it's in the future
$datetime_obj = DateTime::createFromFormat('Y-m-d\TH:i', $start_datetime);
if (!$datetime_obj) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date/time format']);
    exit();
}

$now = new DateTime();
if ($datetime_obj < $now) {
    echo json_encode(['status' => 'error', 'message' => 'Event date must be in the future']);
    exit();
}

// Convert to MySQL datetime format
$start_datetime_mysql = $datetime_obj->format('Y-m-d H:i:s');

// Handle image upload
$event_image = null;
if (isset($_FILES['event_flyer']) && $_FILES['event_flyer']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['event_flyer'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit();
    }
    
    // Check file size (5MB max)
    if ($file_size > 5 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'File size must be less than 5MB']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/events/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $new_filename = 'event_' . time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        // Store relative path for database
        $event_image = '../uploads/events/' . $new_filename;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit();
    }
}

// Insert event into database
try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    $sql = "INSERT INTO Events (host_user_id, title, description, event_type, start_datetime, location, event_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('issssss', 
        $user_id, 
        $title, 
        $description, 
        $event_type, 
        $start_datetime_mysql, 
        $location, 
        $event_image
    );
    
    if ($stmt->execute()) {
        $event_id = $stmt->insert_id;
        $response = [
            'status' => 'success',
            'message' => 'Event created successfully!',
            'event_id' => $event_id
        ];
    } else {
        throw new Exception('Failed to create event: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>

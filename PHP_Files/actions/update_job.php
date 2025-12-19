<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once('../settings/db_class.php');

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$company = isset($_POST['company']) ? trim($_POST['company']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$job_type = isset($_POST['job_type']) ? trim($_POST['job_type']) : '';

if (!$job_id || empty($title) || empty($company) || empty($description) || empty($location) || empty($job_type)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();

    // Check if user owns this job
    $check_query = "SELECT job_id FROM JobListings WHERE job_id = ? AND posted_by = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $job_id, $user_id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found or you do not have permission to edit it']);
        exit();
    }

    // Update job
    $update_query = "UPDATE JobListings 
                     SET title = ?, company = ?, description = ?, location = ?, job_type = ? 
                     WHERE job_id = ? AND posted_by = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('sssssii', $title, $company, $description, $location, $job_type, $job_id, $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
    } else {
        throw new Exception('Failed to update job');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to update job']);
}
?>

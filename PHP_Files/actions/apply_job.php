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
$cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : null;

if (!$job_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid job ID']);
    exit();
}

// Handle CV upload
$cv_file_path = null;
if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../uploads/cvs/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file format. Please upload PDF, DOC, or DOCX']);
        exit();
    }
    
    // Check file size (5MB max)
    if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
        exit();
    }
    
    $file_name = 'cv_' . $user_id . '_' . $job_id . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload CV file']);
        exit();
    }
    
    $cv_file_path = '../uploads/cvs/' . $file_name;
} else {
    echo json_encode(['success' => false, 'error' => 'CV file is required']);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();

    // Check if job exists
    $check_query = "SELECT posted_by FROM JobListings WHERE job_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        exit();
    }

    // Check if user is the job poster
    if ($job['posted_by'] == $user_id) {
        echo json_encode(['success' => false, 'error' => 'You cannot apply to your own job']);
        exit();
    }

    // Check if already applied
    $check_application = "SELECT application_id FROM JobApplications WHERE job_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_application);
    $stmt->bind_param('ii', $job_id, $user_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        echo json_encode(['success' => false, 'error' => 'You have already applied to this job']);
        exit();
    }

    // Submit application with CV
    $insert_query = "INSERT INTO JobApplications (job_id, user_id, cover_letter, cv_file_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('iiss', $job_id, $user_id, $cover_letter, $cv_file_path);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    } else {
        throw new Exception('Failed to submit application');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to submit application']);
}
?>

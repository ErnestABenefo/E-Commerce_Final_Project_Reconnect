<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once('../settings/db_class.php');

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'users' => [],
        'universities' => [],
        'products' => [],
        'events' => [],
        'jobs' => [],
        'groups' => []
    ]);
    exit();
}

try {
    $db = new db_connection();
    $conn = $db->db_conn();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $search_term = '%' . $query . '%';
    $results = [
        'users' => [],
        'universities' => [],
        'products' => [],
        'events' => [],
        'jobs' => [],
        'groups' => []
    ];

    // Search Users
    try {
        $stmt = $conn->prepare("SELECT u.user_id, u.first_name, u.last_name, u.email, u.profile_photo, u.bio,
                                (SELECT COUNT(*) FROM AlumniVerification av WHERE av.user_id = u.user_id AND av.verification_status = 'approved') as is_verified
                                FROM Users u
                                WHERE u.user_id != ? AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?) 
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('isss', $user_id, $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['users'][] = [
                    'id' => $row['user_id'],
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'email' => $row['email'],
                    'photo' => $row['profile_photo'],
                    'bio' => $row['bio'],
                    'is_verified' => $row['is_verified'] > 0,
                    'url' => '../view/user_profile.php?id=' . $row['user_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    // Search Universities
    try {
        $stmt = $conn->prepare("SELECT university_id, name, location, university_type 
                                FROM University 
                                WHERE name LIKE ? OR location LIKE ? 
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('ss', $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['universities'][] = [
                    'id' => $row['university_id'],
                    'name' => $row['name'],
                    'location' => $row['location'],
                    'type' => $row['university_type'],
                    'url' => 'view/university_profile.php?id=' . $row['university_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    // Search Marketplace Products
    try {
        $stmt = $conn->prepare("SELECT m.item_id, m.title, m.price, m.category, m.image_url, m.status,
                                u.first_name, u.last_name
                                FROM MarketplaceItems m
                                JOIN Users u ON m.seller_id = u.user_id
                                WHERE m.status = 'available' AND (m.title LIKE ? OR m.description LIKE ? OR m.category LIKE ?)
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('sss', $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['products'][] = [
                    'id' => $row['item_id'],
                    'title' => $row['title'],
                    'price' => $row['price'],
                    'category' => $row['category'],
                    'image' => $row['image_url'],
                    'seller' => $row['first_name'] . ' ' . $row['last_name'],
                    'url' => 'view/marketplace_view.php?id=' . $row['item_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    // Search Events
    try {
        $stmt = $conn->prepare("SELECT event_id, title, description, start_datetime, location, event_type 
                                FROM Events 
                                WHERE start_datetime >= NOW() AND (title LIKE ? OR description LIKE ? OR location LIKE ?)
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('sss', $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['events'][] = [
                    'id' => $row['event_id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'date' => $row['start_datetime'],
                    'location' => $row['location'],
                    'type' => $row['event_type'],
                    'url' => 'view/event_details.php?id=' . $row['event_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    // Search Jobs
    try {
        $stmt = $conn->prepare("SELECT job_id, title, company, location, job_type, salary_range 
                                FROM Jobs 
                                WHERE status = 'open' AND (title LIKE ? OR company LIKE ? OR location LIKE ? OR description LIKE ?)
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('ssss', $search_term, $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['jobs'][] = [
                    'id' => $row['job_id'],
                    'title' => $row['title'],
                    'company' => $row['company'],
                    'location' => $row['location'],
                    'type' => $row['job_type'],
                    'salary' => $row['salary_range'],
                    'url' => 'view/job_details.php?id=' . $row['job_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    // Search Groups
    try {
        $stmt = $conn->prepare("SELECT group_id, name, description, privacy, created_at 
                                FROM `Groups` 
                                WHERE name LIKE ? OR description LIKE ?
                                LIMIT 5");
        if ($stmt) {
            $stmt->bind_param('ss', $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $results['groups'][] = [
                    'id' => $row['group_id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'privacy' => $row['privacy'],
                    'url' => 'view/group_details.php?id=' . $row['group_id']
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {}

    $conn->close();
    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode([
        'users' => [],
        'universities' => [],
        'products' => [],
        'events' => [],
        'jobs' => [],
        'groups' => []
    ]);
}
?>

<?php
// Events Page - Display all events posted by alumni and users
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();

if (!$db->db_connect()) {
    die("Database connection failed");
}

$conn = $db->db_conn();

if (!$conn) {
    die("Failed to get database connection");
}

// Helper to fetch all rows
function fetch_all($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// Helper to fetch one row
function fetch_one($conn, $sql, $types = null, $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

// Get current user info
$user = fetch_one($conn, "SELECT first_name, last_name FROM Users WHERE user_id = ?", "i", [$user_id]);
$displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';

// Check if user is verified alumni
$is_verified = false;
$verification_check = fetch_one($conn, "SELECT verification_id FROM AlumniVerification WHERE user_id = ? AND verification_status = 'approved' LIMIT 1", "i", [$user_id]);
if ($verification_check) {
    $is_verified = true;
}

// Get all events with host information and attendance status
$allEvents = fetch_all($conn, "
    SELECT 
        e.event_id,
        e.host_user_id,
        e.title,
        e.description,
        e.event_type,
        e.start_datetime,
        e.location,
        e.event_image,
        u.first_name,
        u.last_name,
        u.profile_photo,
        (SELECT COUNT(*) FROM EventAttendees WHERE event_id = e.event_id) as attendee_count,
        (SELECT COUNT(*) FROM EventAttendees WHERE event_id = e.event_id AND user_id = ?) as is_attending,
        (SELECT verification_id FROM AlumniVerification WHERE user_id = u.user_id AND verification_status = 'approved' LIMIT 1) as host_is_verified
    FROM Events e
    LEFT JOIN Users u ON e.host_user_id = u.user_id
    WHERE e.start_datetime >= NOW()
    ORDER BY e.start_datetime ASC
", "i", [$user_id]);

// Get past events
$pastEvents = fetch_all($conn, "
    SELECT 
        e.event_id,
        e.host_user_id,
        e.title,
        e.description,
        e.event_type,
        e.start_datetime,
        e.location,
        e.event_image,
        u.first_name,
        u.last_name,
        (SELECT COUNT(*) FROM EventAttendees WHERE event_id = e.event_id) as attendee_count,
        (SELECT verification_id FROM AlumniVerification WHERE user_id = u.user_id AND verification_status = 'approved' LIMIT 1) as host_is_verified
    FROM Events e
    LEFT JOIN Users u ON e.host_user_id = u.user_id
    WHERE e.start_datetime < NOW()
    ORDER BY e.start_datetime DESC
    LIMIT 10
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Events - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --danger: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
            --info: #3498db;
            --muted: #666;
            --light: #f4f6f8;
            --dark: #2c3e50;
            --border: #e0e0e0;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            color: var(--dark);
        }

        /* Navigation Bar */
        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 15px 0;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-menu {
            display: flex;
            gap: 25px;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .navbar-menu a {
            text-decoration: none;
            color: #555;
            font-weight: 600;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar-menu a:hover,
        .navbar-menu a.active {
            color: var(--primary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary);
            display: inline-block;
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }

        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-body {
            padding: 20px;
        }

        .event-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .event-type.workshop {
            background: #e3f2fd;
            color: #1976d2;
        }

        .event-type.seminar {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .event-type.networking {
            background: #fff3e0;
            color: #e65100;
        }

        .event-type.conference {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .event-type.social {
            background: #fce4ec;
            color: #c2185b;
        }

        .event-type.other {
            background: #f5f5f5;
            color: #616161;
        }

        .event-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .event-description {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .event-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .event-meta-item i {
            width: 20px;
            color: var(--primary);
        }

        .event-host {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--light);
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .host-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            overflow: hidden;
        }

        .host-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .host-info {
            flex: 1;
        }

        .host-name {
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .host-label {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .event-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .attendee-info {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .btn-attend {
            flex: 1;
        }

        .btn-attending {
            background: var(--success);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }

            .navbar-menu {
                gap: 15px;
                font-size: 0.85rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="homepage.php" class="navbar-brand">
                <i class="fas fa-link"></i> ReConnect
            </a>
            
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="connections.php"><i class="fas fa-users"></i> Connections</a></li>
                <li><a href="groups.php"><i class="fas fa-users-cog"></i> Groups</a></li>
                <li><a href="events.php" class="active"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li>
                    <form method="post" action="../actions/logout_user_action.php" style="margin:0">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h1 style="margin-bottom: 0;">
                    <i class="fas fa-calendar-alt"></i> Events
                </h1>
                <a href="events_create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Event
                </a>
            </div>
            <p>Discover and attend events organized by the alumni community</p>
        </div>

        <!-- Upcoming Events Section -->
        <div style="margin-bottom: 50px;">
            <h2 class="section-title">Upcoming Events</h2>
            
            <?php if (!empty($allEvents)): ?>
                <div class="events-grid">
                    <?php foreach ($allEvents as $event): ?>
                        <div class="event-card">
                            <!-- Event Image -->
                            <div class="event-image">
                                <?php if (!empty($event['event_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['event_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-calendar-day"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Event Body -->
                            <div class="event-body">
                                <!-- Event Type -->
                                <span class="event-type <?php echo strtolower($event['event_type']); ?>">
                                    <?php echo htmlspecialchars($event['event_type']); ?>
                                </span>

                                <!-- Event Title -->
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>

                                <!-- Event Description -->
                                <?php if (!empty($event['description'])): ?>
                                    <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <?php endif; ?>

                                <!-- Event Meta -->
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="far fa-calendar"></i>
                                        <span><?php echo date('l, F j, Y', strtotime($event['start_datetime'])); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($event['start_datetime'])); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                </div>

                                <!-- Event Host -->
                                <div class="event-host">
                                    <div class="host-avatar">
                                        <?php if (!empty($event['profile_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($event['profile_photo']); ?>" alt="Host">
                                        <?php else: ?>
                                            <?php
                                            $initials = '';
                                            if (!empty($event['first_name'])) $initials .= strtoupper($event['first_name'][0]);
                                            if (!empty($event['last_name'])) $initials .= strtoupper($event['last_name'][0]);
                                            echo $initials;
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="host-info">
                                        <div class="host-label">Hosted by</div>
                                        <div class="host-name">
                                            <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                            <?php if ($event['host_is_verified']): ?>
                                                <span class="verified-badge">
                                                    <i class="fas fa-check-circle"></i> Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Event Footer -->
                                <div class="event-footer">
                                    <div class="attendee-info">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $event['attendee_count']; ?> attending</span>
                                    </div>
                                    <button class="btn btn-attend <?php echo $event['is_attending'] ? 'btn-attending' : 'btn-primary'; ?>" 
                                            data-event-id="<?php echo $event['event_id']; ?>"
                                            onclick="toggleAttendance(<?php echo $event['event_id']; ?>)">
                                        <?php if ($event['is_attending']): ?>
                                            <i class="fas fa-check"></i> Attending
                                        <?php else: ?>
                                            <i class="fas fa-plus"></i> Attend
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Upcoming Events</h3>
                    <p>Check back later for new events from the alumni community</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Events Section -->
        <?php if (!empty($pastEvents)): ?>
            <div style="margin-bottom: 50px;">
                <h2 class="section-title">Past Events</h2>
                
                <div class="events-grid">
                    <?php foreach ($pastEvents as $event): ?>
                        <div class="event-card" style="opacity: 0.7;">
                            <!-- Event Image -->
                            <div class="event-image">
                                <?php if (!empty($event['event_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['event_image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-calendar-day"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Event Body -->
                            <div class="event-body">
                                <!-- Event Type -->
                                <span class="event-type <?php echo strtolower($event['event_type']); ?>">
                                    <?php echo htmlspecialchars($event['event_type']); ?>
                                </span>

                                <!-- Event Title -->
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>

                                <!-- Event Description -->
                                <?php if (!empty($event['description'])): ?>
                                    <p class="event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <?php endif; ?>

                                <!-- Event Meta -->
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="far fa-calendar"></i>
                                        <span><?php echo date('F j, Y', strtotime($event['start_datetime'])); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $event['attendee_count']; ?> attended</span>
                                    </div>
                                </div>

                                <!-- Event Host -->
                                <div class="event-host">
                                    <div class="host-avatar">
                                        <?php
                                        $initials = '';
                                        if (!empty($event['first_name'])) $initials .= strtoupper($event['first_name'][0]);
                                        if (!empty($event['last_name'])) $initials .= strtoupper($event['last_name'][0]);
                                        echo $initials;
                                        ?>
                                    </div>
                                    <div class="host-info">
                                        <div class="host-label">Hosted by</div>
                                        <div class="host-name">
                                            <?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?>
                                            <?php if ($event['host_is_verified']): ?>
                                                <span class="verified-badge">
                                                    <i class="fas fa-check-circle"></i> Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function toggleAttendance(eventId) {
            const btn = document.querySelector(`button[data-event-id="${eventId}"]`);
            const isAttending = btn.classList.contains('btn-attending');
            
            btn.disabled = true;

            try {
                const response = await fetch('../actions/attend_event_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `event_id=${eventId}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    // Update button state
                    if (isAttending) {
                        btn.classList.remove('btn-attending');
                        btn.classList.add('btn-primary');
                        btn.innerHTML = '<i class="fas fa-plus"></i> Attend';
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-attending');
                        btn.innerHTML = '<i class="fas fa-check"></i> Attending';
                    }

                    // Update attendee count
                    const attendeeInfo = btn.parentElement.querySelector('.attendee-info span');
                    if (attendeeInfo) {
                        const currentCount = parseInt(attendeeInfo.textContent);
                        const newCount = isAttending ? currentCount - 1 : currentCount + 1;
                        attendeeInfo.textContent = `${newCount} attending`;
                    }
                } else {
                    alert(data.message || 'Failed to update attendance');
                }
            } catch (error) {
                console.error('Error toggling attendance:', error);
                alert('An error occurred. Please try again.');
            }

            btn.disabled = false;
        }
    </script>
</body>
</html>

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../settings/db_class.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../loginPage.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = new db_connection();

if (!$db->db_connect()) {
    die("Database connection failed");
}

$conn = $db->db_conn();

if (!$conn) {
    die("Failed to get database connection");
}

// Get connection requests received (pending)
$received_query = "SELECT uc.connection_id, uc.user_id_1 as sender_id, uc.created_at,
                   u.first_name, u.last_name, u.email, u.profile_photo, u.bio,
                   (SELECT COUNT(*) FROM AlumniVerification av WHERE av.user_id = u.user_id AND av.verification_status = 'approved') as is_verified
                   FROM UserConnections uc
                   JOIN Users u ON uc.user_id_1 = u.user_id
                   WHERE uc.user_id_2 = ? AND uc.status = 'pending'
                   ORDER BY uc.created_at DESC";
$stmt = $conn->prepare($received_query);

if (!$stmt) {
    die("Failed to prepare received query: " . $conn->error);
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$received_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get connection requests sent (pending)
$sent_query = "SELECT uc.connection_id, uc.user_id_2 as receiver_id, uc.created_at,
               u.first_name, u.last_name, u.email, u.profile_photo,
               (SELECT COUNT(*) FROM AlumniVerification av WHERE av.user_id = u.user_id AND av.verification_status = 'approved') as is_verified
               FROM UserConnections uc
               JOIN Users u ON uc.user_id_2 = u.user_id
               WHERE uc.user_id_1 = ? AND uc.status = 'pending'
               ORDER BY uc.created_at DESC";
$stmt = $conn->prepare($sent_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$sent_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all accepted connections
$connections_query = "SELECT 
                      CASE 
                        WHEN uc.user_id_1 = ? THEN uc.user_id_2
                        ELSE uc.user_id_1
                      END as connection_user_id,
                      u.first_name, u.last_name, u.email, u.profile_photo, u.bio,
                      uc.updated_at as connected_since,
                      (SELECT COUNT(*) FROM AlumniVerification av WHERE av.user_id = u.user_id AND av.verification_status = 'approved') as is_verified
                      FROM UserConnections uc
                      JOIN Users u ON (
                        CASE 
                          WHEN uc.user_id_1 = ? THEN uc.user_id_2
                          ELSE uc.user_id_1
                        END = u.user_id
                      )
                      WHERE (uc.user_id_1 = ? OR uc.user_id_2 = ?) AND uc.status = 'accepted'
                      ORDER BY uc.updated_at DESC";
$stmt = $conn->prepare($connections_query);
$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$connections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Connections - ReConnect</title>
    <link rel="stylesheet" href="css/socialv.css">
    <link rel="stylesheet" href="css/libs.min.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        .connections-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .connections-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .connections-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .connections-tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-button {
            padding: 15px 30px;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .tab-button.active {
            color: #667eea;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
        }

        .tab-badge {
            display: inline-block;
            background: #ff4757;
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .connection-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .connection-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .connection-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .connection-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .connection-info {
            flex: 1;
            min-width: 0;
        }

        .connection-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .connection-name a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }

        .connection-name a:hover {
            color: #667eea;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .verified-badge i {
            font-size: 0.85rem;
        }

        .connection-email {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 5px;
        }

        .connection-bio {
            color: #888;
            font-size: 0.9rem;
            margin-top: 8px;
            line-height: 1.4;
        }

        .connection-date {
            color: #999;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .connection-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
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
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #999;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .connection-card {
                flex-direction: column;
                text-align: center;
            }

            .connection-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .connections-tabs {
                overflow-x: auto;
                gap: 10px;
            }

            .tab-button {
                padding: 10px 20px;
                font-size: 0.9rem;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar" style="background:#fff;padding:15px 0;box-shadow:0 2px 8px rgba(0,0,0,0.1);position:sticky;top:0;z-index:1000;">
        <div class="navbar-container" style="max-width:1400px;margin:0 auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;gap:20px;">
            <a href="dashboard.php" class="navbar-brand" style="font-size:1.5rem;font-weight:700;color:#667eea;text-decoration:none;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-graduation-cap"></i>
                ReConnect
            </a>
            
            <?php include 'search_component.php'; ?>
            
            <ul class="navbar-menu" style="display:flex;gap:25px;list-style:none;margin:0;padding:0;align-items:center;">
                <li><a href="homepage.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="connections.php" class="active" style="text-decoration:none;color:#667eea;font-weight:600;"><i class="fas fa-user-friends"></i> Connections</a></li>
                <li><a href="groups.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php" style="text-decoration:none;color:#555;font-weight:600;transition:color 0.3s;"><i class="fas fa-user"></i> Profile</a></li>
                <li>
                    <form method="post" action="../actions/logout_user_action.php" style="margin:0">
                        <button type="submit" class="btn btn-danger" style="background:#dc3545;color:white;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <div class="connections-container">
        <div class="connections-header">
            <h1><i class="fas fa-users"></i> My Connections</h1>
        </div>

        <div class="connections-tabs">
            <button class="tab-button active" onclick="switchTab('received')">
                Connection Requests
                <?php if (count($received_requests) > 0): ?>
                    <span class="tab-badge"><?php echo count($received_requests); ?></span>
                <?php endif; ?>
            </button>
            <button class="tab-button" onclick="switchTab('connections')">
                My Connections (<?php echo count($connections); ?>)
            </button>
            <button class="tab-button" onclick="switchTab('sent')">
                Sent Requests (<?php echo count($sent_requests); ?>)
            </button>
        </div>

        <!-- Received Requests Tab -->
        <div id="received-tab" class="tab-content active">
            <?php if (count($received_requests) > 0): ?>
                <?php foreach ($received_requests as $request): ?>
                    <div class="connection-card">
                        <div class="connection-avatar">
                            <?php if ($request['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($request['profile_photo']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php
                                $initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                                echo $initials;
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="connection-info">
                            <div class="connection-name">
                                <a href="user_profile.php?id=<?php echo $request['sender_id']; ?>">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </a>
                                <?php if ($request['is_verified'] > 0): ?>
                                    <span class="verified-badge" title="Verified Alumni">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="connection-email"><?php echo htmlspecialchars($request['email']); ?></div>
                            <?php if ($request['bio']): ?>
                                <div class="connection-bio"><?php echo htmlspecialchars(substr($request['bio'], 0, 100)); ?><?php echo strlen($request['bio']) > 100 ? '...' : ''; ?></div>
                            <?php endif; ?>
                            <div class="connection-date">
                                <i class="far fa-clock"></i> Sent <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                        <div class="connection-actions">
                            <form method="POST" action="../actions/connection_action.php" style="display: inline;">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="other_user_id" value="<?php echo $request['sender_id']; ?>">
                                <input type="hidden" name="redirect_to" value="connections">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                            </form>
                            <form method="POST" action="../actions/connection_action.php" style="display: inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="other_user_id" value="<?php echo $request['sender_id']; ?>">
                                <input type="hidden" name="redirect_to" value="connections">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Decline
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Connection Requests</h3>
                    <p>You don't have any pending connection requests at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- My Connections Tab -->
        <div id="connections-tab" class="tab-content">
            <?php if (count($connections) > 0): ?>
                <?php foreach ($connections as $connection): ?>
                    <div class="connection-card">
                        <div class="connection-avatar">
                            <?php if ($connection['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($connection['profile_photo']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php
                                $initials = strtoupper(substr($connection['first_name'], 0, 1) . substr($connection['last_name'], 0, 1));
                                echo $initials;
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="connection-info">
                            <div class="connection-name">
                                <a href="user_profile.php?id=<?php echo $connection['connection_user_id']; ?>">
                                    <?php echo htmlspecialchars($connection['first_name'] . ' ' . $connection['last_name']); ?>
                                </a>
                                <?php if ($connection['is_verified'] > 0): ?>
                                    <span class="verified-badge" title="Verified Alumni">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="connection-email"><?php echo htmlspecialchars($connection['email']); ?></div>
                            <?php if ($connection['bio']): ?>
                                <div class="connection-bio"><?php echo htmlspecialchars(substr($connection['bio'], 0, 100)); ?><?php echo strlen($connection['bio']) > 100 ? '...' : ''; ?></div>
                            <?php endif; ?>
                            <div class="connection-date">
                                <i class="fas fa-user-check"></i> Connected since <?php echo date('M j, Y', strtotime($connection['connected_since'])); ?>
                            </div>
                        </div>
                        <div class="connection-actions">
                            <a href="user_profile.php?id=<?php echo $connection['connection_user_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                            <a href="user_chat.php?user_id=<?php echo $connection['connection_user_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-comments"></i> Chat
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>No Connections Yet</h3>
                    <p>Start connecting with other users to build your network!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sent Requests Tab -->
        <div id="sent-tab" class="tab-content">
            <?php if (count($sent_requests) > 0): ?>
                <?php foreach ($sent_requests as $request): ?>
                    <div class="connection-card">
                        <div class="connection-avatar">
                            <?php if ($request['profile_photo']): ?>
                                <img src="<?php echo htmlspecialchars($request['profile_photo']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php
                                $initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                                echo $initials;
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="connection-info">
                            <div class="connection-name">
                                <a href="user_profile.php?id=<?php echo $request['receiver_id']; ?>">
                                    <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                </a>
                                <?php if ($request['is_verified'] > 0): ?>
                                    <span class="verified-badge" title="Verified Alumni">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="connection-email"><?php echo htmlspecialchars($request['email']); ?></div>
                            <div class="connection-date">
                                <i class="far fa-clock"></i> Sent <?php echo date('M j, Y', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                        <div class="connection-actions">
                            <a href="user_profile.php?id=<?php echo $request['receiver_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-paper-plane"></i>
                    <h3>No Sent Requests</h3>
                    <p>You haven't sent any connection requests yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>

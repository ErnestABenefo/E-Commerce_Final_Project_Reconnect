<?php
// Create Event Page
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();
$conn = $db->db_conn();

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Event - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --danger: #e74c3c;
            --success: #27ae60;
            --warning: #f39c12;
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
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            max-width: 900px;
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

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        select.form-control {
            cursor: pointer;
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 5px;
        }

        .image-upload-container {
            border: 3px dashed var(--border);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: var(--light);
            transition: all 0.3s;
            cursor: pointer;
        }

        .image-upload-container:hover {
            border-color: var(--primary);
            background: #f0f4ff;
        }

        .image-upload-container i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .image-upload-container p {
            color: var(--muted);
            margin: 5px 0;
        }

        .upload-text {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }

        #eventFlyerInput {
            display: none;
        }

        .image-preview-container {
            margin-top: 20px;
            display: none;
        }

        .image-preview {
            width: 100%;
            max-height: 400px;
            object-fit: contain;
            border-radius: 12px;
            border: 2px solid var(--border);
        }

        .remove-image-btn {
            margin-top: 10px;
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .remove-image-btn:hover {
            background: #c0392b;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--border);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .navbar-menu {
                gap: 15px;
                font-size: 0.85rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .card {
                padding: 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
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
            <h1>
                <i class="fas fa-calendar-plus"></i> Create Event
            </h1>
            <p>Share an event with the alumni community</p>
        </div>

        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span id="successMessage"></span>
        </div>
        <div id="alertError" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorMessage"></span>
        </div>

        <!-- Event Form -->
        <div class="card">
            <form id="createEventForm" enctype="multipart/form-data">
                <!-- Event Title -->
                <div class="form-group">
                    <label class="form-label" for="eventTitle">
                        Event Title <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="eventTitle" 
                           name="title" 
                           class="form-control" 
                           placeholder="Enter event title"
                           required>
                </div>

                <!-- Event Description -->
                <div class="form-group">
                    <label class="form-label" for="eventDescription">
                        Description <span class="required">*</span>
                    </label>
                    <textarea id="eventDescription" 
                              name="description" 
                              class="form-control" 
                              placeholder="Describe your event, what attendees can expect, agenda, etc."
                              required></textarea>
                </div>

                <!-- Event Type -->
                <div class="form-group">
                    <label class="form-label" for="eventType">
                        Event Type <span class="required">*</span>
                    </label>
                    <select id="eventType" name="event_type" class="form-control" required>
                        <option value="">Select event type</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Networking">Networking</option>
                        <option value="Conference">Conference</option>
                        <option value="Social">Social</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <!-- Date and Time -->
                <div class="form-group">
                    <label class="form-label" for="eventDateTime">
                        Date & Time <span class="required">*</span>
                    </label>
                    <input type="datetime-local" 
                           id="eventDateTime" 
                           name="start_datetime" 
                           class="form-control" 
                           required>
                    <div class="form-help">When will your event take place?</div>
                </div>

                <!-- Location -->
                <div class="form-group">
                    <label class="form-label" for="eventLocation">
                        Location <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="eventLocation" 
                           name="location" 
                           class="form-control" 
                           placeholder="e.g., University Hall, Room 205 or Virtual via Zoom"
                           required>
                </div>

                <!-- Event Flyer/Poster -->
                <div class="form-group">
                    <label class="form-label">
                        Event Flyer/Poster (Optional)
                    </label>
                    <div class="image-upload-container" onclick="document.getElementById('eventFlyerInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="upload-text">Click to upload event flyer</p>
                        <p>Supported formats: JPG, PNG, GIF (Max 5MB)</p>
                    </div>
                    <input type="file" 
                           id="eventFlyerInput" 
                           name="event_flyer" 
                           accept="image/jpeg,image/jpg,image/png,image/gif">
                    
                    <div id="imagePreviewContainer" class="image-preview-container">
                        <img id="imagePreview" class="image-preview" alt="Event flyer preview">
                        <button type="button" class="remove-image-btn" onclick="removeImage()">
                            <i class="fas fa-times"></i> Remove Image
                        </button>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="events.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-check"></i> Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        const flyerInput = document.getElementById('eventFlyerInput');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');

        flyerInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showError('File size must be less than 5MB');
                    flyerInput.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showError('Please upload a valid image file (JPG, PNG, or GIF)');
                    flyerInput.value = '';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        function removeImage() {
            flyerInput.value = '';
            imagePreview.src = '';
            imagePreviewContainer.style.display = 'none';
        }

        // Form submission
        document.getElementById('createEventForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const originalBtnText = submitBtn.innerHTML;

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Event...';

            // Hide previous alerts
            hideAlerts();

            const formData = new FormData(this);

            try {
                const response = await fetch('../actions/create_event_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showSuccess('Event created successfully! Redirecting...');
                    setTimeout(() => {
                        window.location.href = 'events.php';
                    }, 1500);
                } else {
                    showError(data.message || 'Failed to create event. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });

        function showSuccess(message) {
            const alert = document.getElementById('alertSuccess');
            const messageSpan = document.getElementById('successMessage');
            messageSpan.textContent = message;
            alert.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showError(message) {
            const alert = document.getElementById('alertError');
            const messageSpan = document.getElementById('errorMessage');
            messageSpan.textContent = message;
            alert.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function hideAlerts() {
            document.getElementById('alertSuccess').style.display = 'none';
            document.getElementById('alertError').style.display = 'none';
        }

        // Set minimum datetime to current date/time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('eventDateTime').min = now.toISOString().slice(0, 16);
    </script>
</body>
</html>

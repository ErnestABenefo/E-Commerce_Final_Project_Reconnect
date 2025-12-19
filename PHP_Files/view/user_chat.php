<?php
session_start();
require_once('../settings/db_class.php');

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$current_user_id || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login_register.php");
    exit();
}

$chat_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$chat_user_id || $chat_user_id === $current_user_id) {
    header("Location: dashboard.php");
    exit();
}

$db = new db_connection();
$conn = $db->db_conn();

// Get chat user info
$user_query = "SELECT user_id, first_name, last_name, profile_photo FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $chat_user_id);
$stmt->execute();
$chat_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chat_user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: dashboard.php");
    exit();
}

// Check if they are connected
$connection_query = "SELECT * FROM UserConnections 
                     WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)) 
                     AND status = 'accepted'";
$stmt = $conn->prepare($connection_query);
$stmt->bind_param("iiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id);
$stmt->execute();
$is_connected = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$is_connected) {
    $_SESSION['error_message'] = "You must be connected to chat with this user.";
    header("Location: user_profile.php?id=" . $chat_user_id);
    exit();
}

// Note: Messages table doesn't have is_read column, so we skip marking messages as read
// If you want to track read status, you'll need to add is_read column to Messages table

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($chat_user['first_name'] . ' ' . $chat_user['last_name']); ?></title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --dark: #1f2937;
            --muted: #6b7280;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light);
            height: 100vh;
            overflow: hidden;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .chat-header {
            background: white;
            padding: 20px 30px;
            border-bottom: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .chat-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            border: 2px solid var(--primary);
        }

        .chat-user-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        .back-btn {
            padding: 10px 20px;
            background: var(--light);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #e5e7eb;
        }

        .refresh-btn {
            padding: 10px 20px;
            background: var(--light);
            color: var(--dark);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .refresh-btn:hover {
            background: #e5e7eb;
        }

        .refresh-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
           

        .back-btn:hover {
            background: var(--border);
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: #f9fafb;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-bubble {
            max-width: 60%;
            padding: 12px 18px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.received .message-bubble {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }

        .message.sent .message-bubble {
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-time {
            font-size: 0.75rem;
            margin-top: 4px;
            opacity: 0.7;
        }

        .message.received .message-time {
            color: var(--muted);
        }

        .message.sent .message-time {
            color: white;
            text-align: right;
        }

        .chat-input-container {
            padding: 20px 30px;
            background: white;
            border-top: 2px solid var(--border);
        }

        .chat-input-form {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border);
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: var(--primary);
        }

        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .send-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background: var(--muted);
            cursor: not-allowed;
            transform: scale(1);
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: var(--muted);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .message-bubble {
                max-width: 80%;
            }

            .chat-header {
                padding: 15px 20px;
            }

            .messages-container {
                padding: 20px;
            }

            .chat-input-container {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="chat-user-info">
                <?php if (!empty($chat_user['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($chat_user['profile_photo']); ?>" alt="Avatar" class="chat-avatar">
                <?php else: ?>
                    <div class="chat-avatar-placeholder">
                        <?php echo strtoupper(substr($chat_user['first_name'], 0, 1) . substr($chat_user['last_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="chat-user-name">
                    <?php echo htmlspecialchars($chat_user['first_name'] . ' ' . $chat_user['last_name']); ?>
                </div>
            </div>
            <div class="header-actions">
                <button class="refresh-btn" id="refreshBtn" onclick="manualRefresh()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="user_profile.php?id=<?php echo $chat_user_id; ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>

        <!-- Messages -->
        <div class="messages-container" id="messagesContainer">
            <div class="loading" id="loadingIndicator">
                <i class="fas fa-spinner fa-spin"></i> Loading messages...
            </div>
        </div>

        <!-- Input -->
        <div class="chat-input-container">
            <form class="chat-input-form" id="messageForm">
                <input type="text" class="chat-input" id="messageInput" placeholder="Type a message..." autocomplete="off" required>
                <button type="submit" class="send-btn" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const chatUserId = <?php echo $chat_user_id; ?>;
        const messagesContainer = document.getElementById('messagesContainer');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const refreshBtn = document.getElementById('refreshBtn');

        let lastMessageId = 0;
        let isRefreshing = false;
        let autoRefreshInterval = null;

        // Load messages function
        async function loadMessages(showLoading = false) {
            if (isRefreshing) return;
            
            try {
                isRefreshing = true;
                if (showLoading && refreshBtn) {
                    refreshBtn.disabled = true;
                    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                }

                const response = await fetch(`../actions/chat_messages.php?user_id=${chatUserId}&last_id=${lastMessageId}`);
                
                // Handle 403 errors (InfinityFree blocking)
                if (response.status === 403) {
                    console.warn('Request blocked (403). Please use manual refresh button.');
                    // Stop auto-refresh if getting 403 errors
                    if (autoRefreshInterval) {
                        clearInterval(autoRefreshInterval);
                        autoRefreshInterval = null;
                    }
                    if (showLoading) {
                        alert('Auto-refresh blocked by server. Please use the Refresh button to check for new messages.');
                    }
                    return;
                }
                
                if (!response.ok) {
                    console.error('Server error:', response.status);
                    return;
                }
                
                const data = await response.json();

                if (loadingIndicator) {
                    loadingIndicator.remove();
                }

                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        if (msg.message_id > lastMessageId) {
                            lastMessageId = msg.message_id;
                            appendMessage(msg);
                        }
                    });
                    scrollToBottom();
                } else if (lastMessageId === 0) {
                    // Show empty state only on first load
                    messagesContainer.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No messages yet. Start the conversation!</p>
                            <p style="font-size: 0.9em; margin-top: 10px;">Use the Refresh button to check for new messages.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                if (showLoading) {
                    alert('Failed to load messages. Please try again.');
                }
            } finally {
                isRefreshing = false;
                if (showLoading && refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                }
            }
        }

        // Manual refresh function
        function manualRefresh() {
            loadMessages(true);
        }

        // Append message to container
        function appendMessage(msg) {
            const emptyState = messagesContainer.querySelector('.empty-state');
            if (emptyState) emptyState.remove();

            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
            
            // Use sent_at instead of created_at (Messages table uses sent_at)
            const time = new Date(msg.sent_at).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });

            // Use content instead of message (Messages table uses content column)
            messageDiv.innerHTML = `
                <div class="message-bubble">
                    <div>${escapeHtml(msg.content)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;

            messagesContainer.appendChild(messageDiv);
        }

        // Send message
        messageForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;

            sendBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('user_id', chatUserId);
                formData.append('message', message);

                const response = await fetch('../actions/send_message.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    messageInput.value = '';
                    // Immediately show the sent message
                    loadMessages(false);
                } else {
                    alert(data.error || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message');
            }

            sendBtn.disabled = false;
            messageInput.focus();
        });

        // Scroll to bottom
        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial load
        loadMessages(true);

        // Poll for new messages every 10 seconds (InfinityFree-friendly interval)
        // Note: InfinityFree may still block this, so manual refresh button is available
        autoRefreshInterval = setInterval(() => loadMessages(false), 10000);
    </script>
</body>
</html>

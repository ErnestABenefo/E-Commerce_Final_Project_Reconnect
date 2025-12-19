<?php
// Group Chat Page
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../log_in_and_register.php');
    exit();
}

require_once __DIR__ . '/../settings/db_class.php';

$user_id = (int)$_SESSION['user_id'];

$db = new db_connection();
$conn = $db->db_conn();

// Get current user info
$stmt = $conn->prepare("SELECT first_name, last_name FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$displayName = $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Group Chats - ReConnect</title>
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <style>
        :root{--primary:#667eea;--danger:#e74c3c;--success:#27ae60;--muted:#666;--border:#e0e0e0}
        *{box-sizing:border-box}
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f4f6f8;margin:0;padding:0}
        
        /* Navigation */
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
            color: #667eea;
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
        }

        .navbar-menu a:hover,
        .navbar-menu a.active {
            color: #667eea;
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
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }
        
        /* Chat Layout */
        .chat-container{display:flex;height:calc(100vh - 80px);max-width:1400px;margin:80px auto 0;}
        
        /* Sidebar */
        .groups-sidebar{width:320px;background:#fff;border-right:1px solid var(--border);display:flex;flex-direction:column}
        .sidebar-header{padding:20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
        .sidebar-header h2{margin:0;font-size:1.3rem;color:#333}
        .groups-list{flex:1;overflow-y:auto}
        .group-item{padding:16px 20px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.2s}
        .group-item:hover{background:#f8f9fa}
        .group-item.active{background:#e8eaf6;border-left:3px solid var(--primary)}
        .group-name{font-weight:600;font-size:1rem;margin-bottom:4px;color:#333}
        .group-meta{font-size:0.85rem;color:var(--muted);display:flex;gap:12px}
        .group-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:0.75rem;font-weight:600;margin-top:4px}
        .group-badge.university{background:#e3f2fd;color:#1976d2}
        .group-badge.year{background:#fff3e0;color:#f57c00}
        
        /* Chat Area */
        .chat-area{flex:1;display:flex;flex-direction:column;background:#fff}
        .chat-header{padding:20px;border-bottom:1px solid var(--border);background:#fafafa}
        .chat-header h2{margin:0 0 4px 0;font-size:1.3rem;color:#333}
        .chat-header .chat-meta{font-size:0.9rem;color:var(--muted)}
        .messages-container{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px}
        .message{display:flex;gap:12px;padding:12px;border-radius:8px;max-width:70%}
        .message.own{align-self:flex-end;background:#e3f2fd;flex-direction:row-reverse}
        .message.other{align-self:flex-start;background:#f5f5f5}
        .message-avatar{width:40px;height:40px;border-radius:50%;background:#ddd;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0}
        .message-content{flex:1}
        .message-author{font-weight:600;font-size:0.9rem;margin-bottom:4px;color:#333}
        .message-text{font-size:0.95rem;color:#333;word-wrap:break-word}
        .message-time{font-size:0.75rem;color:var(--muted);margin-top:4px}
        
        /* Message Input */
        .message-input-container{padding:20px;border-top:1px solid var(--border);background:#fafafa}
        .message-input-form{display:flex;gap:12px}
        .message-input{flex:1;padding:12px 16px;border:1px solid var(--border);border-radius:24px;outline:none;font-size:1rem;font-family:inherit}
        .send-btn{background:var(--primary);color:#fff;border:none;padding:12px 24px;border-radius:24px;cursor:pointer;font-weight:600;font-size:1rem;transition:background 0.2s}
        .send-btn:hover{background:#5568d3}
        .send-btn:disabled{background:#ccc;cursor:not-allowed}
        
        /* Empty States */
        .empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--muted);text-align:center;padding:40px}
        .empty-state i{font-size:4rem;margin-bottom:20px;opacity:0.3}
        .empty-state h3{margin:0 0 8px 0;font-size:1.3rem}
        .empty-state p{margin:0;font-size:0.95rem}
        
        /* Loading */
        .loading{text-align:center;padding:20px;color:var(--muted)}
        
        /* Enroll Button */
        .enroll-btn{background:var(--success);color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.9rem}
        .enroll-btn:hover{background:#229954}
        
        /* Scrollbar */
        .groups-list::-webkit-scrollbar, .messages-container::-webkit-scrollbar{width:8px}
        .groups-list::-webkit-scrollbar-track, .messages-container::-webkit-scrollbar-track{background:#f1f1f1}
        .groups-list::-webkit-scrollbar-thumb, .messages-container::-webkit-scrollbar-thumb{background:#bbb;border-radius:4px}
        .groups-list::-webkit-scrollbar-thumb:hover, .messages-container::-webkit-scrollbar-thumb:hover{background:#999}
        
        /* View Members Button */
        .view-members-btn{background:var(--primary);color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-weight:600;font-size:0.9rem;margin-top:8px;display:inline-flex;align-items:center;gap:6px;transition:background 0.2s}
        .view-members-btn:hover{background:#5568d3}
        
        /* Members Modal */
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5)}
        .modal.active{display:flex;align-items:center;justify-content:center}
        .modal-content{background:#fff;border-radius:12px;max-width:700px;width:90%;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 10px 40px rgba(0,0,0,0.3)}
        .modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
        .modal-header h2{margin:0;font-size:1.4rem;color:#333}
        .modal-close{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#999;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background 0.2s}
        .modal-close:hover{background:#f0f0f0}
        .modal-body{padding:20px 24px;overflow-y:auto;flex:1}
        .member-card{display:flex;gap:16px;padding:16px;border:1px solid var(--border);border-radius:8px;margin-bottom:12px;transition:box-shadow 0.2s}
        .member-card:hover{box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .member-avatar{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#764ba2);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:1.3rem;flex-shrink:0}
        .member-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}
        .member-info{flex:1;min-width:0}
        .member-name{font-size:1.1rem;font-weight:700;color:#333;margin-bottom:4px;display:flex;align-items:center;gap:8px}
        .member-name a{color:#333;text-decoration:none;transition:color 0.3s}
        .member-name a:hover{color:var(--primary)}
        .verified-badge{display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;white-space:nowrap}
        .verified-badge i{font-size:0.85rem}
        .member-email{color:#666;font-size:0.9rem;margin-bottom:4px}
        .member-bio{color:#888;font-size:0.85rem;line-height:1.4;margin-bottom:8px}
        .member-date{color:#999;font-size:0.8rem}
        .member-actions{display:flex;gap:8px;flex-direction:column}
        .member-actions button, .member-actions a{padding:8px 16px;border-radius:6px;text-decoration:none;font-size:0.85rem;font-weight:600;transition:background 0.2s;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
        .member-actions .btn-profile{background:#667eea;color:white;width:100%}
        .member-actions .btn-profile:hover{background:#5568d3}
        .member-actions .btn-promote{background:#27ae60;color:white;width:100%}
        .member-actions .btn-promote:hover{background:#229954}
        .member-actions .btn-demote{background:#f39c12;color:white;width:100%}
        .member-actions .btn-demote:hover{background:#e67e22}
        .member-actions .btn-remove{background:#e74c3c;color:white;width:100%}
        .member-actions .btn-remove:hover{background:#c0392b}
        .members-count{color:var(--muted);font-size:0.9rem;margin-bottom:12px}
        .admin-badge{display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);color:white;padding:3px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;white-space:nowrap}
        .admin-badge i{font-size:0.85rem}
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-graduation-cap"></i>
                ReConnect
            </a>
            
            <?php include 'search_component.php'; ?>
            
            <ul class="navbar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="connections.php"><i class="fas fa-user-friends"></i> Connections</a></li>
                <li><a href="groups.php" class="active"><i class="fas fa-users"></i> Groups</a></li>
                <li><a href="events.php"><i class="fas fa-calendar"></i> Events</a></li>
                <li><a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a></li>
                <li><a href="jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="dashboard.php"><i class="fas fa-user"></i> Profile</a></li>
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

    <!-- Chat Container -->
    <div class="chat-container">
        <!-- Groups Sidebar -->
        <div class="groups-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-users"></i> My Groups</h2>
                <button id="enrollBtn" class="enroll-btn" title="Join your university and year groups">
                    <i class="fas fa-user-plus"></i> Enroll
                </button>
            </div>
            <div class="groups-list" id="groupsList">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading groups...
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area" id="chatArea">
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>Select a Group</h3>
                <p>Choose a group from the sidebar to start chatting</p>
            </div>
        </div>
    </div>

    <!-- Members Modal -->
    <div id="membersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalGroupName">Group Members</h2>
                <button class="modal-close" onclick="closeMembersModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="membersContainer">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading members...
                </div>
            </div>
        </div>
    </div>

    <script>
        const userId = <?php echo $user_id; ?>;
        let currentGroupId = null;
        let messagePollingInterval = null;

        // Load user's groups
        async function loadGroups() {
            try {
                const response = await fetch('../actions/get_user_groups_action.php');
                const data = await response.json();

                const groupsList = document.getElementById('groupsList');

                if (data.status === 'success' && data.groups.length > 0) {
                    groupsList.innerHTML = '';
                    data.groups.forEach(group => {
                        const groupDiv = document.createElement('div');
                        groupDiv.className = 'group-item';
                        groupDiv.dataset.groupId = group.group_id;
                        
                        let badgeClass = group.group_type === 'university' ? 'university' : 'year';
                        let badgeText = group.group_type === 'university' ? 'University' : 'Year Group';
                        
                        groupDiv.innerHTML = `
                            <div class="group-name"><i class="fas fa-users"></i> ${escapeHtml(group.name)}</div>
                            <div class="group-meta">
                                <span><i class="fas fa-user"></i> ${group.member_count} members</span>
                                <span><i class="fas fa-comment"></i> ${group.message_count} messages</span>
                            </div>
                            <span class="group-badge ${badgeClass}">${badgeText}</span>
                        `;
                        
                        groupDiv.addEventListener('click', () => selectGroup(group.group_id, group.name, group.description, group.member_count));
                        groupsList.appendChild(groupDiv);
                    });
                } else {
                    groupsList.innerHTML = `
                        <div class="empty-state" style="padding:40px 20px">
                            <i class="fas fa-users" style="font-size:3rem"></i>
                            <p style="margin:12px 0 0 0">No groups yet. Click "Enroll" to join your university and year groups.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading groups:', error);
                document.getElementById('groupsList').innerHTML = `
                    <div class="empty-state" style="padding:40px 20px">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading groups</p>
                    </div>
                `;
            }
        }

        // Select a group
        function selectGroup(groupId, groupName, groupDesc, memberCount) {
            // Update active state
            document.querySelectorAll('.group-item').forEach(item => item.classList.remove('active'));
            document.querySelector(`[data-group-id="${groupId}"]`).classList.add('active');
            
            currentGroupId = groupId;
            
            // Update chat area
            const chatArea = document.getElementById('chatArea');
            chatArea.innerHTML = `
                <div class="chat-header">
                    <h2>${escapeHtml(groupName)}</h2>
                    <div class="chat-meta">
                        <i class="fas fa-user"></i> ${memberCount} members • ${escapeHtml(groupDesc || '')}
                    </div>
                    <button class="view-members-btn" onclick="viewGroupMembers(${groupId}, '${escapeHtml(groupName)}')">
                        <i class="fas fa-users"></i> View Group Members
                    </button>
                </div>
                <div class="messages-container" id="messagesContainer">
                    <div class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Loading messages...
                    </div>
                </div>
                <div class="message-input-container">
                    <form class="message-input-form" id="messageForm">
                        <input type="text" class="message-input" id="messageInput" placeholder="Type a message..." autocomplete="off">
                        <button type="submit" class="send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </form>
                </div>
            `;
            
            // Load messages
            loadMessages(groupId);
            
            // Setup message form
            document.getElementById('messageForm').addEventListener('submit', handleSendMessage);
            
            // Start polling for new messages
            if (messagePollingInterval) clearInterval(messagePollingInterval);
            messagePollingInterval = setInterval(() => loadMessages(groupId, true), 3000);
        }

        // Load messages
        async function loadMessages(groupId, silent = false) {
            try {
                const response = await fetch(`../actions/group_chat_action.php?action=get&group_id=${groupId}&limit=100`);
                const data = await response.json();

                const container = document.getElementById('messagesContainer');
                if (!container) return;

                if (data.status === 'success') {
                    const scrollAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
                    
                    if (data.messages.length === 0) {
                        if (!silent) {
                            container.innerHTML = `
                                <div class="empty-state">
                                    <i class="fas fa-comment"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            `;
                        }
                    } else {
                        container.innerHTML = '';
                        data.messages.forEach(msg => {
                            const messageDiv = createMessageElement(msg);
                            container.appendChild(messageDiv);
                        });
                        
                        // Scroll to bottom if user was already at bottom
                        if (scrollAtBottom || !silent) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        // Create message element
        function createMessageElement(msg) {
            const div = document.createElement('div');
            const isOwn = msg.sender_id === userId;
            div.className = `message ${isOwn ? 'own' : 'other'}`;
            
            const initials = (msg.first_name.charAt(0) + msg.last_name.charAt(0)).toUpperCase();
            const userName = escapeHtml(msg.first_name + ' ' + msg.last_name);
            
            div.innerHTML = `
                <div class="message-avatar">${initials}</div>
                <div class="message-content">
                    ${!isOwn ? `<div class="message-author">${userName}</div>` : ''}
                    <div class="message-text">${escapeHtml(msg.content)}</div>
                    <div class="message-time">${formatTime(msg.sent_at)}</div>
                </div>
            `;
            
            return div;
        }

        // Handle send message
        async function handleSendMessage(e) {
            e.preventDefault();
            
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentGroupId) return;
            
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('group_id', currentGroupId);
                formData.append('message', message);
                
                const response = await fetch('../actions/group_chat_action.php?action=send', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    input.value = '';
                    loadMessages(currentGroupId, true);
                } else {
                    alert(data.message || 'Failed to send message');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('An error occurred');
            }
            
            sendBtn.disabled = false;
            input.focus();
        }

        // Enroll in groups
        document.getElementById('enrollBtn').addEventListener('click', async function() {
            this.disabled = true;
            
            try {
                const response = await fetch('../actions/group_chat_action.php?action=enroll');
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('Successfully enrolled in your groups!');
                    loadGroups();
                } else {
                    alert(data.message || 'Failed to enroll');
                }
            } catch (error) {
                console.error('Error enrolling:', error);
                alert('An error occurred');
            }
            
            this.disabled = false;
        });

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const messageDate = new Date(date);
            messageDate.setHours(0, 0, 0, 0);
            
            if (messageDate.getTime() === today.getTime()) {
                return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }
            
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        // View Group Members
        async function viewGroupMembers(groupId, groupName) {
            const modal = document.getElementById('membersModal');
            const modalGroupName = document.getElementById('modalGroupName');
            const membersContainer = document.getElementById('membersContainer');
            
            modalGroupName.textContent = `${groupName} - Members`;
            membersContainer.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading members...</div>';
            
            modal.classList.add('active');
            
            try {
                const response = await fetch(`../actions/get_group_members_action.php?group_id=${groupId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    if (data.members.length > 0) {
                        let membersHtml = `<div class="members-count"><i class="fas fa-users"></i> ${data.count} ${data.count === 1 ? 'member' : 'members'}</div>`;
                        
                        data.members.forEach(member => {
                            const fullName = `${member.first_name} ${member.last_name}`;
                            const initials = (member.first_name.charAt(0) + member.last_name.charAt(0)).toUpperCase();
                            const avatar = member.profile_photo 
                                ? `<img src="${escapeHtml(member.profile_photo)}" alt="${escapeHtml(fullName)}">` 
                                : initials;
                            const verifiedBadge = member.is_verified 
                                ? '<span class="verified-badge" title="Verified Alumni"><i class="fas fa-check-circle"></i> Verified</span>' 
                                : '';
                            const adminBadge = member.is_admin 
                                ? '<span class="admin-badge" title="Group Admin"><i class="fas fa-shield-alt"></i> Admin</span>' 
                                : '';
                            const bio = member.bio ? `<div class="member-bio">${escapeHtml(member.bio.substring(0, 150))}${member.bio.length > 150 ? '...' : ''}</div>` : '';
                            const joinedDate = new Date(member.joined_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                            
                            // Build action buttons
                            let actionButtons = `<a href="user_profile.php?id=${member.user_id}" class="btn-profile">
                                <i class="fas fa-user"></i> View Profile
                            </a>`;
                            
                            // Add admin controls if current user is admin and target is not current user
                            if (data.current_user_is_admin && member.user_id !== userId) {
                                if (member.is_admin) {
                                    // If target is admin, show demote button
                                    actionButtons += `
                                        <button class="btn-demote" onclick="demoteGroupMember(${groupId}, ${member.user_id}, '${escapeHtml(fullName)}')">
                                            <i class="fas fa-arrow-down"></i> Remove Admin
                                        </button>
                                    `;
                                } else {
                                    // If target is not admin, show promote button
                                    actionButtons += `
                                        <button class="btn-promote" onclick="promoteGroupMember(${groupId}, ${member.user_id}, '${escapeHtml(fullName)}')">
                                            <i class="fas fa-arrow-up"></i> Make Admin
                                        </button>
                                    `;
                                }
                                
                                // Always show remove button for other users
                                actionButtons += `
                                    <button class="btn-remove" onclick="removeGroupMember(${groupId}, ${member.user_id}, '${escapeHtml(fullName)}')">
                                        <i class="fas fa-user-times"></i> Remove User
                                    </button>
                                `;
                            }
                            
                            membersHtml += `
                                <div class="member-card">
                                    <div class="member-avatar">${avatar}</div>
                                    <div class="member-info">
                                        <div class="member-name">
                                            <a href="user_profile.php?id=${member.user_id}">${escapeHtml(fullName)}</a>
                                            ${verifiedBadge}
                                            ${adminBadge}
                                        </div>
                                        <div class="member-email"><i class="fas fa-envelope"></i> ${escapeHtml(member.email)}</div>
                                        ${bio}
                                        <div class="member-date"><i class="fas fa-calendar"></i> Joined ${joinedDate}</div>
                                    </div>
                                    <div class="member-actions">
                                        ${actionButtons}
                                    </div>
                                </div>
                            `;
                        });
                        
                        membersContainer.innerHTML = membersHtml;
                    } else {
                        membersContainer.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No members found</p></div>';
                    }
                } else {
                    membersContainer.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${escapeHtml(data.message || 'Error loading members')}</p></div>`;
                }
            } catch (error) {
                console.error('Error loading group members:', error);
                membersContainer.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading members</p></div>';
            }
        }

        // Close Members Modal
        function closeMembersModal() {
            document.getElementById('membersModal').classList.remove('active');
        }

        // Close modal on outside click
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('membersModal');
            if (event.target === modal) {
                closeMembersModal();
            }
        });

        // Promote user to group admin
        async function promoteGroupMember(groupId, targetUserId, userName) {
            if (!confirm(`Are you sure you want to make ${userName} a group admin?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'promote');
                formData.append('group_id', groupId);
                formData.append('target_user_id', targetUserId);
                
                const response = await fetch('../actions/manage_group_member_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert(data.message);
                    // Reload members list
                    const groupName = document.getElementById('modalGroupName').textContent.split(' - ')[0];
                    viewGroupMembers(groupId, groupName);
                } else {
                    alert(data.message || 'Failed to promote user');
                }
            } catch (error) {
                console.error('Error promoting user:', error);
                alert('An error occurred while promoting the user');
            }
        }

        // Demote user from group admin
        async function demoteGroupMember(groupId, targetUserId, userName) {
            if (!confirm(`Are you sure you want to remove admin privileges from ${userName}?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'demote');
                formData.append('group_id', groupId);
                formData.append('target_user_id', targetUserId);
                
                const response = await fetch('../actions/manage_group_member_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert(data.message);
                    // Reload members list
                    const groupName = document.getElementById('modalGroupName').textContent.split(' - ')[0];
                    viewGroupMembers(groupId, groupName);
                } else {
                    alert(data.message || 'Failed to demote user');
                }
            } catch (error) {
                console.error('Error demoting user:', error);
                alert('An error occurred while demoting the user');
            }
        }

        // Remove user from group
        async function removeGroupMember(groupId, targetUserId, userName) {
            if (!confirm(`Are you sure you want to remove ${userName} from this group? This action cannot be undone.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('group_id', groupId);
                formData.append('target_user_id', targetUserId);
                
                const response = await fetch('../actions/manage_group_member_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    alert(data.message);
                    // Reload members list
                    const groupName = document.getElementById('modalGroupName').textContent.split(' - ')[0];
                    viewGroupMembers(groupId, groupName);
                } else {
                    alert(data.message || 'Failed to remove user');
                }
            } catch (error) {
                console.error('Error removing user:', error);
                alert('An error occurred while removing the user');
            }
        }

        // Load groups on page load
        loadGroups();

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (messagePollingInterval) clearInterval(messagePollingInterval);
        });
    </script>
</body>
</html>

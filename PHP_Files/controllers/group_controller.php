<?php
// Group Controller
require_once __DIR__ . '/../classes/group_class.php';

/**
 * Get user's groups
 */
function get_user_groups_ctr($user_id) {
    $gm = new GroupManager();
    return $gm->getUserGroups($user_id);
}

/**
 * Auto-enroll user in groups
 */
function auto_enroll_user_ctr($user_id) {
    $gm = new GroupManager();
    return $gm->autoEnrollUser($user_id);
}

/**
 * Check if user is member of group
 */
function is_group_member_ctr($user_id, $group_id) {
    $gm = new GroupManager();
    return $gm->isGroupMember($user_id, $group_id);
}

/**
 * Get group details
 */
function get_group_details_ctr($group_id) {
    $gm = new GroupManager();
    return $gm->getGroupDetails($group_id);
}

/**
 * Send message to group chat
 */
function send_group_message_ctr($user_id, $group_id, $message) {
    $gm = new GroupManager();
    
    // Verify user is member
    if (!$gm->isGroupMember($user_id, $group_id)) {
        return false;
    }
    
    $conn = $gm->db_conn();
    
    // Get chat_id for this group
    $stmt = $conn->prepare("SELECT chat_id FROM GroupChats WHERE group_id = ?");
    $stmt->bind_param('i', $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chat = $result->fetch_assoc();
    $stmt->close();
    
    if (!$chat) {
        // Create chat if doesn't exist
        $stmt = $conn->prepare("INSERT INTO GroupChats (group_id, created_at) VALUES (?, NOW())");
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $chat_id = $stmt->insert_id;
        $stmt->close();
    } else {
        $chat_id = $chat['chat_id'];
    }
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO GroupChatMessages (chat_id, sender_id, content, sent_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iis', $chat_id, $user_id, $message);
    $success = $stmt->execute();
    $msg_id = $stmt->insert_id;
    $stmt->close();
    
    if ($success) {
        // Return the new message
        $stmt = $conn->prepare("
            SELECT gcm.msg_id, gcm.content, gcm.sent_at, 
                   u.first_name, u.last_name, u.profile_photo
            FROM GroupChatMessages gcm
            JOIN Users u ON gcm.sender_id = u.user_id
            WHERE gcm.msg_id = ?
        ");
        $stmt->bind_param('i', $msg_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $stmt->close();
        
        return $message;
    }
    
    return false;
}

/**
 * Get messages from group chat
 */
function get_group_messages_ctr($user_id, $group_id, $limit = 50, $offset = 0) {
    $gm = new GroupManager();
    
    // Verify user is member
    if (!$gm->isGroupMember($user_id, $group_id)) {
        return false;
    }
    
    $conn = $gm->db_conn();
    
    // Get messages
    $stmt = $conn->prepare("
        SELECT gcm.msg_id, gcm.content, gcm.sent_at, gcm.sender_id,
               u.first_name, u.last_name, u.profile_photo
        FROM GroupChats gc
        JOIN GroupChatMessages gcm ON gc.chat_id = gcm.chat_id
        JOIN Users u ON gcm.sender_id = u.user_id
        WHERE gc.group_id = ?
        ORDER BY gcm.sent_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('iii', $group_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    // Reverse to show oldest first
    return array_reverse($messages);
}
?>

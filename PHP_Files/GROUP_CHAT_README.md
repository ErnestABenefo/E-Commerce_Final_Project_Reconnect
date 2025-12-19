# Group Chat Feature Implementation

## Overview
The group chat feature enables users to communicate in community-exclusive groups based on:
1. **University Groups** - All verified alumni from the same university
2. **Year Groups** - Alumni who graduated in the same year from the same university

## Database Schema

### Required Tables (Already Exist)
- `Groups` - Stores group information
- `GroupMembers` - Links users to groups
- `GroupChats` - One chat per group
- `GroupChatMessages` - Messages in group chats

### Database Migrations Required

**IMPORTANT**: Run this SQL before using the feature:

```sql
-- Add role column to GroupMembers
ALTER TABLE GroupMembers 
ADD COLUMN role ENUM('admin', 'member') NOT NULL DEFAULT 'member' AFTER user_id;

-- Add is_auto_generated flag to Groups
ALTER TABLE `Groups` 
ADD COLUMN is_auto_generated BOOLEAN DEFAULT FALSE AFTER group_type;

-- Ensure unique chat per group
ALTER TABLE GroupChats
ADD UNIQUE KEY unique_group_chat (group_id);
```

Or run: `migrations/add_group_chat_enhancements.sql`

## Implementation Architecture

### Backend Components

#### 1. **GroupManager Class** (`classes/group_class.php`)
Core functionality for managing groups:

**Methods:**
- `getOrCreateUniversityGroup($university_id)` - Creates university-wide group
- `getOrCreateYearGroup($university_id, $graduation_year)` - Creates year-specific group
- `addUserToGroup($user_id, $group_id, $role)` - Adds user to group
- `autoEnrollUser($user_id)` - Auto-enrolls user based on verification
- `getUserGroups($user_id)` - Gets all groups user belongs to
- `isGroupMember($user_id, $group_id)` - Checks membership
- `getGroupDetails($group_id)` - Gets group information

**Auto-Creation Logic:**
- University groups created with format: "{University Name} Community"
- Year groups created with format: "{University Name} - Class of {Year}"
- Groups marked with `is_auto_generated = 1`
- Each group automatically gets a GroupChat record

#### 2. **Group Controller** (`controllers/group_controller.php`)
Controller functions wrapping GroupManager:

**Functions:**
- `get_user_groups_ctr($user_id)` - Get user's groups
- `auto_enroll_user_ctr($user_id)` - Trigger auto-enrollment
- `is_group_member_ctr($user_id, $group_id)` - Check membership
- `get_group_details_ctr($group_id)` - Get group info
- `send_group_message_ctr($user_id, $group_id, $message)` - Send message
- `get_group_messages_ctr($user_id, $group_id, $limit, $offset)` - Get messages

#### 3. **Action Endpoints**

**`actions/get_user_groups_action.php`**
- GET request
- Returns JSON with all groups user is member of
- Includes member count, message count, group type

**`actions/group_chat_action.php`**
- Multiple actions via `?action=` parameter:
  - `send` (POST) - Send message to group
  - `get` (GET) - Retrieve messages from group
  - `enroll` (GET) - Auto-enroll user in their groups

### Frontend Components

#### **Groups Page** (`view/groups.php`)
Full-featured group chat interface with:

**Layout:**
- Left sidebar: List of user's groups
- Right area: Active chat view

**Features:**
- Real-time message polling (every 3 seconds)
- Send messages with enter key
- Responsive design
- Group badges (University vs Year Group)
- Member and message counts
- "Enroll" button to join groups

**UI Elements:**
- Group list with badges and metadata
- Message bubbles (left for others, right for own)
- User avatars with initials
- Timestamp formatting (relative times)
- Empty states for no groups/messages

## Auto-Enrollment Process

### When Does It Happen?

Users are automatically enrolled when:
1. **Alumni verification is approved** (`AlumniVerification.verification_status = 'approved'`)
2. **Graduation year is set** in `UserAcademicProfile`
3. **User clicks "Enroll" button** on groups page

### Enrollment Logic

```php
// Triggered by: group_chat_action.php?action=enroll
autoEnrollUser($user_id)
  ├─ Query AlumniVerification for approved universities
  ├─ For each university:
  │   ├─ getOrCreateUniversityGroup() → adds to university community
  │   └─ If graduation_year exists:
  │       └─ getOrCreateYearGroup() → adds to year group
  └─ Query UserAcademicProfile for additional graduation years
      └─ Add to relevant year groups
```

### Group Types

**1. University Groups** (`group_type = 'university'`)
- One per university
- All verified alumni are members
- Name: "{University} Community"
- Description: "Official community group for all {University} members"

**2. Year Groups** (`group_type = 'year_group'`)
- One per university per graduation year
- Only alumni from that specific year
- Name: "{University} - Class of {Year}"
- Description: "Group for {University} alumni graduating in {Year}"

## User Flow

### 1. First Time User
```
User registers → Gets verified → Has graduation year
                                       ↓
                            Clicks "Enroll" button
                                       ↓
                    System creates/finds university group
                                       ↓
                    System creates/finds year group
                                       ↓
                    User added to both groups automatically
                                       ↓
                    Groups appear in sidebar
```

### 2. Sending Messages
```
Select group → Type message → Press Send
                                  ↓
              Server validates membership
                                  ↓
              Message saved to GroupChatMessages
                                  ↓
              Message appears in chat (polling updates)
```

### 3. Viewing Messages
```
Click group → Server checks membership
                       ↓
            Loads last 100 messages
                       ↓
            Displays in chronological order
                       ↓
            Auto-refreshes every 3 seconds
```

## Security Features

### Access Control
- ✅ Users can only see groups they're members of
- ✅ Users can only send messages to groups they're in
- ✅ Membership verified before every operation
- ✅ Session authentication required for all endpoints

### Data Validation
- ✅ Message content required (not empty)
- ✅ Group ID validated
- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (escapeHtml on output)

## Navigation Integration

### Dashboard (`view/dashboard.php`)
- "My Groups" button in Quick Actions section
- Links directly to `groups.php`

### Feed (`index.php`)
- "Groups" link in top navigation bar
- Accessible from any page

## Technical Details

### Message Polling
- Interval: 3000ms (3 seconds)
- Method: JavaScript setInterval
- Clears on page unload or group change
- Only loads new messages if user at bottom of chat

### Performance Optimizations
- Limit 100 messages per query
- Indexes on group_id, user_id, sent_at
- UNIQUE constraint on (group_id, user_id) in GroupMembers
- Unique chat per group constraint

### Responsive Design
- Sidebar: 320px fixed width
- Chat area: Flexible
- Messages: Max 70% width
- Scrollable containers with custom scrollbars

## Testing Checklist

### Database Setup
- [ ] Run migration SQL
- [ ] Verify columns added: `role`, `is_auto_generated`
- [ ] Check unique constraints

### Auto-Enrollment
- [ ] Create user with verified university
- [ ] Set graduation year in academic profile
- [ ] Click "Enroll" button
- [ ] Verify university group created
- [ ] Verify year group created
- [ ] Check GroupMembers table for entries

### Messaging
- [ ] Send message to university group
- [ ] Send message to year group
- [ ] Verify messages appear in real-time
- [ ] Check message timestamps
- [ ] Test with multiple users
- [ ] Verify only members can see messages

### UI/UX
- [ ] Groups load on page load
- [ ] Active group highlights
- [ ] Messages scroll to bottom
- [ ] Send button disables during send
- [ ] Empty states display correctly
- [ ] Timestamps format properly

## Troubleshooting

### "No groups yet" Message
**Cause**: User not enrolled or no verified universities
**Solution**: 
1. Verify user has approved AlumniVerification records
2. Click "Enroll" button
3. Check graduation_year is set

### "You are not a member of this group"
**Cause**: GroupMembers entry missing
**Solution**:
```sql
-- Manually add user to group
INSERT INTO GroupMembers (group_id, user_id, role, joined_at) 
VALUES (?, ?, 'member', NOW());
```

### Messages Not Loading
**Cause**: GroupChat record missing
**Solution**:
```sql
-- Create chat for group
INSERT INTO GroupChats (group_id, created_at) VALUES (?, NOW());
```

### Polling Not Working
**Cause**: JavaScript error or network issue
**Solution**:
1. Check browser console for errors
2. Verify action endpoint returns JSON
3. Check network tab for failed requests

## Future Enhancements

Possible improvements:
- [ ] WebSocket support for real-time updates (no polling)
- [ ] File/image sharing in chats
- [ ] Message reactions (emoji)
- [ ] @mentions and notifications
- [ ] Group admin controls (mute, kick users)
- [ ] Search messages
- [ ] Pin important messages
- [ ] Custom user-created groups (not just auto-generated)
- [ ] Voice/video chat integration
- [ ] Read receipts
- [ ] Message editing/deletion
- [ ] Rich text formatting (bold, italic, links)

## API Reference

### Get User Groups
```javascript
GET /actions/get_user_groups_action.php

Response:
{
  "status": "success",
  "groups": [
    {
      "group_id": 1,
      "name": "University of Ghana Community",
      "description": "...",
      "group_type": "university",
      "is_auto_generated": 1,
      "role": "member",
      "member_count": 150,
      "message_count": 523
    }
  ]
}
```

### Send Message
```javascript
POST /actions/group_chat_action.php?action=send
Body: FormData {
  group_id: 1,
  message: "Hello everyone!"
}

Response:
{
  "status": "success",
  "message": {
    "msg_id": 45,
    "content": "Hello everyone!",
    "sent_at": "2025-12-08 14:30:00",
    "first_name": "John",
    "last_name": "Doe"
  }
}
```

### Get Messages
```javascript
GET /actions/group_chat_action.php?action=get&group_id=1&limit=100

Response:
{
  "status": "success",
  "messages": [
    {
      "msg_id": 45,
      "content": "Hello!",
      "sent_at": "2025-12-08 14:30:00",
      "sender_id": 5,
      "first_name": "John",
      "last_name": "Doe",
      "profile_photo": null
    }
  ]
}
```

### Auto-Enroll
```javascript
GET /actions/group_chat_action.php?action=enroll

Response:
{
  "status": "success",
  "enrolled": true,
  "message": "Successfully enrolled in groups"
}
```

## Database Queries

### Get All University Groups
```sql
SELECT * FROM `Groups` 
WHERE group_type = 'university' 
AND is_auto_generated = 1;
```

### Get Year Groups for University
```sql
SELECT * FROM `Groups` 
WHERE university_id = ? 
AND group_type = 'year_group' 
AND is_auto_generated = 1
ORDER BY name;
```

### Get Group Members
```sql
SELECT u.user_id, u.first_name, u.last_name, gm.role, gm.joined_at
FROM GroupMembers gm
JOIN Users u ON gm.user_id = u.user_id
WHERE gm.group_id = ?
ORDER BY gm.joined_at DESC;
```

### Get Recent Messages
```sql
SELECT gcm.*, u.first_name, u.last_name
FROM GroupChatMessages gcm
JOIN GroupChats gc ON gcm.chat_id = gc.chat_id
JOIN Users u ON gcm.sender_id = u.user_id
WHERE gc.group_id = ?
ORDER BY gcm.sent_at DESC
LIMIT 100;
```

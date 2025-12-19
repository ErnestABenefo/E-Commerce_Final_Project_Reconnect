# University Context Switching Feature - Implementation Guide

## Overview
University admins can now act as their university, creating posts and managing groups as the university entity instead of their personal account.

---

## How It Works

### 1. **Session-Based Context Switching**
When a university admin logs in, they can switch between:
- **Personal Context**: Acting as themselves (normal user)
- **University Context**: Acting as the university (university admin)

### 2. **Key Components**

#### **Session Variables**
```php
$_SESSION['acting_as_university']      // bool - true if acting as university
$_SESSION['active_university_id']      // int - ID of active university
$_SESSION['active_university_name']    // string - Name of active university
$_SESSION['original_user_id']          // int - Track which admin is acting
```

#### **Controller Functions** (in `controllers/user_controller.php`)
```php
get_user_universities_ctr($user_id)                    // Get universities user can manage
can_act_as_university_ctr($user_id, $university_id)   // Check permission
switch_to_university_context_ctr($university_id)      // Switch to university
switch_to_personal_context_ctr()                      // Switch back to personal
is_acting_as_university_ctr()                         // Check current context
get_active_university_context_ctr()                   // Get context details
```

#### **Action Endpoint** (`actions/switch_university_context_action.php`)
```
POST with action=switch_to_university&university_id=X  // Switch to university
POST with action=switch_to_personal                    // Switch to personal
POST with action=get_my_universities                   // Get available universities
POST with action=get_current_context                   // Get current context
```

#### **UI Component** (`view/context_switcher.php`)
A dropdown component showing:
- Current context (Personal or University Name)
- List of universities user can manage
- One-click switching between contexts

---

## Implementation Steps

### Step 1: Add Context Switcher to Your Pages

In your navigation/header file (e.g., `view/dashboard.php`), add:

```php
<?php
session_start();
// ... your other code
?>

<!-- In your navigation area -->
<nav>
    <!-- Your existing nav items -->
    
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <!-- Include the context switcher -->
        <?php include 'context_switcher.php'; ?>
    <?php endif; ?>
</nav>
```

### Step 2: Update Post Creation to Support Context

The `actions/create_post_action.php` has been updated to:
- Check if user is acting as university (`$_SESSION['acting_as_university']`)
- If yes: Create post with `university_id` and `creator_type='university'`
- If no: Create post with `user_id` and `creator_type='user'`

**Example of checking context in your code:**
```php
require_once '../controllers/user_controller.php';

if (is_acting_as_university_ctr()) {
    $context = get_active_university_context_ctr();
    $university_id = $context['university_id'];
    // Create content as university
} else {
    $user_id = $_SESSION['user_id'];
    // Create content as user
}
```

### Step 3: Update Group Creation (If Needed)

For group creation, you'll need to update your group creation action similar to posts:

```php
$acting_as_university = isset($_SESSION['acting_as_university']) && $_SESSION['acting_as_university'] === true;

if ($acting_as_university) {
    $university_id = (int)$_SESSION['active_university_id'];
    // Insert with university_id, set owner_user_id to NULL
    $stmt = $conn->prepare("INSERT INTO `Groups` (university_id, name, description, group_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $university_id, $name, $description, $group_type);
} else {
    // Insert with owner_user_id
    $stmt = $conn->prepare("INSERT INTO `Groups` (owner_user_id, name, description, group_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $name, $description, $group_type);
}
```

---

## Database Schema (Already Set Up)

### Posts Table
```sql
CREATE TABLE IF NOT EXISTS Posts (
  post_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id   INT UNSIGNED,              -- User who created (NULL if university)
  university_id INT UNSIGNED,          -- University that created (NULL if user)
  content   TEXT NOT NULL,
  post_type VARCHAR(50) NOT NULL,
  creator_type ENUM('user', 'university') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id),
  -- Constraint ensures either user_id OR university_id is set, not both
  CONSTRAINT chk_posts_creator CHECK (
    (creator_type = 'user' AND user_id IS NOT NULL AND university_id IS NULL) OR 
    (creator_type = 'university' AND university_id IS NOT NULL AND user_id IS NULL)
  )
);
```

### Groups Table
```sql
CREATE TABLE IF NOT EXISTS `Groups` (
  group_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_user_id INT UNSIGNED,          -- User who owns (NULL if university)
  university_id INT UNSIGNED,          -- University that owns (NULL if user)
  name       VARCHAR(255) NOT NULL,
  description TEXT,
  group_type VARCHAR(50) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (group_id),
  FOREIGN KEY (owner_user_id) REFERENCES Users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (university_id) REFERENCES University(university_id) ON DELETE CASCADE
);
```

---

## Testing Workflow

### 1. **Setup Test Data**
```sql
-- Create a test university
INSERT INTO University (name, location) VALUES ('Test University', 'Test City');

-- Make yourself an admin (replace USER_ID and UNIVERSITY_ID)
INSERT INTO UniversityAdmins (university_id, user_id, role, status) 
VALUES (1, YOUR_USER_ID, 'admin', 'active');
```

### 2. **Test Scenario**
1. **Login** as your user account
2. **Navigate** to dashboard - you should see the context switcher
3. **Click** the context switcher button - dropdown should show:
   - "Personal Account" (currently active)
   - "Test University" option
4. **Click** "Test University" - page should reload
5. **Verify** context badge shows "University" and button shows "Test University"
6. **Create a post** - it should be created as the university
7. **Check database**:
   ```sql
   SELECT post_id, user_id, university_id, creator_type, content 
   FROM Posts 
   ORDER BY post_id DESC LIMIT 5;
   ```
   Your post should have:
   - `user_id` = NULL
   - `university_id` = 1
   - `creator_type` = 'university'
8. **Click** context switcher again, select "Personal Account"
9. **Create another post** - it should be created as you
10. **Check database** again - this post should have:
    - `user_id` = YOUR_ID
    - `university_id` = NULL
    - `creator_type` = 'user'

---

## Display Posts with Creator Information

When displaying posts, check the creator type:

```php
if ($post['creator_type'] === 'university') {
    // Fetch university name
    $creator = $post['university_name'];
    $creator_icon = '<i class="fas fa-university"></i>';
} else {
    // Fetch user name
    $creator = $post['user_first_name'] . ' ' . $post['user_last_name'];
    $creator_icon = '<i class="fas fa-user"></i>';
}
```

**Example Query:**
```sql
SELECT 
    p.post_id,
    p.content,
    p.creator_type,
    p.created_at,
    u.first_name,
    u.last_name,
    uni.name as university_name
FROM Posts p
LEFT JOIN Users u ON p.user_id = u.user_id
LEFT JOIN University uni ON p.university_id = uni.university_id
ORDER BY p.created_at DESC;
```

---

## Security Considerations

1. **Permission Checks**: Always verify user is an active admin before allowing context switch
   ```php
   if (!can_act_as_university_ctr($user_id, $university_id)) {
       // Deny access
   }
   ```

2. **Audit Trail**: The `original_user_id` session variable tracks which admin is acting as the university

3. **Validation**: All actions check `is_acting_as_university_ctr()` before creating university content

4. **Database Constraints**: The CHECK constraint ensures data integrity - posts can't have both user_id and university_id

---

## Troubleshooting

### Context Switcher Doesn't Appear
- **Cause**: User is not a university admin
- **Solution**: Check `UniversityAdmins` table, ensure user has `status='active'`

### Can't Create Post as University
- **Cause**: Session variables not set correctly
- **Solution**: Check `$_SESSION['acting_as_university']` and `$_SESSION['active_university_id']`

### Database Constraint Error
- **Cause**: Trying to set both `user_id` and `university_id`
- **Solution**: Ensure your INSERT only sets one based on context

### Context Not Persisting
- **Cause**: Session is being reset
- **Solution**: Ensure `session_start()` is called at the top of every page

---

## API Reference

### Switch Context Endpoint

**URL**: `actions/switch_university_context_action.php`

#### Get Current Context
```javascript
fetch('../actions/switch_university_context_action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=get_current_context'
})
```

**Response:**
```json
{
    "status": "success",
    "context": {
        "acting_as_university": true,
        "active_university_id": 1,
        "active_university_name": "Test University",
        "user_id": 5,
        "user_name": "John Doe"
    }
}
```

#### Switch to University
```javascript
fetch('../actions/switch_university_context_action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=switch_to_university&university_id=1'
})
```

#### Switch to Personal
```javascript
fetch('../actions/switch_university_context_action.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=switch_to_personal'
})
```

---

## Future Enhancements

1. **Multiple University Management**: Allow switching between multiple universities if user is admin of several
2. **Role-Based Actions**: Different permissions for 'admin' vs 'superadmin' roles
3. **Activity Log**: Track all actions performed as university for auditing
4. **Delegation**: Allow university admins to delegate specific permissions
5. **Context Expiration**: Auto-switch back to personal after inactivity

---

## Summary

✅ **What's Implemented:**
- Session-based context switching
- Permission validation
- UI component for easy switching
- Post creation supports both contexts
- Database schema supports dual ownership

❌ **What You Need to Do:**
1. Include `context_switcher.php` in your navigation
2. Ensure university admins are properly set in `UniversityAdmins` table
3. Update any additional content creation (comments, events, etc.) to respect context
4. Test thoroughly with the provided workflow

---

**Last Updated**: December 7, 2025
**Files Modified**:
- `controllers/user_controller.php`
- `controllers/general_controller.php`
- `classes/general_class.php`
- `actions/switch_university_context_action.php` (new)
- `actions/create_post_action.php`
- `view/context_switcher.php` (new)

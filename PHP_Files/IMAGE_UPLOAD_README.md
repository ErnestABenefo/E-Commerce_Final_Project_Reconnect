# Image Upload Feature Implementation

## Overview
This update adds the ability for users and universities to upload images with their posts. Posts can now contain text, images, or both.

## Database Changes Required

**IMPORTANT**: You must run the SQL migration before using the image upload feature.

### Option 1: Run Migration File
```sql
-- Navigate to phpMyAdmin or your MySQL client
-- Select your database (reconnectdb or reconnectdb2)
-- Import or run: migrations/add_image_upload_features.sql
```

### Option 2: Run SQL Manually
```sql
-- Add image_url column to Posts table
ALTER TABLE Posts ADD COLUMN image_url VARCHAR(500) NULL AFTER content;

-- Create PostLikes table (for likes feature)
CREATE TABLE IF NOT EXISTS PostLikes (
  like_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (like_id),
  UNIQUE KEY unique_post_like (post_id, user_id),
  INDEX idx_likes_post (post_id),
  INDEX idx_likes_user (user_id),
  FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## File System Permissions

The upload directory will be created automatically, but ensure your web server has write permissions:

```bash
# If needed, set permissions (adjust path to your installation)
chmod 755 uploads/
chmod 755 uploads/posts/
```

## Implementation Details

### Backend Changes
1. **create_post_action.php**
   - Validates uploaded images (JPEG, PNG, GIF, WebP only)
   - Creates unique filenames: `post_timestamp_uniqid.ext`
   - Stores images in: `uploads/posts/`
   - Maximum file size: 5MB (PHP default)
   - Validates: content OR image required (not both empty)

2. **Dashboard Queries**
   - Updated to fetch `image_url` column
   - Context-filtered queries (user vs university posts)
   - Includes like/comment counts

### Frontend Changes
1. **Create Post Modal** (dashboard.php)
   - Added file input with image icon
   - Image preview before upload
   - Remove image button
   - Accepts: .jpg, .jpeg, .png, .gif, .webp

2. **Post Display**
   - Shows images with max-width: 100%
   - Click to open full-size in new tab
   - Rounded corners, responsive design

3. **JavaScript**
   - Uses FormData for multipart/form-data upload
   - Image preview with FileReader API
   - Enhanced post rendering with image support

## Testing

1. **Run SQL migration** (see above)
2. **Test user post with image**:
   - Login as regular user
   - Create post with image only
   - Create post with text + image
   - Verify image displays correctly
3. **Test university post with image**:
   - Switch to university context
   - Create post with image
   - Verify creator_type is 'university'
4. **Test error handling**:
   - Try uploading non-image file (should fail)
   - Try creating empty post (should fail)
   - Verify error messages display

## Security Features

- File type validation (whitelist: JPEG, PNG, GIF, WebP)
- Unique filename generation prevents overwrite attacks
- MIME type checking
- File size limits (PHP max_upload_filesize)
- XSS protection: htmlspecialchars() on output
- SQL injection protection: prepared statements

## File Upload Limits

Default PHP limits (adjust in php.ini if needed):
```ini
upload_max_filesize = 5M
post_max_size = 8M
max_file_uploads = 20
```

## Troubleshooting

### Images not uploading
1. Check file permissions on uploads/posts/ directory
2. Verify PHP upload limits in php.ini
3. Check browser console for JavaScript errors
4. Verify database has image_url column

### Images not displaying
1. Check image_url in database (should be `../uploads/posts/filename.ext`)
2. Verify file exists in uploads/posts/ directory
3. Check browser console for 404 errors
4. Ensure relative path is correct from dashboard.php location

### "Failed to create post" error
1. Verify SQL migration was run
2. Check PHP error logs
3. Test file upload with small image (< 1MB)
4. Verify allowed file types

## Additional Features Included

### Like System
- Toggle likes on posts
- Real-time like count updates
- User-specific like state (red heart if liked)
- Prevents duplicate likes (database constraint)

### Comment System
- Add comments to posts
- View all comments
- Delete own comments
- Real-time comment count updates

## Future Enhancements

Possible improvements:
- Image compression/thumbnail generation
- Multiple images per post
- Image editing/cropping before upload
- Video upload support
- Drag-and-drop upload interface
- Progress bar for large uploads

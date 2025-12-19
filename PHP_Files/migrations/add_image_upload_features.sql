-- ==============================================
-- Migration: Add image upload and likes features
-- Date: 2024
-- Description: Adds image_url column to Posts and creates PostLikes table
-- ==============================================

-- Add image_url column to Posts table
ALTER TABLE Posts ADD COLUMN image_url VARCHAR(500) NULL AFTER content;

-- Create PostLikes table
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

-- Verify the changes
-- SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = 'reconnectdb' AND TABLE_NAME = 'Posts' AND COLUMN_NAME = 'image_url';

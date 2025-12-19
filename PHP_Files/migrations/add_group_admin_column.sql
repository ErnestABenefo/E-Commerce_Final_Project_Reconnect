-- Migration: Add is_admin column to GroupMembers table
-- Date: 2025-12-13
-- Description: Adds ability for groups to have multiple admins

-- Add is_admin column if it doesn't exist
ALTER TABLE GroupMembers 
ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER user_id;

-- Add index for faster admin queries
ALTER TABLE GroupMembers 
ADD INDEX IF NOT EXISTS idx_group_members_admin (group_id, is_admin);

-- Make group owners admins by default
UPDATE GroupMembers gm
JOIN `Groups` g ON gm.group_id = g.group_id
SET gm.is_admin = 1
WHERE gm.user_id = g.owner_user_id;

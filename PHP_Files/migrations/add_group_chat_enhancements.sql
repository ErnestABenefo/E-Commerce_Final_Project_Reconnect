-- ==============================================
-- Migration: Enhance Groups for University and Year Group Auto-creation
-- ==============================================

-- Add role column to GroupMembers for admin/member distinction
ALTER TABLE GroupMembers 
ADD COLUMN role ENUM('admin', 'member') NOT NULL DEFAULT 'member' AFTER user_id;

-- Add is_auto_generated flag to Groups to mark system-created groups
ALTER TABLE `Groups` 
ADD COLUMN is_auto_generated BOOLEAN DEFAULT FALSE AFTER group_type;

-- Create index on graduation year for efficient year group queries
CREATE INDEX idx_groups_graduation_year ON `Groups`(group_type, university_id) 
WHERE group_type IN ('university', 'year_group');

-- Ensure GroupChats has one-to-one relationship with Groups
-- (Each group should have exactly one chat)
ALTER TABLE GroupChats
ADD UNIQUE KEY unique_group_chat (group_id);

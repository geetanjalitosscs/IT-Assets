-- Update branches table to remove AUTO_INCREMENT and allow manual ID assignment
-- This will enable ID reuse when branches are deleted

-- First, let's check the current structure
-- ALTER TABLE branches MODIFY COLUMN id INT NOT NULL;

-- Remove AUTO_INCREMENT from the id column
ALTER TABLE branches MODIFY COLUMN id INT NOT NULL;

-- Note: After running this script, the branches table will no longer auto-increment
-- New branches will use the lowest available ID (gaps will be filled first)
-- If no gaps exist, the next sequential ID will be used

-- To verify the change, you can run:
-- SHOW CREATE TABLE branches;

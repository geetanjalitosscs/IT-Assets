-- Update login credentials for IT Asset Management System
-- Run this script to update the user credentials

-- First, let's clear existing users (optional - comment out if you want to keep existing users)
-- DELETE FROM users;

-- Insert Super Admin user
-- Username: Super Admin, Password: admin@123
INSERT IGNORE INTO users (username, password, role, full_name, email) VALUES
('Super Admin', '$2y$12$a1PWYqOQuHy4owRCJXk4muo6Hlx7DomuTwA4BHvIztMOvnH0Qvh46', 'super_admin', 'Super Administrator', 'superadmin@company.com');

-- Insert Admin user (Branch Admin role)
-- Username: Admin, Password: admin@123
INSERT IGNORE INTO users (username, password, role, branch_id, full_name, email) VALUES
('Admin', '$2y$12$a1PWYqOQuHy4owRCJXk4muo6Hlx7DomuTwA4BHvIztMOvnH0Qvh46', 'branch_admin', 1, 'Administrator', 'admin@company.com');

-- Display completion message
SELECT 'User credentials updated successfully!' as Status;
SELECT 'New login credentials:' as Info;
SELECT 'Super Admin: Super Admin / admin@123' as SuperAdmin;
SELECT 'Admin: Admin / admin@123' as Admin;

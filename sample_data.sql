-- Sample data for IT Asset Management System
-- Run this after the system is set up to populate with sample data

-- Insert sample branches
INSERT INTO branches (name, location) VALUES
('Head Office', 'New York, NY'),
('West Coast Branch', 'Los Angeles, CA'),
('East Coast Branch', 'Boston, MA'),
('Central Branch', 'Chicago, IL');

-- Insert sample employees
INSERT INTO employees (employee_id, full_name, email, phone, department, position, branch_id) VALUES
('EMP001', 'John Smith', 'john.smith@company.com', '555-0101', 'IT', 'System Administrator', 1),
('EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', '555-0102', 'HR', 'HR Manager', 1),
('EMP003', 'Mike Wilson', 'mike.wilson@company.com', '555-0103', 'Finance', 'Accountant', 2),
('EMP004', 'Lisa Brown', 'lisa.brown@company.com', '555-0104', 'Marketing', 'Marketing Manager', 2),
('EMP005', 'David Lee', 'david.lee@company.com', '555-0105', 'IT', 'Developer', 3),
('EMP006', 'Emma Davis', 'emma.davis@company.com', '555-0106', 'Sales', 'Sales Representative', 3),
('EMP007', 'Tom Anderson', 'tom.anderson@company.com', '555-0107', 'IT', 'Network Engineer', 4),
('EMP008', 'Anna Taylor', 'anna.taylor@company.com', '555-0108', 'Operations', 'Operations Manager', 4);

-- Insert sample systems
INSERT INTO systems (system_code, branch_id, type, cpu, ram, storage, os, status, assigned_to, assigned_date) VALUES
('C1', 1, 'Desktop', 'Intel i7-10700K', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Assigned', 1, '2024-01-15'),
('C2', 1, 'Laptop', 'Intel i5-1135G7', '8GB DDR4', '256GB SSD', 'Windows 11 Home', 'Assigned', 2, '2024-01-20'),
('C3', 1, 'Server', 'Intel Xeon E5-2620', '32GB DDR4', '1TB SSD', 'Ubuntu Server 22.04', 'Unassigned', NULL, NULL),
('C4', 2, 'Desktop', 'AMD Ryzen 7 5800X', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Assigned', 3, '2024-02-01'),
('C5', 2, 'Laptop', 'Intel i7-1165G7', '16GB DDR4', '512GB SSD', 'macOS Monterey', 'Assigned', 4, '2024-02-05'),
('C6', 3, 'Desktop', 'Intel i5-10400', '8GB DDR4', '256GB SSD', 'Windows 11 Pro', 'Assigned', 5, '2024-02-10'),
('C7', 3, 'Laptop', 'AMD Ryzen 5 5600U', '8GB DDR4', '256GB SSD', 'Windows 11 Home', 'In Repair', NULL, NULL),
('C8', 4, 'Desktop', 'Intel i7-11700K', '32GB DDR4', '1TB SSD', 'Windows 11 Pro', 'Assigned', 7, '2024-02-15'),
('C9', 4, 'Server', 'AMD EPYC 7302P', '64GB DDR4', '2TB SSD', 'CentOS 8', 'Unassigned', NULL, NULL);

-- Insert sample peripherals
INSERT INTO peripherals (name, type, brand, model, serial_number, system_id, status) VALUES
('Office Keyboard', 'Keyboard', 'Logitech', 'K380', 'KB001', 1, 'Assigned'),
('Wireless Mouse', 'Mouse', 'Logitech', 'MX Master 3', 'MS001', 1, 'Assigned'),
('24" Monitor', 'Monitor', 'Dell', 'P2422H', 'MN001', 1, 'Assigned'),
('Office Keyboard', 'Keyboard', 'Microsoft', 'Surface Keyboard', 'KB002', 2, 'Assigned'),
('Wireless Mouse', 'Mouse', 'Microsoft', 'Surface Mouse', 'MS002', 2, 'Assigned'),
('Laser Printer', 'Printer', 'HP', 'LaserJet Pro', 'PR001', NULL, 'Available'),
('Scanner', 'Scanner', 'Canon', 'Canoscan Lide', 'SC001', NULL, 'Available'),
('Gaming Keyboard', 'Keyboard', 'Corsair', 'K95 RGB', 'KB003', 4, 'Assigned'),
('Gaming Mouse', 'Mouse', 'Razer', 'DeathAdder V2', 'MS003', 4, 'Assigned'),
('27" Monitor', 'Monitor', 'ASUS', 'VG27AQ', 'MN002', 4, 'Assigned'),
('MacBook Charger', 'Other', 'Apple', 'USB-C Power Adapter', 'CH001', 5, 'Assigned'),
('External Hard Drive', 'Other', 'Seagate', 'Expansion 1TB', 'HD001', NULL, 'Available');

-- Insert sample system history
INSERT INTO system_history (system_id, employee_id, assigned_date, returned_date, notes) VALUES
(1, 1, '2024-01-15', NULL, 'Initial assignment'),
(2, 2, '2024-01-20', NULL, 'Initial assignment'),
(4, 3, '2024-02-01', NULL, 'Initial assignment'),
(5, 4, '2024-02-05', NULL, 'Initial assignment'),
(6, 5, '2024-02-10', NULL, 'Initial assignment'),
(8, 7, '2024-02-15', NULL, 'Initial assignment'),
(7, 6, '2024-01-25', '2024-02-08', 'System returned for repair'),
(3, 1, '2023-12-01', '2024-01-10', 'Previous assignment');

-- Insert sample branch admin users
INSERT INTO users (username, password, role, branch_id, full_name, email) VALUES
('branch_admin_ny', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 1, 'Branch Admin NY', 'admin.ny@company.com'),
('branch_admin_la', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 2, 'Branch Admin LA', 'admin.la@company.com'),
('branch_admin_boston', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 3, 'Branch Admin Boston', 'admin.boston@company.com'),
('branch_admin_chicago', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 4, 'Branch Admin Chicago', 'admin.chicago@company.com');

-- Note: The password hash above is for 'password' - change these in production!

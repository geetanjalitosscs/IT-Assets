-- IT Asset Management System Database Setup
-- Run this script in your MySQL database to set up the complete system

-- Create database
CREATE DATABASE IF NOT EXISTS it_asset_management;
USE it_asset_management;

-- Create branches table
CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'branch_admin') NOT NULL,
    branch_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- Create employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50),
    position VARCHAR(50),
    branch_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

-- Create systems table
CREATE TABLE IF NOT EXISTS systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_code VARCHAR(20) NOT NULL,
    branch_id INT NOT NULL,
    type ENUM('Laptop', 'Desktop', 'Server') NOT NULL,
    cpu VARCHAR(100),
    ram VARCHAR(50),
    storage VARCHAR(100),
    os VARCHAR(50),
    status ENUM('Assigned', 'Unassigned', 'In Repair') DEFAULT 'Unassigned',
    assigned_to INT NULL,
    assigned_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE SET NULL
);

-- Create peripherals table
CREATE TABLE IF NOT EXISTS peripherals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Keyboard', 'Mouse', 'Monitor', 'Printer', 'Scanner', 'Other') NOT NULL,
    brand VARCHAR(50),
    model VARCHAR(50),
    serial_number VARCHAR(100),
    system_id INT NULL,
    status ENUM('Available', 'Assigned', 'In Repair') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE SET NULL
);

-- Create system_history table
CREATE TABLE IF NOT EXISTS system_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    employee_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    returned_date DATE NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert default super admin user
-- Password: admin123 (hashed using PHP password_hash function)
INSERT IGNORE INTO users (username, password, role, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Super Administrator', 'admin@company.com');

-- Insert sample branches
INSERT IGNORE INTO branches (name, location) VALUES
('Head Office', 'New York, NY'),
('West Coast Branch', 'Los Angeles, CA'),
('East Coast Branch', 'Boston, MA'),
('Central Branch', 'Chicago, IL'),
('Southern Branch', 'Houston, TX'),
('Northwest Branch', 'Seattle, WA'),
('Southeast Branch', 'Miami, FL'),
('Midwest Branch', 'Detroit, MI');

-- Insert sample employees
INSERT IGNORE INTO employees (employee_id, full_name, email, phone, department, position, branch_id) VALUES
('EMP001', 'John Smith', 'john.smith@company.com', '555-0101', 'IT', 'System Administrator', 1),
('EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', '555-0102', 'HR', 'HR Manager', 1),
('EMP003', 'Mike Wilson', 'mike.wilson@company.com', '555-0103', 'Finance', 'Accountant', 2),
('EMP004', 'Lisa Brown', 'lisa.brown@company.com', '555-0104', 'Marketing', 'Marketing Manager', 2),
('EMP005', 'David Lee', 'david.lee@company.com', '555-0105', 'IT', 'Developer', 3),
('EMP006', 'Emma Davis', 'emma.davis@company.com', '555-0106', 'Sales', 'Sales Representative', 3),
('EMP007', 'Tom Anderson', 'tom.anderson@company.com', '555-0107', 'IT', 'Network Engineer', 4),
('EMP008', 'Anna Taylor', 'anna.taylor@company.com', '555-0108', 'Operations', 'Operations Manager', 4),
('EMP009', 'Robert Garcia', 'robert.garcia@company.com', '555-0109', 'IT', 'Database Administrator', 5),
('EMP010', 'Jennifer Martinez', 'jennifer.martinez@company.com', '555-0110', 'Finance', 'Financial Analyst', 5),
('EMP011', 'Michael Thompson', 'michael.thompson@company.com', '555-0111', 'Sales', 'Sales Manager', 6),
('EMP012', 'Lisa Rodriguez', 'lisa.rodriguez@company.com', '555-0112', 'HR', 'HR Specialist', 6),
('EMP013', 'James Wilson', 'james.wilson@company.com', '555-0113', 'IT', 'Software Engineer', 7),
('EMP014', 'Maria Garcia', 'maria.garcia@company.com', '555-0114', 'Marketing', 'Digital Marketing Specialist', 7),
('EMP015', 'Christopher Lee', 'christopher.lee@company.com', '555-0115', 'Operations', 'Operations Analyst', 8),
('EMP016', 'Amanda White', 'amanda.white@company.com', '555-0116', 'Finance', 'Budget Analyst', 8),
('EMP017', 'Daniel Brown', 'daniel.brown@company.com', '555-0117', 'IT', 'DevOps Engineer', 1),
('EMP018', 'Jessica Davis', 'jessica.davis@company.com', '555-0118', 'Sales', 'Account Executive', 2),
('EMP019', 'Kevin Miller', 'kevin.miller@company.com', '555-0119', 'IT', 'Security Analyst', 3),
('EMP020', 'Rachel Taylor', 'rachel.taylor@company.com', '555-0120', 'HR', 'Recruitment Specialist', 4);

-- Insert sample systems
INSERT IGNORE INTO systems (system_code, branch_id, type, cpu, ram, storage, os, status, assigned_to, assigned_date) VALUES
('C1', 1, 'Desktop', 'Intel i7-10700K', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Assigned', 1, '2024-01-15'),
('C2', 1, 'Laptop', 'Intel i5-1135G7', '8GB DDR4', '256GB SSD', 'Windows 11 Home', 'Assigned', 2, '2024-01-20'),
('C3', 1, 'Server', 'Intel Xeon E5-2620', '32GB DDR4', '1TB SSD', 'Ubuntu Server 22.04', 'Unassigned', NULL, NULL),
('C4', 2, 'Desktop', 'AMD Ryzen 7 5800X', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Assigned', 3, '2024-02-01'),
('C5', 2, 'Laptop', 'Intel i7-1165G7', '16GB DDR4', '512GB SSD', 'macOS Monterey', 'Assigned', 4, '2024-02-05'),
('C6', 3, 'Desktop', 'Intel i5-10400', '8GB DDR4', '256GB SSD', 'Windows 11 Pro', 'Assigned', 5, '2024-02-10'),
('C7', 3, 'Laptop', 'AMD Ryzen 5 5600U', '8GB DDR4', '256GB SSD', 'Windows 11 Home', 'In Repair', NULL, NULL),
('C8', 4, 'Desktop', 'Intel i7-11700K', '32GB DDR4', '1TB SSD', 'Windows 11 Pro', 'Assigned', 7, '2024-02-15'),
('C9', 4, 'Server', 'AMD EPYC 7302P', '64GB DDR4', '2TB SSD', 'CentOS 8', 'Unassigned', NULL, NULL),
('C10', 5, 'Desktop', 'Intel i9-12900K', '32GB DDR5', '1TB NVMe SSD', 'Windows 11 Pro', 'Assigned', 9, '2024-02-20'),
('C11', 5, 'Laptop', 'AMD Ryzen 7 6800H', '16GB DDR5', '512GB SSD', 'Windows 11 Pro', 'Assigned', 10, '2024-02-22'),
('C12', 6, 'Desktop', 'Intel i7-12700K', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Unassigned', NULL, NULL),
('C13', 6, 'Laptop', 'Apple M2 Pro', '16GB Unified Memory', '512GB SSD', 'macOS Ventura', 'Assigned', 11, '2024-02-25'),
('C14', 7, 'Desktop', 'AMD Ryzen 9 5900X', '32GB DDR4', '1TB SSD', 'Ubuntu 22.04 LTS', 'Assigned', 13, '2024-02-28'),
('C15', 7, 'Laptop', 'Intel i7-11800H', '16GB DDR4', '256GB SSD', 'Windows 11 Home', 'In Repair', NULL, NULL),
('C16', 8, 'Desktop', 'Intel i5-12400', '8GB DDR4', '256GB SSD', 'Windows 11 Pro', 'Assigned', 15, '2024-03-01'),
('C17', 8, 'Server', 'AMD EPYC 7763', '128GB DDR4', '4TB NVMe SSD', 'Ubuntu Server 22.04', 'Unassigned', NULL, NULL),
('C18', 1, 'Laptop', 'Intel i7-1260P', '16GB DDR4', '512GB SSD', 'Windows 11 Pro', 'Assigned', 17, '2024-03-05'),
('C19', 2, 'Desktop', 'AMD Ryzen 5 5600G', '16GB DDR4', '256GB SSD', 'Windows 11 Pro', 'Assigned', 18, '2024-03-08'),
('C20', 3, 'Laptop', 'Intel i5-1235U', '8GB DDR4', '256GB SSD', 'Windows 11 Home', 'Unassigned', NULL, NULL),
('C21', 4, 'Desktop', 'Intel i7-13700K', '32GB DDR5', '1TB NVMe SSD', 'Windows 11 Pro', 'Assigned', 20, '2024-03-10'),
('C22', 5, 'Laptop', 'Apple M1 Pro', '16GB Unified Memory', '512GB SSD', 'macOS Monterey', 'In Repair', NULL, NULL),
('C23', 6, 'Desktop', 'AMD Ryzen 7 7700X', '32GB DDR5', '1TB NVMe SSD', 'Windows 11 Pro', 'Unassigned', NULL, NULL),
('C24', 7, 'Laptop', 'Intel i7-12800H', '32GB DDR5', '1TB SSD', 'Windows 11 Pro', 'Assigned', 14, '2024-03-12'),
('C25', 8, 'Desktop', 'Intel i9-13900K', '64GB DDR5', '2TB NVMe SSD', 'Windows 11 Pro', 'Unassigned', NULL, NULL);

-- Insert sample peripherals
INSERT IGNORE INTO peripherals (name, type, brand, model, serial_number, system_id, status) VALUES
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
('External Hard Drive', 'Other', 'Seagate', 'Expansion 1TB', 'HD001', NULL, 'Available'),
('Mechanical Keyboard', 'Keyboard', 'Keychron', 'K2', 'KB004', 6, 'Assigned'),
('Wireless Mouse', 'Mouse', 'Logitech', 'G Pro X', 'MS004', 6, 'Assigned'),
('32" Monitor', 'Monitor', 'LG', '32UN880-B', 'MN003', 6, 'Assigned'),
('Bluetooth Headset', 'Other', 'Sony', 'WH-1000XM4', 'HS001', 7, 'Assigned'),
('Webcam', 'Other', 'Logitech', 'C920', 'WC001', 8, 'Assigned'),
('USB Hub', 'Other', 'Anker', '7-Port Hub', 'UH001', 8, 'Assigned'),
('Ergonomic Keyboard', 'Keyboard', 'Microsoft', 'Sculpt Ergonomic', 'KB005', 10, 'Assigned'),
('Trackball Mouse', 'Mouse', 'Logitech', 'MX Ergo', 'MS005', 10, 'Assigned'),
('Dual Monitor Setup', 'Monitor', 'Dell', 'U2720Q', 'MN004', 10, 'Assigned'),
('Wireless Charger', 'Other', 'Belkin', 'Boost Up', 'WC002', 11, 'Assigned'),
('Mechanical Keyboard', 'Keyboard', 'Ducky', 'One 2 Mini', 'KB006', 13, 'Assigned'),
('Gaming Mouse', 'Mouse', 'SteelSeries', 'Rival 600', 'MS006', 13, 'Assigned'),
('Ultrawide Monitor', 'Monitor', 'Samsung', 'CRG9', 'MN005', 13, 'Assigned'),
('Standing Desk Converter', 'Other', 'FlexiSpot', 'E7', 'SD001', 14, 'Assigned'),
('Noise Cancelling Headphones', 'Other', 'Bose', 'QuietComfort 45', 'HS002', 16, 'Assigned'),
('Document Scanner', 'Scanner', 'Epson', 'WorkForce ES-400', 'SC002', NULL, 'Available'),
('Color Laser Printer', 'Printer', 'Brother', 'HL-L3270CDW', 'PR002', NULL, 'Available'),
('Wireless Keyboard', 'Keyboard', 'Apple', 'Magic Keyboard', 'KB007', 18, 'Assigned'),
('Magic Mouse', 'Mouse', 'Apple', 'Magic Mouse 2', 'MS007', 18, 'Assigned'),
('Studio Display', 'Monitor', 'Apple', '27" Studio Display', 'MN006', 18, 'Assigned'),
('Thunderbolt Dock', 'Other', 'CalDigit', 'TS3 Plus', 'TD001', 19, 'Assigned'),
('Mechanical Keyboard', 'Keyboard', 'Das Keyboard', '4 Professional', 'KB008', 21, 'Assigned'),
('Precision Mouse', 'Mouse', 'Microsoft', 'Precision Mouse', 'MS008', 21, 'Assigned'),
('4K Monitor', 'Monitor', 'BenQ', 'PD3200U', 'MN007', 21, 'Assigned'),
('USB-C Hub', 'Other', 'OWC', 'Thunderbolt 3 Hub', 'UH002', 24, 'Assigned'),
('Wireless Charging Pad', 'Other', 'Anker', 'PowerWave', 'WC003', NULL, 'Available'),
('Bluetooth Speaker', 'Other', 'JBL', 'Charge 4', 'BS001', NULL, 'Available'),
('Network Switch', 'Other', 'Netgear', 'GS108T', 'NS001', NULL, 'Available'),
('UPS Battery Backup', 'Other', 'APC', 'Back-UPS Pro', 'UPS001', NULL, 'Available');

-- Insert sample system history
INSERT IGNORE INTO system_history (system_id, employee_id, assigned_date, returned_date, notes) VALUES
(1, 1, '2024-01-15', NULL, 'Initial assignment'),
(2, 2, '2024-01-20', NULL, 'Initial assignment'),
(4, 3, '2024-02-01', NULL, 'Initial assignment'),
(5, 4, '2024-02-05', NULL, 'Initial assignment'),
(6, 5, '2024-02-10', NULL, 'Initial assignment'),
(8, 7, '2024-02-15', NULL, 'Initial assignment'),
(7, 6, '2024-01-25', '2024-02-08', 'System returned for repair'),
(3, 1, '2023-12-01', '2024-01-10', 'Previous assignment');

-- Insert sample branch admin users
-- Password: password (hashed using PHP password_hash function)
INSERT IGNORE INTO users (username, password, role, branch_id, full_name, email) VALUES
('branch_admin_ny', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 1, 'Branch Admin NY', 'admin.ny@company.com'),
('branch_admin_la', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 2, 'Branch Admin LA', 'admin.la@company.com'),
('branch_admin_boston', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 3, 'Branch Admin Boston', 'admin.boston@company.com'),
('branch_admin_chicago', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 4, 'Branch Admin Chicago', 'admin.chicago@company.com'),
('branch_admin_houston', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 5, 'Branch Admin Houston', 'admin.houston@company.com'),
('branch_admin_seattle', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 6, 'Branch Admin Seattle', 'admin.seattle@company.com'),
('branch_admin_miami', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 7, 'Branch Admin Miami', 'admin.miami@company.com'),
('branch_admin_detroit', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'branch_admin', 8, 'Branch Admin Detroit', 'admin.detroit@company.com');

-- Display completion message
SELECT 'Database setup completed successfully!' as Status;
SELECT 'Default login credentials:' as Info;
SELECT 'Super Admin - Username: admin, Password: admin123' as SuperAdmin;
SELECT 'Branch Admins - Username: branch_admin_[location], Password: password' as BranchAdmins;

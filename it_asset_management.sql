-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 23, 2025 at 08:56 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it_asset_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `activity_type` enum('system_add','system_edit','system_delete','system_assign','employee_add','employee_edit','employee_delete','user_add','user_edit','user_delete','branch_add','branch_edit','branch_delete') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `activity_type`, `entity_id`, `entity_name`, `description`, `branch_id`, `created_at`) VALUES
(1, 'system_add', 10, 'C9', 'New system C9 added and assigned', 2, '2025-09-22 07:04:25'),
(2, 'system_add', 11, 'C9', 'New system C9 added and assigned', 2, '2025-09-22 07:04:34'),
(3, 'system_delete', 11, 'C9', 'System C9 deleted', 2, '2025-09-22 07:10:34'),
(4, 'system_delete', 10, 'C9', 'System C9 deleted', 2, '2025-09-22 07:13:15'),
(5, 'system_add', 9, 'C9', 'New system C9 added', 3, '2025-09-22 07:13:51'),
(6, 'system_add', 10, 'C9', 'New system C9 added', 3, '2025-09-22 07:13:55'),
(7, 'system_add', 11, 'C9', 'New system C9 added', 3, '2025-09-22 07:13:59'),
(8, 'system_add', 10, 'C9', 'New system C9 added', 3, '2025-09-22 07:18:54'),
(9, 'branch_add', 5, 'dgfdg', 'New branch dgfdg (jbp) added', NULL, '2025-09-22 07:24:02'),
(10, 'branch_delete', 5, 'dgfdg', 'Branch dgfdg (jbp) deleted', NULL, '2025-09-22 07:24:06'),
(11, 'user_add', 3, 'Toss_API', 'New user fjgj (Toss_API) added as branch_admin', 4, '2025-09-22 07:24:59'),
(12, 'user_delete', 3, 'Toss_API', 'User fjgj (Toss_API) deleted', 4, '2025-09-22 07:25:02'),
(13, 'employee_add', 5, 'EMP68', 'New employee Geeta (EMP68) added', 4, '2025-09-22 07:25:16'),
(14, 'employee_delete', 5, 'EMP68', 'Employee Geeta (EMP68) deleted', 4, '2025-09-22 07:25:19'),
(15, 'system_add', 11, 'C11', 'New system C11 added', 2, '2025-09-22 07:25:38'),
(16, 'system_delete', 11, 'C11', 'System C11 deleted', 2, '2025-09-22 07:25:43'),
(17, 'system_delete', 9, 'C9', 'System C9 deleted', 3, '2025-09-22 07:26:56'),
(18, 'system_delete', 8, 'C9', 'System C9 deleted', 3, '2025-09-22 07:26:59'),
(19, 'system_add', 8, 'C9', 'New system C9 added', 3, '2025-09-22 07:27:20'),
(20, 'system_add', 9, 'C8', 'New system C8 added', 4, '2025-09-22 07:27:37'),
(21, 'system_add', 10, 'C9', 'New system C9 added', 4, '2025-09-22 07:27:46'),
(22, 'system_delete', 10, 'C9', 'System C9 deleted', 4, '2025-09-22 07:28:15'),
(23, 'system_delete', 8, 'C9', 'System C9 deleted', 3, '2025-09-22 07:28:18'),
(24, 'system_delete', 8, 'C8', 'System C8 deleted', 4, '2025-09-22 07:28:21'),
(25, 'system_delete', 7, 'C7', 'System C7 deleted', 4, '2025-09-22 08:05:09'),
(26, 'branch_add', 5, 'Toss Solution', 'New branch Toss Solution (Jabalpur) added', NULL, '2025-09-22 09:42:41'),
(27, 'employee_add', 5, 'EMP006', 'New employee Siddharth (EMP006) added', 5, '2025-09-22 09:44:07'),
(28, 'system_assign', 1, 'C1', 'System C1 assigned to Siddharth', 1, '2025-09-22 09:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `location`, `created_at`) VALUES
(1, 'Head Office', 'New York, NY', '2025-09-20 05:26:53'),
(2, 'West Coast Branch', 'Los Angeles, CA', '2025-09-20 05:26:53'),
(3, 'East Coast Branch', 'Boston, MA', '2025-09-20 05:26:53'),
(4, 'Central Branch', 'Chicago, IL', '2025-09-20 05:26:53'),
(5, 'Toss Solution', 'Jabalpur', '2025-09-22 09:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `branch_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `email`, `phone`, `department`, `position`, `branch_id`, `created_at`) VALUES
(1, 'EMP001', 'John Smith', 'john.smith@company.com', '555-0101', 'IT', 'System Administrator', 1, '2025-09-20 05:27:53'),
(2, 'EMP002', 'Sarah Johnson', 'sarah.johnson@company.com', '555-0102', 'HR', 'HR Manager', 1, '2025-09-20 05:27:53'),
(3, 'EMP003', 'Mike Wilson', 'mike.wilson@company.com', '555-0103', 'Finance', 'Accountant', 2, '2025-09-20 05:27:53'),
(4, 'EMP004', 'Lisa Brown', 'lisa.brown@company.com', '555-0104', 'Marketing', 'Marketing Manager', 2, '2025-09-20 05:27:53'),
(5, 'EMP006', 'Siddharth', 'shreyash.toss.cs@gmail.com', '7654891234', 'IT', 'Intern', 5, '2025-09-22 09:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `peripherals`
--

CREATE TABLE `peripherals` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Keyboard','Mouse','Monitor','Printer','Scanner','Other') NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `system_id` int(11) DEFAULT NULL,
  `status` enum('Available','Assigned','In Repair') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `systems`
--

CREATE TABLE `systems` (
  `id` int(11) NOT NULL,
  `system_code` varchar(20) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `type` enum('Laptop','Desktop','Server') NOT NULL,
  `cpu` varchar(100) DEFAULT NULL,
  `ram` varchar(50) DEFAULT NULL,
  `storage` varchar(100) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `peripherals` text DEFAULT NULL,
  `status` enum('Assigned','Unassigned','In Repair') DEFAULT 'Unassigned',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `systems`
--

INSERT INTO `systems` (`id`, `system_code`, `branch_id`, `type`, `cpu`, `ram`, `storage`, `os`, `peripherals`, `status`, `assigned_to`, `assigned_date`, `created_at`) VALUES
(1, 'C1', 1, 'Desktop', '', '', '512GB SSD', '', '', 'Assigned', 5, '2025-09-22', '2025-09-20 05:28:14'),
(2, 'C2', 1, 'Laptop', 'Intel i5-1135G7', '8GB DDR4', '256GB SSD', 'Windows 11 Home', '', 'Assigned', 2, '2024-01-20', '2025-09-20 05:28:14'),
(3, 'C3', 1, 'Server', 'Intel Xeon E5-2620', '32GB DDR4', '1TB SSD', 'Ubuntu Server 22.04', '', 'Assigned', 3, '2025-09-20', '2025-09-20 05:28:14'),
(4, 'C4', 2, 'Desktop', '', '', '512GB SSD', '', 'Mouse', 'Unassigned', NULL, NULL, '2025-09-20 05:28:14'),
(5, 'C5', 3, 'Laptop', 'AMD Ryzen 7', '64GB', '500GB HDD', 'CentOS 7', 'Keyboard,Mouse,Monitor,Printer,Scanner,Webcam,Speakers,Headset', 'Assigned', 3, '2025-09-20', '2025-09-20 08:48:06'),
(6, 'C6', 3, 'Laptop', 'AMD Ryzen 7', '16GB', '2TB SSD', 'Ubuntu 20.04 LTS', 'Speakers (1), Monitor (7)', 'Assigned', 4, '2025-09-20', '2025-09-20 09:32:40');

-- --------------------------------------------------------

--
-- Table structure for table `system_history`
--

CREATE TABLE `system_history` (
  `id` int(11) NOT NULL,
  `system_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `returned_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_history`
--

INSERT INTO `system_history` (`id`, `system_id`, `employee_id`, `assigned_date`, `returned_date`, `notes`, `created_at`) VALUES
(1, 1, 4, '2025-09-20', '2025-09-20', NULL, '2025-09-20 08:09:28'),
(2, 3, 3, '2025-09-20', NULL, NULL, '2025-09-20 08:15:54'),
(3, 3, 3, '2025-09-20', NULL, NULL, '2025-09-20 08:17:48'),
(4, 3, 3, '2025-09-20', NULL, NULL, '2025-09-20 08:19:03'),
(8, 5, 3, '2025-09-20', NULL, NULL, '2025-09-20 08:48:06'),
(13, 1, 5, '2025-09-22', NULL, NULL, '2025-09-22 09:44:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','branch_admin') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `branch_id`, `full_name`, `email`, `phone`, `created_at`) VALUES
(1, 'Super Admin', '$2y$12$a1PWYqOQuHy4owRCJXk4muo6Hlx7DomuTwA4BHvIztMOvnH0Qvh46', 'super_admin', NULL, 'Siddharth', 'superadmin@company.com', '7654891234', '2025-09-20 05:40:54'),
(2, 'Admin', '$2y$10$YCpOzW.MxBEHYO4jWlMLi.A0UUWkXKZMWAzL9D0FqYw9emFaiioxu', 'branch_admin', 1, 'Kritika', 'admin@company.com', '', '2025-09-20 05:40:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `peripherals`
--
ALTER TABLE `peripherals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`);

--
-- Indexes for table `systems`
--
ALTER TABLE `systems`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `system_history`
--
ALTER TABLE `system_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_id` (`system_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `branch_id` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `peripherals`
--
ALTER TABLE `peripherals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_history`
--
ALTER TABLE `system_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peripherals`
--
ALTER TABLE `peripherals`
  ADD CONSTRAINT `peripherals_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `systems`
--
ALTER TABLE `systems`
  ADD CONSTRAINT `systems_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `systems_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `system_history`
--
ALTER TABLE `system_history`
  ADD CONSTRAINT `system_history_ibfk_1` FOREIGN KEY (`system_id`) REFERENCES `systems` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `system_history_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

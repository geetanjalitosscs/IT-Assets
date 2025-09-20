<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_asset_management');

// Create connection with better caching control and retry logic
function getConnection() {
    $maxRetries = 3;
    $retryCount = 0;
    
    while ($retryCount < $maxRetries) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Test the connection
            $pdo->query("SELECT 1");
            return $pdo;
        } catch(PDOException $e) {
            $retryCount++;
            if ($retryCount >= $maxRetries) {
                error_log("Database connection failed after $maxRetries attempts: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
            sleep(1); // Wait 1 second before retry
        }
    }
}

// Force fresh connection for critical operations
function getFreshConnection() {
    $maxRetries = 3;
    $retryCount = 0;
    
    while ($retryCount < $maxRetries) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Test the connection
            $pdo->query("SELECT 1");
            return $pdo;
        } catch(PDOException $e) {
            $retryCount++;
            if ($retryCount >= $maxRetries) {
                error_log("Fresh database connection failed after $maxRetries attempts: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
            sleep(1); // Wait 1 second before retry
        }
    }
}

// Initialize database tables
function initializeDatabase() {
    $pdo = getConnection();
    
    // Create branches table
    $pdo->exec("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(200) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin', 'branch_admin') NOT NULL,
        branch_id INT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
    )");
    
    // Create employees table
    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
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
    )");
    
    // Create systems table
    $pdo->exec("CREATE TABLE IF NOT EXISTS systems (
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
    )");
    
    // Create peripherals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS peripherals (
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
    )");
    
    // Create system_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        system_id INT NOT NULL,
        employee_id INT NOT NULL,
        assigned_date DATE NOT NULL,
        returned_date DATE NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
    
    // Insert default super admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, 'super_admin', 'Super Administrator', 'admin@company.com')");
        $stmt->execute(['admin', $hashedPassword]);
    }
}

// Initialize database on first run
initializeDatabase();
?>

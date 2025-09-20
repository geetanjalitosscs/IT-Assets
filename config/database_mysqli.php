<?php
// Alternative database configuration using MySQLi
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'it_asset_management');

// Create connection using MySQLi
function getConnection() {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }
        
        return $connection;
    } catch(Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database tables using MySQLi
function initializeDatabase() {
    $conn = getConnection();
    
    // Create branches table
    $conn->query("CREATE TABLE IF NOT EXISTS branches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(200) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create users table
    $conn->query("CREATE TABLE IF NOT EXISTS users (
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
    $conn->query("CREATE TABLE IF NOT EXISTS employees (
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
    $conn->query("CREATE TABLE IF NOT EXISTS systems (
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
    $conn->query("CREATE TABLE IF NOT EXISTS peripherals (
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
    $conn->query("CREATE TABLE IF NOT EXISTS system_history (
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
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin'");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email) VALUES (?, ?, 'super_admin', 'Super Administrator', 'admin@company.com')");
        $stmt->bind_param("ss", $username, $password);
        $username = 'admin';
        $password = $hashedPassword;
        $stmt->execute();
    }
    
    $conn->close();
}

// Initialize database on first run
initializeDatabase();
?>

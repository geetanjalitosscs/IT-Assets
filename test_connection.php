<?php
// Test database connection
echo "<h2>Database Connection Test</h2>";

// Test 1: PDO MySQL
echo "<h3>Testing PDO MySQL:</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=it_asset_management", "root", "");
    echo "✅ PDO MySQL connection successful!<br>";
} catch(PDOException $e) {
    echo "❌ PDO MySQL failed: " . $e->getMessage() . "<br>";
}

// Test 2: MySQLi
echo "<h3>Testing MySQLi:</h3>";
try {
    $mysqli = new mysqli("localhost", "root", "", "it_asset_management");
    if ($mysqli->connect_error) {
        echo "❌ MySQLi failed: " . $mysqli->connect_error . "<br>";
    } else {
        echo "✅ MySQLi connection successful!<br>";
    }
} catch(Exception $e) {
    echo "❌ MySQLi failed: " . $e->getMessage() . "<br>";
}

// Test 3: Basic MySQL
echo "<h3>Testing Basic MySQL:</h3>";
try {
    $mysql = mysql_connect("localhost", "root", "");
    if ($mysql) {
        echo "✅ Basic MySQL connection successful!<br>";
    } else {
        echo "❌ Basic MySQL failed<br>";
    }
} catch(Exception $e) {
    echo "❌ Basic MySQL failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Recommendations:</h3>";
echo "1. If PDO MySQL works → Use the original system<br>";
echo "2. If only MySQLi works → Replace database.php with database_mysqli.php<br>";
echo "3. If none work → Check your MySQL server and PHP installation<br>";
?>

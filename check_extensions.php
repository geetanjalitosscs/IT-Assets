<?php
// Check PHP extensions
echo "<h2>PHP Extension Check</h2>";

// Check PDO
if (extension_loaded('pdo')) {
    echo "✅ PDO extension is loaded<br>";
} else {
    echo "❌ PDO extension is NOT loaded<br>";
}

// Check PDO MySQL
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is loaded<br>";
} else {
    echo "❌ PDO MySQL extension is NOT loaded<br>";
}

// List all loaded extensions
echo "<h3>All Loaded Extensions:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "• " . $ext . "<br>";
}

// PHP version
echo "<h3>PHP Version:</h3>";
echo "PHP " . phpversion() . "<br>";

// Show php.ini location
echo "<h3>PHP Configuration:</h3>";
echo "php.ini location: " . php_ini_loaded_file() . "<br>";
?>

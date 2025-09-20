<?php
require_once 'config/database.php';

echo "<h2>Testing Branch ID Reordering Functionality</h2>";

$pdo = getConnection();

// Function to get the next sequential ID (same as in branches.php)
function getNextSequentialBranchId($pdo) {
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM branches");
    $result = $stmt->fetch();
    return $result['next_id'];
}

// Show current branches
echo "<h3>Current Branches:</h3>";
$stmt = $pdo->query("SELECT id, name, location FROM branches ORDER BY id");
$branches = $stmt->fetchAll();

if (empty($branches)) {
    echo "<p>No branches found.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Location</th></tr>";
    foreach ($branches as $branch) {
        echo "<tr><td>{$branch['id']}</td><td>{$branch['name']}</td><td>{$branch['location']}</td></tr>";
    }
    echo "</table>";
}

// Show what the next ID would be
$next_id = getNextSequentialBranchId($pdo);
echo "<h3>Next Sequential ID: " . $next_id . "</h3>";

// Check if sequence is continuous
echo "<h3>Sequence Analysis:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM branches");
$total = $stmt->fetch()['total'];

if ($total > 0) {
    $stmt = $pdo->query("SELECT MIN(id) as min_id, MAX(id) as max_id FROM branches");
    $result = $stmt->fetch();
    $min_id = $result['min_id'];
    $max_id = $result['max_id'];
    
    if (($max_id - $min_id + 1) == $total) {
        echo "<p style='color: green;'>✅ Sequence is continuous (no gaps)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Sequence has gaps</p>";
    }
    
    echo "<p>Total branches: " . $total . "</p>";
    echo "<p>ID range: " . $min_id . " to " . $max_id . "</p>";
} else {
    echo "<p>No branches found.</p>";
}

echo "<hr>";
echo "<p><strong>How it works now:</strong></p>";
echo "<ol>";
echo "<li><strong>Adding branches:</strong> Always gets the next sequential ID</li>";
echo "<li><strong>Deleting branches:</strong> Remaining branches are reordered to maintain continuous sequence</li>";
echo "<li><strong>Example:</strong> If you have branches 1,2,3,4,5,6 and delete branch 5, then branch 6 becomes 5, and new branch gets ID 6</li>";
echo "</ol>";

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Go to the Super Admin dashboard</li>";
echo "<li>Navigate to Branches management</li>";
echo "<li>Add some branches (they'll get sequential IDs)</li>";
echo "<li>Delete a branch in the middle - remaining branches will be reordered</li>";
echo "<li>Add a new branch - it will get the next sequential ID</li>";
echo "</ol>";
?>

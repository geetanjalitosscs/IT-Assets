<?php
// Cache control and data synchronization
// This file ensures changes are reflected across all pages

// Disable browser caching for dynamic content
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Force database connection refresh (using function from database.php)
function clearConnectionCache() {
    // Clear any existing connection cache
    if (isset($GLOBALS['pdo_connection'])) {
        unset($GLOBALS['pdo_connection']);
    }
    
    // Force garbage collection
    gc_collect_cycles();
    
    return true;
}

// Function to refresh data and clear caches
function refreshData() {
    // Clear any PHP output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Clear connection cache
    clearConnectionCache();
    
    // Force garbage collection
    gc_collect_cycles();
    
    return true;
}

// Function to ensure data consistency
function ensureDataConsistency($table) {
    // Use the getFreshConnection function from database.php
    if (function_exists('getFreshConnection')) {
        $pdo = getFreshConnection();
    } else {
        $pdo = getConnection();
    }
    
    // Force a simple query to refresh connection
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table}");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    return $count;
}

// Auto-refresh mechanism
function autoRefresh() {
    // Add timestamp to prevent caching
    $timestamp = time();
    echo "<!-- Data refreshed at: " . date('Y-m-d H:i:s', $timestamp) . " -->";
}

// Call auto-refresh
autoRefresh();
?>

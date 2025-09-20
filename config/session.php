<?php
// Start session
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check if user has specific role
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

// Check if user is super admin
function isSuperAdmin() {
    return hasRole('super_admin');
}

// Check if user is branch admin
function isBranchAdmin() {
    return hasRole('branch_admin');
}

// Get current user's branch ID
function getCurrentUserBranch() {
    return $_SESSION['branch_id'] ?? null;
}

// Redirect to login if not authenticated
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect based on role
function redirectByRole() {
    if (isSuperAdmin()) {
        header('Location: dashboard.php');
    } elseif (isBranchAdmin()) {
        header('Location: branch_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit();
}

// Logout function
function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

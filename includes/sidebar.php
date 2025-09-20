<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-content">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'dashboard.php' || $currentPage == 'branch_dashboard.php') ? 'active' : ''; ?>" 
                   href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <?php if (isSuperAdmin()): ?>
            <!-- Branches (Super Admin Only) -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'branches.php') ? 'active' : ''; ?>" href="branches.php">
                    <i class="fas fa-building me-3"></i>
                    <span>Branches</span>
                </a>
            </li>
            
            <!-- Users (Super Admin Only) -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-3"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Systems -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'systems.php') ? 'active' : ''; ?>" href="systems.php">
                    <i class="fas fa-desktop me-3"></i>
                    <span>Systems</span>
                </a>
            </li>
            
            <!-- Employees -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'employees.php') ? 'active' : ''; ?>" href="employees.php">
                    <i class="fas fa-user-tie me-3"></i>
                    <span>Employees</span>
                </a>
            </li>
            
            <?php if (isBranchAdmin()): ?>
            <!-- Users (Admin can manage branch users) -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'admin_users.php') ? 'active' : ''; ?>" href="admin_users.php">
                    <i class="fas fa-users me-3"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Reports -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-3"></i>
                    <span>Reports</span>
                </a>
            </li>
            
        </ul>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: var(--header-height);
    left: 0;
    width: var(--sidebar-width);
    height: calc(100vh - var(--header-height));
    background: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1020;
    overflow-y: auto;
}

.sidebar-content {
    padding: 20px 0;
}

.sidebar .nav-link {
    color: #6c757d;
    padding: 15px 25px;
    border-radius: 0;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    font-weight: 500;
}

.sidebar .nav-link:hover {
    color: var(--primary-color);
    background-color: rgba(30, 64, 175, 0.1);
    border-left-color: var(--primary-color);
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    color: var(--primary-color);
    background-color: rgba(30, 64, 175, 0.15);
    border-left-color: var(--primary-color);
    font-weight: 600;
}

.sidebar .nav-link i {
    width: 20px;
    text-align: center;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>

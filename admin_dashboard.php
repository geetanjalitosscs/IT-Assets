<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require login and check if user is branch admin
requireLogin();
if (!isBranchAdmin()) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();
$branchId = getCurrentUserBranch();

// Get branch information
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branchId]);
$branch = $stmt->fetch();

// Get statistics for this branch
$stats = [];

// Total systems in this branch
$stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE branch_id = ?");
$stmt->execute([$branchId]);
$stats['total_systems'] = $stmt->fetchColumn();

// Total employees in this branch
$stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE branch_id = ?");
$stmt->execute([$branchId]);
$stats['total_employees'] = $stmt->fetchColumn();

// Total users in this branch
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ?");
$stmt->execute([$branchId]);
$stats['total_users'] = $stmt->fetchColumn();

// Recent activities for this branch
$stmt = $pdo->prepare("
    SELECT 'system' as type, id, 'System assigned' as action, created_at 
    FROM systems 
    WHERE branch_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$branchId]);
$recent_activities = $stmt->fetchAll();

// Get recent employees
$stmt = $pdo->prepare("
    SELECT employee_id, full_name, department, position, created_at 
    FROM employees 
    WHERE branch_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$branchId]);
$recent_employees = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Admin Dashboard</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active">Admin Dashboard</li>
                    </ol>
                </nav>
            </div>
            <div class="text-end">
                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($branch['name'] ?? 'Branch'); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($branch['location'] ?? ''); ?></small>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Systems
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['total_systems']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-desktop fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Employees
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['total_employees']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Users
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['total_users']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Recent Activities -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">No recent activities found</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-desktop text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Employees -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Recent Employees</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_employees)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-tie fa-3x text-gray-300 mb-3"></i>
                                <p class="text-muted">No employees found</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_employees as $employee): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($employee['full_name']); ?></h6>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($employee['department']); ?></p>
                                                <small class="text-muted"><?php echo htmlspecialchars($employee['position']); ?></small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('M j', strtotime($employee['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="systems.php" class="btn btn-outline-primary btn-block w-100">
                                    <i class="fas fa-desktop me-2"></i>Manage Systems
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="employees.php" class="btn btn-outline-success btn-block w-100">
                                    <i class="fas fa-user-tie me-2"></i>Manage Employees
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin_users.php" class="btn btn-outline-info btn-block w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="btn btn-outline-warning btn-block w-100">
                                    <i class="fas fa-chart-bar me-2"></i>View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/footer.php'; ?>

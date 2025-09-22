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

// Recent activities for this branch - Get from activity_log
$stmt = $pdo->prepare("
    SELECT al.activity_type as type, al.entity_name, al.description, al.created_at as activity_date
    FROM activity_log al
    WHERE al.branch_id = ? AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute([$branchId]);
$recent_activities = $stmt->fetchAll();

// If no activities in activity_log, fall back to system_history for assignments
if (empty($recent_activities)) {
    $stmt = $pdo->prepare("
        SELECT 'assignment' as type, s.system_code as entity_name, e.full_name as employee_name, sh.assigned_date as activity_date,
               CONCAT('System ', s.system_code, ' assigned to ', e.full_name) as description
        FROM system_history sh
        JOIN systems s ON sh.system_id = s.id
        JOIN employees e ON sh.employee_id = e.id
        WHERE s.branch_id = ? AND sh.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY sh.assigned_date DESC
        LIMIT 10
    ");
    $stmt->execute([$branchId]);
    $recent_activities = $stmt->fetchAll();
}

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
                                <small class="text-muted">Activities will appear here as users interact with the system</small>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="list-group-item d-flex align-items-start">
                                        <div class="me-3">
                                            <?php
                                            $iconClass = '';
                                            $iconColor = '';
                                            switch($activity['type']) {
                                                case 'assignment':
                                                    $iconClass = 'fas fa-user-plus';
                                                    $iconColor = 'text-success';
                                                    break;
                                                case 'system_add':
                                                    $iconClass = 'fas fa-desktop';
                                                    $iconColor = 'text-primary';
                                                    break;
                                                case 'system_edit':
                                                    $iconClass = 'fas fa-edit';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'system_delete':
                                                    $iconClass = 'fas fa-trash';
                                                    $iconColor = 'text-danger';
                                                    break;
                                                case 'system_assign':
                                                    $iconClass = 'fas fa-user-plus';
                                                    $iconColor = 'text-success';
                                                    break;
                                                case 'employee_add':
                                                    $iconClass = 'fas fa-user-tie';
                                                    $iconColor = 'text-info';
                                                    break;
                                                case 'employee_edit':
                                                    $iconClass = 'fas fa-user-edit';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'employee_delete':
                                                    $iconClass = 'fas fa-user-times';
                                                    $iconColor = 'text-danger';
                                                    break;
                                                case 'user_add':
                                                    $iconClass = 'fas fa-user-cog';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'user_edit':
                                                    $iconClass = 'fas fa-user-edit';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'user_delete':
                                                    $iconClass = 'fas fa-user-slash';
                                                    $iconColor = 'text-danger';
                                                    break;
                                                case 'branch_add':
                                                    $iconClass = 'fas fa-building';
                                                    $iconColor = 'text-success';
                                                    break;
                                                case 'branch_edit':
                                                    $iconClass = 'fas fa-building';
                                                    $iconColor = 'text-warning';
                                                    break;
                                                case 'branch_delete':
                                                    $iconClass = 'fas fa-building';
                                                    $iconColor = 'text-danger';
                                                    break;
                                                default:
                                                    $iconClass = 'fas fa-circle';
                                                    $iconColor = 'text-muted';
                                            }
                                            ?>
                                            <i class="<?php echo $iconClass . ' ' . $iconColor; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-medium text-dark">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?>
                                            </small>
                                        </div>
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

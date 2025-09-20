<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    header('Location: branch_dashboard.php');
    exit();
}

$pageTitle = 'Super Admin Dashboard';

// Get statistics
$pdo = getConnection();

// Total branches
$stmt = $pdo->query("SELECT COUNT(*) FROM branches");
$totalBranches = $stmt->fetchColumn();

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// Total systems
$stmt = $pdo->query("SELECT COUNT(*) FROM systems");
$totalSystems = $stmt->fetchColumn();

// Total employees
$stmt = $pdo->query("SELECT COUNT(*) FROM employees");
$totalEmployees = $stmt->fetchColumn();

// Systems by status
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM systems GROUP BY status");
$systemsByStatus = $stmt->fetchAll();

// Recent activities
$stmt = $pdo->query("
    SELECT 'system' as type, s.system_code, e.full_name as employee_name, b.name as branch_name, sh.assigned_date
    FROM system_history sh
    JOIN systems s ON sh.system_id = s.id
    JOIN employees e ON sh.employee_id = e.id
    JOIN branches b ON s.branch_id = b.id
    WHERE sh.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY sh.assigned_date DESC
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt me-3"></i>Super Admin Dashboard
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Branches</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalBranches; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Systems</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSystems; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-desktop fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Employees</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalEmployees; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalUsers; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Systems Status Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Systems Status Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="systemsStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>No recent activities</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['system_code']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Assigned to <?php echo htmlspecialchars($activity['employee_name']); ?>
                                                <br>
                                                <?php echo htmlspecialchars($activity['branch_name']); ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j', strtotime($activity['assigned_date'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="branches.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-building me-2"></i>Manage Branches
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="users.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="systems.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-desktop me-2"></i>View All Systems
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="reports.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Systems Status Chart
const ctx = document.getElementById('systemsStatusChart').getContext('2d');
const systemsData = <?php echo json_encode($systemsByStatus); ?>;

const labels = systemsData.map(item => item.status);
const data = systemsData.map(item => item.count);
const colors = ['#28a745', '#ffc107', '#dc3545'];

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});
</script>

<style>
        .border-left-primary {
            border-left: 0.25rem solid #1e40af !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #16a34a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #3b82f6 !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f59e0b !important;
        }

.text-xs {
    font-size: 0.7rem;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.chart-pie {
    position: relative;
    height: 15rem;
}
</style>

<?php include 'includes/footer.php'; ?>

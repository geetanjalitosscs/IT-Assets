<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require login
requireLogin();

$pageTitle = 'Branch Dashboard';

// Get current user's branch
$currentBranchId = getCurrentUserBranch();

try {
    $pdo = getConnection();
    
    // Get branch information
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$currentBranchId]);
    $branch = $stmt->fetch();

    // Get comprehensive statistics for current branch
    $stats = [];
    
    // Total systems
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE branch_id = ?");
    $stmt->execute([$currentBranchId]);
    $stats['total_systems'] = $stmt->fetchColumn();

    // Total employees
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE branch_id = ?");
    $stmt->execute([$currentBranchId]);
    $stats['total_employees'] = $stmt->fetchColumn();

    // Total users in this branch
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE branch_id = ?");
    $stmt->execute([$currentBranchId]);
    $stats['total_users'] = $stmt->fetchColumn();

    // Systems by status for current branch
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM systems WHERE branch_id = ? GROUP BY status");
    $stmt->execute([$currentBranchId]);
    $systemsByStatus = $stmt->fetchAll();

    // Systems by type for current branch
    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM systems WHERE branch_id = ? GROUP BY type");
    $stmt->execute([$currentBranchId]);
    $systemsByType = $stmt->fetchAll();

    // Recently assigned systems (last 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM systems 
        WHERE branch_id = ? AND assigned_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$currentBranchId]);
    $stats['recently_assigned'] = $stmt->fetchColumn();

    // Systems in repair
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE branch_id = ? AND status = 'In Repair'");
    $stmt->execute([$currentBranchId]);
    $stats['in_repair'] = $stmt->fetchColumn();

    // Available systems (unassigned)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE branch_id = ? AND status = 'Unassigned'");
    $stmt->execute([$currentBranchId]);
    $stats['available'] = $stmt->fetchColumn();


    // Recent activities for current branch - Try activity_log first, fallback to system_history
    $stmt = $pdo->prepare("
        SELECT al.activity_type as type, al.entity_name, al.description, al.created_at as activity_date
        FROM activity_log al
        WHERE al.branch_id = ? AND al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentBranchId]);
    $recentActivities = $stmt->fetchAll();

    // If no activities in activity_log, fall back to system_history for assignments
    if (empty($recentActivities)) {
        $stmt = $pdo->prepare("
            SELECT 'assignment' as type, s.system_code as entity_name, e.full_name as employee_name, 
                   sh.assigned_date as activity_date,
                   CONCAT('System ', s.system_code, ' assigned to ', e.full_name) as description
            FROM system_history sh
            JOIN systems s ON sh.system_id = s.id
            JOIN employees e ON sh.employee_id = e.id
            WHERE s.branch_id = ? AND sh.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY sh.assigned_date DESC
            LIMIT 10
        ");
        $stmt->execute([$currentBranchId]);
        $recentActivities = $stmt->fetchAll();
    }

    // Monthly system assignments trend (last 6 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(assigned_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM systems 
        WHERE branch_id = ? AND assigned_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(assigned_date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$currentBranchId]);
    $monthlyAssignments = $stmt->fetchAll();

    // Department-wise system distribution
    $stmt = $pdo->prepare("
        SELECT e.department, COUNT(s.id) as system_count
        FROM systems s
        JOIN employees e ON s.assigned_to = e.id
        WHERE s.branch_id = ? AND s.status = 'Assigned'
        GROUP BY e.department
        ORDER BY system_count DESC
    ");
    $stmt->execute([$currentBranchId]);
    $departmentDistribution = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Database error in branch_dashboard.php: " . $e->getMessage());
    die("Unable to load dashboard data. Please try again later.");
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt me-3"></i>Branch Dashboard
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
            <?php if ($branch): ?>
                <p class="text-muted mb-0">Welcome to <?php echo htmlspecialchars($branch['name']); ?> - <?php echo htmlspecialchars($branch['location']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <a href="systems.php" class="dashboard-card-link">
                    <div class="card border-left-primary shadow h-100 py-2 clickable-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Systems</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_systems']; ?></div>
                                    <div class="text-xs text-muted">
                                        <span class="text-success"><?php echo $stats['available']; ?> Available</span> | 
                                        <span class="text-warning"><?php echo $stats['in_repair']; ?> In Repair</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-desktop fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <a href="employees.php" class="dashboard-card-link">
                    <div class="card border-left-success shadow h-100 py-2 clickable-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Employees</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_employees']; ?></div>
                                    <div class="text-xs text-muted">
                                        <?php echo $stats['recently_assigned']; ?> recently assigned
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <a href="admin_users.php" class="dashboard-card-link">
                    <div class="card border-left-info shadow h-100 py-2 clickable-card">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                                    <div class="text-xs text-muted">
                                        Branch administrators
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>

        <div class="row">
            <!-- Systems Status Overview -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Systems Status Overview</h6>
                        <div class="dropdown no-arrow">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" id="chartDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-pie"></i> View Options
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="switchChart('status')">By Status</a>
                                <a class="dropdown-item" href="#" onclick="switchChart('type')">By Type</a>
                                <a class="dropdown-item" href="#" onclick="switchChart('department')">By Department</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Chart Controls -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="chart-controls">
                                    <button class="btn btn-sm btn-outline-primary active" onclick="switchChart('status')" id="btn-status">
                                        <i class="fas fa-chart-pie"></i> Status
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="switchChart('type')" id="btn-type">
                                        <i class="fas fa-chart-bar"></i> Type
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="switchChart('department')" id="btn-department">
                                        <i class="fas fa-building"></i> Department
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-sm btn-outline-secondary" onclick="refreshChart()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="exportChart()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        
                        <!-- Chart Container -->
                        <div class="chart-pie pt-4 pb-2">
                            <canvas id="systemsStatusChart"></canvas>
                        </div>
                        
                        <!-- Chart Legend -->
                        <div class="chart-legend mt-3" id="chartLegend">
                            <!-- Legend will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">Recent Activities</h6>
                        <span class="badge bg-primary">Last 7 days</span>
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
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-primary me-2" style="font-size: 8px;"></i>
                                                <strong><?php echo htmlspecialchars($activity['entity_name'] ?? 'N/A'); ?></strong>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($activity['description'] ?? 'Activity'); ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j', strtotime($activity['activity_date'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Data Sections -->
        <div class="row">
            <!-- Monthly Assignments Trend -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Monthly Assignments Trend</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="monthlyAssignmentsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Distribution -->
            <div class="col-xl-6 col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Department Distribution</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($departmentDistribution)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-building fa-2x mb-2"></i>
                                <p>No department data available</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($departmentDistribution as $dept): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-building text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($dept['department'] ?? 'Unknown'); ?></strong>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?php echo $dept['system_count']; ?> systems</span>
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
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Quick Actions</h6>
                    </div>
                     <div class="card-body">
                         <div class="row">
                             <div class="col-md-3 mb-3">
                                 <a href="systems.php" class="btn btn-outline-primary w-100">
                                     <i class="fas fa-desktop me-2"></i>Manage Systems
                                 </a>
                             </div>
                             <div class="col-md-3 mb-3">
                                 <a href="employees.php" class="btn btn-outline-success w-100">
                                     <i class="fas fa-user-tie me-2"></i>Manage Employees
                                 </a>
                             </div>
                             <div class="col-md-3 mb-3">
                                 <a href="admin_users.php" class="btn btn-outline-info w-100">
                                     <i class="fas fa-users me-2"></i>Manage Users
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
// Chart data from PHP
const systemsByStatus = <?php echo json_encode($systemsByStatus); ?>;
const systemsByType = <?php echo json_encode($systemsByType); ?>;
const departmentDistribution = <?php echo json_encode($departmentDistribution); ?>;
const monthlyAssignments = <?php echo json_encode($monthlyAssignments); ?>;

let currentChart = null;
let currentChartType = 'status';

// Chart colors
const statusColors = ['#28a745', '#ffc107', '#dc3545', '#6c757d'];
const typeColors = ['#007bff', '#28a745', '#ffc107', '#dc3545'];
const departmentColors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#17a2b8'];

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Initialize main chart
    switchChart('status');
    
    // Initialize monthly assignments chart
    initializeMonthlyChart();
}

function switchChart(type) {
    currentChartType = type;
    
    // Update button states
    document.querySelectorAll('.chart-controls button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('btn-' + type).classList.add('active');
    
    // Destroy existing chart
    if (currentChart) {
        currentChart.destroy();
    }
    
    const ctx = document.getElementById('systemsStatusChart').getContext('2d');
    let chartData, chartLabels, chartColors, chartTitle;
    
    switch(type) {
        case 'status':
            chartData = systemsByStatus.map(item => item.count);
            chartLabels = systemsByStatus.map(item => item.status);
            chartColors = statusColors;
            chartTitle = 'Systems by Status';
            break;
        case 'type':
            chartData = systemsByType.map(item => item.count);
            chartLabels = systemsByType.map(item => item.type);
            chartColors = typeColors;
            chartTitle = 'Systems by Type';
            break;
        case 'department':
            chartData = departmentDistribution.map(item => item.system_count);
            chartLabels = departmentDistribution.map(item => item.department || 'Unknown');
            chartColors = departmentColors;
            chartTitle = 'Systems by Department';
            break;
    }
    
    currentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartData,
                backgroundColor: chartColors,
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
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                title: {
                    display: true,
                    text: chartTitle,
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                }
            }
        }
    });
    
    // Update legend
    updateChartLegend(chartLabels, chartData, chartColors);
}

function updateChartLegend(labels, data, colors) {
    const legendContainer = document.getElementById('chartLegend');
    legendContainer.innerHTML = '';
    
    labels.forEach((label, index) => {
        const legendItem = document.createElement('div');
        legendItem.className = 'd-inline-block me-3 mb-2';
        legendItem.innerHTML = `
            <span class="me-2" style="display: inline-block; width: 12px; height: 12px; background-color: ${colors[index]}; border-radius: 50%;"></span>
            <span class="text-muted">${label}: ${data[index]}</span>
        `;
        legendContainer.appendChild(legendItem);
    });
}

function initializeMonthlyChart() {
    const ctx = document.getElementById('monthlyAssignmentsChart').getContext('2d');
    
    const months = monthlyAssignments.map(item => item.month);
    const counts = monthlyAssignments.map(item => item.count);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'System Assignments',
                data: counts,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function refreshChart() {
    // Reload the page to refresh data
    window.location.reload();
}

function exportChart() {
    if (currentChart) {
        const url = currentChart.toBase64Image();
        const link = document.createElement('a');
        link.download = 'systems-chart-' + currentChartType + '.png';
        link.href = url;
        link.click();
    }
}
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

.chart-area {
    position: relative;
    height: 12rem;
}

.chart-controls .btn {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}

.chart-controls .btn.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.chart-legend {
    text-align: center;
    padding: 1rem 0;
}

/* Clickable Dashboard Cards */
.dashboard-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.dashboard-card-link:hover {
    text-decoration: none;
    color: inherit;
}

.clickable-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.clickable-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.clickable-card:hover .card-body {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(79, 70, 229, 0.02) 100%);
}

.clickable-card:hover .text-primary {
    color: #4f46e5 !important;
}

.clickable-card:hover .text-success {
    color: #10b981 !important;
}

.clickable-card:hover .text-info {
    color: #06b6d4 !important;
}

.clickable-card:hover .text-warning {
    color: #f59e0b !important;
}

.clickable-card:hover .text-gray-300 {
    color: #9ca3af !important;
}

/* Dark Mode Dashboard Styling */
[data-theme="dark"] .border-left-primary {
    border-left: 0.25rem solid var(--primary-color) !important;
}

[data-theme="dark"] .border-left-success {
    border-left: 0.25rem solid #10b981 !important;
}

[data-theme="dark"] .border-left-info {
    border-left: 0.25rem solid #06b6d4 !important;
}

[data-theme="dark"] .border-left-warning {
    border-left: 0.25rem solid #f59e0b !important;
}

[data-theme="dark"] .text-gray-300 {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .text-gray-800 {
    color: var(--text-color) !important;
}

[data-theme="dark"] .chart-controls .btn.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

[data-theme="dark"] .list-group-item {
    background-color: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .list-group-item:hover {
    background-color: var(--hover-bg);
}

[data-theme="dark"] .badge {
    background-color: var(--primary-color) !important;
}

/* Dark Mode Clickable Cards */
[data-theme="dark"] .clickable-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3) !important;
}

[data-theme="dark"] .clickable-card:hover .card-body {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(79, 70, 229, 0.05) 100%);
}

[data-theme="dark"] .clickable-card:hover .text-primary {
    color: var(--primary-color) !important;
}

[data-theme="dark"] .clickable-card:hover .text-success {
    color: #10b981 !important;
}

[data-theme="dark"] .clickable-card:hover .text-info {
    color: #06b6d4 !important;
}

[data-theme="dark"] .clickable-card:hover .text-warning {
    color: #f59e0b !important;
}

[data-theme="dark"] .clickable-card:hover .text-gray-300 {
    color: var(--text-muted) !important;
}
</style>

<?php include 'includes/footer.php'; ?>

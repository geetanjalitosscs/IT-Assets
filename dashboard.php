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

// Get comprehensive statistics
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

// Systems by type
$stmt = $pdo->query("SELECT type, COUNT(*) as count FROM systems GROUP BY type");
$systemsByType = $stmt->fetchAll();

// Systems by branch
$stmt = $pdo->query("
    SELECT b.name as branch_name, COUNT(s.id) as system_count
    FROM branches b
    LEFT JOIN systems s ON b.id = s.branch_id
    GROUP BY b.id, b.name
    ORDER BY system_count DESC
");
$systemsByBranch = $stmt->fetchAll();

// Recently assigned systems (last 30 days)
$stmt = $pdo->query("
    SELECT COUNT(*) FROM systems 
    WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$recentlyAssigned = $stmt->fetchColumn();

// Systems in repair
$stmt = $pdo->query("SELECT COUNT(*) FROM systems WHERE status = 'In Repair'");
$systemsInRepair = $stmt->fetchColumn();

// Available systems (unassigned)
$stmt = $pdo->query("SELECT COUNT(*) FROM systems WHERE status = 'Unassigned'");
$availableSystems = $stmt->fetchColumn();

// Total peripherals
$stmt = $pdo->query("SELECT COUNT(*) FROM peripherals");
$totalPeripherals = $stmt->fetchColumn();

// Peripherals by status
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM peripherals GROUP BY status");
$peripheralsByStatus = $stmt->fetchAll();

// Monthly system assignments trend (last 6 months)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(assigned_date, '%Y-%m') as month,
        COUNT(*) as count
    FROM systems 
    WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(assigned_date, '%Y-%m')
    ORDER BY month
");
$monthlyAssignments = $stmt->fetchAll();

// Department-wise system distribution
$stmt = $pdo->query("
    SELECT e.department, COUNT(s.id) as system_count
    FROM systems s
    JOIN employees e ON s.assigned_to = e.id
    WHERE s.status = 'Assigned'
    GROUP BY e.department
    ORDER BY system_count DESC
");
$departmentDistribution = $stmt->fetchAll();

// Recent activities - Get comprehensive recent activities from activity_log
$stmt = $pdo->query("
    SELECT al.activity_type as type, al.entity_name, al.description, b.name as branch_name, al.created_at as activity_date
    FROM activity_log al
    LEFT JOIN branches b ON al.branch_id = b.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY al.created_at DESC
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();

// If no activities in activity_log, fall back to system_history for assignments
if (empty($recentActivities)) {
$stmt = $pdo->query("
        SELECT 'assignment' as type, s.system_code as entity_name, e.full_name as employee_name, b.name as branch_name, sh.assigned_date as activity_date,
               CONCAT('System ', s.system_code, ' assigned to ', e.full_name) as description
    FROM system_history sh
    JOIN systems s ON sh.system_id = s.id
    JOIN employees e ON sh.employee_id = e.id
    JOIN branches b ON s.branch_id = b.id
    WHERE sh.assigned_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY sh.assigned_date DESC
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();
}

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
                <a href="branches.php" class="dashboard-card-link">
                    <div class="card border-left-primary shadow h-100 py-2 clickable-card">
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
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="systems.php" class="dashboard-card-link">
                    <div class="card border-left-success shadow h-100 py-2 clickable-card">
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
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="employees.php" class="dashboard-card-link">
                    <div class="card border-left-info shadow h-100 py-2 clickable-card">
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
                </a>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <a href="users.php" class="dashboard-card-link">
                    <div class="card border-left-warning shadow h-100 py-2 clickable-card">
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
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Systems Status Overview -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-chart-pie me-2"></i>Systems Status Overview
                        </h6>
                        <div class="dropdown no-arrow">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" id="chartDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-pie"></i> View Options
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="#" onclick="switchChart('status')">By Status</a>
                                <a class="dropdown-item" href="#" onclick="switchChart('type')">By Type</a>
                                <a class="dropdown-item" href="#" onclick="switchChart('branch')">By Branch</a>
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
                                    <button class="btn btn-sm btn-outline-primary" onclick="switchChart('branch')" id="btn-branch">
                                        <i class="fas fa-building"></i> Branch
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="switchChart('department')" id="btn-department">
                                        <i class="fas fa-users"></i> Department
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
                        
                        <!-- Chart Statistics -->
                        <div class="row mb-3">
                            <div class="col-md-3 text-center">
                                <div class="stat-item">
                                    <div class="stat-number text-primary"><?php echo $totalSystems; ?></div>
                                    <div class="stat-label">Total Systems</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?php echo $recentlyAssigned; ?></div>
                                    <div class="stat-label">Recently Assigned</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="stat-item">
                                    <div class="stat-number text-warning"><?php echo $systemsInRepair; ?></div>
                                    <div class="stat-label">In Repair</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="stat-item">
                                    <div class="stat-number text-info"><?php echo $availableSystems; ?></div>
                                    <div class="stat-label">Available</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Professional Chart -->
                        <div class="chart-container" style="position: relative; height: 300px;">
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
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>No recent activities</p>
                                    <small>Activities will appear here as users interact with the system</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php
                                    $iconClass = '';
                                    $iconColor = '';
                                    $iconBgClass = '';
                                    switch($activity['type']) {
                                        case 'assignment':
                                            $iconClass = 'fas fa-user-plus';
                                            $iconColor = 'success';
                                            $iconBgClass = 'success';
                                            break;
                                        case 'system_add':
                                            $iconClass = 'fas fa-desktop';
                                            $iconColor = 'primary';
                                            $iconBgClass = 'primary';
                                            break;
                                        case 'system_edit':
                                            $iconClass = 'fas fa-edit';
                                            $iconColor = 'warning';
                                            $iconBgClass = 'warning';
                                            break;
                                        case 'system_delete':
                                            $iconClass = 'fas fa-trash';
                                            $iconColor = 'danger';
                                            $iconBgClass = 'danger';
                                            break;
                                        case 'system_assign':
                                            $iconClass = 'fas fa-user-plus';
                                            $iconColor = 'success';
                                            $iconBgClass = 'success';
                                            break;
                                        case 'employee_add':
                                            $iconClass = 'fas fa-user-tie';
                                            $iconColor = 'info';
                                            $iconBgClass = 'info';
                                            break;
                                        case 'employee_edit':
                                            $iconClass = 'fas fa-user-edit';
                                            $iconColor = 'warning';
                                            $iconBgClass = 'warning';
                                            break;
                                        case 'employee_delete':
                                            $iconClass = 'fas fa-user-times';
                                            $iconColor = 'danger';
                                            $iconBgClass = 'danger';
                                            break;
                                        case 'user_add':
                                            $iconClass = 'fas fa-user-cog';
                                            $iconColor = 'warning';
                                            $iconBgClass = 'warning';
                                            break;
                                        case 'user_edit':
                                            $iconClass = 'fas fa-user-edit';
                                            $iconColor = 'warning';
                                            $iconBgClass = 'warning';
                                            break;
                                        case 'user_delete':
                                            $iconClass = 'fas fa-user-slash';
                                            $iconColor = 'danger';
                                            $iconBgClass = 'danger';
                                            break;
                                        case 'branch_add':
                                            $iconClass = 'fas fa-building';
                                            $iconColor = 'success';
                                            $iconBgClass = 'success';
                                            break;
                                        case 'branch_edit':
                                            $iconClass = 'fas fa-building';
                                            $iconColor = 'warning';
                                            $iconBgClass = 'warning';
                                            break;
                                        case 'branch_delete':
                                            $iconClass = 'fas fa-building';
                                            $iconColor = 'danger';
                                            $iconBgClass = 'danger';
                                            break;
                                        default:
                                            $iconClass = 'fas fa-circle';
                                            $iconColor = 'muted';
                                            $iconBgClass = 'muted';
                                    }
                                    ?>
                                    <div class="recent-activities-item d-flex align-items-start">
                                        <div class="activity-icon <?php echo $iconBgClass; ?>">
                                            <i class="<?php echo $iconClass; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <div class="activity-meta">
                                                <span class="activity-branch">
                                                <?php echo htmlspecialchars($activity['branch_name']); ?>
                                                </span>
                                                <span class="activity-time">
                                                    <?php echo date('M j, g:i A', strtotime($activity['activity_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
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
// Chart data from PHP
const systemsByStatus = <?php echo json_encode($systemsByStatus); ?>;
const systemsByType = <?php echo json_encode($systemsByType); ?>;
const systemsByBranch = <?php echo json_encode($systemsByBranch); ?>;
const departmentDistribution = <?php echo json_encode($departmentDistribution); ?>;

let currentChart = null;
let currentChartType = 'status';

// Chart colors
const statusColors = ['#10b981', '#f59e0b', '#ef4444', '#6b7280'];
const typeColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
const branchColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316'];
const departmentColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Initialize main chart
    switchChart('status');
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
        case 'branch':
            chartData = systemsByBranch.map(item => item.system_count);
            chartLabels = systemsByBranch.map(item => item.branch_name);
            chartColors = branchColors;
            chartTitle = 'Systems by Branch';
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
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverBorderWidth: 4,
                hoverBorderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
            cutout: '60%',
        plugins: {
            legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: chartTitle,
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#374151'
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
                easing: 'easeOutQuart'
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

// Chart Functions
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
        /* Enhanced Chart Styling */
        .stat-item {
            padding: 15px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .chart-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
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

        .chart-legend {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .legend-item {
            padding: 8px 12px;
            border-radius: 6px;
            background: white;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .legend-item:hover {
            background: #e9ecef;
            transform: translateX(4px);
        }

        .legend-color {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .legend-label {
            font-weight: 500;
            color: #374151;
        }

        .legend-count {
            color: var(--primary-color);
        }

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

/* Dark Mode Dashboard Styling */
[data-theme="dark"] .stat-item {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
    border-color: var(--primary-color);
}

[data-theme="dark"] .stat-item .stat-number {
    color: var(--text-color) !important;
}

[data-theme="dark"] .stat-item .stat-label {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .chart-container {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
}

[data-theme="dark"] .chart-controls .btn.active {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
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

[data-theme="dark"] .chart-legend {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
}

[data-theme="dark"] .legend-item {
    background: var(--card-hover);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .legend-item:hover {
    background: var(--primary-color);
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
}

[data-theme="dark"] .legend-label {
    color: var(--text-color);
}

[data-theme="dark"] .legend-count {
    color: var(--secondary-color);
}

/* Dark Mode Border Colors */
[data-theme="dark"] .border-left-primary {
    border-left: 0.25rem solid var(--primary-color) !important;
}

[data-theme="dark"] .border-left-success {
    border-left: 0.25rem solid #10b981 !important;
}

[data-theme="dark"] .border-left-warning {
    border-left: 0.25rem solid #f59e0b !important;
}

[data-theme="dark"] .border-left-info {
    border-left: 0.25rem solid #06b6d4 !important;
}

/* Dark Mode Text Colors */
[data-theme="dark"] .text-gray-300 {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .text-gray-800 {
    color: var(--text-color) !important;
}

/* Dark Mode Recent Activities Styling */
[data-theme="dark"] .list-group-item {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color) !important;
    color: var(--text-color) !important;
    transition: all 0.3s ease;
}

[data-theme="dark"] .list-group-item:hover {
    background-color: var(--card-hover) !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .list-group-item .text-dark {
    color: var(--text-color) !important;
}

[data-theme="dark"] .list-group-item .text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .list-group-item .text-center.text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .list-group-item .text-center.text-muted i {
    color: var(--text-muted) !important;
}

/* Dark Mode Activity Icons */
[data-theme="dark"] .list-group-item .text-primary {
    color: var(--primary-color) !important;
}

[data-theme="dark"] .list-group-item .text-success {
    color: #10b981 !important;
}

[data-theme="dark"] .list-group-item .text-warning {
    color: #f59e0b !important;
}

[data-theme="dark"] .list-group-item .text-danger {
    color: #ef4444 !important;
}

[data-theme="dark"] .list-group-item .text-info {
    color: #06b6d4 !important;
}

[data-theme="dark"] .list-group-item .text-muted {
    color: var(--text-muted) !important;
}

/* Enhanced Recent Activities Styling */
.recent-activities-item {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.recent-activities-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

[data-theme="dark"] .recent-activities-item {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border-left-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .recent-activities-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    border-left-color: var(--secondary-color);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    transition: all 0.3s ease;
}

.activity-icon.primary {
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary-color);
}

.activity-icon.success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.activity-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.activity-icon.danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.activity-icon.info {
    background: rgba(6, 182, 212, 0.1);
    color: #06b6d4;
}

[data-theme="dark"] .activity-icon.primary {
    background: rgba(79, 70, 229, 0.2);
    color: var(--primary-color);
}

[data-theme="dark"] .activity-icon.success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

[data-theme="dark"] .activity-icon.warning {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

[data-theme="dark"] .activity-icon.danger {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

[data-theme="dark"] .activity-icon.info {
    background: rgba(6, 182, 212, 0.2);
    color: #06b6d4;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    margin-bottom: 4px;
    color: var(--text-color);
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}

.activity-branch {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 400;
}
</style>

<?php include 'includes/footer.php'; ?>

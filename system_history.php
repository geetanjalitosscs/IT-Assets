<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    header('Location: branch_dashboard.php');
    exit();
}

$pageTitle = 'System History';
$pdo = getConnection();

// Get system history data
$stmt = $pdo->query("
    SELECT 
        sh.id,
        sh.assigned_date,
        sh.returned_date,
        sh.notes,
        s.system_code,
        s.type,
        s.cpu,
        s.ram,
        s.storage,
        s.os,
        e.full_name as employee_name,
        e.employee_id,
        e.department,
        e.position,
        b.name as branch_name,
        CASE 
            WHEN sh.returned_date IS NULL THEN 'Currently Assigned'
            ELSE 'Returned'
        END as status
    FROM system_history sh
    JOIN systems s ON sh.system_id = s.id
    JOIN employees e ON sh.employee_id = e.id
    JOIN branches b ON s.branch_id = b.id
    ORDER BY sh.assigned_date DESC
");
$historyRecords = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM system_history");
$totalAssignments = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM system_history WHERE returned_date IS NULL");
$currentlyAssigned = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM system_history WHERE returned_date IS NOT NULL");
$returnedSystems = $stmt->fetchColumn();

// Get recent activity from activity_log
$stmt = $pdo->query("
    SELECT al.activity_type, al.entity_name, al.description, b.name as branch_name, al.created_at
    FROM activity_log al
    LEFT JOIN branches b ON al.branch_id = b.id
    WHERE al.activity_type IN ('system_add', 'system_edit', 'system_delete', 'system_assign')
    ORDER BY al.created_at DESC
    LIMIT 20
");
$recentActivities = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.history-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.history-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.status-badge {
    font-size: 0.8rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.status-assigned {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.status-returned {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
}

.activity-item {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-left: 4px solid var(--primary-color);
    transition: all 0.2s ease;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

.stats-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    text-align: center;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stats-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.stats-label {
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-history me-3"></i>System History & Activity Log
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">System History</li>
                </ol>
            </nav>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?php echo $totalAssignments; ?></div>
                    <div class="stats-label">Total Assignments</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $currentlyAssigned; ?></div>
                    <div class="stats-label">Currently Assigned</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-number text-info"><?php echo $returnedSystems; ?></div>
                    <div class="stats-label">Returned Systems</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo count($recentActivities); ?></div>
                    <div class="stats-label">Recent Activities</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- System History Table -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-list me-2"></i>System Assignment History
                        </h6>
                        <span class="badge bg-light text-primary"><?php echo count($historyRecords); ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>System</th>
                                        <th>Employee</th>
                                        <th>Branch</th>
                                        <th>Assigned Date</th>
                                        <th>Returned Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historyRecords)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                                <p>No system history records found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($historyRecords as $record): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($record['system_code']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($record['type']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($record['department']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($record['assigned_date'])); ?></td>
                                                <td>
                                                    <?php if ($record['returned_date']): ?>
                                                        <?php echo date('M j, Y', strtotime($record['returned_date'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo $record['status'] == 'Currently Assigned' ? 'status-assigned' : 'status-returned'; ?>">
                                                        <?php echo $record['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($record['notes']): ?>
                                                        <?php echo htmlspecialchars($record['notes']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-clock me-2"></i>Recent System Activities
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>No recent activities</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-medium text-dark">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($activity['branch_name'] ?? 'Global'); ?>
                                            </small>
                                        </div>
                                        <div class="ms-2">
                                            <small class="text-muted">
                                                <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                            </small>
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
</div>

<?php include 'includes/footer.php'; ?>
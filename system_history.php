<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'System History';
$pdo = getConnection();

// Get system history based on user role
if (isSuperAdmin()) {
    $stmt = $pdo->query("
        SELECT sh.*, s.system_code, e.full_name as employee_name, e.employee_id, b.name as branch_name
        FROM system_history sh
        JOIN systems s ON sh.system_id = s.id
        JOIN employees e ON sh.employee_id = e.id
        JOIN branches b ON s.branch_id = b.id
        ORDER BY sh.assigned_date DESC, sh.created_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT sh.*, s.system_code, e.full_name as employee_name, e.employee_id, b.name as branch_name
        FROM system_history sh
        JOIN systems s ON sh.system_id = s.id
        JOIN employees e ON sh.employee_id = e.id
        JOIN branches b ON s.branch_id = b.id
        WHERE s.branch_id = ?
        ORDER BY sh.assigned_date DESC, sh.created_at DESC
    ");
    $stmt->execute([getCurrentUserBranch()]);
}
$history = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-history me-3"></i>System History
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">System History</li>
                </ol>
            </nav>
        </div>

        <!-- System History Table -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>System Assignment History
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>System</th>
                                <th>Employee</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th>Branch</th>
                                <?php endif; ?>
                                <th>Assigned Date</th>
                                <th>Returned Date</th>
                                <th>Duration</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['system_code']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                    </td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($record['branch_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge badge-success">
                                            <?php echo date('M j, Y', strtotime($record['assigned_date'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($record['returned_date']): ?>
                                            <span class="badge badge-warning">
                                                <?php echo date('M j, Y', strtotime($record['returned_date'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Currently Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $assignedDate = new DateTime($record['assigned_date']);
                                        $returnedDate = $record['returned_date'] ? new DateTime($record['returned_date']) : new DateTime();
                                        $duration = $assignedDate->diff($returnedDate);
                                        
                                        if ($duration->y > 0) {
                                            echo $duration->y . ' year' . ($duration->y > 1 ? 's' : '') . ' ';
                                        }
                                        if ($duration->m > 0) {
                                            echo $duration->m . ' month' . ($duration->m > 1 ? 's' : '') . ' ';
                                        }
                                        if ($duration->d > 0) {
                                            echo $duration->d . ' day' . ($duration->d > 1 ? 's' : '');
                                        }
                                        if ($duration->y == 0 && $duration->m == 0 && $duration->d == 0) {
                                            echo 'Less than 1 day';
                                        }
                                        ?>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

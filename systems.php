<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'System Management';
$pdo = getFreshConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $systemCode = trim($_POST['system_code']);
                $branchId = isSuperAdmin() ? $_POST['branch_id'] : getCurrentUserBranch();
                $type = $_POST['type'];
                $cpu = trim($_POST['cpu']);
                $ram = trim($_POST['ram']);
                $storage = trim($_POST['storage']);
                $os = trim($_POST['os']);
                
                if (!empty($systemCode) && !empty($branchId)) {
                    // Check if system code already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE system_code = ?");
                    $stmt->execute([$systemCode]);
                    if ($stmt->fetchColumn() == 0) {
                        // Get the next sequential ID
                        $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM systems");
                        $result = $stmt->fetch();
                        $next_id = $result['next_id'];
                        
                        // Insert with the next sequential ID
                        $stmt = $pdo->prepare("INSERT INTO systems (id, system_code, branch_id, type, cpu, ram, storage, os) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$next_id, $systemCode, $branchId, $type, $cpu, $ram, $storage, $os]);
                        $success = "System added successfully with ID: " . $next_id;
                        // Force refresh after successful operation
                        clearConnectionCache();
                    } else {
                        $error = "System code already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $systemCode = trim($_POST['system_code']);
                $branchId = isSuperAdmin() ? $_POST['branch_id'] : getCurrentUserBranch();
                $type = $_POST['type'];
                $cpu = trim($_POST['cpu']);
                $ram = trim($_POST['ram']);
                $storage = trim($_POST['storage']);
                $os = trim($_POST['os']);
                $status = $_POST['status'];
                
                if (!empty($systemCode) && !empty($branchId)) {
                    // Check if system code already exists (excluding current system)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE system_code = ? AND id != ?");
                    $stmt->execute([$systemCode, $id]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $pdo->prepare("UPDATE systems SET system_code = ?, branch_id = ?, type = ?, cpu = ?, ram = ?, storage = ?, os = ?, status = ? WHERE id = ?");
                        $stmt->execute([$systemCode, $branchId, $type, $cpu, $ram, $storage, $os, $status, $id]);
                        $success = "System updated successfully!";
                    } else {
                        $error = "System code already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'assign':
                $systemId = $_POST['system_id'];
                $employeeId = $_POST['employee_id'];
                
                if (!empty($systemId) && !empty($employeeId)) {
                    $pdo->beginTransaction();
                    try {
                        // Update system status
                        $stmt = $pdo->prepare("UPDATE systems SET status = 'Assigned', assigned_to = ?, assigned_date = CURDATE() WHERE id = ?");
                        $stmt->execute([$employeeId, $systemId]);
                        
                        // Add to history
                        $stmt = $pdo->prepare("INSERT INTO system_history (system_id, employee_id, assigned_date) VALUES (?, ?, CURDATE())");
                        $stmt->execute([$systemId, $employeeId]);
                        
                        $pdo->commit();
                        $success = "System assigned successfully!";
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Error assigning system: " . $e->getMessage();
                    }
                } else {
                    $error = "Please select both system and employee.";
                }
                break;
                
            case 'unassign':
                $systemId = $_POST['system_id'];
                
                if (!empty($systemId)) {
                    $pdo->beginTransaction();
                    try {
                        // Get current assignment
                        $stmt = $pdo->prepare("SELECT assigned_to FROM systems WHERE id = ?");
                        $stmt->execute([$systemId]);
                        $assignedTo = $stmt->fetchColumn();
                        
                        // Update system status
                        $stmt = $pdo->prepare("UPDATE systems SET status = 'Unassigned', assigned_to = NULL, assigned_date = NULL WHERE id = ?");
                        $stmt->execute([$systemId]);
                        
                        // Update history
                        $stmt = $pdo->prepare("UPDATE system_history SET returned_date = CURDATE() WHERE system_id = ? AND employee_id = ? AND returned_date IS NULL");
                        $stmt->execute([$systemId, $assignedTo]);
                        
                        $pdo->commit();
                        $success = "System unassigned successfully!";
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Error unassigning system: " . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // First, delete the system
                $stmt = $pdo->prepare("DELETE FROM systems WHERE id = ?");
                $stmt->execute([$id]);
                
                // Then reorder the remaining systems to fill the gap
                // Step 1: Get all remaining systems ordered by current ID
                $stmt = $pdo->query("SELECT id, system_code, branch_id, type, cpu, ram, storage, os, status, assigned_to, assigned_date FROM systems ORDER BY id");
                $remaining_systems = $stmt->fetchAll();
                
                // Step 2: Update each system with new sequential ID
                $new_id = 1;
                foreach ($remaining_systems as $system) {
                    if ($system['id'] != $new_id) {
                        $stmt = $pdo->prepare("UPDATE systems SET id = ? WHERE id = ?");
                        $stmt->execute([$new_id, $system['id']]);
                    }
                    $new_id++;
                }
                
                $success = "System deleted successfully and IDs reordered!";
                break;
        }
    }
}

// Get systems based on user role
if (isSuperAdmin()) {
    $stmt = $pdo->query("
        SELECT s.*, b.name as branch_name, e.full_name as employee_name 
        FROM systems s 
        LEFT JOIN branches b ON s.branch_id = b.id 
        LEFT JOIN employees e ON s.assigned_to = e.id 
        ORDER BY s.branch_id, s.system_code
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT s.*, b.name as branch_name, e.full_name as employee_name 
        FROM systems s 
        LEFT JOIN branches b ON s.branch_id = b.id 
        LEFT JOIN employees e ON s.assigned_to = e.id 
        WHERE s.branch_id = ? 
        ORDER BY s.system_code
    ");
    $stmt->execute([getCurrentUserBranch()]);
}
$systems = $stmt->fetchAll();

// Get branches for dropdown (Super Admin only)
$branches = [];
if (isSuperAdmin()) {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY name");
    $branches = $stmt->fetchAll();
}

// Get employees for assignment dropdown
$employees = [];
if (isSuperAdmin()) {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY full_name");
} else {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE branch_id = ? ORDER BY full_name");
    $stmt->execute([getCurrentUserBranch()]);
}
$employees = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-desktop me-3"></i>System Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Systems</li>
                </ol>
            </nav>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add System Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSystemModal">
                    <i class="fas fa-plus me-2"></i>Add New System
                </button>
            </div>
        </div>

        <!-- Systems Table -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>All Systems
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>System Code</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th>Branch</th>
                                <?php endif; ?>
                                <th>Type</th>
                                <th>Configuration</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($systems as $system): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($system['system_code']); ?></strong></td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($system['branch_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($system['type']); ?></span>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>CPU:</strong> <?php echo htmlspecialchars($system['cpu']); ?><br>
                                            <strong>RAM:</strong> <?php echo htmlspecialchars($system['ram']); ?><br>
                                            <strong>Storage:</strong> <?php echo htmlspecialchars($system['storage']); ?><br>
                                            <strong>OS:</strong> <?php echo htmlspecialchars($system['os']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($system['status']) {
                                            case 'Assigned':
                                                $statusClass = 'badge-success';
                                                break;
                                            case 'Unassigned':
                                                $statusClass = 'badge-warning';
                                                break;
                                            case 'In Repair':
                                                $statusClass = 'badge-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $system['status']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($system['employee_name']): ?>
                                            <?php echo htmlspecialchars($system['employee_name']); ?>
                                            <br><small class="text-muted">Since <?php echo date('M j, Y', strtotime($system['assigned_date'])); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editSystem(<?php echo htmlspecialchars(json_encode($system)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($system['status'] == 'Unassigned'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="assignSystem(<?php echo $system['id']; ?>, '<?php echo htmlspecialchars($system['system_code']); ?>')">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            <?php elseif ($system['status'] == 'Assigned'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="unassignSystem(<?php echo $system['id']; ?>, '<?php echo htmlspecialchars($system['system_code']); ?>')">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteSystem(<?php echo $system['id']; ?>, '<?php echo htmlspecialchars($system['system_code']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<!-- Add System Modal -->
<div class="modal fade" id="addSystemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New System
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="system_code" class="form-label">System Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="system_code" name="system_code" required>
                                <small class="form-text text-muted">e.g., C1, C2, C3</small>
                            </div>
                        </div>
                        <?php if (isSuperAdmin()): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" id="branch_id" name="branch_id" required>
                                        <option value="">Select Branch</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="Laptop">Laptop</option>
                                    <option value="Desktop">Desktop</option>
                                    <option value="Server">Server</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cpu" class="form-label">CPU</label>
                                <input type="text" class="form-control" id="cpu" name="cpu">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ram" class="form-label">RAM</label>
                                <input type="text" class="form-control" id="ram" name="ram">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="storage" class="form-label">Storage</label>
                                <input type="text" class="form-control" id="storage" name="storage">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="os" class="form-label">Operating System</label>
                                <input type="text" class="form-control" id="os" name="os">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit System Modal -->
<div class="modal fade" id="editSystemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit System
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_system_code" class="form-label">System Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_system_code" name="system_code" required>
                            </div>
                        </div>
                        <?php if (isSuperAdmin()): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_branch_id" name="branch_id" required>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="Laptop">Laptop</option>
                                    <option value="Desktop">Desktop</option>
                                    <option value="Server">Server</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="Assigned">Assigned</option>
                                    <option value="Unassigned">Unassigned</option>
                                    <option value="In Repair">In Repair</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cpu" class="form-label">CPU</label>
                                <input type="text" class="form-control" id="edit_cpu" name="cpu">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_ram" class="form-label">RAM</label>
                                <input type="text" class="form-control" id="edit_ram" name="ram">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_storage" class="form-label">Storage</label>
                                <input type="text" class="form-control" id="edit_storage" name="storage">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_os" class="form-label">Operating System</label>
                                <input type="text" class="form-control" id="edit_os" name="os">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign System Modal -->
<div class="modal fade" id="assignSystemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Assign System
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="system_id" id="assign_system_id">
                    <div class="mb-3">
                        <label class="form-label">System</label>
                        <p class="form-control-plaintext" id="assign_system_name"></p>
                    </div>
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Assign to Employee <span class="text-danger">*</span></label>
                        <select class="form-select" id="employee_id" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['full_name'] . ' (' . $employee['employee_id'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unassign System Modal -->
<div class="modal fade" id="unassignSystemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-minus me-2"></i>Unassign System
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="unassign">
                    <input type="hidden" name="system_id" id="unassign_system_id">
                    <p>Are you sure you want to unassign the system <strong id="unassign_system_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Unassign System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete System Modal -->
<div class="modal fade" id="deleteSystemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete the system <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete System</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSystem(system) {
    document.getElementById('edit_id').value = system.id;
    document.getElementById('edit_system_code').value = system.system_code;
    document.getElementById('edit_type').value = system.type;
    document.getElementById('edit_cpu').value = system.cpu || '';
    document.getElementById('edit_ram').value = system.ram || '';
    document.getElementById('edit_storage').value = system.storage || '';
    document.getElementById('edit_os').value = system.os || '';
    document.getElementById('edit_status').value = system.status;
    
    <?php if (isSuperAdmin()): ?>
        document.getElementById('edit_branch_id').value = system.branch_id;
    <?php endif; ?>
    
    const editModal = new bootstrap.Modal(document.getElementById('editSystemModal'));
    editModal.show();
}

function assignSystem(id, name) {
    document.getElementById('assign_system_id').value = id;
    document.getElementById('assign_system_name').textContent = name;
    
    const assignModal = new bootstrap.Modal(document.getElementById('assignSystemModal'));
    assignModal.show();
}

function unassignSystem(id, name) {
    document.getElementById('unassign_system_id').value = id;
    document.getElementById('unassign_system_name').textContent = name;
    
    const unassignModal = new bootstrap.Modal(document.getElementById('unassignSystemModal'));
    unassignModal.show();
}

function deleteSystem(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteSystemModal'));
    deleteModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>

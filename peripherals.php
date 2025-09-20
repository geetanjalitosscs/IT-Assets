<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'Peripheral Management';
$pdo = getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $brand = trim($_POST['brand']);
                $model = trim($_POST['model']);
                $serialNumber = trim($_POST['serial_number']);
                $systemId = !empty($_POST['system_id']) ? $_POST['system_id'] : null;
                
                if (!empty($name) && !empty($type)) {
                    $status = $systemId ? 'Assigned' : 'Available';
                    $stmt = $pdo->prepare("INSERT INTO peripherals (name, type, brand, model, serial_number, system_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $type, $brand, $model, $serialNumber, $systemId, $status]);
                    $success = "Peripheral added successfully!";
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $brand = trim($_POST['brand']);
                $model = trim($_POST['model']);
                $serialNumber = trim($_POST['serial_number']);
                $systemId = !empty($_POST['system_id']) ? $_POST['system_id'] : null;
                $status = $_POST['status'];
                
                if (!empty($name) && !empty($type)) {
                    $stmt = $pdo->prepare("UPDATE peripherals SET name = ?, type = ?, brand = ?, model = ?, serial_number = ?, system_id = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $type, $brand, $model, $serialNumber, $systemId, $status, $id]);
                    $success = "Peripheral updated successfully!";
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'assign':
                $peripheralId = $_POST['peripheral_id'];
                $systemId = $_POST['system_id'];
                
                if (!empty($peripheralId) && !empty($systemId)) {
                    $stmt = $pdo->prepare("UPDATE peripherals SET system_id = ?, status = 'Assigned' WHERE id = ?");
                    $stmt->execute([$systemId, $peripheralId]);
                    $success = "Peripheral assigned successfully!";
                } else {
                    $error = "Please select both peripheral and system.";
                }
                break;
                
            case 'unassign':
                $peripheralId = $_POST['peripheral_id'];
                
                if (!empty($peripheralId)) {
                    $stmt = $pdo->prepare("UPDATE peripherals SET system_id = NULL, status = 'Available' WHERE id = ?");
                    $stmt->execute([$peripheralId]);
                    $success = "Peripheral unassigned successfully!";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM peripherals WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Peripheral deleted successfully!";
                break;
        }
    }
}

// Get peripherals based on user role
if (isSuperAdmin()) {
    $stmt = $pdo->query("
        SELECT p.*, s.system_code, b.name as branch_name 
        FROM peripherals p 
        LEFT JOIN systems s ON p.system_id = s.id 
        LEFT JOIN branches b ON s.branch_id = b.id 
        ORDER BY p.type, p.name
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, s.system_code, b.name as branch_name 
        FROM peripherals p 
        LEFT JOIN systems s ON p.system_id = s.id 
        LEFT JOIN branches b ON s.branch_id = b.id 
        WHERE s.branch_id = ? OR p.system_id IS NULL
        ORDER BY p.type, p.name
    ");
    $stmt->execute([getCurrentUserBranch()]);
}
$peripherals = $stmt->fetchAll();

// Get systems for assignment dropdown
if (isSuperAdmin()) {
    $stmt = $pdo->query("SELECT s.*, b.name as branch_name FROM systems s LEFT JOIN branches b ON s.branch_id = b.id ORDER BY s.branch_id, s.system_code");
} else {
    $stmt = $pdo->prepare("SELECT s.*, b.name as branch_name FROM systems s LEFT JOIN branches b ON s.branch_id = b.id WHERE s.branch_id = ? ORDER BY s.system_code");
    $stmt->execute([getCurrentUserBranch()]);
}
$systems = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-keyboard me-3"></i>Peripheral Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Peripherals</li>
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

        <!-- Add Peripheral Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPeripheralModal">
                    <i class="fas fa-plus me-2"></i>Add New Peripheral
                </button>
            </div>
        </div>

        <!-- Peripherals Table -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>All Peripherals
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                                <th>Assigned System</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th>Branch</th>
                                <?php endif; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peripherals as $peripheral): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($peripheral['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($peripheral['type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($peripheral['brand'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($peripheral['model'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($peripheral['serial_number'] ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($peripheral['status']) {
                                            case 'Available':
                                                $statusClass = 'badge-success';
                                                break;
                                            case 'Assigned':
                                                $statusClass = 'badge-warning';
                                                break;
                                            case 'In Repair':
                                                $statusClass = 'badge-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $peripheral['status']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($peripheral['system_code']): ?>
                                            <strong><?php echo htmlspecialchars($peripheral['system_code']); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($peripheral['branch_name'] ?: '-'); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editPeripheral(<?php echo htmlspecialchars(json_encode($peripheral)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($peripheral['status'] == 'Available'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="assignPeripheral(<?php echo $peripheral['id']; ?>, '<?php echo htmlspecialchars($peripheral['name']); ?>')">
                                                    <i class="fas fa-link"></i>
                                                </button>
                                            <?php elseif ($peripheral['status'] == 'Assigned'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="unassignPeripheral(<?php echo $peripheral['id']; ?>, '<?php echo htmlspecialchars($peripheral['name']); ?>')">
                                                    <i class="fas fa-unlink"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deletePeripheral(<?php echo $peripheral['id']; ?>, '<?php echo htmlspecialchars($peripheral['name']); ?>')">
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

<!-- Add Peripheral Modal -->
<div class="modal fade" id="addPeripheralModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Peripheral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="Keyboard">Keyboard</option>
                                    <option value="Mouse">Mouse</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Printer">Printer</option>
                                    <option value="Scanner">Scanner</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="serial_number" name="serial_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="system_id" class="form-label">Assign to System (Optional)</label>
                                <select class="form-select" id="system_id" name="system_id">
                                    <option value="">Select System</option>
                                    <?php foreach ($systems as $system): ?>
                                        <option value="<?php echo $system['id']; ?>">
                                            <?php echo htmlspecialchars($system['system_code']); ?>
                                            <?php if (isSuperAdmin()): ?>
                                                - <?php echo htmlspecialchars($system['branch_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Peripheral</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Peripheral Modal -->
<div class="modal fade" id="editPeripheralModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Peripheral
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
                                <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="Keyboard">Keyboard</option>
                                    <option value="Mouse">Mouse</option>
                                    <option value="Monitor">Monitor</option>
                                    <option value="Printer">Printer</option>
                                    <option value="Scanner">Scanner</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="edit_brand" name="brand">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="edit_model" name="model">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_serial_number" class="form-label">Serial Number</label>
                                <input type="text" class="form-control" id="edit_serial_number" name="serial_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="Available">Available</option>
                                    <option value="Assigned">Assigned</option>
                                    <option value="In Repair">In Repair</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="edit_system_id" class="form-label">Assign to System</label>
                                <select class="form-select" id="edit_system_id" name="system_id">
                                    <option value="">Select System</option>
                                    <?php foreach ($systems as $system): ?>
                                        <option value="<?php echo $system['id']; ?>">
                                            <?php echo htmlspecialchars($system['system_code']); ?>
                                            <?php if (isSuperAdmin()): ?>
                                                - <?php echo htmlspecialchars($system['branch_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Peripheral</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Peripheral Modal -->
<div class="modal fade" id="assignPeripheralModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-link me-2"></i>Assign Peripheral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="peripheral_id" id="assign_peripheral_id">
                    <div class="mb-3">
                        <label class="form-label">Peripheral</label>
                        <p class="form-control-plaintext" id="assign_peripheral_name"></p>
                    </div>
                    <div class="mb-3">
                        <label for="assign_system_id" class="form-label">Assign to System <span class="text-danger">*</span></label>
                        <select class="form-select" id="assign_system_id" name="system_id" required>
                            <option value="">Select System</option>
                            <?php foreach ($systems as $system): ?>
                                <option value="<?php echo $system['id']; ?>">
                                    <?php echo htmlspecialchars($system['system_code']); ?>
                                    <?php if (isSuperAdmin()): ?>
                                        - <?php echo htmlspecialchars($system['branch_name']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Assign Peripheral</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unassign Peripheral Modal -->
<div class="modal fade" id="unassignPeripheralModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-unlink me-2"></i>Unassign Peripheral
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="unassign">
                    <input type="hidden" name="peripheral_id" id="unassign_peripheral_id">
                    <p>Are you sure you want to unassign the peripheral <strong id="unassign_peripheral_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Unassign Peripheral</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Peripheral Modal -->
<div class="modal fade" id="deletePeripheralModal" tabindex="-1">
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
                    <p>Are you sure you want to delete the peripheral <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Peripheral</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPeripheral(peripheral) {
    document.getElementById('edit_id').value = peripheral.id;
    document.getElementById('edit_name').value = peripheral.name;
    document.getElementById('edit_type').value = peripheral.type;
    document.getElementById('edit_brand').value = peripheral.brand || '';
    document.getElementById('edit_model').value = peripheral.model || '';
    document.getElementById('edit_serial_number').value = peripheral.serial_number || '';
    document.getElementById('edit_status').value = peripheral.status;
    document.getElementById('edit_system_id').value = peripheral.system_id || '';
    
    const editModal = new bootstrap.Modal(document.getElementById('editPeripheralModal'));
    editModal.show();
}

function assignPeripheral(id, name) {
    document.getElementById('assign_peripheral_id').value = id;
    document.getElementById('assign_peripheral_name').textContent = name;
    
    const assignModal = new bootstrap.Modal(document.getElementById('assignPeripheralModal'));
    assignModal.show();
}

function unassignPeripheral(id, name) {
    document.getElementById('unassign_peripheral_id').value = id;
    document.getElementById('unassign_peripheral_name').textContent = name;
    
    const unassignModal = new bootstrap.Modal(document.getElementById('unassignPeripheralModal'));
    unassignModal.show();
}

function deletePeripheral(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deletePeripheralModal'));
    deleteModal.show();
}
</script>

<?php include 'includes/footer.php'; ?>

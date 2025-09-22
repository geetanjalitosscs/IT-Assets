<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'System Management';
$pdo = getFreshConnection();

// Handle success message from URL parameters
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

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
                $peripherals = isset($_POST['peripherals']) ? trim($_POST['peripherals']) : '';
                $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
                
                if (!empty($systemCode) && !empty($branchId)) {
                    // Validate system code format (must start with C followed by number)
                    if (!preg_match('/^C\d+$/', $systemCode)) {
                        $error = "System code must start with 'C' followed by a number (e.g., C1, C2, C3)";
                    } else {
                        // Get the next sequential ID
                        $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM systems");
                        $result = $stmt->fetch();
                        $next_id = $result['next_id'];
                        
                        // Determine status and assignment date
                        $status = $assignedTo ? 'Assigned' : 'Unassigned';
                        $assignedDate = $assignedTo ? date('Y-m-d') : null;
                        
                        // Insert with the next sequential ID
                        $stmt = $pdo->prepare("INSERT INTO systems (id, system_code, branch_id, type, cpu, ram, storage, os, peripherals, status, assigned_to, assigned_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$next_id, $systemCode, $branchId, $type, $cpu, $ram, $storage, $os, $peripherals, $status, $assignedTo, $assignedDate]);
                        
                        // Add to history if assigned
                        if ($assignedTo) {
                            $stmt = $pdo->prepare("INSERT INTO system_history (system_id, employee_id, assigned_date) VALUES (?, ?, ?)");
                            $stmt->execute([$next_id, $assignedTo, $assignedDate]);
                        }
                        
                        // Log the system addition activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'system_add',
                            $next_id,
                            $systemCode,
                            'New system ' . $systemCode . ' added' . ($assignedTo ? ' and assigned' : ''),
                            $branchId
                        ]);
                        
                        $success = "System added successfully";
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
                $cpu = isset($_POST['cpu']) ? trim($_POST['cpu']) : '';
                $ram = isset($_POST['ram']) ? trim($_POST['ram']) : '';
                $storage = isset($_POST['storage']) ? trim($_POST['storage']) : '';
                $os = isset($_POST['os']) ? trim($_POST['os']) : '';
                $status = $_POST['status'];
                $peripherals = isset($_POST['peripherals']) ? trim($_POST['peripherals']) : '';
                
                if (!empty($systemCode) && !empty($branchId)) {
                    // Validate system code format (must start with C followed by number)
                    if (!preg_match('/^C\d+$/', $systemCode)) {
                        $error = "System code must start with 'C' followed by a number (e.g., C1, C2, C3)";
                    } else {
                        $stmt = $pdo->prepare("UPDATE systems SET system_code = ?, branch_id = ?, type = ?, cpu = ?, ram = ?, storage = ?, os = ?, status = ?, peripherals = ? WHERE id = ?");
                        $stmt->execute([$systemCode, $branchId, $type, $cpu, $ram, $storage, $os, $status, $peripherals, $id]);
                        
                        // Log the system edit activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'system_edit',
                            $id,
                            $systemCode,
                            'System ' . $systemCode . ' updated',
                            $branchId
                        ]);
                        
                        $success = "System updated successfully";
                        // Redirect to prevent form resubmission
                        header("Location: systems.php?success=" . urlencode($success));
                        exit();
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
                        
                        // Get system and employee info for logging
                        $stmt = $pdo->prepare("SELECT s.system_code, s.branch_id, e.full_name FROM systems s JOIN employees e ON e.id = ? WHERE s.id = ?");
                        $stmt->execute([$employeeId, $systemId]);
                        $assignInfo = $stmt->fetch();
                        
                        if ($assignInfo) {
                            // Log the assignment activity
                            $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([
                                'system_assign',
                                $systemId,
                                $assignInfo['system_code'],
                                'System ' . $assignInfo['system_code'] . ' assigned to ' . $assignInfo['full_name'],
                                $assignInfo['branch_id']
                            ]);
                        }
                        
                        $pdo->commit();
                        $success = "System assigned successfully";
                        // Redirect to prevent form resubmission
                        header("Location: systems.php?success=" . urlencode($success));
                        exit();
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
                        $success = "System unassigned successfully";
                        // Redirect to prevent form resubmission
                        header("Location: systems.php?success=" . urlencode($success));
                        exit();
                    } catch (Exception $e) {
                        $pdo->rollback();
                        $error = "Error unassigning system: " . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $pdo->beginTransaction();
                try {
                    // Get system info before deletion for logging
                    $stmt = $pdo->prepare("SELECT system_code, branch_id FROM systems WHERE id = ?");
                    $stmt->execute([$id]);
                    $systemInfo = $stmt->fetch();
                    
                    if ($systemInfo) {
                        // Log the deletion activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'system_delete',
                            $id,
                            $systemInfo['system_code'],
                            'System ' . $systemInfo['system_code'] . ' deleted',
                            $systemInfo['branch_id']
                        ]);
                    }
                    
                    // First, delete related records from system_history
                    $stmt = $pdo->prepare("DELETE FROM system_history WHERE system_id = ?");
                    $stmt->execute([$id]);
                    
                    // Then delete the system
                $stmt = $pdo->prepare("DELETE FROM systems WHERE id = ?");
                $stmt->execute([$id]);
                    
                    // Then reorder the remaining systems to fill the gap
                    // Step 1: Get all remaining systems ordered by current ID
                    $stmt = $pdo->query("SELECT id, system_code, branch_id, type, cpu, ram, storage, os, peripherals, status, assigned_to, assigned_date FROM systems ORDER BY id");
                    $remaining_systems = $stmt->fetchAll();
                    
                    // Step 2: Temporarily disable foreign key checks for reordering
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                    
                    // Step 3: Update each system with new sequential ID
                    $new_id = 1;
                    foreach ($remaining_systems as $system) {
                        if ($system['id'] != $new_id) {
                            $stmt = $pdo->prepare("UPDATE systems SET id = ? WHERE id = ?");
                            $stmt->execute([$new_id, $system['id']]);
                        }
                        $new_id++;
                    }
                    
                    // Step 4: Re-enable foreign key checks
                    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    
                    $pdo->commit();
                    $success = "System deleted successfully";
                    // Redirect to prevent form resubmission
                    header("Location: systems.php?success=" . urlencode($success));
                    exit();
                    
                } catch (Exception $e) {
                    $pdo->rollback();
                    $error = "Error deleting system: " . $e->getMessage();
                }
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
        ORDER BY CAST(SUBSTRING(s.system_code, 2) AS UNSIGNED)
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT s.*, b.name as branch_name, e.full_name as employee_name 
        FROM systems s 
        LEFT JOIN branches b ON s.branch_id = b.id 
        LEFT JOIN employees e ON s.assigned_to = e.id 
        WHERE s.branch_id = ? 
        ORDER BY CAST(SUBSTRING(s.system_code, 2) AS UNSIGNED)
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
                    <table class="table table-bordered data-table" width="100%" cellspacing="0" style="min-height: 120px;">
                        <thead>
                            <tr>
                                <th style="width: 120px;">System Code</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th style="width: 150px;">Branch</th>
                                <?php endif; ?>
                                <th style="width: 100px;">Type</th>
                                <th style="width: 250px;">Configuration</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Assigned To</th>
                                <th style="width: 120px; min-width: 120px;">Actions</th>
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
                                    <td style="vertical-align: middle; line-height: 1.4; font-size: 0.9em; padding: 12px 8px; min-height: 100px;">
                                        <div style="margin-bottom: 4px;">
                                            <strong>CPU:</strong> <?php echo htmlspecialchars($system['cpu']); ?>
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <strong>RAM:</strong> <?php echo htmlspecialchars($system['ram']); ?>
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <strong>Storage:</strong> <?php echo htmlspecialchars($system['storage']); ?>
                                        </div>
                                        <div style="margin-bottom: 4px;">
                                            <strong>OS:</strong> <?php echo htmlspecialchars($system['os']); ?>
                                        </div>
                                        <div style="margin-bottom: 0;">
                                            <strong>Peripherals:</strong> 
                                            <?php if (!empty($system['peripherals'])): ?>
                                                <?php echo htmlspecialchars($system['peripherals']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">None</span>
                                            <?php endif; ?>
                                        </div>
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
                                    <td style="vertical-align: middle; line-height: 1.3; font-size: 0.9em;">
                                        <?php if ($system['employee_name']): ?>
                                            <div><?php echo htmlspecialchars($system['employee_name']); ?></div>
                                            <div class="text-muted" style="font-size: 0.8em;">Since <?php echo date('M j, Y', strtotime($system['assigned_date'])); ?></div>
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
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-desktop me-2"></i>Add New System
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="system_code" class="form-label">System Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="system_code" name="system_code" value="C" required>
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
                                <select class="form-select" id="cpu" name="cpu">
                                    <option value="">Select CPU</option>
                                    <option value="Intel Core i3">Intel Core i3</option>
                                    <option value="Intel Core i5">Intel Core i5</option>
                                    <option value="Intel Core i7">Intel Core i7</option>
                                    <option value="Intel Core i9">Intel Core i9</option>
                                    <option value="AMD Ryzen 3">AMD Ryzen 3</option>
                                    <option value="AMD Ryzen 5">AMD Ryzen 5</option>
                                    <option value="AMD Ryzen 7">AMD Ryzen 7</option>
                                    <option value="AMD Ryzen 9">AMD Ryzen 9</option>
                                    <option value="Intel Xeon">Intel Xeon</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ram" class="form-label">RAM</label>
                                <select class="form-select" id="ram" name="ram">
                                    <option value="">Select RAM</option>
                                    <option value="4GB">4GB</option>
                                    <option value="8GB">8GB</option>
                                    <option value="16GB">16GB</option>
                                    <option value="32GB">32GB</option>
                                    <option value="64GB">64GB</option>
                                    <option value="128GB">128GB</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="storage" class="form-label">Storage</label>
                                <select class="form-select" id="storage" name="storage">
                                    <option value="">Select Storage</option>
                                    <option value="128GB SSD">128GB SSD</option>
                                    <option value="256GB SSD">256GB SSD</option>
                                    <option value="512GB SSD">512GB SSD</option>
                                    <option value="1TB SSD">1TB SSD</option>
                                    <option value="2TB SSD">2TB SSD</option>
                                    <option value="500GB HDD">500GB HDD</option>
                                    <option value="1TB HDD">1TB HDD</option>
                                    <option value="2TB HDD">2TB HDD</option>
                                    <option value="1TB HDD + 256GB SSD">1TB HDD + 256GB SSD</option>
                                    <option value="2TB HDD + 512GB SSD">2TB HDD + 512GB SSD</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="os" class="form-label">Operating System</label>
                                <select class="form-select" id="os" name="os">
                                    <option value="">Select OS</option>
                                    <option value="Windows 10">Windows 10</option>
                                    <option value="Windows 11">Windows 11</option>
                                    <option value="Windows Server 2019">Windows Server 2019</option>
                                    <option value="Windows Server 2022">Windows Server 2022</option>
                                    <option value="Ubuntu 20.04 LTS">Ubuntu 20.04 LTS</option>
                                    <option value="Ubuntu 22.04 LTS">Ubuntu 22.04 LTS</option>
                                    <option value="CentOS 7">CentOS 7</option>
                                    <option value="CentOS 8">CentOS 8</option>
                                    <option value="macOS Monterey">macOS Monterey</option>
                                    <option value="macOS Ventura">macOS Ventura</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">Assign to Employee</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">No Assignment</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['id']; ?>"><?php echo htmlspecialchars($employee['full_name'] . ' (' . $employee['employee_id'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Leave empty to add system without assignment</small>
                    </div>
                </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Peripherals Configuration</label>
                                <div class="peripheral-config-container">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="peripheral_device" class="form-label">Device Type</label>
                                            <select class="form-select" id="peripheral_device">
                                                <option value="">Select Device</option>
                                                <option value="Keyboard">Keyboard</option>
                                                <option value="Mouse">Mouse</option>
                                                <option value="Monitor">Monitor</option>
                                                <option value="Printer">Printer</option>
                                                <option value="Scanner">Scanner</option>
                                                <option value="Webcam">Webcam</option>
                                                <option value="Speakers">Speakers</option>
                                                <option value="Headset">Headset</option>
                                                <option value="UPS">UPS</option>
                                                <option value="Router">Router</option>
                                                <option value="Switch">Switch</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="peripheral_quantity" class="form-label">Quantity</label>
                                            <select class="form-select" id="peripheral_quantity">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                                <option value="6">6</option>
                                                <option value="7">7</option>
                                                <option value="8">8</option>
                                                <option value="9">9</option>
                                                <option value="10">10</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Model/Brand</label>
                                            <div id="model_inputs_container">
                                                <input type="text" class="form-control mb-2" id="peripheral_model_1" placeholder="e.g., Logitech MX Master 3">
                                            </div>
                                            <small class="form-text text-muted">Enter model or brand name for each device</small>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPeripheral()">
                                                <i class="fas fa-plus me-1"></i>Add Device
                                            </button>
                                        </div>
                                    </div>
                                    <div id="selected_peripherals" class="selected-peripherals">
                                        <div class="text-muted small">No peripherals selected</div>
                                    </div>
                                    <input type="hidden" name="peripherals" id="peripherals_input">
                                </div>
                                <small class="form-text text-muted">Select device type and quantity, then click "Add Device" to include peripherals</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none; box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);">
                        <i class="fas fa-plus me-2"></i>Add System
                    </button>
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
                                <select class="form-select" id="edit_cpu" name="cpu">
                                    <option value="">Select CPU</option>
                                    <option value="Intel Core i3">Intel Core i3</option>
                                    <option value="Intel Core i5">Intel Core i5</option>
                                    <option value="Intel Core i7">Intel Core i7</option>
                                    <option value="Intel Core i9">Intel Core i9</option>
                                    <option value="AMD Ryzen 3">AMD Ryzen 3</option>
                                    <option value="AMD Ryzen 5">AMD Ryzen 5</option>
                                    <option value="AMD Ryzen 7">AMD Ryzen 7</option>
                                    <option value="AMD Ryzen 9">AMD Ryzen 9</option>
                                    <option value="Intel Xeon">Intel Xeon</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_ram" class="form-label">RAM</label>
                                <select class="form-select" id="edit_ram" name="ram">
                                    <option value="">Select RAM</option>
                                    <option value="4GB">4GB</option>
                                    <option value="8GB">8GB</option>
                                    <option value="16GB">16GB</option>
                                    <option value="32GB">32GB</option>
                                    <option value="64GB">64GB</option>
                                    <option value="128GB">128GB</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_storage" class="form-label">Storage</label>
                                <select class="form-select" id="edit_storage" name="storage">
                                    <option value="">Select Storage</option>
                                    <option value="128GB SSD">128GB SSD</option>
                                    <option value="256GB SSD">256GB SSD</option>
                                    <option value="512GB SSD">512GB SSD</option>
                                    <option value="1TB SSD">1TB SSD</option>
                                    <option value="2TB SSD">2TB SSD</option>
                                    <option value="500GB HDD">500GB HDD</option>
                                    <option value="1TB HDD">1TB HDD</option>
                                    <option value="2TB HDD">2TB HDD</option>
                                    <option value="1TB HDD + 256GB SSD">1TB HDD + 256GB SSD</option>
                                    <option value="2TB HDD + 512GB SSD">2TB HDD + 512GB SSD</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_os" class="form-label">Operating System</label>
                                <select class="form-select" id="edit_os" name="os">
                                    <option value="">Select OS</option>
                                    <option value="Windows 10">Windows 10</option>
                                    <option value="Windows 11">Windows 11</option>
                                    <option value="Windows Server 2019">Windows Server 2019</option>
                                    <option value="Windows Server 2022">Windows Server 2022</option>
                                    <option value="Ubuntu 20.04 LTS">Ubuntu 20.04 LTS</option>
                                    <option value="Ubuntu 22.04 LTS">Ubuntu 22.04 LTS</option>
                                    <option value="CentOS 7">CentOS 7</option>
                                    <option value="CentOS 8">CentOS 8</option>
                                    <option value="macOS Monterey">macOS Monterey</option>
                                    <option value="macOS Ventura">macOS Ventura</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Peripherals Configuration</label>
                                <div class="peripheral-config-container">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="edit_peripheral_device" class="form-label">Device Type</label>
                                            <select class="form-select" id="edit_peripheral_device">
                                                <option value="">Select Device</option>
                                                <option value="Keyboard">Keyboard</option>
                                                <option value="Mouse">Mouse</option>
                                                <option value="Monitor">Monitor</option>
                                                <option value="Printer">Printer</option>
                                                <option value="Scanner">Scanner</option>
                                                <option value="Webcam">Webcam</option>
                                                <option value="Speakers">Speakers</option>
                                                <option value="Headset">Headset</option>
                                                <option value="UPS">UPS</option>
                                                <option value="Router">Router</option>
                                                <option value="Switch">Switch</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="edit_peripheral_quantity" class="form-label">Quantity</label>
                                            <select class="form-select" id="edit_peripheral_quantity">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                                <option value="6">6</option>
                                                <option value="7">7</option>
                                                <option value="8">8</option>
                                                <option value="9">9</option>
                                                <option value="10">10</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Model/Brand</label>
                                            <div id="edit_model_inputs_container">
                                                <input type="text" class="form-control mb-2" id="edit_peripheral_model_1" placeholder="e.g., Logitech MX Master 3">
                                            </div>
                                            <small class="form-text text-muted">Enter model or brand name for each device</small>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addEditPeripheral()">
                                                <i class="fas fa-plus me-1"></i>Add Device
                                            </button>
                                        </div>
                                    </div>
                                    <div id="edit_selected_peripherals" class="selected-peripherals">
                                        <div class="text-muted small">No peripherals selected</div>
                                    </div>
                                    <input type="hidden" name="peripherals" id="edit_peripherals_input">
                                </div>
                                <small class="form-text text-muted">Select device type and quantity, then click "Add Device" to include peripherals</small>
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
    
    // Clear editSelectedPeripherals array
    editSelectedPeripherals = [];
    
    // Reset edit model inputs
    updateEditModelInputs();
    
    if (system.peripherals) {
        // Parse peripherals with model information
        const peripheralEntries = system.peripherals.split(',');
        peripheralEntries.forEach(entry => {
            const trimmed = entry.trim();
            // Check if it contains model info (format: "Device - Model (quantity)")
            if (trimmed.includes(' - ') && trimmed.includes('(')) {
                const match = trimmed.match(/^(.+?)\s*-\s*(.+?)\s*\((\d+)\)$/);
                if (match) {
                    const device = match[1].trim();
                    const model = match[2].trim();
                    const quantity = parseInt(match[3]);
                    
                    // Add to editSelectedPeripherals array for display
                    editSelectedPeripherals.push({
                        device: device,
                        model: model,
                        quantity: 1,
                        deviceKey: `${device} - ${model} - ${quantity}`,
                        deviceNumber: quantity
                    });
                }
            } else if (trimmed.includes('(')) {
                // Format: "Device (quantity)"
                const match = trimmed.match(/^(.+?)\s*\((\d+)\)$/);
                if (match) {
                    const device = match[1].trim();
                    const quantity = parseInt(match[2]);
                    
                    editSelectedPeripherals.push({
                        device: device,
                        model: '',
                        quantity: 1,
                        deviceKey: `${device} - ${quantity}`,
                        deviceNumber: quantity
                    });
                }
            }
        });
        
        // Update edit peripheral display
        updateEditPeripheralDisplay();
        updateEditPeripheralInput();
    }
    
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

// Peripheral management
let selectedPeripherals = [];
let editSelectedPeripherals = [];

// Function to update model input boxes based on quantity
function updateModelInputs() {
    const quantity = parseInt(document.getElementById('peripheral_quantity').value);
    const container = document.getElementById('model_inputs_container');
    
    // Clear existing inputs
    container.innerHTML = '';
    
    // Create input boxes based on quantity
    for (let i = 1; i <= quantity; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control mb-2';
        input.id = `peripheral_model_${i}`;
        input.placeholder = `Model ${i} (e.g., Logitech MX Master 3)`;
        container.appendChild(input);
    }
}

// Function to update edit model input boxes based on quantity
function updateEditModelInputs() {
    const quantity = parseInt(document.getElementById('edit_peripheral_quantity').value);
    const container = document.getElementById('edit_model_inputs_container');
    
    // Clear existing inputs
    container.innerHTML = '';
    
    // Create input boxes based on quantity
    for (let i = 1; i <= quantity; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control mb-2';
        input.id = `edit_peripheral_model_${i}`;
        input.placeholder = `Model ${i} (e.g., Logitech MX Master 3)`;
        container.appendChild(input);
    }
}

function addPeripheral() {
    const device = document.getElementById('peripheral_device').value;
    const quantity = parseInt(document.getElementById('peripheral_quantity').value);
    
    if (!device) {
        alert('Please select a device type');
        return;
    }
    
    // Collect all model inputs
    const models = [];
    for (let i = 1; i <= quantity; i++) {
        const modelInput = document.getElementById(`peripheral_model_${i}`);
        const model = modelInput ? modelInput.value.trim() : '';
        models.push(model || `Model ${i}`);
    }
    
    // Create individual peripheral entries for each model with sequential numbering
    models.forEach((model, index) => {
        const deviceKey = `${device} - ${model} - ${index + 1}`;
        
        selectedPeripherals.push({ 
            device: device, 
            model: model,
            quantity: 1,
            deviceKey: deviceKey,
            deviceNumber: index + 1 // Add device number for display
        });
    });
    
    // Clear the form fields
    document.getElementById('peripheral_device').value = '';
    document.getElementById('peripheral_quantity').value = '1';
    updateModelInputs(); // Reset model inputs
    
    updatePeripheralDisplay();
    updatePeripheralInput();
}

function removePeripheral(deviceKey) {
    selectedPeripherals = selectedPeripherals.filter(p => p.deviceKey !== deviceKey);
    updatePeripheralDisplay();
    updatePeripheralInput();
}

function updatePeripheralDisplay() {
    const container = document.getElementById('selected_peripherals');
    
    if (selectedPeripherals.length === 0) {
        container.innerHTML = '<div class="text-muted small">No peripherals selected</div>';
        return;
    }
    
    let html = '<div class="row">';
    selectedPeripherals.forEach(peripheral => {
        const displayText = peripheral.model ? 
            `${peripheral.device} - ${peripheral.model} (${peripheral.deviceNumber || peripheral.quantity})` : 
            `${peripheral.device} (${peripheral.deviceNumber || peripheral.quantity})`;
        
        html += `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center p-2 border rounded" style="background: white;">
                    <div>
                        <span class="fw-medium">${displayText}</span>
                        ${peripheral.model ? `<br><small class="text-muted">Model: ${peripheral.model}</small>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePeripheral('${peripheral.deviceKey}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function updatePeripheralInput() {
    const peripheralString = selectedPeripherals.map(p => {
        if (p.model) {
            return `${p.device} - ${p.model} (${p.deviceNumber || p.quantity})`;
        } else {
            return `${p.device} (${p.deviceNumber || p.quantity})`;
        }
    }).join(', ');
    document.getElementById('peripherals_input').value = peripheralString;
}

// Edit modal peripheral management
function addEditPeripheral() {
    const device = document.getElementById('edit_peripheral_device').value;
    const quantity = parseInt(document.getElementById('edit_peripheral_quantity').value);
    
    if (!device) {
        alert('Please select a device type');
        return;
    }
    
    // Collect all model inputs
    const models = [];
    for (let i = 1; i <= quantity; i++) {
        const modelInput = document.getElementById(`edit_peripheral_model_${i}`);
        const model = modelInput ? modelInput.value.trim() : '';
        models.push(model || `Model ${i}`);
    }
    
    // Create individual peripheral entries for each model with sequential numbering
    models.forEach((model, index) => {
        const deviceKey = `${device} - ${model} - ${index + 1}`;
        
        editSelectedPeripherals.push({ 
            device: device, 
            model: model,
            quantity: 1,
            deviceKey: deviceKey,
            deviceNumber: index + 1 // Add device number for display
        });
    });
    
    // Clear the form fields
    document.getElementById('edit_peripheral_device').value = '';
    document.getElementById('edit_peripheral_quantity').value = '1';
    updateEditModelInputs(); // Reset model inputs
    
    updateEditPeripheralDisplay();
    updateEditPeripheralInput();
}

function removeEditPeripheral(deviceKey) {
    editSelectedPeripherals = editSelectedPeripherals.filter(p => p.deviceKey !== deviceKey);
    updateEditPeripheralDisplay();
    updateEditPeripheralInput();
}

function updateEditPeripheralDisplay() {
    const container = document.getElementById('edit_selected_peripherals');
    
    if (editSelectedPeripherals.length === 0) {
        container.innerHTML = '<div class="text-muted small">No peripherals selected</div>';
        return;
    }
    
    let html = '<div class="row">';
    editSelectedPeripherals.forEach(peripheral => {
        const displayText = peripheral.model ? 
            `${peripheral.device} - ${peripheral.model} (${peripheral.deviceNumber || peripheral.quantity})` : 
            `${peripheral.device} (${peripheral.deviceNumber || peripheral.quantity})`;
        
        html += `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center p-2 border rounded" style="background: white;">
                    <div>
                        <span class="fw-medium">${displayText}</span>
                        ${peripheral.model ? `<br><small class="text-muted">Model: ${peripheral.model}</small>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEditPeripheral('${peripheral.deviceKey}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function updateEditPeripheralInput() {
    const peripheralString = editSelectedPeripherals.map(p => {
        if (p.model) {
            return `${p.device} - ${p.model} (${p.deviceNumber || p.quantity})`;
        } else {
            return `${p.device} (${p.deviceNumber || p.quantity})`;
        }
    }).join(', ');
    document.getElementById('edit_peripherals_input').value = peripheralString;
}

// Clear form fields when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for quantity changes
    document.getElementById('peripheral_quantity').addEventListener('change', updateModelInputs);
    document.getElementById('edit_peripheral_quantity').addEventListener('change', updateEditModelInputs);
    
    // Initialize model inputs
    updateModelInputs();
    updateEditModelInputs();
    // Clear Add System Modal
    const addModal = document.getElementById('addSystemModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addSystemModal').querySelector('form').reset();
        // Reset system code to default "C"
        document.getElementById('system_code').value = 'C';
        // Clear peripherals
        selectedPeripherals = [];
        updatePeripheralDisplay();
        updatePeripheralInput();
        // Reset model inputs
        updateModelInputs();
    });
    
    // Clear Edit System Modal
    const editModal = document.getElementById('editSystemModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editSystemModal').querySelector('form').reset();
        // Clear edit peripherals
        editSelectedPeripherals = [];
        updateEditPeripheralDisplay();
        updateEditPeripheralInput();
        // Reset edit model inputs
        updateEditModelInputs();
    });
    
    // Clear Assign System Modal
    const assignModal = document.getElementById('assignSystemModal');
    assignModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('assignSystemModal').querySelector('form').reset();
    });
});
</script>

<style>
/* Peripheral Configuration Styling */
.peripheral-config-container {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.peripheral-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
}

.peripheral-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.peripheral-list {
    max-height: 200px;
    overflow-y: auto;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

/* Dark Mode Peripheral Configuration */
[data-theme="dark"] .peripheral-config-container {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
}

[data-theme="dark"] .peripheral-item {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .peripheral-item:hover {
    background: var(--card-hover);
    border-color: var(--primary-color);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .peripheral-item .btn-sm {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

[data-theme="dark"] .peripheral-item .btn-sm:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
}

[data-theme="dark"] .modal-footer {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border-top: 1px solid var(--border-color);
}

[data-theme="dark"] .peripheral-config-container .form-label {
    color: var(--text-color);
}

[data-theme="dark"] .peripheral-config-container .form-text {
    color: var(--text-muted);
}

[data-theme="dark"] .peripheral-config-container .text-muted {
    color: var(--text-muted);
}

[data-theme="dark"] .peripheral-config-container .btn-outline-primary {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

[data-theme="dark"] .peripheral-config-container .btn-outline-primary:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

[data-theme="dark"] .peripheral-config-container .btn-outline-secondary {
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .peripheral-config-container .btn-outline-secondary:hover {
    background-color: var(--card-hover);
    border-color: var(--border-color);
    color: var(--text-color);
}
</style>

<?php include 'includes/footer.php'; ?>


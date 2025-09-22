<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'Employee Management';
$pdo = getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $employeeId = trim($_POST['employee_id']);
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $department = trim($_POST['department']);
                $position = trim($_POST['position']);
                $branchId = isSuperAdmin() ? $_POST['branch_id'] : getCurrentUserBranch();
                
                if (!empty($employeeId) && !empty($fullName) && !empty($email) && !empty($branchId)) {
                    // Check if employee ID already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ?");
                    $stmt->execute([$employeeId]);
                    if ($stmt->fetchColumn() == 0) {
                        // Get the next sequential ID
                        $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM employees");
                        $result = $stmt->fetch();
                        $next_id = $result['next_id'];
                        
                        // Insert with the next sequential ID
                        $stmt = $pdo->prepare("INSERT INTO employees (id, employee_id, full_name, email, phone, department, position, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$next_id, $employeeId, $fullName, $email, $phone, $department, $position, $branchId]);
                        
                        // Log the employee addition activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'employee_add',
                            $next_id,
                            $employeeId,
                            'New employee ' . $fullName . ' (' . $employeeId . ') added',
                            $branchId
                        ]);
                        
                        $success = "Employee added successfully";
                    } else {
                        $error = "Employee ID already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $employeeId = trim($_POST['employee_id']);
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $department = trim($_POST['department']);
                $position = trim($_POST['position']);
                $branchId = isSuperAdmin() ? $_POST['branch_id'] : getCurrentUserBranch();
                
                if (!empty($employeeId) && !empty($fullName) && !empty($email) && !empty($branchId)) {
                    // Check if employee ID already exists (excluding current employee)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ? AND id != ?");
                    $stmt->execute([$employeeId, $id]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $pdo->prepare("UPDATE employees SET employee_id = ?, full_name = ?, email = ?, phone = ?, department = ?, position = ?, branch_id = ? WHERE id = ?");
                        $stmt->execute([$employeeId, $fullName, $email, $phone, $department, $position, $branchId, $id]);
                        
                        // Log the employee edit activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'employee_edit',
                            $id,
                            $employeeId,
                            'Employee ' . $fullName . ' (' . $employeeId . ') updated',
                            $branchId
                        ]);
                        
                        $success = "Employee updated successfully";
                    } else {
                        $error = "Employee ID already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get employee info before deletion for logging
                $stmt = $pdo->prepare("SELECT employee_id, full_name, branch_id FROM employees WHERE id = ?");
                $stmt->execute([$id]);
                $employeeInfo = $stmt->fetch();
                
                if ($employeeInfo) {
                    // Log the deletion activity
                    $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        'employee_delete',
                        $id,
                        $employeeInfo['employee_id'],
                        'Employee ' . $employeeInfo['full_name'] . ' (' . $employeeInfo['employee_id'] . ') deleted',
                        $employeeInfo['branch_id']
                    ]);
                }
                
                // First, delete the employee
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$id]);
                
                // Then reorder the remaining employees to fill the gap
                // Step 1: Get all remaining employees ordered by current ID
                $stmt = $pdo->query("SELECT id, employee_id, full_name, email, phone, department, position, branch_id FROM employees ORDER BY id");
                $remaining_employees = $stmt->fetchAll();
                
                // Step 2: Update each employee with new sequential ID
                $new_id = 1;
                foreach ($remaining_employees as $employee) {
                    if ($employee['id'] != $new_id) {
                        $stmt = $pdo->prepare("UPDATE employees SET id = ? WHERE id = ?");
                        $stmt->execute([$new_id, $employee['id']]);
                    }
                    $new_id++;
                }
                
                $success = "Employee deleted successfully";
                break;
        }
    }
}

// Get employees based on user role
if (isSuperAdmin()) {
    $stmt = $pdo->query("
        SELECT e.*, b.name as branch_name, 
               COUNT(s.id) as assigned_systems
        FROM employees e 
        LEFT JOIN branches b ON e.branch_id = b.id 
        LEFT JOIN systems s ON e.id = s.assigned_to 
        GROUP BY e.id
        ORDER BY e.branch_id, e.full_name
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT e.*, b.name as branch_name, 
               COUNT(s.id) as assigned_systems
        FROM employees e 
        LEFT JOIN branches b ON e.branch_id = b.id 
        LEFT JOIN systems s ON e.id = s.assigned_to 
        WHERE e.branch_id = ? 
        GROUP BY e.id
        ORDER BY e.full_name
    ");
    $stmt->execute([getCurrentUserBranch()]);
}
$employees = $stmt->fetchAll();

// Get branches for dropdown (Super Admin only)
$branches = [];
if (isSuperAdmin()) {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY name");
    $branches = $stmt->fetchAll();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user-tie me-3"></i>Employee Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Employees</li>
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

        <!-- Add Employee Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus me-2"></i>Add New Employee
                </button>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>All Employees
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0" style="min-height: 60px;">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Employee ID</th>
                                <th style="width: 150px;">Full Name</th>
                                <th style="width: 180px;">Email</th>
                                <th style="width: 150px;">Phone</th>
                                <th style="width: 120px;">Department</th>
                                <th style="width: 220px;">Position</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th style="width: 120px;">Branch</th>
                                <?php endif; ?>
                                <th style="width: 100px;">Systems</th>
                                <th style="width: 120px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                    <td style="vertical-align: middle; line-height: 1.2; font-size: 0.9em;">
                                        <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" style="text-decoration: none; color: #0d6efd;">
                                            <?php 
                                            $email = htmlspecialchars($employee['email']);
                                            $emailParts = explode('@', $email);
                                            echo $emailParts[0] . '<br>@' . $emailParts[1];
                                            ?>
                                        </a>
                                    </td>
                                    <td style="vertical-align: middle; line-height: 1.4;">
                                        <?php if ($employee['phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>">
                                                <?php echo htmlspecialchars($employee['phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span title="<?php echo htmlspecialchars($employee['department'] ?: '-'); ?>">
                                            <?php echo strlen($employee['department']) > 12 ? substr(htmlspecialchars($employee['department'] ?: '-'), 0, 12) . '...' : htmlspecialchars($employee['department'] ?: '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($employee['position'] ?: '-'); ?>
                                    </td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($employee['branch_name']); ?></td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?php echo $employee['assigned_systems']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)"
                                                    title="Edit Employee">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['full_name']); ?>')"
                                                    title="Delete Employee">
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus me-2"></i>Add New Employee
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" value="EMP" required>
                                <small class="form-text text-muted">e.g., EMP001, EMP002</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
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
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">HR</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="Research & Development">Research & Development</option>
                                    <option value="Quality Assurance">Quality Assurance</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <select class="form-select" id="position" name="position">
                                    <option value="">Select Position</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Senior Developer">Senior Developer</option>
                                    <option value="Developer">Developer</option>
                                    <option value="Junior Developer">Junior Developer</option>
                                    <option value="System Administrator">System Administrator</option>
                                    <option value="Network Engineer">Network Engineer</option>
                                    <option value="Database Administrator">Database Administrator</option>
                                    <option value="IT Support">IT Support</option>
                                    <option value="HR Manager">HR Manager</option>
                                    <option value="HR Specialist">HR Specialist</option>
                                    <option value="Accountant">Accountant</option>
                                    <option value="Financial Analyst">Financial Analyst</option>
                                    <option value="Marketing Manager">Marketing Manager</option>
                                    <option value="Marketing Specialist">Marketing Specialist</option>
                                    <option value="Sales Manager">Sales Manager</option>
                                    <option value="Sales Representative">Sales Representative</option>
                                    <option value="Operations Manager">Operations Manager</option>
                                    <option value="Administrative Assistant">Administrative Assistant</option>
                                    <option value="Customer Service Representative">Customer Service Representative</option>
                                    <option value="Research Analyst">Research Analyst</option>
                                    <option value="Quality Assurance Specialist">Quality Assurance Specialist</option>
                                    <option value="Intern">Intern</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none; box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);">
                        <i class="fas fa-user-plus me-2"></i>Add Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Employee
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
                                <label for="edit_employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_employee_id" name="employee_id" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="edit_phone" name="phone">
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
                                <label for="edit_department" class="form-label">Department</label>
                                <select class="form-select" id="edit_department" name="department">
                                    <option value="">Select Department</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">HR</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Operations">Operations</option>
                                    <option value="Administration">Administration</option>
                                    <option value="Customer Service">Customer Service</option>
                                    <option value="Research & Development">Research & Development</option>
                                    <option value="Quality Assurance">Quality Assurance</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_position" class="form-label">Position</label>
                                <select class="form-select" id="edit_position" name="position">
                                    <option value="">Select Position</option>
                                    <option value="Manager">Manager</option>
                                    <option value="Senior Developer">Senior Developer</option>
                                    <option value="Developer">Developer</option>
                                    <option value="Junior Developer">Junior Developer</option>
                                    <option value="System Administrator">System Administrator</option>
                                    <option value="Network Engineer">Network Engineer</option>
                                    <option value="Database Administrator">Database Administrator</option>
                                    <option value="IT Support">IT Support</option>
                                    <option value="HR Manager">HR Manager</option>
                                    <option value="HR Specialist">HR Specialist</option>
                                    <option value="Accountant">Accountant</option>
                                    <option value="Financial Analyst">Financial Analyst</option>
                                    <option value="Marketing Manager">Marketing Manager</option>
                                    <option value="Marketing Specialist">Marketing Specialist</option>
                                    <option value="Sales Manager">Sales Manager</option>
                                    <option value="Sales Representative">Sales Representative</option>
                                    <option value="Operations Manager">Operations Manager</option>
                                    <option value="Administrative Assistant">Administrative Assistant</option>
                                    <option value="Customer Service Representative">Customer Service Representative</option>
                                    <option value="Research Analyst">Research Analyst</option>
                                    <option value="Quality Assurance Specialist">Quality Assurance Specialist</option>
                                    <option value="Intern">Intern</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Employee Modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
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
                    <p>Are you sure you want to delete the employee <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editEmployee(employee) {
    document.getElementById('edit_id').value = employee.id;
    document.getElementById('edit_employee_id').value = employee.employee_id;
    document.getElementById('edit_full_name').value = employee.full_name;
    document.getElementById('edit_email').value = employee.email;
    document.getElementById('edit_phone').value = employee.phone || '';
    document.getElementById('edit_department').value = employee.department || '';
    document.getElementById('edit_position').value = employee.position || '';
    
    <?php if (isSuperAdmin()): ?>
        document.getElementById('edit_branch_id').value = employee.branch_id;
    <?php endif; ?>
    
    const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    editModal.show();
}

function deleteEmployee(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
    deleteModal.show();
}

// Clear form fields when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    // Clear Add Employee Modal
    const addModal = document.getElementById('addEmployeeModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addEmployeeModal').querySelector('form').reset();
        // Reset employee ID to default "EMP"
        document.getElementById('employee_id').value = 'EMP';
    });
    
    // Clear Edit Employee Modal
    const editModal = document.getElementById('editEmployeeModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editEmployeeModal').querySelector('form').reset();
    });
});
</script>

<style>
/* Modal Dark Mode Styling */
.modal-footer {
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

[data-theme="dark"] .modal-content {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .modal-header {
    background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
    border-color: var(--border-color);
}

[data-theme="dark"] .modal-body {
    background: transparent;
    color: var(--text-color);
}

[data-theme="dark"] .modal-footer {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border-top: 1px solid var(--border-color);
}

[data-theme="dark"] .modal-title {
    color: white;
}

[data-theme="dark"] .btn-close {
    filter: invert(1);
}

[data-theme="dark"] .form-label {
    color: var(--text-color);
}

[data-theme="dark"] .form-text {
    color: var(--text-muted);
}

[data-theme="dark"] .text-muted {
    color: var(--text-muted);
}

[data-theme="dark"] .btn-outline-secondary {
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .btn-outline-secondary:hover {
    background-color: var(--card-hover);
    border-color: var(--border-color);
    color: var(--text-color);
}
</style>

<?php include 'includes/footer.php'; ?>

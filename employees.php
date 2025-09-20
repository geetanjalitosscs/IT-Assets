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
                        $success = "Employee added successfully with ID: " . $next_id;
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
                        $success = "Employee updated successfully!";
                    } else {
                        $error = "Employee ID already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
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
                
                $success = "Employee deleted successfully and IDs reordered!";
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
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Position</th>
                                <?php if (isSuperAdmin()): ?>
                                    <th>Branch</th>
                                <?php endif; ?>
                                <th>Assigned Systems</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>">
                                            <?php echo htmlspecialchars($employee['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($employee['phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($employee['phone']); ?>">
                                                <?php echo htmlspecialchars($employee['phone']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($employee['department'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position'] ?: '-'); ?></td>
                                    <?php if (isSuperAdmin()): ?>
                                        <td><?php echo htmlspecialchars($employee['branch_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="badge badge-info"><?php echo $employee['assigned_systems']; ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['full_name']); ?>')">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Employee
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
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
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
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
                                <input type="text" class="form-control" id="edit_department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_position" class="form-label">Position</label>
                                <input type="text" class="form-control" id="edit_position" name="position">
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
</script>

<?php include 'includes/footer.php'; ?>

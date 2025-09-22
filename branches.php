<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    header('Location: branch_dashboard.php');
    exit();
}

$pageTitle = 'Branch Management';
$pdo = getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $location = trim($_POST['location']);
                
                if (!empty($name) && !empty($location)) {
                    // Get the next sequential ID (since we maintain continuous sequence)
                    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM branches");
                    $result = $stmt->fetch();
                    $next_id = $result['next_id'];
                    
                    // Insert with the next sequential ID
                    $stmt = $pdo->prepare("INSERT INTO branches (id, name, location) VALUES (?, ?, ?)");
                    $stmt->execute([$next_id, $name, $location]);
                    
                    // Log the branch addition activity
                    $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        'branch_add',
                        $next_id,
                        $name,
                        'New branch ' . $name . ' (' . $location . ') added',
                        null // Branches don't have a branch_id
                    ]);
                    
                    $success = "Branch added successfully";
                } else {
                    $error = "Please fill in all fields.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $location = trim($_POST['location']);
                
                if (!empty($name) && !empty($location)) {
                    $stmt = $pdo->prepare("UPDATE branches SET name = ?, location = ? WHERE id = ?");
                    $stmt->execute([$name, $location, $id]);
                    
                    // Log the branch edit activity
                    $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        'branch_edit',
                        $id,
                        $name,
                        'Branch ' . $name . ' (' . $location . ') updated',
                        null // Branches don't have a branch_id
                    ]);
                    
                    $success = "Branch updated successfully";
                } else {
                    $error = "Please fill in all fields.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get branch info before deletion for logging
                $stmt = $pdo->prepare("SELECT name, location FROM branches WHERE id = ?");
                $stmt->execute([$id]);
                $branchInfo = $stmt->fetch();
                
                if ($branchInfo) {
                    // Log the deletion activity
                    $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        'branch_delete',
                        $id,
                        $branchInfo['name'],
                        'Branch ' . $branchInfo['name'] . ' (' . $branchInfo['location'] . ') deleted',
                        null // Branches don't have a branch_id
                    ]);
                }
                
                // First, delete the branch
                $stmt = $pdo->prepare("DELETE FROM branches WHERE id = ?");
                $stmt->execute([$id]);
                
                // Then reorder the remaining branches to fill the gap
                // We'll do this in multiple steps to avoid MySQL syntax issues
                
                // Step 1: Get all remaining branches ordered by current ID
                $stmt = $pdo->query("SELECT id, name, location FROM branches ORDER BY id");
                $remaining_branches = $stmt->fetchAll();
                
                // Step 2: Update each branch with new sequential ID
                $new_id = 1;
                foreach ($remaining_branches as $branch) {
                    if ($branch['id'] != $new_id) {
                        $stmt = $pdo->prepare("UPDATE branches SET id = ? WHERE id = ?");
                        $stmt->execute([$new_id, $branch['id']]);
                    }
                    $new_id++;
                }
                
                $success = "Branch deleted successfully";
                break;
        }
    }
}

// Get all branches
$stmt = $pdo->query("SELECT b.*, COUNT(DISTINCT s.id) as system_count, COUNT(DISTINCT e.id) as employee_count FROM branches b LEFT JOIN systems s ON b.id = s.branch_id LEFT JOIN employees e ON b.id = e.branch_id GROUP BY b.id ORDER BY b.name");
$branches = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-building me-3"></i>Branch Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Branches</li>
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

        <!-- Add Branch Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                    <i class="fas fa-plus me-2"></i>Add New Branch
                </button>
            </div>
        </div>

        <!-- Branches Table -->
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>All Branches
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Branch Name</th>
                                <th>Location</th>
                                <th>Systems</th>
                                <th>Employees</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?php echo $branch['id']; ?></td>
                                    <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                    <td><?php echo htmlspecialchars($branch['location']); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $branch['system_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $branch['employee_count']; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($branch['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editBranch(<?php echo htmlspecialchars(json_encode($branch)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="deleteBranch(<?php echo $branch['id']; ?>, '<?php echo htmlspecialchars($branch['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-building me-2"></i>Add New Branch
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="form-text text-muted">Enter the branch name (e.g., New York Office)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" required>
                                <small class="form-text text-muted">Enter the branch location (e.g., New York, NY)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border: none; box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);">
                        <i class="fas fa-building me-2"></i>Add Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal fade" id="editBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Branch Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="edit_location" name="location" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Branch Modal -->
<div class="modal fade" id="deleteBranchModal" tabindex="-1">
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
                    <p>Are you sure you want to delete the branch <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone and will also delete all associated systems and employees.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBranch(branch) {
    document.getElementById('edit_id').value = branch.id;
    document.getElementById('edit_name').value = branch.name;
    document.getElementById('edit_location').value = branch.location;
    
    const editModal = new bootstrap.Modal(document.getElementById('editBranchModal'));
    editModal.show();
}

function deleteBranch(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteBranchModal'));
    deleteModal.show();
}

// Clear form fields when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    // Clear Add Branch Modal
    const addModal = document.getElementById('addBranchModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addBranchModal').querySelector('form').reset();
    });
    
    // Clear Edit Branch Modal
    const editModal = document.getElementById('editBranchModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editBranchModal').querySelector('form').reset();
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

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
                    $success = "Branch added successfully with ID: " . $next_id;
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
                    $success = "Branch updated successfully!";
                } else {
                    $error = "Please fill in all fields.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
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
                
                $success = "Branch deleted successfully and IDs reordered!";
                break;
        }
    }
}

// Get all branches
$stmt = $pdo->query("SELECT b.*, COUNT(s.id) as system_count, COUNT(e.id) as employee_count FROM branches b LEFT JOIN systems s ON b.id = s.branch_id LEFT JOIN employees e ON b.id = e.branch_id GROUP BY b.id ORDER BY b.name");
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Branch Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Branch</button>
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
</script>

<?php include 'includes/footer.php'; ?>

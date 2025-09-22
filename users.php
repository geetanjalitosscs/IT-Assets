<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require super admin access
requireLogin();
if (!isSuperAdmin()) {
    header('Location: branch_dashboard.php');
    exit();
}

$pageTitle = 'User Management';
$pdo = getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $branchId = $role == 'branch_admin' ? $_POST['branch_id'] : null;
                
                if (!empty($username) && !empty($password) && !empty($role) && !empty($fullName) && !empty($email)) {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() == 0) {
                        // Get the next sequential ID
                        $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM users");
                        $result = $stmt->fetch();
                        $next_id = $result['next_id'];
                        
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (id, username, password, role, branch_id, full_name, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$next_id, $username, $hashedPassword, $role, $branchId, $fullName, $email]);
                        
                        // Log the user addition activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'user_add',
                            $next_id,
                            $username,
                            'New user ' . $fullName . ' (' . $username . ') added as ' . $role,
                            $branchId
                        ]);
                        
                        $success = "User added successfully";
                    } else {
                        $error = "Username already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $fullName = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $branchId = $role == 'branch_admin' ? $_POST['branch_id'] : null;
                
                if (!empty($username) && !empty($role) && !empty($fullName) && !empty($email)) {
                    // Check if username already exists (excluding current user)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $id]);
                    if ($stmt->fetchColumn() == 0) {
                        if (!empty($password)) {
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ?, branch_id = ?, full_name = ?, email = ? WHERE id = ?");
                            $stmt->execute([$username, $hashedPassword, $role, $branchId, $fullName, $email, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ?, branch_id = ?, full_name = ?, email = ? WHERE id = ?");
                            $stmt->execute([$username, $role, $branchId, $fullName, $email, $id]);
                        }
                        
                        // Log the user edit activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'user_edit',
                            $id,
                            $username,
                            'User ' . $fullName . ' (' . $username . ') updated',
                            $branchId
                        ]);
                        
                        $success = "User updated successfully";
                    } else {
                        $error = "Username already exists!";
                    }
                } else {
                    $error = "Please fill in all required fields.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                // Don't allow deleting the current user
                if ($id == $_SESSION['user_id']) {
                    $error = "You cannot delete your own account!";
                } else {
                    // Get user info before deletion for logging
                    $stmt = $pdo->prepare("SELECT username, full_name, branch_id FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $userInfo = $stmt->fetch();
                    
                    if ($userInfo) {
                        // Log the deletion activity
                        $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'user_delete',
                            $id,
                            $userInfo['username'],
                            'User ' . $userInfo['full_name'] . ' (' . $userInfo['username'] . ') deleted',
                            $userInfo['branch_id']
                        ]);
                    }
                    
                    // First, delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // Then reorder the remaining users to fill the gap
                    // Step 1: Get all remaining users ordered by current ID
                    $stmt = $pdo->query("SELECT id, username, password, role, branch_id, full_name, email FROM users ORDER BY id");
                    $remaining_users = $stmt->fetchAll();
                    
                    // Step 2: Update each user with new sequential ID
                    $new_id = 1;
                    foreach ($remaining_users as $user) {
                        if ($user['id'] != $new_id) {
                            $stmt = $pdo->prepare("UPDATE users SET id = ? WHERE id = ?");
                            $stmt->execute([$new_id, $user['id']]);
                        }
                        $new_id++;
                    }
                    
                    $success = "User deleted successfully";
                }
                break;
        }
    }
}

// Get all users
$stmt = $pdo->query("
    SELECT u.*, b.name as branch_name 
    FROM users u 
    LEFT JOIN branches b ON u.branch_id = b.id 
    ORDER BY u.role, u.username
");
$users = $stmt->fetchAll();

// Get branches for dropdown
$stmt = $pdo->query("SELECT * FROM branches ORDER BY name");
$branches = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users me-3"></i>User Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
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

        <!-- Add User Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Add New User
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow page-content">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-list me-2"></i>All Users
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $roleClass = $user['role'] == 'super_admin' ? 'badge-danger' : 'badge-info';
                                        $roleText = ucfirst(str_replace('_', ' ', $user['role']));
                                        ?>
                                        <span class="badge <?php echo $roleClass; ?>"><?php echo $roleText; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['branch_name'] ?: '-'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0; border: none;">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
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
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required onchange="toggleBranchField()">
                                    <option value="">Select Role</option>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="branch_admin">Branch Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="branch_field" style="display: none;">
                                <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select" id="branch_id" name="branch_id">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                    <?php endforeach; ?>
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
                        <i class="fas fa-user-plus me-2"></i>Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit User
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
                                <label for="edit_username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_password" class="form-label">Password (Leave blank to keep current)</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
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
                                <label for="edit_role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_role" name="role" required onchange="toggleEditBranchField()">
                                    <option value="super_admin">Super Admin</option>
                                    <option value="branch_admin">Branch Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="edit_branch_field">
                                <label for="edit_branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_branch_id" name="branch_id">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
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
                    <p>Are you sure you want to delete the user <strong id="delete_name"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleBranchField() {
    const role = document.getElementById('role').value;
    const branchField = document.getElementById('branch_field');
    const branchSelect = document.getElementById('branch_id');
    
    if (role === 'branch_admin') {
        branchField.style.display = 'block';
        branchSelect.required = true;
    } else {
        branchField.style.display = 'none';
        branchSelect.required = false;
        branchSelect.value = '';
    }
}

function toggleEditBranchField() {
    const role = document.getElementById('edit_role').value;
    const branchField = document.getElementById('edit_branch_field');
    const branchSelect = document.getElementById('edit_branch_id');
    
    if (role === 'branch_admin') {
        branchField.style.display = 'block';
        branchSelect.required = true;
    } else {
        branchField.style.display = 'none';
        branchSelect.required = false;
        branchSelect.value = '';
    }
}

function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_branch_id').value = user.branch_id || '';
    
    toggleEditBranchField();
    
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
}

function deleteUser(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();
}

// Clear form fields when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    // Clear Add User Modal
    const addModal = document.getElementById('addUserModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addUserModal').querySelector('form').reset();
        // Reset role to default and hide branch field
        document.getElementById('role').value = 'super_admin';
        toggleBranchField();
    });
    
    // Clear Edit User Modal
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editUserModal').querySelector('form').reset();
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

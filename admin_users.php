<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Require login and check if user is branch admin
requireLogin();
if (!isBranchAdmin()) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();
$branchId = getCurrentUserBranch();
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'] ?? 'branch_admin';
        
        if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists';
                } else {
                    // Get the next sequential ID
                    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 as next_id FROM users");
                    $result = $stmt->fetch();
                    $next_id = $result['next_id'];
                    
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (id, username, password, role, branch_id, full_name, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$next_id, $username, $hashedPassword, $role, $branchId, $full_name, $email]);
                    
                    // Log the user addition activity
                    $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        'user_add',
                        $next_id,
                        $username,
                        'New user ' . $full_name . ' (' . $username . ') added as ' . $role,
                        $branchId
                    ]);
                    
                    $message = 'User added successfully with ID: ' . $next_id;
                }
            } catch (Exception $e) {
                $error = 'Error adding user: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        if (empty($username) || empty($full_name) || empty($email)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // Check if username already exists (excluding current user)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ? WHERE id = ? AND branch_id = ?");
                    $stmt->execute([$username, $full_name, $email, $role, $id, $branchId]);
                    $message = 'User updated successfully';
                }
            } catch (Exception $e) {
                $error = 'Error updating user: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            // Get user info before deletion for logging
            $stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ? AND branch_id = ?");
            $stmt->execute([$id, $branchId]);
            $userInfo = $stmt->fetch();
            
            if ($userInfo) {
                // Log the deletion activity
                $stmt = $pdo->prepare("INSERT INTO activity_log (activity_type, entity_id, entity_name, description, branch_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    'user_delete',
                    $id,
                    $userInfo['username'],
                    'User ' . $userInfo['full_name'] . ' (' . $userInfo['username'] . ') deleted',
                    $branchId
                ]);
            }
            
            // First, delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND branch_id = ? AND id != ?");
            $stmt->execute([$id, $branchId, $_SESSION['user_id']]);
            
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
            
            $message = 'User deleted successfully and IDs reordered';
        } catch (Exception $e) {
            $error = 'Error deleting user: ' . $e->getMessage();
        }
    } elseif ($action === 'reset_password') {
        $id = $_POST['id'];
        $new_password = $_POST['new_password'];
        
        if (empty($new_password)) {
            $error = 'Please enter a new password';
        } else {
            try {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND branch_id = ?");
                $stmt->execute([$hashedPassword, $id, $branchId]);
                $message = 'Password reset successfully';
            } catch (Exception $e) {
                $error = 'Error resetting password: ' . $e->getMessage();
            }
        }
    }
}

// Get users for this branch
$stmt = $pdo->prepare("SELECT * FROM users WHERE branch_id = ? ORDER BY created_at DESC");
$stmt->execute([$branchId]);
$users = $stmt->fetchAll();

// Get branch information
$stmt = $pdo->prepare("SELECT * FROM branches WHERE id = ?");
$stmt->execute([$branchId]);
$branch = $stmt->fetch();
?>

<?php include 'includes/header.php'; ?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">User Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active">Users</li>
                    </ol>
                </nav>
            </div>
            <div class="text-end">
                <h5 class="text-muted mb-0"><?php echo htmlspecialchars($branch['name'] ?? 'Branch'); ?></h5>
                <small class="text-muted"><?php echo htmlspecialchars($branch['location'] ?? ''); ?></small>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-white">Branch Users</h6>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal" style="background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); border: none; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);">
                    <i class="fas fa-plus me-2"></i>Add User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['role'] === 'branch_admin' ? 'primary' : 'secondary'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="resetPassword(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-key"></i>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0; border: none;">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <small class="form-text text-muted">Enter a unique username</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Enter a secure password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                                <small class="form-text text-muted">Enter the user's full name</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <small class="form-text text-muted">Enter a valid email address</small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="branch_admin">Branch Admin</option>
                                    <option value="user">User</option>
                                </select>
                                <small class="form-text text-muted">Select the user's access level</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; background: #f8f9fa; border-radius: 0 0 15px 15px;">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role">
                            <option value="branch_admin">Branch Admin</option>
                            <option value="user">User</option>
                        </select>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" id="reset_id">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete user <strong id="delete_username"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
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
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function resetPassword(userId) {
    document.getElementById('reset_id').value = userId;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteUser(userId, username) {
    document.getElementById('delete_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}

// Clear form fields when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    // Clear Add User Modal
    const addModal = document.getElementById('addUserModal');
    addModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('addUserModal').querySelector('form').reset();
        // Reset role to default
        document.getElementById('role').value = 'branch_admin';
    });
    
    // Clear Edit User Modal
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('editUserModal').querySelector('form').reset();
    });
    
    // Clear Reset Password Modal
    const resetModal = document.getElementById('resetPasswordModal');
    resetModal.addEventListener('hidden.bs.modal', function() {
        document.getElementById('resetPasswordModal').querySelector('form').reset();
    });
});
</script>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/footer.php'; ?>

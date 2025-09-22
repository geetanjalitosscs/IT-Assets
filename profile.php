<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'Profile';
$pdo = getFreshConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (!empty($fullName) && !empty($email)) {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid email address.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $phone, $_SESSION['user_id']]);
                
                // Update session
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                
                $success = "Profile updated successfully";
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's branch info
$branchName = '';
if ($user['branch_id']) {
    $stmt = $pdo->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->execute([$user['branch_id']]);
    $branch = $stmt->fetch();
    $branchName = $branch ? $branch['name'] : 'N/A';
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user me-3"></i>Profile
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
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

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-user me-2"></i>Profile Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Branch</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($branchName); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Account Summary -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-info-circle me-2"></i>Account Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            </div>
                            <h5 class="mt-3 mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
                        </div>
                        
                        <div class="account-stats">
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Username:</span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </div>
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Email:</span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Branch:</span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($branchName); ?></span>
                                </div>
                            </div>
                            <div class="stat-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Member Since:</span>
                                    <span class="fw-medium"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                            </a>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                            <a href="systems.php" class="btn btn-outline-success">
                                <i class="fas fa-desktop me-2"></i>Manage Systems
                            </a>
                            <a href="employees.php" class="btn btn-outline-info">
                                <i class="fas fa-user-tie me-2"></i>Manage Employees
                            </a>
                            <a href="reports.php" class="btn btn-outline-warning">
                                <i class="fas fa-chart-bar me-2"></i>View Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-cog me-2"></i>System Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="system-info">
                            <div class="info-item mb-2">
                                <small class="text-muted">Version:</small>
                                <span class="fw-medium">1.0.0</span>
                            </div>
                            <div class="info-item mb-2">
                                <small class="text-muted">Last Login:</small>
                                <span class="fw-medium"><?php echo date('M j, Y g:i A'); ?></span>
                            </div>
                            <div class="info-item mb-2">
                                <small class="text-muted">Session Timeout:</small>
                                <span class="fw-medium">24 hours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    margin-bottom: 1rem;
}

.account-stats .stat-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.account-stats .stat-item:last-child {
    border-bottom: none;
}

.system-info .info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border-radius: 12px 12px 0 0 !important;
    border: none;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.25);
}

.page-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.page-title {
    color: var(--primary-color);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
}

.breadcrumb-item a {
    color: var(--secondary-color);
    text-decoration: none;
}

.breadcrumb-item.active {
    color: #6c757d;
}
</style>

<?php include 'includes/footer.php'; ?>

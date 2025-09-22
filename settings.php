<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'Settings';
$pdo = getFreshConnection();

// Handle success messages from redirects
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if (password_verify($currentPassword, $user['password'])) {
                        if ($newPassword === $confirmPassword) {
                            if (strlen($newPassword) >= 6) {
                                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                                
                                $success = "Password changed successfully";
                            } else {
                                $error = "Password must be at least 6 characters long.";
                            }
                        } else {
                            $error = "New passwords do not match.";
                        }
                    } else {
                        $error = "Current password is incorrect.";
                    }
                } else {
                    $error = "Please fill in all password fields.";
                }
                break;
                
            case 'update_preferences':
                $theme = $_POST['theme'] ?? 'light';
                $notifications = isset($_POST['notifications']) ? 1 : 0;
                $email_alerts = isset($_POST['email_alerts']) ? 1 : 0;
                
                // Store preferences in session
                $_SESSION['theme'] = $theme;
                $_SESSION['notifications'] = $notifications;
                $_SESSION['email_alerts'] = $email_alerts;
                
                $success = "Preferences updated successfully";
                
                // Redirect to prevent form resubmission
                header("Location: settings.php?success=" . urlencode($success));
                exit();
                break;
        }
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-cog me-3"></i>Settings
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="profile.php">Profile</a></li>
                    <li class="breadcrumb-item active">Settings</li>
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
            <!-- Security Settings -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-shield-alt me-2"></i>Security Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">Password must be at least 6 characters long</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preferences -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-sliders-h me-2"></i>Preferences
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_preferences">
                            <div class="mb-3">
                                <label for="theme" class="form-label">Theme</label>
                                <select class="form-select" id="theme" name="theme" onchange="previewTheme(this.value)" onblur="cancelPreview()">
                                    <option value="light" <?php echo ($_SESSION['theme'] ?? 'light') == 'light' ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo ($_SESSION['theme'] ?? 'light') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                    <option value="auto" <?php echo ($_SESSION['theme'] ?? 'light') == 'auto' ? 'selected' : ''; ?>>Auto</option>
                                </select>
                                <small class="form-text text-muted">Select the mode</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notifications" name="notifications" 
                                           <?php echo ($_SESSION['notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications">
                                        Enable Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_alerts" name="email_alerts" 
                                           <?php echo ($_SESSION['email_alerts'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_alerts">
                                        Email Alerts
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Account Information -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-info-circle me-2"></i>Account Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="account-info">
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Username:</span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Role:</span>
                                    <span class="fw-medium"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Email:</span>
                                    <span class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Member Since:</span>
                                    <span class="fw-medium"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-server me-2"></i>System Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="system-info">
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Version:</span>
                                    <span class="fw-medium">1.0.0</span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">PHP Version:</span>
                                    <span class="fw-medium"><?php echo PHP_VERSION; ?></span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Server:</span>
                                    <span class="fw-medium"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Last Login:</span>
                                    <span class="fw-medium"><?php echo date('M j, Y g:i A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow border-danger">
                    <div class="card-header py-3 bg-danger text-white">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">Delete Account</h6>
                                <p class="text-muted mb-0">Permanently delete your account and all associated data. This action cannot be undone.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-danger" disabled>
                                    <i class="fas fa-trash me-2"></i>Delete Account
                                </button>
                                <small class="d-block text-muted mt-1">Contact administrator</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
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

.account-info .info-item, .system-info .info-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.account-info .info-item:last-child, .system-info .info-item:last-child {
    border-bottom: none;
}
</style>

<script>
// Store the original theme for preview functionality
let originalTheme = '<?php echo $_SESSION['theme'] ?? 'light'; ?>';
let isPreviewMode = false;

// Apply theme on page load based on saved preference
document.addEventListener('DOMContentLoaded', function() {
    applyTheme(originalTheme);
});

function previewTheme(theme) {
    // Apply theme for preview
    applyTheme(theme);
    isPreviewMode = true;
    
    // Add visual indicator that this is a preview
    document.body.style.transition = 'all 0.3s ease';
}

function cancelPreview() {
    if (isPreviewMode) {
        // Revert to original theme
        setTimeout(() => {
            applyTheme(originalTheme);
            isPreviewMode = false;
            
            // Reset dropdown to original value
            document.getElementById('theme').value = originalTheme;
        }, 100);
    }
}

// Use the global applyTheme function from header.php

// Handle preferences form submission with multiple fallbacks
document.addEventListener('DOMContentLoaded', function() {
    const preferencesForm = document.querySelector('input[name="action"][value="update_preferences"]').closest('form');
    
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', function(e) {
            const selectedTheme = document.getElementById('theme').value;
            
            // Always apply the selected theme immediately
            if (typeof applyTheme === 'function') {
                applyTheme(selectedTheme);
            } else {
                // Fallback: Direct DOM manipulation
                const html = document.documentElement;
                if (selectedTheme === 'dark') {
                    html.setAttribute('data-theme', 'dark');
                } else if (selectedTheme === 'auto') {
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        html.setAttribute('data-theme', 'dark');
                    } else {
                        html.removeAttribute('data-theme');
                    }
                } else {
                    html.removeAttribute('data-theme');
                }
            }
            
            // Update original theme for preview functionality
            if (isPreviewMode) {
                originalTheme = selectedTheme;
                isPreviewMode = false;
            }
            
            // Additional fallback: Store theme in localStorage as backup
            localStorage.setItem('theme', selectedTheme);
            
            // Show immediate feedback
            console.log('Theme applied:', selectedTheme);
        });
    }
    
    // Additional safety: Apply theme from localStorage on page load if session fails
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme && savedTheme !== originalTheme) {
        setTimeout(() => {
            if (typeof applyTheme === 'function') {
                applyTheme(savedTheme);
            }
        }, 100);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$selectedRole = $_GET['role'] ?? '';

// Validate role parameter
if (!in_array($selectedRole, ['super_admin', 'branch_admin'])) {
    header('Location: index.php');
    exit();
}

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? $selectedRole;
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $pdo = getConnection();
        
        // Query based on selected role
        if ($role === 'super_admin') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'super_admin'");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'branch_admin'");
        }
        
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect based on role
            if ($role === 'super_admin') {
                header('Location: dashboard.php');
            } else {
                header('Location: branch_dashboard.php');
            }
            exit();
        } else {
            // Redirect to prevent error message from persisting on refresh
            header('Location: login.php?role=' . $selectedRole . '&error=1');
            exit();
        }
    }
}

// Check for error parameter and set error message
$error = '';
if (isset($_GET['error']) && $_GET['error'] == '1') {
    $error = 'Invalid username or password';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Management - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #1d4ed8;
            --dark-blue: #1e3a8a;
            --light-blue: #dbeafe;
        }
        
        body {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(30, 64, 175, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(30, 64, 175, 0.05) 0%, transparent 50%);
            z-index: 0;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            overflow: hidden;
            border: none;
            max-width: 420px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            z-index: 1;
        }
        
        .login-header > * {
            position: relative;
            z-index: 2;
        }
        
        .login-header i {
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .login-header h3 {
            font-weight: 700;
            margin: 1rem 0 0.5rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .login-header p {
            font-weight: 500;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 14px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(30, 64, 175, 0.15);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-label i {
            color: var(--primary-color);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 1rem;
            transition: all 0.3s ease;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.4);
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-color) 100%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert-danger {
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .text-muted {
            color: #6b7280 !important;
        }
        
        .text-muted strong {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        /* Animation for card entrance */
        .login-card {
            animation: slideInUp 0.6s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .login-card {
                max-width: 100%;
                margin: 0;
                border-radius: 20px;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .login-header h3 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-card {
                border-radius: 16px;
            }
            
            .login-header {
                padding: 1.5rem 1rem;
            }
            
            .login-body {
                padding: 1.5rem 1rem;
            }
            
            .login-header h3 {
                font-size: 1.25rem;
            }
            
            .login-header i {
                font-size: 2.5rem;
            }
        }
        
        @media (max-height: 600px) {
            body {
                padding: 10px;
            }
            
            .login-header {
                padding: 1.5rem 2rem;
            }
            
            .login-body {
                padding: 1.5rem 2rem;
            }
            
            .login-header h3 {
                font-size: 1.25rem;
                margin: 0.5rem 0;
            }
            
            .login-header i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-laptop-code fa-3x mb-3"></i>
                        <h3>IT Asset Management</h3>
                        <p class="mb-0">
                            <?php if ($selectedRole === 'super_admin'): ?>
                                Sign in as Super Admin
                            <?php else: ?>
                                Sign in as Admin
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($selectedRole); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-login w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <?php if ($selectedRole === 'super_admin'): ?>
                                    <strong>Super Admin:</strong> Super Admin / admin@123
                                <?php else: ?>
                                    <strong>Admin:</strong> Admin / admin@123
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left me-2"></i>Back to Role Selection
                            </a>
                        </div>
                    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-hide error message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.alert-danger');
            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.style.transition = 'opacity 0.5s ease';
                    errorAlert.style.opacity = '0';
                    setTimeout(function() {
                        errorAlert.remove();
                    }, 500);
                }, 5000);
            }
            
            // Clear error message when user starts typing
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            function clearError() {
                const errorAlert = document.querySelector('.alert-danger');
                if (errorAlert) {
                    errorAlert.style.transition = 'opacity 0.3s ease';
                    errorAlert.style.opacity = '0';
                    setTimeout(function() {
                        errorAlert.remove();
                    }, 300);
                }
            }
            
            usernameInput.addEventListener('input', clearError);
            passwordInput.addEventListener('input', clearError);
        });
    </script>
</body>
</html>

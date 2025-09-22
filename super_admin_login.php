<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole();
}

$error = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'super_admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['full_name'] = $user['full_name'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            header('Location: super_admin_login.php?error=1');
            exit();
        }
    }
}

// Check for error parameter and set error message
$error = '';
if (isset($_GET['error']) && $_GET['error'] == '1') {
    $error = 'Invalid Super Admin credentials';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Management - Super Admin Login</title>
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
            transition: background 0.3s ease;
        }
        
        /* Dark mode for login */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #16213e 100%);
        }
        
        [data-theme="dark"] body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(79, 70, 229, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(79, 70, 229, 0.05) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }
        
        [data-theme="dark"] .login-card {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.95) 100%);
            color: #e2e8f0;
            border: 1px solid #334155;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        [data-theme="dark"] .form-control {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid #334155;
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .form-control:focus {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-color: #4f46e5;
            color: #e2e8f0;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }
        
        [data-theme="dark"] .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border: none;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        [data-theme="dark"] .btn-primary:hover {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }
        
        [data-theme="dark"] .text-muted {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .form-label {
            color: #e2e8f0 !important;
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 64, 175, 0.4);
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-color) 100%);
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
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--secondary-color);
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
    </style>
    
    <!-- Dark Mode JavaScript -->
    <script>
        // Apply theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = '<?php echo $_SESSION['theme'] ?? 'light'; ?>';
            applyTheme(savedTheme);
        });
        
        function applyTheme(theme) {
            const html = document.documentElement;
            
            if (theme === 'dark') {
                html.setAttribute('data-theme', 'dark');
            } else if (theme === 'auto') {
                // Check system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    html.setAttribute('data-theme', 'dark');
                } else {
                    html.removeAttribute('data-theme');
                }
            } else {
                html.removeAttribute('data-theme');
            }
        }
    </script>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-crown fa-3x mb-3"></i>
            <h3>Super Admin Login</h3>
            <p class="mb-0">Full system access</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
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
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In as Super Admin
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <strong>Super Admin:</strong> Super Admin / admin@123
                </small>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i>Back to Role Selection
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

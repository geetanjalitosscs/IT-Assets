<?php
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectByRole();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Asset Management - Select Role</title>
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
        
        /* Dark mode for role selection */
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
        
        [data-theme="dark"] .role-selection-card {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(22, 33, 62, 0.95) 100%);
            color: #e2e8f0;
            border: 1px solid #334155;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        [data-theme="dark"] .role-selection-card h2 {
            color: #e2e8f0;
        }
        
        [data-theme="dark"] .role-selection-card p {
            color: #94a3b8;
        }
        
        [data-theme="dark"] .role-button {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid #334155;
            color: #e2e8f0;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .role-button:hover {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            border-color: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }
        
        [data-theme="dark"] .role-button i {
            color: #60a5fa;
        }
        
        [data-theme="dark"] .role-button:hover i {
            color: white;
        }
        
        .role-selection-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            overflow: hidden;
            border: none;
            max-width: 600px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .role-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .role-header h2 {
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .role-header p {
            font-weight: 500;
            opacity: 0.9;
            margin: 0;
        }
        
        .role-body {
            padding: 2.5rem 2rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .role-option {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(30, 64, 175, 0.15);
            color: inherit;
            text-decoration: none;
        }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
        }
        
        .role-option:last-child {
            margin-bottom: 0;
        }
        
        .role-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .role-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .role-description {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .role-option:hover .role-icon {
            color: var(--secondary-color);
        }
        
        .role-option:hover .role-title {
            color: var(--primary-color);
        }
        
        .login-form {
            display: none;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            margin-top: 1.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-form.show {
            display: block;
            animation: slideInDown 0.4s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 64, 175, 0.3);
            color: white;
        }
        
        .btn-back {
            background: transparent;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .credentials-info {
            background: rgba(30, 64, 175, 0.05);
            border: 1px solid rgba(30, 64, 175, 0.1);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        /* Animation for card entrance */
        .role-selection-card {
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
            
            .role-selection-card {
                max-width: 100%;
                margin: 0;
                border-radius: 20px;
            }
            
            .role-header {
                padding: 2rem 1.5rem;
            }
            
            .role-body {
                padding: 2rem 1.5rem;
            }
            
            .role-option {
                padding: 1.5rem;
            }
            
            .role-icon {
                font-size: 2.5rem;
            }
            
            .role-title {
                font-size: 1.25rem;
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
    <div class="role-selection-card">
        <div class="role-header">
            <i class="fas fa-laptop-code fa-3x mb-3"></i>
            <h2>IT Asset Management</h2>
            <p>Select your role to continue</p>
        </div>
        
        <div class="role-body">
            <a href="login.php?role=super_admin" class="role-option">
                <div class="text-center">
                    <i class="fas fa-crown role-icon"></i>
                    <h3 class="role-title">Super Admin</h3>
                    <p class="role-description">
                        Full system access with complete control over all branches, users, and system settings.
                    </p>
                </div>
            </a>
            
            <a href="login.php?role=branch_admin" class="role-option">
                <div class="text-center">
                    <i class="fas fa-user-shield role-icon"></i>
                    <h3 class="role-title">Admin</h3>
                    <p class="role-description">
                        Branch-specific access with management capabilities for your assigned branch.
                    </p>
                </div>
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
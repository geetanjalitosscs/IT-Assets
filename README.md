# IT Asset Management System

A comprehensive PHP + MySQL IT Asset Management System with role-based access control for Super Admin and Branch Admin users.

## Features

### 🔑 Role-Based Access Control
- **Super Admin**: Manage branches, users, view all assets across branches, generate global reports
- **Branch Admin**: Manage systems, employees, and peripherals within their assigned branch

### 🖥️ System Management
- Unique system IDs (C1, C2, C3, etc.) per branch
- Complete system configurations (CPU, RAM, Storage, OS)
- System status tracking (Assigned/Unassigned/In Repair)
- Employee assignment and history tracking

### 👥 Employee Management
- Employee directory with contact information
- Department and position tracking
- System assignment history

### ⌨️ Peripheral Management
- Peripheral inventory (keyboard, mouse, monitor, printer, etc.)
- System assignment for peripherals
- Status tracking (Available/Assigned/In Repair)

### 📊 Reporting & Analytics
- Export data to CSV and PDF formats
- Branch-wise, employee-wise, and system availability reports
- System history and audit trails

### 🎨 Modern UI/UX
- Responsive Bootstrap design
- Modular structure (header.php, sidebar.php, footer.php)
- DataTables with search, filter, and pagination
- Interactive charts and dashboards

### 👤 User Profile & Settings
- User profile management with contact information
- Password change functionality
- Account settings and preferences
- Session management and security

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone or download the project files**
   ```bash
   # Place all files in your web server directory
   ```

2. **Database Configuration**
   - Create a MySQL database named `it_asset_management`
   - Update database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'it_asset_management');
   ```

3. **Database Initialization**
   - The database tables will be automatically created when you first access the system
   - Default accounts are pre-configured (see credentials below)

4. **Web Server Configuration**
   - Ensure your web server can execute PHP files
   - Make sure the `config/` directory is writable (if needed)

5. **Access the System**
   - Start **XAMPP** (Apache and MySQL)
   - Ensure the database is running and configured
   - Open your web browser and navigate to: **http://localhost/IT-Assets/index.php**
   - You'll be redirected to the login page
   - Use the default credentials to log in:

   **Default Credentials**
   | Role         | Username     | Password   |
   |--------------|--------------|------------|
   | Super Admin  | Super Admin  | admin@123  |
   | Admin        | Admin        | admin@123  |

6. **Verify Requirements (Optional)**
   - Run `check_extensions.php` to verify all required PHP extensions are installed
   - This will help troubleshoot any compatibility issues

## File Structure

> Note: This reflects the current structure under `IT Assets` as used in the KT document.

```
├── config/
│   ├── database.php              # Database configuration and initialization (PDO + auto schema)
│   ├── database_mysqli.php       # Legacy/alternate DB connector (if used)
│   ├── session.php               # Session management and authentication helpers
│   └── cache_control.php         # HTTP cache-control headers (if referenced)
├── includes/
│   ├── header.php                # Common HTML <head>, top navigation, CSS/JS includes
│   ├── sidebar.php               # Sidebar navigation menu (role-aware)
│   └── footer.php                # Common footer with scripts
├── js/
│   ├── auto-refresh.js           # Auto-refresh logic for selected pages
│   └── notifications.js          # Client-side notification helpers
├── dashboard.php                 # Super Admin dashboard
├── admin_dashboard.php          # Branch Admin dashboard (enhanced)
├── branch_dashboard.php          # Branch Admin dashboard (legacy)
├── login.php                     # Login page
├── logout.php                    # Logout handler
├── super_admin_login.php         # (Optional) dedicated Super Admin login entry
├── admin_login.php               # (Optional) branch admin login entry
├── profile.php                   # User profile management
├── settings.php                  # User settings and password change
├── branches.php                  # Branch management (Super Admin)
├── users.php                     # User management (Super Admin)
├── admin_users.php               # User management within branch (Branch Admin)
├── systems.php                   # System management
├── employees.php                 # Employee management
├── peripherals.php               # Peripheral management
├── system_history.php            # System assignment history
├── reports.php                   # Reports and exports
├── generate_pdf.php              # PDF report generation
├── index.php                     # Main entry point / router to login
├── check_extensions.php          # PHP extensions checker
├── sample_data.sql               # Optional sample data
├── setup_database.sql            # SQL schema setup script
├── it_asset_management.sql       # Full DB dump / reference schema
├── edueyeco_it-assets.sql        # Alternate DB dump (environment-specific)
├── update_branches_table.sql     # Incremental schema/data update script
├── update_credentials.sql        # Script to update credentials (if used)
├── test_connection.php           # DB connectivity test script
├── test_branch_id_reuse.php      # Branch ID reuse test utility
├── REFRESH_SOLUTION.md           # Notes on refresh/timeout handling
├── IT-Assets-KT.md                         # Detailed Knowledge Transfer document
└── README.md                     # This file
```

## Usage Guide

### Super Admin Features
1. **Dashboard**: Overview of all branches, systems, employees, and users
2. **Branch Management**: Add, edit, delete branches
3. **User Management**: Create and manage user accounts
4. **Global Reports**: Generate reports across all branches
5. **System Overview**: View all systems across branches

### Branch Admin Features
1. **Branch Dashboard**: Overview of branch-specific data with enhanced statistics
2. **System Management**: Add, edit, assign systems within branch
3. **Employee Management**: Manage employees within branch
4. **Peripheral Management**: Manage peripherals within branch
5. **Branch Reports**: Generate branch-specific reports
6. **User Management**: Create and manage users within their branch (admin_users.php)
7. **Activity Monitoring**: View recent activities and system changes
8. **Profile & Settings**: Manage personal profile and password

### Common Features
- **System Assignment**: Assign systems to employees
- **Peripheral Assignment**: Assign peripherals to systems
- **History Tracking**: Complete audit trail of assignments
- **Activity Logging**: Real-time activity monitoring and logging
- **Export Functionality**: Export data to CSV/PDF formats
- **Search & Filter**: Advanced search and filtering capabilities
- **Profile Management**: All users can manage their profile and settings

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with secure algorithms
- **Session Management**: Secure session handling with role-based access
- **SQL Injection Prevention**: Uses prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Role-Based Access**: Strict permission system
- **Activity Logging**: Comprehensive audit trail for all user actions

## Activity Logging System

The system includes a robust activity logging mechanism that tracks:
- User additions and modifications
- System assignments and changes
- Employee management actions
- Peripheral assignments
- Branch-level activities
- Login/logout events

All activities are stored in the `activity_log` table with timestamps, user information, and detailed descriptions for audit purposes.

## Customization

### Adding New Fields
1. Update the database schema in `config/database.php`
2. Modify the relevant PHP files to include new fields
3. Update forms and display tables

### Styling
- Modify CSS in `includes/header.php` and `includes/footer.php`
- Bootstrap classes are used throughout for responsive design
- Custom CSS variables are defined for easy theming

### Adding New Reports
1. Add new report type in `reports.php`
2. Create the data query
3. Update the export functionality

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **Permission Denied**
   - Check file permissions on web server
   - Ensure PHP can write to session directory

3. **Login Issues**
   - Verify default credentials: Super Admin / admin@123 or Admin / admin@123
   - Check if user account exists in database

4. **Page Not Found**
   - Ensure all files are in correct directory
   - Check web server configuration
   - Verify PHP is enabled

## Support

For support or questions about this system, please refer to the code comments or create an issue in the project repository.

## License

This project is open source and available under the MIT License.

---

**Note**: Remember to change the default admin password after first login for security purposes.

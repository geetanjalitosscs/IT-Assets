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
   - A default Super Admin account will be created:
     - Username: `admin`
     - Password: `admin123`

4. **Web Server Configuration**
   - Ensure your web server can execute PHP files
   - Make sure the `config/` directory is writable (if needed)

5. **Access the System**
   - Open your web browser and navigate to your project URL
   - You'll be redirected to the login page
   - Use the default credentials to log in

## File Structure

```
├── config/
│   ├── database.php          # Database configuration and initialization
│   └── session.php           # Session management and authentication
├── includes/
│   ├── header.php            # Common header with navigation
│   ├── sidebar.php           # Sidebar navigation menu
│   └── footer.php             # Common footer with scripts
├── dashboard.php              # Super Admin dashboard
├── branch_dashboard.php      # Branch Admin dashboard
├── login.php                  # Login page
├── logout.php                 # Logout handler
├── branches.php               # Branch management (Super Admin)
├── users.php                  # User management (Super Admin)
├── systems.php                # System management
├── employees.php              # Employee management
├── peripherals.php            # Peripheral management
├── system_history.php         # System assignment history
├── reports.php                # Reports and exports
├── generate_pdf.php           # PDF report generation
├── index.php                  # Main entry point
└── README.md                  # This file
```

## Usage Guide

### Super Admin Features
1. **Dashboard**: Overview of all branches, systems, employees, and users
2. **Branch Management**: Add, edit, delete branches
3. **User Management**: Create and manage user accounts
4. **Global Reports**: Generate reports across all branches
5. **System Overview**: View all systems across branches

### Branch Admin Features
1. **Branch Dashboard**: Overview of branch-specific data
2. **System Management**: Add, edit, assign systems within branch
3. **Employee Management**: Manage employees within branch
4. **Peripheral Management**: Manage peripherals within branch
5. **Branch Reports**: Generate branch-specific reports

### Common Features
- **System Assignment**: Assign systems to employees
- **Peripheral Assignment**: Assign peripherals to systems
- **History Tracking**: Complete audit trail of assignments
- **Export Functionality**: Export data to CSV/PDF formats
- **Search & Filter**: Advanced search and filtering capabilities

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with secure algorithms
- **Session Management**: Secure session handling with role-based access
- **SQL Injection Prevention**: Uses prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Role-Based Access**: Strict permission system

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
   - Verify default credentials: admin/admin123
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

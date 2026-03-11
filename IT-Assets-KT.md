## Knowledge Transfer (KT) Document – IT Assets Management
 System

---

### 0. Project Details

| **Item** | **Details** |
|----------|-------------|
| **Project Name** | IT Assets Management
 System |
| **Project Type** | Web application (internal IT asset tracking and assignment) |
| **Tech Stack** | PHP (PDO, sessions), MySQL (`it_asset_management` DB), HTML5, CSS3, Bootstrap, JavaScript (vanilla + simple utilities), Apache/XAMPP on Windows |
| **Repository** | `<to be filled: Git URL or on-prem path, e.g., git@repo.company.com:it/it-asset-management.git>` |
| **Deployment Environment** | Windows server / workstation with XAMPP (Apache + MySQL). Can also be deployed to any LAMP/WAMP stack with PHP and MySQL support. |

Fill in any missing values (repository URL, exact server hostnames, etc.) before final handover.

---

### 1. Project Overview

The **IT Assets Management
 System** is an internal web application used by the organization to:

- **Track IT systems** (laptops, desktops, servers) at each branch.
- **Manage peripherals** (keyboards, mice, monitors, printers, scanners, etc.).
- **Maintain employee records** and the mapping between employees and their assigned systems.
- **Monitor asset lifecycle and history**, including assignment, return, and status changes.
- **Centralize administration** via role-based access for **Super Admins** and **Branch Admins**.

**Business objectives:**

- **Visibility**: Provide a single source of truth for all IT assets by branch and by employee.
- **Accountability**: Track who is responsible for each device and when changes occur.
- **Compliance & Audit**: Support internal and external audits with system history and activity logs.
- **Efficiency**: Reduce manual spreadsheet tracking and improve response time for asset-related queries.

The system is designed to be **simple to operate** for IT admins while remaining robust enough to support multiple branches and roles.

---

### 2. System Architecture

The application is a classic **PHP server-rendered web app** running on Apache with a MySQL backend.

#### 2.1 High-level Components

- **Frontend**  
  - Server-rendered HTML pages (`*.php` files) with embedded PHP and Bootstrap styling.  
  - Uses standard HTML forms for create/update/delete operations.  
  - Minimal JavaScript is used for UI interactions and utility functions (e.g., auto-refresh, notifications, Bootstrap modals).

- **Backend**  
  - Pure PHP code organized per feature: `employees.php`, `systems.php`, `branches.php`, `peripherals.php`, `users.php`, etc.  
  - Uses `config/database.php` for database connectivity via PDO.  
  - Uses `config/session.php` for session-based authentication and role checks.  
  - No framework (e.g., Laravel) – routing is file-based (page = script).

- **Database**  
  - MySQL database named **`it_asset_management`**.  
  - Tables for branches, users, employees, systems, peripherals, system history, and activity logs.  
  - SQL initialisation scripts: `setup_database.sql`, `it_asset_management.sql`, `sample_data.sql`, etc.

- **APIs / Endpoints**  
  - No separate REST API layer.  
  - Each `*.php` page acts as both **UI** and **endpoint**, handling GET (render) and POST (form submission) actions.

- **Integrations**  
  - Uses standard PHP extensions (PDO, MySQL, sessions).  
  - May load Bootstrap, Font Awesome, and related assets (CDN or local).  
  - No external business APIs (payment gateways, external SaaS, etc.) in the current codebase.

- **Infrastructure**  
  - Typically hosted under `C:\xampp\htdocs\IT Assets` in a local or on-prem Windows environment.  
  - Relies on Apache (from XAMPP) and MySQL service running on the same host.  
  - Can be migrated to a Linux-based LAMP stack with minimal path/config changes.

---

### 3. Technology Stack

| **Layer** | **Technology** | **Purpose** |
|----------|----------------|-------------|
| **Backend Language** | PHP (7.x/8.x) | Implements all business logic, form handling, session management, querying MySQL. |
| **Database** | MySQL | Stores branches, users, employees, systems, peripherals, history, activity logs. |
| **DB Access** | PHP PDO | Secure database access using prepared statements and connection retry logic. |
| **Web Server** | Apache (via XAMPP on Windows) | Serves PHP pages and static assets. |
| **Frontend UI** | HTML5, CSS3, Bootstrap | Layout, responsive design, modals, tables, forms. |
| **JavaScript** | Vanilla JS + Bootstrap JS | Handles modals, form population, small UI enhancements (`auto-refresh.js`, `notifications.js`). |
| **OS / Runtime** | Windows 10+ with XAMPP | Local development and typical deployment environment. |

Key custom configuration files:

- `config/database.php` – Database connection and schema initialization.  
- `config/session.php` – Session and role-based access helpers.

---

### 4. Repository and Access Details

> **Note:** Fill in your actual repository details and access instructions here.

- **Repository location**:  
  - URL: `<to be filled: e.g., https://git.company.com/it/it-asset-management.git>`  
  - Default branch: `main` or `master` (confirm and update).

- **Branching strategy (recommended corporate practice)**:
  - `main` / `master`: Always deployable; production-ready code.  
  - `develop`: Integration branch for features (optional; depends on current usage).  
  - Feature branches: `feature/<short-description>` for new modules / changes.  
  - Hotfix branches: `hotfix/<ticket-id>` for urgent production fixes.

- **Required tools to access and work with the repo**:
  - Git client (Git for Windows, SourceTree, or similar).  
  - IDE/Editor: VS Code, PhpStorm, or any PHP-capable editor.  
  - XAMPP (or equivalent AMP stack) installed locally.  
  - MySQL client tool (phpMyAdmin, MySQL Workbench, or CLI).

- **Access control**:
  - Ensure your user is added to the appropriate Git group (e.g., `IT-Apps`/`IT-Infra`).  
  - Use SSH keys or corporate SSO according to company policy.

---

### 5. Project Setup Guide (Very Detailed)

#### 5.1 Prerequisites

- **Operating System**: Windows 10 or later (for XAMPP-based setup).  
- **Software**:
  - XAMPP (Apache + MySQL + PHP).  
  - Git.  
  - Browser (Chrome/Edge/Firefox).  
  - MySQL client (phpMyAdmin via XAMPP or external tool).

#### 5.2 XAMPP Installation

1. Download XAMPP from the official website for Windows.  
2. Install XAMPP to `C:\xampp` (default path is recommended).  
3. Open **XAMPP Control Panel**.  
4. Start **Apache** and **MySQL** services and ensure both are running without errors.

#### 5.3 Cloning the Repository

1. Open PowerShell or Git Bash.  
2. Navigate to the `htdocs` directory:  
   - `cd "C:\xampp\htdocs"`  
3. Clone the repository:  
   - `git clone <repo-url> "IT Assets"`  
4. Confirm the project folder structure exists at:  
   - `C:\xampp\htdocs\IT Assets\...`

If you already have the source copied by other means, ensure it resides under `C:\xampp\htdocs\IT Assets`.

#### 5.4 Database Setup

The application uses a MySQL database named **`it_asset_management`**. There are two ways to set it up:

**Option A – Use provided SQL scripts (recommended for production-like data):**

1. Open `http://localhost/phpmyadmin` in your browser.  
2. Create a new database:  
   - Name: `it_asset_management`  
   - Collation: `utf8mb4_general_ci` (or similar).  
3. Select the `it_asset_management` database.  
4. Use the **Import** tab and upload `setup_database.sql` or `it_asset_management.sql` from the project root.  
5. (Optional) Import `sample_data.sql` to load demo/master data if needed.

**Option B – Let `config/database.php` initialize tables automatically (for quick dev setup):**

1. Make sure the `it_asset_management` database exists (empty is fine).  
2. Ensure `config/database.php` is present and accessible.  
3. On first page request, the `initializeDatabase()` function will:  
   - Create tables: `branches`, `users`, `employees`, `systems`, `peripherals`, `system_history`, `activity_log`.  
   - Insert a **default super admin user**:  
     - Username: `admin`  
     - Password: `admin123` (change in production).

#### 5.5 Database Configuration (`config/database.php`)

Open `config/database.php` and verify the constants:

- `DB_HOST` – typically `localhost`.  
- `DB_USER` – `root` for local XAMPP (or a dedicated DB user in production).  
- `DB_PASS` – empty for default XAMPP; **must be a strong password in production**.  
- `DB_NAME` – `it_asset_management`.

The `getConnection()` function uses PDO with:

- Exception mode and prepared statements.  
- Connection retry (3 attempts) and basic connection testing.

#### 5.6 Session and Authentication (`config/session.php`)

Key helpers:

- `isLoggedIn()` – returns `true` if a user session is active.  
- `isSuperAdmin()` / `isBranchAdmin()` – role checks.  
- `getCurrentUserBranch()` – branch ID for branch admins.  
- `requireLogin()` – redirects to `login.php` if not authenticated.  
- `redirectByRole()` – directs users to appropriate dashboard by role.

These functions are included at the top of most `*.php` pages to protect access.

#### 5.7 Running the Project Locally

1. Ensure Apache and MySQL are running in XAMPP.  
2. Open a browser and navigate to:  
   - `http://localhost/IT%20Assets/` or `http://localhost/IT Assets/` (depending on browser).  
3. You should see the login page (`login.php`/`index.php`).  
4. Log in with:  
   - Username: `admin`  
   - Password: `admin123` (on a fresh DB).  
5. After login:  
   - **Super Admin** is redirected to `dashboard.php`.  
   - **Branch Admin** is redirected to `branch_dashboard.php`.

#### 5.8 Environment Variables and Configuration Files

This project uses **PHP constants** in `config/database.php` instead of `.env` files. For production or multiple environments:

- Create environment-specific copies: e.g., `database.local.php`, `database.prod.php`, etc., and include the correct one based on server environment.  
- Alternatively, read DB credentials from environment variables (`getenv()`) and update `database.php` accordingly.

Other configuration-related files:

- `config/cache_control.php` – HTTP cache behavior (if present).  
- `check_extensions.php` – Utility to verify required PHP extensions.

#### 5.9 Verifying Setup

After setup:

- Login as Super Admin.  
- Navigate through key pages: `branches.php`, `employees.php`, `systems.php`, `peripherals.php`, `users.php`, `reports.php`.  
- Perform basic CRUD operations in a test environment to confirm DB and session behavior.

---
### 6. Folder Structure Explanation

Key files and directories under `IT Assets`:

- **Root PHP pages (feature entry points)**:
  - `index.php` / `login.php` / `super_admin_login.php` / `admin_login.php` – Authentication entry points.  
  - `dashboard.php` – Super Admin dashboard.  
  - `branch_dashboard.php` – Branch Admin dashboard.  
  - `employees.php` – Employee management (CRUD + branch-scoped listing).  
  - `systems.php` – IT systems (laptops/desktops/servers) management and assignment.  
  - `peripherals.php` – Peripheral devices management and linking to systems.  
  - `branches.php` – Branch master data management.  
  - `users.php` – User accounts and role management.  
  - `reports.php` – Reporting screens, exports, summary views.  
  - `system_history.php` – View and manage historical assignments of systems to employees.  
  - `settings.php` – Application-level settings (if implemented).  
  - `profile.php` – Logged-in user profile settings.  
  - `logout.php` – Ends session and redirects to login.

- **`config/`**:
  - `database.php` – DB connection and schema initialization (see Section 5).  
  - `database_mysqli.php` – Legacy or alternative DB connector (if used).  
  - `session.php` – Session and role helper functions.  
  - `cache_control.php` – Headers to control HTTP caching (if referenced).

- **`includes/`**:
  - `header.php` – Shared HTML `<head>` and page header (CSS/JS loads, navbar).  
  - `sidebar.php` – Shared navigation menu; shows modules based on user role.  
  - `footer.php` – Shared footer, JS includes, and closing tags.

- **`js/`**:
  - `auto-refresh.js` – Handles periodic refresh of certain components/pages.  
  - `notifications.js` – Handles client-side notifications or badges.

- **SQL scripts**:
  - `setup_database.sql` – Main DB schema and setup.  
  - `it_asset_management.sql` / `edueyeco_it-assets.sql` – Full schema and/or snapshot of data.  
  - `sample_data.sql` – Sample or seed data.  
  - `update_branches_table.sql`, `update_credentials.sql` – Incremental updates or patches.

- **Documentation**:
  - `README.md` – Quick overview of the project.  
  - `REFRESH_SOLUTION.md` – Notes regarding auto-refresh or performance optimizations.  
  - `KT.md` – This Knowledge Transfer document.

- **Test / utility files**:
  - `test_connection.php` – Quick DB connection test.  
  - `test_branch_id_reuse.php` – Specific test related to branch ID handling.  
  - `check_extensions.php` – Verifies PHP extensions required by the system.

---

### 7. Module-wise Functional Explanation

This section explains the major modules: **purpose**, **workflow**, and **key components**.

#### 7.1 Authentication & Authorization

- **Purpose**: Restrict system access to authorized staff and differentiate privileges between Super Admins and Branch Admins.  
- **Entry points**: `index.php`, `login.php`, `admin_login.php`, `super_admin_login.php`.  
- **Workflow**:
  1. User visits login page and submits username/password.  
  2. PHP validates credentials against the `users` table (username + password hash).  
  3. On success, `$_SESSION['user_id']`, `$_SESSION['role']`, and `$_SESSION['branch_id']` (for branch admins) are set.  
  4. `redirectByRole()` sends users to `dashboard.php` or `branch_dashboard.php`.  
  5. All protected pages call `requireLogin()` at the top, redirecting unauthenticated users to `login.php`.
- **Key components**:  
  - `config/session.php` (session helpers).  
  - `users` table (credentials and roles).

#### 7.2 Branch Management (`branches.php`)

- **Purpose**: Maintain the list of company branches (locations).  
- **Workflow**:
  - Super Admin can create/edit/delete branches.  
  - Branches are referenced by `users.branch_id`, `employees.branch_id`, and `systems.branch_id`.  
  - Deleting a branch cascades to related tables where defined in the DB schema.
- **Key components**:
  - Table: `branches`.  
  - Page: `branches.php`.  
  - Foreign keys in `users`, `employees`, `systems`, `activity_log`.

#### 7.3 Employee Management (`employees.php`)

- **Purpose**: CRUD operations for employees and linking them to branches and systems.  
- **Workflow**:
  - Page requires login (`requireLogin()`) and uses `getConnection()` for DB access.  
  - Supports three main actions via `$_POST['action']`:
    - `add`:  
      - Validates `employee_id`, `full_name`, `email`, `branch_id`.  
      - Ensures `employee_id` is unique.  
      - Inserts into `employees` with a sequential `id`.  
      - Logs activity in `activity_log` (`employee_add`).  
    - `edit`:  
      - Updates employee details (ID, name, email, phone, department, position, branch).  
      - Ensures uniqueness of `employee_id` excluding the current record.  
      - Logs activity (`employee_edit`).  
    - `delete`:  
      - Logs deletion (`employee_delete`) including name and branch before deletion.  
      - Deletes employee from `employees` and reorders IDs for remaining records (sequential `id`).  
  - Employees are listed with their branch and count of assigned systems via join with `systems`.  
  - Super Admin can see all branches; Branch Admin sees only their branch.
- **Key components**:
  - Table: `employees`.  
  - Tables used: `branches`, `systems`, `activity_log`.  
  - UI: Bootstrap table, modals for Add/Edit/Delete.

#### 7.4 Systems Management (`systems.php`)

- **Purpose**: Track core IT assets (laptops, desktops, servers) and assign them to employees.  
- **Typical workflow** (inferred from DB schema):
  - Create system records with `system_code`, type, hardware specs, OS, branch, and status.  
  - Assign a system to an employee (`assigned_to`, `assigned_date`), updating `status` to `Assigned`.  
  - Unassign / mark `In Repair` / `Unassigned` as needed.  
  - Write assignment changes into `system_history` for audit trail.
- **Key components**:
  - Tables: `systems`, `system_history`, `employees`, `branches`.  
  - Page: `systems.php` (forms, tables, filters).

#### 7.5 Peripherals Management (`peripherals.php`)

- **Purpose**: Manage peripheral assets and link them to systems.  
- **Workflow**:
  - Admins create peripherals with name, type, brand, model, serial number.  
  - Peripherals can be assigned to a system (`system_id`), with `status` reflecting availability.  
  - Changes may be logged or reported via `reports.php`.
- **Key components**:
  - Table: `peripherals`.  
  - Page: `peripherals.php`.

#### 7.6 System History (`system_history.php`)

- **Purpose**: Provide a log of which system was assigned to which employee, when, and for how long.  
- **Workflow**:
  - When a system is assigned or returned, a record is created/updated in `system_history`.  
  - History is viewable by system and/or employee to support audits.  
- **Key components**:
  - Table: `system_history`.  
  - Page: `system_history.php`.

#### 7.7 User Management (`users.php`)

- **Purpose**: Manage application users and their roles.  
- **Workflow**:
  - Super Admin can create branch admin users linked to a specific branch.  
  - Passwords are stored as **hashed** values (`password_hash()` in PHP).  
  - Users can be activated/deactivated or their roles adjusted (Super Admin / Branch Admin).
- **Key components**:
  - Table: `users`.  
  - Page: `users.php`.  
  - Login workflow via `login.php` and `config/session.php`.

#### 7.8 Reporting (`reports.php`)

- **Purpose**: Provide consolidated views of assets, assignments, and possibly exports (PDF/CSV).  
- **Workflow** (typical for this kind of system):
  - Filters by branch, status, employee, date range.  
  - Generates tabular data on screen and may support export via `generate_pdf.php` or similar.  
- **Key components**:
  - Page: `reports.php`.  
  - Helper: `generate_pdf.php` (if used for export).  
  - Underlying tables: `systems`, `employees`, `peripherals`, `system_history`, `branches`.

---

### 8. API Documentation (Page Endpoints)

The system does **not** expose a separate REST API; instead, each page is an endpoint handling form submissions via POST. Below is a typical pattern using `employees.php` as an example.

#### 8.1 `employees.php`

- **URL**: `/IT Assets/employees.php` (or relative: `employees.php`).  
- **Methods**:
  - `GET` – Renders employee list UI.  
  - `POST` – Handles create/update/delete actions via `$_POST['action']`.

**Authentication**

- Requires active session; `requireLogin()` is called.  
- Access level:
  - Super Admin – all branches.  
  - Branch Admin – only `employees` of their branch.

**Actions (POST)**

- **Add employee**  
  - `action`: `"add"`  
  - **Request parameters (form fields)**:
    - `employee_id` (string, required)  
    - `full_name` (string, required)  
    - `email` (string, required)  
    - `phone` (string, optional)  
    - `department` (string, optional)  
    - `position` (string, optional)  
    - `branch_id` (int, required for Super Admin; derived from session for Branch Admin)  
  - **Response**:
    - On success: page reloads with success alert `"Employee added successfully"`.  
    - On error: page reloads with error alert (e.g., `"Employee ID already exists!"`).

- **Edit employee**  
  - `action`: `"edit"`  
  - **Request parameters**:
    - `id` (int, primary key)  
    - Same core fields as `add`  
  - **Response**:
    - Success or error alerts similar to `add`.

- **Delete employee**  
  - `action`: `"delete"`  
  - **Request parameters**:
    - `id` (int, primary key)  
  - **Response**:
    - Success alert `"Employee deleted successfully"`.

Other modules (`branches.php`, `systems.php`, `peripherals.php`, `users.php`) follow the same pattern (GET = list/form, POST + `action` = CRUD).

---

### 9. Database Structure

Below are the most important tables; refer to `setup_database.sql` or `it_asset_management.sql` for complete definitions.

#### 9.1 `branches`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | Unique branch identifier |
| `name` | VARCHAR(100) | Branch name |
| `location` | VARCHAR(200) | Physical address or location |
| `created_at` | TIMESTAMP | Created timestamp |

**Relationships**

- Referenced by `users.branch_id`, `employees.branch_id`, `systems.branch_id`, `activity_log.branch_id`.

#### 9.2 `users`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | Unique user ID |
| `username` | VARCHAR(50), UNIQUE | Login username |
| `password` | VARCHAR(255) | Password hash (bcrypt) |
| `role` | ENUM('super_admin','branch_admin') | User role |
| `branch_id` | INT, FK, nullable | Branch for branch admins |
| `full_name` | VARCHAR(100) | Person’s name |
| `email` | VARCHAR(100) | Contact email |
| `created_at` | TIMESTAMP | Creation timestamp |

#### 9.3 `employees`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | Internal numeric ID |
| `employee_id` | VARCHAR(20), UNIQUE | Business employee code (e.g., `EMP001`) |
| `full_name` | VARCHAR(100) | Employee full name |
| `email` | VARCHAR(100) | Work email |
| `phone` | VARCHAR(20) | Phone number (optional) |
| `department` | VARCHAR(50) | Department (IT, HR, etc.) |
| `position` | VARCHAR(50) | Job title |
| `branch_id` | INT, FK | Branch where employee is assigned |
| `created_at` | TIMESTAMP | Creation timestamp |

**Relationships**

- `branch_id` → `branches.id` (ON DELETE CASCADE).  
- Referenced by `systems.assigned_to`, `system_history.employee_id`.

#### 9.4 `systems`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | System ID |
| `system_code` | VARCHAR(20) | Internal asset code |
| `branch_id` | INT, FK | Branch where system is located |
| `type` | ENUM('Laptop','Desktop','Server') | System type |
| `cpu` | VARCHAR(100) | CPU specs |
| `ram` | VARCHAR(50) | RAM specs |
| `storage` | VARCHAR(100) | Storage specs |
| `os` | VARCHAR(50) | Operating system |
| `status` | ENUM('Assigned','Unassigned','In Repair') | System status |
| `assigned_to` | INT, FK, nullable | `employees.id` currently using the system |
| `assigned_date` | DATE, nullable | Assignment date |
| `created_at` | TIMESTAMP | Creation timestamp |

#### 9.5 `peripherals`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | Peripheral ID |
| `name` | VARCHAR(100) | Name (e.g., Dell Monitor) |
| `type` | ENUM('Keyboard','Mouse','Monitor','Printer','Scanner','Other') | Peripheral type |
| `brand` | VARCHAR(50) | Brand |
| `model` | VARCHAR(50) | Model |
| `serial_number` | VARCHAR(100) | Serial number |
| `system_id` | INT, FK, nullable | Linked system, if any |
| `status` | ENUM('Available','Assigned','In Repair') | Availability status |
| `created_at` | TIMESTAMP | Creation timestamp |

#### 9.6 `system_history`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | History record ID |
| `system_id` | INT, FK | System ID |
| `employee_id` | INT, FK | Employee ID |
| `assigned_date` | DATE | Date system assigned |
| `returned_date` | DATE, nullable | Date returned |
| `notes` | TEXT | Freeform notes |
| `created_at` | TIMESTAMP | Record creation time |

#### 9.7 `activity_log`

| **Column** | **Type** | **Description** |
|-----------|----------|-----------------|
| `id` | INT, PK, AUTO_INCREMENT | Log entry ID |
| `activity_type` | VARCHAR(50) | Activity code (e.g., `employee_add`) |
| `entity_id` | INT, nullable | Affected record ID |
| `entity_name` | VARCHAR(100), nullable | Human-readable name (e.g., employee ID) |
| `description` | TEXT, nullable | Description of the action |
| `branch_id` | INT, FK, nullable | Branch context |
| `created_at` | TIMESTAMP | Timestamp |

---

### 10. Deployment Process

#### 10.1 Build Steps

There is **no separate build step** (PHP is interpreted). Deployment consists of:

- Pulling the latest code from Git.  
- Synchronizing PHP files, `config/`, `includes/`, `js/`, and assets to the web server document root.  
- Running DB migrations or applying SQL scripts if schema changes are introduced.

#### 10.2 Manual Deployment to a Windows XAMPP Server

1. Connect to the server (RDP or equivalent).  
2. Stop Apache briefly if needed (optional, for large updates).  
3. Backup the current application folder (e.g., zip `C:\xampp\htdocs\IT Assets`).  
4. Pull latest changes:  
   - If Git is available directly in the directory:  
     - `cd "C:\xampp\htdocs\IT Assets"`  
     - `git pull origin <branch>`  
   - Or deploy via copying an updated folder from CI/local machine.  
5. Apply any pending DB migrations/SQL scripts.  
6. Start / restart Apache and verify:  
   - `http://<server-host>/IT%20Assets/` is accessible.  
7. Perform a quick smoke test (login, view branches, employees, systems).

#### 10.3 CI/CD (If Adopted)

If the organization uses CI/CD (e.g., GitLab CI, Jenkins, GitHub Actions):

- Typical pipeline stages:
  - **Lint/Test** (optional for this project).  
  - **Package**: Bundle PHP source into an artifact (zip).  
  - **Deploy**:  
    - Copy to server via SSH/WinRM.  
    - Unzip into `C:\xampp\htdocs\IT Assets`.  
    - Run DB migration scripts.  
    - Run basic health checks.

Document any actual pipeline file or job URL here once finalized.

---

### 11. Logs and Monitoring

#### 11.1 Application Logs

- **PHP error logs**:  
  - Typically under `C:\xampp\php\logs\php_error_log` (configurable in `php.ini`).  
  - Used to debug fatal errors, warnings, notices.

- **Apache access/error logs**:  
  - Located under `C:\xampp\apache\logs\access.log` and `error.log`.  
  - Useful for tracing HTTP-level issues (404, 500, etc.).

- **Database logs**:  
  - MySQL logs (`mysql_error.log`, `general.log`) – managed by DBAs.

#### 11.2 Application Activity Log (Business-Level)

- Table `activity_log` captures important events, for example:  
  - Employee add/update/delete.  
  - (Optionally) similar logs for systems and peripherals if implemented.  
- Used for business-level audit and troubleshooting (who changed what, when).

#### 11.3 Monitoring

- On smaller deployments, monitoring is mostly manual:
  - Check Apache/PHP logs periodically.  
  - Spot-check `activity_log`.  
  - Monitor server CPU/RAM/disk.
- In more mature environments, integrate with centralized monitoring (Splunk/ELK/CloudWatch/etc.) if available.

---

### 12. Common Issues and Troubleshooting

| **Issue** | **Possible Cause** | **Resolution Steps** |
|-----------|--------------------|----------------------|
| Cannot connect to DB / “Database connection failed” | MySQL service stopped, wrong DB credentials, DB not created | 1. Check MySQL in XAMPP. 2. Verify `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` in `config/database.php`. 3. Confirm `it_asset_management` DB exists. |
| Login fails for known user | Password changed, bad data | 1. Check `users` table entry. 2. Reset password by re-inserting with `password_hash()`. 3. Check PHP logs. |
| Blank page / HTTP 500 | PHP fatal error, missing extensions | 1. Enable `display_errors` in dev only. 2. Check `php_error_log`. 3. Run `check_extensions.php` for required dependencies. |
| Redirects back to login unexpectedly | Session issues | 1. Ensure `session_start()` and `requireLogin()` are used correctly. 2. Check browser cookies. 3. Verify PHP session save path and permissions. |
| Data not saving (employees/systems) | Validation failures, DB constraints | 1. Look at error/success messages. 2. Check browser Network tab for POST errors. 3. Validate unique keys and foreign key constraints. |
| CSS/JS not loading | Wrong asset paths | 1. Check dev tools for 404s. 2. Verify paths in `includes/header.php`. |
| Slow pages | Large datasets, no pagination | 1. Add indexes if missing. 2. Implement server-side pagination/filtering. 3. Check server resource utilization. |

When reporting bugs, always capture: **URL, user role, time, error message, and relevant log snippets**.

---

### 13. Maintenance Guide

Routine tasks to keep the system healthy:

- **Database maintenance**:
  - Regular backups of `it_asset_management` (daily/weekly).  
  - Prune old `system_history` and `activity_log` records if allowed by audit policy.  
  - Run `OPTIMIZE TABLE` or equivalent periodically.

- **User and Access Management**:
  - Remove or disable users who leave the organization.  
  - Periodically verify roles and branch assignments.  
  - Enforce strong password policies.

- **Application updates**:
  - Use feature branches and PRs.  
  - Test in staging before production.  
  - Document DB changes in versioned SQL scripts.

- **Server/Environment**:
  - Keep PHP/MySQL patched within corporate standards.  
  - Monitor disk space, especially for logs and DB.  
  - Renew SSL certificates if HTTPS is used.

---

### 14. Security Considerations

#### 14.1 Authentication

- Session-based authentication via `config/session.php`.  
- Passwords stored using `password_hash()` (bcrypt).  
- Default super admin (`admin` / `admin123`) created on first run must be changed immediately in production.

#### 14.2 Authorization

- Role checks:
  - `isSuperAdmin()` – full access.  
  - `isBranchAdmin()` – branch-scoped access.
- All protected pages should:
  - Call `requireLogin()`.  
  - Optionally enforce branch/role authorization on operations.

#### 14.3 Data Protection

- Prepared statements with PDO help mitigate SQL injection.  
- `htmlspecialchars()` is used for many outputs (e.g., in `employees.php`) to mitigate XSS.  
- DB credentials in `config/database.php` must be protected with proper file permissions and strong passwords.

#### 14.4 Session Security

- In production, enable `session.cookie_secure` and `session.cookie_httponly`.  
- Use HTTPS to protect credentials and session cookies.  
- Implement session timeout/idle timeout as per security policy.

---

### 15. Third Party Integrations

Currently, the system is mostly **self-contained**:

- Uses third-party libraries primarily for UI (Bootstrap, Font Awesome, possibly jQuery/DataTables).  
- No external business APIs (payment gateways, external HR systems, etc.) are integrated yet.  
- PHP extension requirements (PDO MySQL, etc.) can be checked via `check_extensions.php`.

If/when new integrations are added:

- Document API endpoints, auth credentials, and scopes.  
- Describe request/response formats (JSON/XML).  
- Define retry, timeout, and error-handling strategies.

---

### 16. Known Limitations or Pending Tasks

Known limitations (update as work is done):

- Re-numbering `employees.id` on delete may complicate external references; immutable IDs would be safer.  
- No `created_by` / `updated_by` audit fields on core tables; relies on `activity_log`.  
- No built-in pagination on big tables; might be slow with very large data volumes.  
- English-only UI (no i18n).  
- No automated tests (unit/integration) at present.  
- Schema changes are manual via SQL files; no structured migration framework.

Pending tasks (example placeholders – replace with actual items/tickets):

- `<Pending Task 1 – e.g., implement CSV export for systems>`  
- `<Pending Task 2 – e.g., add password reset flow via email>`  
- `<Pending Task 3 – e.g., enhance audit logging for peripherals>`

---

### 17. Important Contacts

Replace placeholders with actual names/emails/teams before final handover.

| **Area** | **Role / Team** | **Contact** |
|----------|-----------------|------------|
| Application Ownership | IT Applications Team | `<Team email / DL>` |
| Product/Process Owner | IT Manager / Asset Manager | `<Name / Email>` |
| Infrastructure (Server/DB) | IT Infrastructure / DBA Team | `<Name / DL / Ticket queue>` |
| Security | InfoSec / Security Operations | `<Contact or portal>` |
| Support / Helpdesk | Service Desk / L1 Support | `<Helpdesk number / ticketing tool>` |

---

### 18. Best Practices for Future Developers

- **Understand the domain first**:
  - Talk with IT asset/process owners to understand lifecycle, audits, and reporting needs.  
  - Confirm which fields and reports are business-critical before changing data structures.

- **Change management**:
  - Always develop on feature branches, never directly on `main`.  
  - Commit SQL migration scripts alongside code changes.  
  - Coordinate schema changes with DBAs and operations teams.

- **Code consistency**:
  - Reuse PDO patterns and helper functions already in use.  
  - Keep layout consistent (use `header.php`, `sidebar.php`, `footer.php`).  
  - Follow existing naming conventions for tables/columns where possible.

- **Security and privacy**:
  - Do not log sensitive information (passwords, personal info) in plaintext.  
  - Escape all user-controlled output to prevent XSS.  
  - Follow least-privilege principles for new DB users or roles.

- **Performance and scalability**:
  - Think about indexes and query plans for new features.  
  - Use pagination for any list that can grow large (employees, systems, history).  
  - Avoid N+1 query patterns in loops.

- **Documentation**:
  - Update `README.md` and `KT.md` whenever you add modules or change DB/deployment.  
  - Maintain a simple **CHANGELOG** for major changes and releases.

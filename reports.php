<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
$pageTitle = 'Reports';
$pdo = getConnection();

// Handle report generation
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'generate_report') {
    $reportType = $_POST['report_type'];
    $format = $_POST['format'];
    
    // Get data based on report type
    switch ($reportType) {
        case 'systems':
            if (isSuperAdmin()) {
                $stmt = $pdo->query("
                    SELECT s.system_code, s.type, s.cpu, s.ram, s.storage, s.os, s.status, 
                           e.full_name as assigned_to, b.name as branch_name, s.assigned_date
                    FROM systems s
                    LEFT JOIN employees e ON s.assigned_to = e.id
                    LEFT JOIN branches b ON s.branch_id = b.id
                    ORDER BY b.name, s.system_code
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT s.system_code, s.type, s.cpu, s.ram, s.storage, s.os, s.status, 
                           e.full_name as assigned_to, b.name as branch_name, s.assigned_date
                    FROM systems s
                    LEFT JOIN employees e ON s.assigned_to = e.id
                    LEFT JOIN branches b ON s.branch_id = b.id
                    WHERE s.branch_id = ?
                    ORDER BY s.system_code
                ");
                $stmt->execute([getCurrentUserBranch()]);
            }
            $data = $stmt->fetchAll();
            $filename = 'systems_report_' . date('Y-m-d');
            break;
            
        case 'employees':
            if (isSuperAdmin()) {
                $stmt = $pdo->query("
                    SELECT e.employee_id, e.full_name, e.email, e.phone, e.department, e.position,
                           b.name as branch_name, COUNT(s.id) as assigned_systems
                    FROM employees e
                    LEFT JOIN branches b ON e.branch_id = b.id
                    LEFT JOIN systems s ON e.id = s.assigned_to
                    GROUP BY e.id
                    ORDER BY b.name, e.full_name
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT e.employee_id, e.full_name, e.email, e.phone, e.department, e.position,
                           b.name as branch_name, COUNT(s.id) as assigned_systems
                    FROM employees e
                    LEFT JOIN branches b ON e.branch_id = b.id
                    LEFT JOIN systems s ON e.id = s.assigned_to
                    WHERE e.branch_id = ?
                    GROUP BY e.id
                    ORDER BY e.full_name
                ");
                $stmt->execute([getCurrentUserBranch()]);
            }
            $data = $stmt->fetchAll();
            $filename = 'employees_report_' . date('Y-m-d');
            break;
            
            
        case 'system_history':
            if (isSuperAdmin()) {
                $stmt = $pdo->query("
                    SELECT s.system_code, e.full_name as employee_name, e.employee_id,
                           b.name as branch_name, sh.assigned_date, sh.returned_date, sh.notes
                    FROM system_history sh
                    JOIN systems s ON sh.system_id = s.id
                    JOIN employees e ON sh.employee_id = e.id
                    JOIN branches b ON s.branch_id = b.id
                    ORDER BY sh.assigned_date DESC
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT s.system_code, e.full_name as employee_name, e.employee_id,
                           b.name as branch_name, sh.assigned_date, sh.returned_date, sh.notes
                    FROM system_history sh
                    JOIN systems s ON sh.system_id = s.id
                    JOIN employees e ON sh.employee_id = e.id
                    JOIN branches b ON s.branch_id = b.id
                    WHERE s.branch_id = ?
                    ORDER BY sh.assigned_date DESC
                ");
                $stmt->execute([getCurrentUserBranch()]);
            }
            $data = $stmt->fetchAll();
            $filename = 'system_history_report_' . date('Y-m-d');
            break;
    }
    
    if ($format == 'csv') {
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit();
    } elseif ($format == 'pdf') {
        // For PDF, we'll redirect to a PDF generation page
        $_SESSION['report_data'] = $data;
        $_SESSION['report_type'] = $reportType;
        $_SESSION['report_filename'] = $filename;
        header('Location: generate_pdf.php');
        exit();
    }
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* Professional Report Cards Styling */
.report-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.report-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.report-card:hover::before {
    opacity: 1;
}

.report-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.report-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    position: relative;
    overflow: hidden;
}

.report-icon::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
    border-radius: 16px;
}

.report-icon i {
    position: relative;
    z-index: 2;
}

.systems-icon {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
}

.employees-icon {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
}


.history-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}

.report-badge {
    margin-top: -8px;
}

.report-badge .badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    line-height: 1.3;
}

.report-description {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.report-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.feature-tag {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
    font-size: 0.8rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 12px;
    border: 1px solid #d1d5db;
}

.report-icon-container {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.report-tips .alert {
    border-radius: 16px;
    padding: 20px;
}

.report-tips ul {
    padding-left: 20px;
}

.report-tips li {
    margin-bottom: 8px;
    line-height: 1.5;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .report-card {
        padding: 20px;
    }
    
    .report-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .report-title {
        font-size: 1.1rem;
    }
    
    .report-description {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .report-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .report-badge {
        margin-top: 0;
        align-self: flex-end;
    }
}

/* Dark Mode Reports Styling */
[data-theme="dark"] .report-card {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
    color: var(--text-color);
    box-shadow: 0 4px 20px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(79, 70, 229, 0.2);
    border-color: var(--primary-color);
}

[data-theme="dark"] .report-card::before {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
}

[data-theme="dark"] .report-card:hover::before {
    opacity: 0.1;
}

[data-theme="dark"] .report-title {
    color: var(--text-color);
}

[data-theme="dark"] .report-description {
    color: var(--text-muted);
}

[data-theme="dark"] .feature-tag {
    background: var(--card-hover);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

[data-theme="dark"] .feature-tag:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Dark Mode Report Icons */
[data-theme="dark"] .report-icon.systems-icon {
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(99, 102, 241, 0.1) 100%);
    color: var(--primary-color);
}

[data-theme="dark"] .report-icon.employees-icon {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(34, 197, 94, 0.1) 100%);
    color: #10b981;
}

[data-theme="dark"] .report-icon.history-icon {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(251, 191, 36, 0.1) 100%);
    color: #f59e0b;
}

/* Dark Mode Available Reports Section */
[data-theme="dark"] .card {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
}

[data-theme="dark"] .card-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    border-color: var(--border-color);
}

[data-theme="dark"] .card-body {
    background: transparent;
}

/* Dark Mode Modal Styling */
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
    background: transparent;
    border-color: var(--border-color);
}

[data-theme="dark"] .modal-title {
    color: white;
}

[data-theme="dark"] .btn-close {
    filter: invert(1);
}

/* Dark Mode Form Elements */
[data-theme="dark"] .form-control {
    background-color: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .form-control:focus {
    background-color: var(--card-bg);
    border-color: var(--primary-color);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
}

[data-theme="dark"] .form-select {
    background-color: var(--card-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .form-select:focus {
    background-color: var(--card-bg);
    border-color: var(--primary-color);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
}

[data-theme="dark"] .form-select option {
    background-color: var(--card-bg);
    color: var(--text-color);
}

/* Dark Mode Statistics Cards */
[data-theme="dark"] .stat-card {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

[data-theme="dark"] .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
    border-color: var(--primary-color);
}

[data-theme="dark"] .stat-number {
    color: var(--primary-color);
}

[data-theme="dark"] .stat-label {
    color: var(--text-muted);
}
</style>

<div class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-bar me-3"></i>Reports
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>

        <!-- Report Generation Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-file-export me-2"></i>Generate Report
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="generate_report">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="report_type" class="form-label">Report Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="report_type" name="report_type" required>
                                            <option value="">Select Report Type</option>
                                            <option value="systems">Systems Report</option>
                                            <option value="employees">Employees Report</option>
                                            <option value="system_history">System History Report</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="format" class="form-label">Export Format <span class="text-danger">*</span></label>
                                        <select class="form-select" id="format" name="format" required>
                                            <option value="">Select Format</option>
                                            <option value="csv">CSV (Excel Compatible)</option>
                                            <option value="pdf">PDF Document</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-chart-pie me-2"></i>Quick Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get quick stats
                        if (isSuperAdmin()) {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM systems");
                            $totalSystems = $stmt->fetchColumn();
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
                            $totalEmployees = $stmt->fetchColumn();
                            
                            
                            $stmt = $pdo->query("SELECT COUNT(*) FROM branches");
                            $totalBranches = $stmt->fetchColumn();
                        } else {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM systems WHERE branch_id = ?");
                            $stmt->execute([getCurrentUserBranch()]);
                            $totalSystems = $stmt->fetchColumn();
                            
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE branch_id = ?");
                            $stmt->execute([getCurrentUserBranch()]);
                            $totalEmployees = $stmt->fetchColumn();
                            
                        }
                        ?>
                        
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-primary mb-1"><?php echo $totalSystems; ?></h4>
                                    <small class="text-muted">Systems</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="border rounded p-3">
                                    <h4 class="text-success mb-1"><?php echo $totalEmployees; ?></h4>
                                    <small class="text-muted">Employees</small>
                                </div>
                            </div>
                            <?php if (isSuperAdmin()): ?>
                                <div class="col-12 mb-3">
                                    <div class="border rounded p-3">
                                        <h4 class="text-warning mb-1"><?php echo $totalBranches; ?></h4>
                                        <small class="text-muted">Branches</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Reports Info -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                    <div class="card-header border-0" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: 15px 15px 0 0 !important;">
                        <h6 class="m-0 font-weight-bold text-white d-flex align-items-center">
                            <div class="report-icon-container me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            Available Reports
                            <span class="badge bg-light text-primary ms-auto">3 Reports</span>
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Systems Report -->
                            <div class="col-lg-6 col-md-12">
                                <div class="report-card h-100">
                                    <div class="report-card-header">
                                        <div class="report-icon systems-icon">
                                            <i class="fas fa-desktop"></i>
                                        </div>
                                        <div class="report-badge">
                                            <span class="badge bg-primary">Essential</span>
                                        </div>
                                    </div>
                                    <div class="report-card-body">
                                        <h5 class="report-title">Systems Report</h5>
                                        <p class="report-description">Complete inventory of all computer systems including hardware specifications, software configurations, current status, and employee assignments across all branches.</p>
                                        <div class="report-features">
                                            <span class="feature-tag">Hardware Specs</span>
                                            <span class="feature-tag">Status Tracking</span>
                                            <span class="feature-tag">Assignments</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Employees Report -->
                            <div class="col-lg-6 col-md-12">
                                <div class="report-card h-100">
                                    <div class="report-card-header">
                                        <div class="report-icon employees-icon">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div class="report-badge">
                                            <span class="badge bg-success">Directory</span>
                                        </div>
                                    </div>
                                    <div class="report-card-body">
                                        <h5 class="report-title">Employees Report</h5>
                                        <p class="report-description">Comprehensive employee directory featuring contact details, departmental information, job positions, and the number of assigned IT assets per employee.</p>
                                        <div class="report-features">
                                            <span class="feature-tag">Contact Info</span>
                                            <span class="feature-tag">Department</span>
                                            <span class="feature-tag">Asset Count</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <!-- System History Report -->
                            <div class="col-lg-12 col-md-12">
                                <div class="report-card h-100">
                                    <div class="report-card-header">
                                        <div class="report-icon history-icon">
                                            <i class="fas fa-history"></i>
                                        </div>
                                        <div class="report-badge">
                                            <span class="badge bg-warning">Audit Trail</span>
                                        </div>
                                    </div>
                                    <div class="report-card-body">
                                        <h5 class="report-title">System History Report</h5>
                                        <p class="report-description">Complete audit trail documenting all system assignments, transfers, returns, and maintenance activities with timestamps and detailed notes for compliance tracking.</p>
                                        <div class="report-features">
                                            <span class="feature-tag">Timestamps</span>
                                            <span class="feature-tag">Transfers</span>
                                            <span class="feature-tag">Compliance</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Report Generation Tips -->
                        <div class="report-tips mt-4">
                            <div class="alert alert-light border-0" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                                    <div>
                                        <h6 class="mb-2 text-dark">Report Generation Tips</h6>
                                        <ul class="mb-0 text-muted small">
                                            <li>CSV format is ideal for data analysis in Excel or Google Sheets</li>
                                            <li>PDF format provides formatted reports suitable for presentations</li>
                                            <li>Reports are generated based on your current access level and branch permissions</li>
                                            <li>All reports include timestamps and can be regenerated at any time</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

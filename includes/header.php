<?php
// Include cache control
require_once 'config/cache_control.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>IT Asset Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #1d4ed8;
            --dark-blue: #1e3a8a;
            --light-blue: #dbeafe;
            --sidebar-width: 220px;
            --header-height: 70px;
        }
        
        /* Dark Mode Variables */
        [data-theme="dark"] {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --accent-color: #5b21b6;
            --dark-blue: #3730a3;
            --light-blue: #312e81;
            --bg-color: #0f0f23;
            --card-bg: #1a1a2e;
            --card-hover: #16213e;
            --text-color: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --gradient-start: #1e1b4b;
            --gradient-end: #312e81;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Dark Mode Styles */
        [data-theme="dark"] body {
            background: linear-gradient(135deg, var(--bg-color) 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text-color);
            position: relative;
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
        
        [data-theme="dark"] .card {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            box-shadow: 0 8px 32px var(--shadow-color);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(79, 70, 229, 0.2);
            border-color: var(--primary-color);
        }
        
        [data-theme="dark"] .card-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border-color: var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        [data-theme="dark"] .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        [data-theme="dark"] .card:hover .card-header::before {
            left: 100%;
        }
        
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
            color: var(--text-color) !important;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border-color: var(--primary-color);
            color: var(--text-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
            transform: translateY(-1px);
        }
        
        /* Fix dropdown options visibility */
        [data-theme="dark"] .form-select option {
            background-color: var(--card-bg);
            color: var(--text-color);
        }
        
        [data-theme="dark"] .form-select option:hover {
            background-color: var(--card-hover);
            color: var(--text-color);
        }
        
        [data-theme="dark"] .form-select option:checked {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* DataTables Table Dark Mode - Force Override */
        [data-theme="dark"] .dataTables_wrapper .dataTable {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable thead th {
            background-color: var(--gradient-start) !important;
            color: white !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody tr {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody tr:nth-child(odd) {
            background-color: var(--card-hover) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody tr:hover {
            background-color: rgba(79, 70, 229, 0.1) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody td {
            background-color: transparent !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody td a {
            color: var(--primary-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTable tbody td a:hover {
            color: var(--secondary-color) !important;
        }
        
        /* Regular table dark mode */
        [data-theme="dark"] .table {
            color: var(--text-color);
            background-color: var(--card-bg);
        }
        
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        [data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }
        
        [data-theme="dark"] .sidebar {
            background: linear-gradient(180deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border-color: var(--border-color);
            box-shadow: 4px 0 20px var(--shadow-color);
        }
        
        [data-theme="dark"] .sidebar .nav-link {
            color: var(--text-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        [data-theme="dark"] .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(79, 70, 229, 0.1), transparent);
            transition: left 0.3s;
        }
        
        [data-theme="dark"] .sidebar .nav-link:hover {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        [data-theme="dark"] .sidebar .nav-link:hover::before {
            left: 100%;
        }
        
        [data-theme="dark"] .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        [data-theme="dark"] .page-header {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            color: var(--text-color);
            box-shadow: 0 8px 32px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .breadcrumb-item a {
            color: var(--secondary-color);
            transition: color 0.3s ease;
        }
        
        [data-theme="dark"] .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        [data-theme="dark"] .breadcrumb-item.active {
            color: var(--text-muted);
        }
        
        /* Dark Mode Button Enhancements */
        [data-theme="dark"] .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }
        
        [data-theme="dark"] .btn-secondary {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .btn-secondary:hover {
            background: linear-gradient(135deg, var(--card-hover) 0%, var(--primary-color) 100%);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        [data-theme="dark"] .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            height: var(--header-height);
            z-index: 1030;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .navbar {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 6px 12px;
            margin-left: 15px;
            max-width: 200px;
            overflow: hidden;
            text-decoration: none !important;
        }
        
        .user-info:hover {
            background: rgba(255,255,255,0.2);
            color: white !important;
        }
        
        .user-info .text-white {
            font-size: 0.85rem;
            line-height: 1.2;
        }
        
        .user-info small {
            font-size: 0.75rem;
            display: block;
            margin-top: 2px;
            opacity: 0.8;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        /* Responsive adjustments for user info */
        @media (max-width: 768px) {
            .user-info {
                max-width: 150px;
                padding: 4px 8px;
                margin-left: 10px;
            }
            
            .user-info .text-white {
                font-size: 0.8rem;
            }
            
            .user-info small {
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 576px) {
            .user-info {
                max-width: 120px;
                padding: 3px 6px;
            }
            
            .user-info .text-white {
                font-size: 0.75rem;
            }
            
            .user-info small {
                font-size: 0.65rem;
            }
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
        }
        
        .content-wrapper {
            padding: 20px 10px 20px 20px;
            max-width: 100%;
            margin: 0;
        }
        
        /* Make cards wider and reduce gaps */
        .card {
            margin-bottom: 20px;
        }
        
        .table-responsive {
            margin: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 10px 0 0 0;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            font-weight: 600;
        }
        
        .card-header h6 {
            color: white !important;
            font-weight: 700 !important;
            margin: 0;
        }
        
        .card-header .m-0 {
            color: white !important;
            font-weight: 700 !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30, 64, 175, 0.4);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #e9ecef;
        }
        
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .badge-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
        
        .badge-danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }
        
        .badge-info {
            background: linear-gradient(135deg, #3b82f6, #1e40af);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Hide Bootstrap alerts (replaced by professional notifications) */
        .alert {
            display: none !important;
        }
        
        /* Ensure notification container is always on top */
        #notification-container {
            z-index: 9999 !important;
        }
        
        /* Smooth transitions for notifications */
        .notification-slide-in {
            animation: slideInRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .notification-slide-out {
            animation: slideOutRight 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* DataTables Search Icon Styling */
        .dataTables_filter {
            position: relative;
        }
        
        .dataTables_filter input[type="search"] {
            padding-left: 35px !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat !important;
            background-position: 10px center !important;
            background-size: 16px 16px !important;
        }
        
        .dataTables_filter input[type="search"]:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%231e40af' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
        }
        
        /* Dark Mode DataTables */
        [data-theme="dark"] .dataTables_filter input[type="search"] {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-color);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23a0a0a0' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat !important;
            background-position: 10px center !important;
            background-size: 16px 16px !important;
        }
        
        [data-theme="dark"] .dataTables_filter input[type="search"]:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%233b82f6' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat !important;
            background-position: 10px center !important;
            background-size: 16px 16px !important;
        }
        
        /* DataTables Dark Mode - Complete Override */
        [data-theme="dark"] .dataTables_wrapper {
            color: var(--text-color);
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_length,
        [data-theme="dark"] .dataTables_wrapper .dataTables_filter,
        [data-theme="dark"] .dataTables_wrapper .dataTables_info,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate {
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_length select,
        [data-theme="dark"] .dataTables_wrapper .dataTables_filter input {
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_length select option {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-color) !important;
            background-color: transparent !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            color: var(--primary-color) !important;
            background-color: var(--card-hover) !important;
            border-color: var(--primary-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: var(--text-muted) !important;
            background-color: transparent !important;
            border-color: var(--border-color) !important;
        }

        /* Fix pagination buttons with white backgrounds */
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.first,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.next,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.last {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-color) !important;
            margin: 0 2px !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            transition: all 0.3s ease !important;
        }

        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.first:hover,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.previous:hover,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.next:hover,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.last:hover {
            background-color: var(--card-hover) !important;
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            transform: translateY(-1px) !important;
        }

        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.first.disabled,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.previous.disabled,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.next.disabled,
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.last.disabled {
            background-color: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-muted) !important;
            opacity: 0.5 !important;
        }
        
        /* Fix white pagination buttons in dark mode */
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-color) !important;
            margin: 0 2px !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            transition: all 0.3s ease !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: var(--card-hover) !important;
            border-color: var(--primary-color) !important;
            color: var(--primary-color) !important;
            transform: translateY(-1px) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }
        
        /* Fix white input backgrounds in dark mode */
        [data-theme="dark"] .dataTables_wrapper .dataTables_length select,
        [data-theme="dark"] .dataTables_wrapper .dataTables_filter input {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .dataTables_length select option {
            background-color: var(--card-bg) !important;
            color: var(--text-color) !important;
        }

        /* Dark Mode Dashboard Components */
        [data-theme="dark"] .stat-item {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
            color: var(--text-color);
        }

        [data-theme="dark"] .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.2);
            border-color: var(--primary-color);
        }

        [data-theme="dark"] .stat-item .stat-number {
            color: var(--text-color) !important;
        }

        [data-theme="dark"] .stat-item .stat-label {
            color: var(--text-muted) !important;
        }

        [data-theme="dark"] .chart-container {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .chart-legend {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .legend-item {
            background: var(--card-hover);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
        }

        [data-theme="dark"] .legend-item:hover {
            background: var(--primary-color);
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        }

        [data-theme="dark"] .legend-label {
            color: var(--text-color);
        }

        [data-theme="dark"] .legend-count {
            color: var(--secondary-color);
        }

        /* Dark Mode Border Colors */
        [data-theme="dark"] .border-left-primary {
            border-left: 0.25rem solid var(--primary-color) !important;
        }

        [data-theme="dark"] .border-left-success {
            border-left: 0.25rem solid #10b981 !important;
        }

        [data-theme="dark"] .border-left-warning {
            border-left: 0.25rem solid #f59e0b !important;
        }

        [data-theme="dark"] .border-left-info {
            border-left: 0.25rem solid #06b6d4 !important;
        }

        /* Dark Mode Text Colors */
        [data-theme="dark"] .text-gray-300 {
            color: var(--text-muted) !important;
        }

[data-theme="dark"] .text-gray-800 {
    color: var(--text-color) !important;
}

/* Dark Mode Recent Activities Styling */
[data-theme="dark"] .list-group-item {
    background-color: var(--card-bg) !important;
    border-color: var(--border-color) !important;
    color: var(--text-color) !important;
    transition: all 0.3s ease;
}

[data-theme="dark"] .list-group-item:hover {
    background-color: var(--card-hover) !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .list-group-item .text-dark {
    color: var(--text-color) !important;
}

[data-theme="dark"] .list-group-item .text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .list-group-item .text-center.text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .list-group-item .text-center.text-muted i {
    color: var(--text-muted) !important;
}

/* Dark Mode Activity Icons */
[data-theme="dark"] .list-group-item .text-primary {
    color: var(--primary-color) !important;
}

[data-theme="dark"] .list-group-item .text-success {
    color: #10b981 !important;
}

[data-theme="dark"] .list-group-item .text-warning {
    color: #f59e0b !important;
}

[data-theme="dark"] .list-group-item .text-danger {
    color: #ef4444 !important;
}

[data-theme="dark"] .list-group-item .text-info {
    color: #06b6d4 !important;
}

[data-theme="dark"] .list-group-item .text-muted {
    color: var(--text-muted) !important;
}

/* Enhanced Recent Activities Styling */
.recent-activities-item {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.recent-activities-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

[data-theme="dark"] .recent-activities-item {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
    border-left-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
}

[data-theme="dark"] .recent-activities-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    border-left-color: var(--secondary-color);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    transition: all 0.3s ease;
}

.activity-icon.primary {
    background: rgba(79, 70, 229, 0.1);
    color: var(--primary-color);
}

.activity-icon.success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.activity-icon.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.activity-icon.danger {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.activity-icon.info {
    background: rgba(6, 182, 212, 0.1);
    color: #06b6d4;
}

[data-theme="dark"] .activity-icon.primary {
    background: rgba(79, 70, 229, 0.2);
    color: var(--primary-color);
}

[data-theme="dark"] .activity-icon.success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

[data-theme="dark"] .activity-icon.warning {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

[data-theme="dark"] .activity-icon.danger {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

[data-theme="dark"] .activity-icon.info {
    background: rgba(6, 182, 212, 0.2);
    color: #06b6d4;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    margin-bottom: 4px;
    color: var(--text-color);
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}

.activity-branch {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 400;
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

/* Fix Available Reports white backgrounds */
[data-theme="dark"] .card.shadow-lg {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%) !important;
    border: 1px solid var(--border-color) !important;
    box-shadow: 0 8px 32px rgba(79, 70, 229, 0.1) !important;
}

[data-theme="dark"] .card.shadow-lg .card-body {
    background: transparent !important;
}

[data-theme="dark"] .card.shadow-lg .card-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%) !important;
    border-color: var(--border-color) !important;
}

/* Fix any remaining white backgrounds in cards */
[data-theme="dark"] .card {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%) !important;
    border: 1px solid var(--border-color) !important;
}

[data-theme="dark"] .card .card-body {
    background: transparent !important;
}

[data-theme="dark"] .card .card-header {
    background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%) !important;
    border-color: var(--border-color) !important;
}

/* Fix any white backgrounds in report cards specifically */
[data-theme="dark"] .report-card {
    background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-color) !important;
}

[data-theme="dark"] .report-card .report-title {
    color: var(--text-color) !important;
}

[data-theme="dark"] .report-card .report-description {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .report-card .feature-tag {
    background: var(--card-hover) !important;
    color: var(--text-color) !important;
    border: 1px solid var(--border-color) !important;
}

[data-theme="dark"] .report-card .feature-tag:hover {
    background: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}
        
        /* Dark Mode Alert Enhancements */
        [data-theme="dark"] .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #10b981;
        }
        
        [data-theme="dark"] .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        [data-theme="dark"] .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }
        
        [data-theme="dark"] .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        
        /* Dark Mode Badge Enhancements */
        [data-theme="dark"] .badge {
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        [data-theme="dark"] .badge-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .badge-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .badge-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .badge-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
            color: white !important;
        }
        
        /* Dark Mode Modal Enhancements */
        [data-theme="dark"] .modal-content {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--card-hover) 100%);
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        [data-theme="dark"] .modal-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            border-color: var(--border-color);
        }
        
        [data-theme="dark"] .modal-footer {
            border-color: var(--border-color);
        }
    </style>
    
    <!-- Dark Mode JavaScript -->
    <script>
        // Enhanced theme application with multiple fallbacks
        document.addEventListener('DOMContentLoaded', function() {
            const sessionTheme = '<?php echo $_SESSION['theme'] ?? 'light'; ?>';
            const localStorageTheme = localStorage.getItem('theme');
            
            // Priority: Session > localStorage > default
            const themeToApply = sessionTheme || localStorageTheme || 'light';
            
            // Apply theme with multiple attempts
            applyThemeWithFallback(themeToApply);
            
            // Store theme in localStorage as backup
            localStorage.setItem('theme', themeToApply);
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
            
            // Store in localStorage as backup
            localStorage.setItem('theme', theme);
        }
        
        function applyThemeWithFallback(theme) {
            try {
                applyTheme(theme);
            } catch (error) {
                console.error('Theme application failed:', error);
                // Fallback: Direct DOM manipulation
                const html = document.documentElement;
                if (theme === 'dark') {
                    html.setAttribute('data-theme', 'dark');
                } else {
                    html.removeAttribute('data-theme');
                }
            }
        }
        
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                const currentTheme = '<?php echo $_SESSION['theme'] ?? 'light'; ?>';
                if (currentTheme === 'auto') {
                    applyThemeWithFallback('auto');
                }
            });
        }
        
        // Additional safety: Reapply theme after a short delay
        setTimeout(() => {
            const currentTheme = localStorage.getItem('theme') || '<?php echo $_SESSION['theme'] ?? 'light'; ?>';
            if (currentTheme) {
                applyThemeWithFallback(currentTheme);
            }
        }, 500);
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo isSuperAdmin() ? 'dashboard.php' : 'branch_dashboard.php'; ?>">
                <i class="fas fa-laptop-code me-2"></i>IT Asset Management
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center user-info" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <div>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <small class="d-block"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

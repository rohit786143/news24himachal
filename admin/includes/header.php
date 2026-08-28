<?php
/**
 * Admin Panel Header & Layout Component
 * Himachal News Portal - Khabar 24 Admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Authentication check: Redirect unauthenticated users to login.php
if (empty($_SESSION['admin_user'])) {
    header("Location: /admin/login.php");
    exit;
}

$currentUser = $_SESSION['admin_user'];
$isAdmin = ($currentUser['role'] === 'admin');
$isEditor = ($currentUser['role'] === 'editor');
$currentUserId = (int)$currentUser['id'];

// Restrict editor access to admin-only pages
$adminOnlyPages = [
    'categories.php',
    'pages.php',
    'page-edit.php',
    'settings.php',
    'advertisements.php',
    'live-bulletins.php',
    'users.php',
    'user-edit.php',
    'messages.php',
    'notifications.php',
    'subscribers.php'
];

if ($isEditor && in_array($currentPage, $adminOnlyPages)) {
    $_SESSION['flash_message'] = "अनुमति अस्वीकृत: आपके पास इस सेक्शन को एक्सेस करने का अधिकार नहीं है।";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

// Flash message support
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Get quick stats for sidebar badges
$pendingMessages = (int)$pdo->query("SELECT COUNT(*) FROM `contacts`")->fetchColumn();
$totalSubscribers = (int)$pdo->query("SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'active'")->fetchColumn();
$totalUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();
$siteName = getSetting($pdo, 'site_name', 'News 24 Himachal');

// Ensure proper trailing slash for /admin directory to prevent broken relative links
if (isset($_SERVER['REQUEST_URI']) && preg_match('#^/admin(\?.*)?$#i', $_SERVER['REQUEST_URI'])) {
    $queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: /admin/" . $queryString);
    exit;
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <base href="/admin/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? sanitize($adminTitle) . ' | ' : '' ?>एडमिन कंट्रोल पैनल - <?= sanitize($siteName) ?></title>
    
    <!-- Google Fonts: Inter & Outfit & Hind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Quill Rich Text Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <style>
        :root {
            /* Bright 70% White / Light palette */
            --bg-body: #F8FAFC;
            --bg-card: #FFFFFF;
            --bg-card-hover: #F1F5F9;
            --bg-input: #FFFFFF;
            --border-color: #E2E8F0;
            --border-light: #CBD5E1;
            
            /* Signature Red & Royal Blue Accent */
            --primary: #E31B23;
            --primary-hover: #C41219;
            --primary-light: rgba(227, 27, 35, 0.08);
            --primary-blue: #2F3E9E;
            --primary-blue-dark: #1E2B7B;
            
            /* Navy & Dark Charcoal Elements (Sidebar & Deep Typography) */
            --bg-sidebar: #101935;
            --bg-sidebar-card: #182348;
            --border-sidebar: #1F2E5E;
            --text-heading: #0F172A;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --text-dim: #94A3B8;

            /* Accent Colors */
            --accent-blue: #0284c7;
            --accent-green: #16a34a;
            --accent-amber: #d97706;

            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 14px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', 'Hind', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            line-height: 1.5;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Layout Structure */
        .admin-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* 20% Black Sleek Sidebar */
        .admin-sidebar {
            width: 270px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-brand {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-sidebar);
            background: linear-gradient(180deg, rgba(229,9,20,0.12) 0%, transparent 100%);
        }

        .sidebar-logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: #fff;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.45);
        }

        .sidebar-brand-text h2 {
            font-size: 1.15rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.2;
        }

        .sidebar-brand-text span {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            flex-grow: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .menu-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748B;
            font-weight: 700;
            padding: 14px 12px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            color: #94A3B8;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.92rem;
            transition: var(--transition);
        }

        .nav-item-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-item i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
            color: #64748B;
            transition: var(--transition);
        }

        .nav-item:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.06);
        }

        .nav-item:hover i {
            color: var(--primary);
        }

        .nav-item.active {
            color: #FFFFFF;
            background: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.4);
        }

        .nav-item.active i {
            color: #FFFFFF;
        }

        .nav-badge {
            background: #1E293B;
            border: 1px solid #334155;
            color: #F8FAFC;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .nav-item.active .nav-badge {
            background: rgba(255, 255, 255, 0.25);
            border-color: transparent;
            color: #fff;
        }

        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--border-sidebar);
            background: rgba(0, 0, 0, 0.3);
        }

        .view-site-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.12);
            color: #38BDF8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            padding: 10px;
            border-radius: var(--radius-sm);
            font-size: 0.86rem;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition);
        }

        .view-site-btn:hover {
            background: #38BDF8;
            color: #000;
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.35);
        }

        /* Bright Main Content Area */
        .admin-main {
            margin-left: 270px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: var(--bg-body);
        }

        /* Bright Topbar with Red and Dark Accents */
        .admin-topbar {
            height: 68px;
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 900;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.3px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #FFFFFF;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid var(--primary);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(229, 9, 20, 0.25);
        }

        .topbar-btn:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.35);
        }

        .topbar-btn-secondary {
            background: #FFFFFF;
            color: var(--text-main);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .topbar-btn-secondary:hover {
            background: #F8FAFC;
            color: var(--primary);
            border-color: var(--border-light);
            box-shadow: var(--shadow-md);
        }

        /* Content Body Area */
        .admin-content {
            padding: 30px 32px;
            flex-grow: 1;
        }

        /* Flash Message Alert */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }

        .alert-danger {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        .alert-info {
            background: #F0F9FF;
            border: 1px solid #BAE6FD;
            color: #0369A1;
        }

        /* Bright Statistics Cards: 4 in 1 Single Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 580px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-light);
            box-shadow: var(--shadow-md);
        }

        .stat-info {
            min-width: 0;
        }

        .stat-info h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 700;
        }

        .stat-info .stat-number {
            font-size: 1.95rem;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1;
        }

        .stat-icon-wrap {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-icon-red { background: #FEE2E2; color: var(--primary); }
        .stat-icon-blue { background: #E0F2FE; color: var(--accent-blue); }
        .stat-icon-green { background: #DCFCE7; color: var(--accent-green); }
        .stat-icon-amber { background: #FEF3C7; color: var(--accent-amber); }

        /* Bright Panels / Boxes */
        .panel {
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 28px;
            box-shadow: var(--shadow-sm);
        }

        .panel-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F8FAFC;
        }

        .panel-title {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--primary);
        }

        .panel-body {
            padding: 24px;
            background: #FFFFFF;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .col-12 { grid-column: span 12; }
        .col-8 { grid-column: span 8; }
        .col-6 { grid-column: span 6; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }

        .form-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label .required {
            color: var(--primary);
        }

        .form-control {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            color: var(--text-heading);
            font-size: 0.94rem;
            font-family: inherit;
            transition: var(--transition);
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.15);
            background: #FFFFFF;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 12px 16px;
            background: #F8FAFC;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .form-check:hover {
            border-color: var(--border-light);
            background: #F1F5F9;
        }

        .form-check input[type="checkbox"] {
            accent-color: var(--primary);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Bright Table Design */
        .table-responsive {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
            background: #FFFFFF;
        }

        .admin-table th {
            padding: 14px 18px;
            background: #F8FAFC;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
        }

        .admin-table tbody tr {
            transition: var(--transition);
        }

        .admin-table tbody tr:hover {
            background: #F8FAFC;
        }

        .table-thumb {
            width: 56px;
            height: 42px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: #E2E8F0;
            border: 1px solid var(--border-color);
        }

        .action-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            background: #FFFFFF;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .btn-icon:hover {
            color: var(--text-heading);
            border-color: var(--border-light);
            background: #F8FAFC;
            transform: translateY(-1px);
        }

        .btn-icon-edit:hover { background: #E0F2FE; color: var(--accent-blue); border-color: #BAE6FD; }
        .btn-icon-delete:hover { background: #FEE2E2; color: var(--primary); border-color: #FECACA; }

        /* Crisp Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 4px;
        }

        .badge-red { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; }
        .badge-blue { background: #E0F2FE; color: #0284C7; border: 1px solid #BAE6FD; }
        .badge-green { background: #DCFCE7; color: #16A34A; border: 1px solid #BBF7D0; }
        .badge-gray { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }

        /* Rich Text Editor Container in Bright Theme */
        .quill-wrapper {
            background: #FFFFFF;
            border: 1.5px solid #CBD5E1;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .ql-toolbar.ql-snow {
            background: #F8FAFC;
            border: none !important;
            border-bottom: 1.5px solid #CBD5E1 !important;
            padding: 10px 14px !important;
            font-family: inherit;
        }
        .ql-snow .ql-formats {
            margin-right: 12px !important;
            margin-bottom: 6px !important;
        }
        .ql-snow .ql-picker.ql-header {
            width: 135px;
        }
        .ql-snow .ql-picker.ql-size {
            width: 100px;
        }
        .ql-snow .ql-picker-label {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        .ql-snow .ql-picker-options {
            border-radius: 6px;
            box-shadow: var(--shadow-md);
            border-color: #CBD5E1 !important;
        }
        .ql-snow .ql-toolbar button {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            transition: var(--transition);
        }
        .ql-snow .ql-toolbar button:hover,
        .ql-snow .ql-toolbar button.ql-active,
        .ql-snow .ql-picker-label:hover,
        .ql-snow .ql-picker-label.ql-active {
            background-color: #E2E8F0;
            color: var(--primary) !important;
        }
        .ql-snow .ql-toolbar button:hover .ql-stroke,
        .ql-snow .ql-toolbar button.ql-active .ql-stroke,
        .ql-snow .ql-picker-label:hover .ql-stroke,
        .ql-snow .ql-picker-label.ql-active .ql-stroke {
            stroke: var(--primary) !important;
        }
        .ql-snow .ql-toolbar button:hover .ql-fill,
        .ql-snow .ql-toolbar button.ql-active .ql-fill,
        .ql-snow .ql-picker-label:hover .ql-fill,
        .ql-snow .ql-picker-label.ql-active .ql-fill {
            fill: var(--primary) !important;
        }
        .ql-container.ql-snow {
            border: none !important;
            min-height: 360px;
            font-size: 1.05rem;
            color: var(--text-heading);
            font-family: 'Hind', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #FFFFFF;
            line-height: 1.85;
        }
        .ql-editor {
            min-height: 360px;
            padding: 16px 20px !important;
        }
        .ql-editor p {
            margin-bottom: 14px;
        }

        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .col-8, .col-6, .col-4, .col-3 {
                grid-column: span 12;
            }
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo-icon">
                <i class="fas <?= $isAdmin ? 'fa-shield-halved' : 'fa-feather-pointed' ?>" style="color: #FFFFFF;"></i>
            </div>
            <div class="sidebar-brand-text">
                <h2><?= sanitize($siteName) ?></h2>
                <span style="<?= $isAdmin ? 'color: var(--primary);' : 'color: #38BDF8;' ?> font-weight: 700;">
                    <?= $isAdmin ? '👑 Admin Panel' : '✍️ Reporter Portal' ?>
                </span>
            </div>
        </div>

        <!-- Logged-in User Info Card in Sidebar -->
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-sidebar); background: rgba(255,255,255,0.02); display: flex; align-items: center; gap: 12px;">
            <img src="<?= sanitize($currentUser['avatar']) ?>" alt="User Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $isAdmin ? 'var(--primary)' : '#38BDF8' ?>; background: #222;" onerror="this.src='https://via.placeholder.com/44?text=User';">
            <div style="flex-grow: 1; min-width: 0;">
                <div style="font-size: 0.9rem; font-weight: 700; color: #FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= sanitize($currentUser['name']) ?>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; margin-top: 2px;">
                    <span class="badge <?= $isAdmin ? 'badge-red' : 'badge-blue' ?>" style="font-size: 0.65rem; padding: 1px 6px;">
                        <?= $isAdmin ? '👑 मुख्य एडमिन' : '✍️ संवाददाता' ?>
                    </span>
                    <a href="/admin/profile.php" style="color: #94A3B8; font-size: 0.75rem; text-decoration: none;" title="प्रोफ़ाइल एडिट करें">
                        <i class="fas fa-pen-to-square"></i>
                    </a>
                </div>
            </div>
        </div>

        <nav class="sidebar-menu">
            <?php if ($isEditor): ?>
                <!-- ==========================================
                     EDITOR ONLY DEDICATED SIDEBAR (4 ITEMS ONLY)
                     ========================================== -->
                <span class="menu-label">रिपोर्टर वर्कस्पेस</span>
                
                <a href="/admin/index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-gauge-high"></i>
                        <span>मेरा वर्कस्पेस (Overview)</span>
                    </div>
                </a>

                <a href="/admin/post-edit.php" class="nav-item <?= in_array($currentPage, ['post-edit.php']) && !isset($_GET['id']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-pen-nib"></i>
                        <span>नई खबर लिखें (Add Post)</span>
                    </div>
                </a>

                <a href="/admin/posts.php" class="nav-item <?= in_array($currentPage, ['posts.php']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-newspaper"></i>
                        <span>मेरी प्रकाशित खबरें (My Posts)</span>
                    </div>
                </a>

                <span class="menu-label">खाता सेटिंग्स</span>

                <a href="/admin/profile.php" class="nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-user-pen"></i>
                        <span>मेरी प्रोफाइल एवं पासवर्ड</span>
                    </div>
                </a>
            <?php else: ?>
                <!-- ==========================================
                     ADMIN MASTER SIDEBAR (ALL MODULES)
                     ========================================== -->
                <span class="menu-label">मुख्य मेनू</span>
                
                <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-chart-pie"></i>
                        <span>डैशबोर्ड (Dashboard)</span>
                    </div>
                </a>

                <a href="visitors.php" class="nav-item <?= $currentPage === 'visitors.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-chart-line" style="color: #38BDF8;"></i>
                        <span>दैनिक विज़िटर्स (Visitors & Traffic)</span>
                    </div>
                </a>

                <span class="menu-label">समाचार सामग्री (News CMS)</span>

                <a href="posts.php" class="nav-item <?= in_array($currentPage, ['posts.php']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-file-lines"></i>
                        <span>सभी खबरें (All Posts)</span>
                    </div>
                </a>

                <a href="post-edit.php" class="nav-item <?= in_array($currentPage, ['post-edit.php']) && !isset($_GET['id']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-plus-circle"></i>
                        <span>नई खबर जोड़ें (Add Post)</span>
                    </div>
                </a>

                <a href="categories.php" class="nav-item <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-folder-tree"></i>
                        <span>श्रेणियां (Categories)</span>
                    </div>
                </a>

                <a href="live-bulletins.php" class="nav-item <?= $currentPage === 'live-bulletins.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-tower-broadcast" style="color: var(--primary-red);"></i>
                        <span>लाइव बुलेटिन (Live Stream)</span>
                    </div>
                </a>

                <span class="menu-label">संपादक एवं उपयोगकर्ता</span>

                <a href="users.php" class="nav-item <?= in_array($currentPage, ['users.php', 'user-edit.php']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-users-viewfinder"></i>
                        <span>संपादक / यूज़र्स (Editors)</span>
                    </div>
                    <?php if ($totalUsersCount > 0): ?>
                        <span class="nav-badge"><?= $totalUsersCount ?></span>
                    <?php endif; ?>
                </a>

                <span class="menu-label">पेज एवं वेबसाइट सेटिंग</span>

                <a href="pages.php" class="nav-item <?= in_array($currentPage, ['pages.php', 'page-edit.php']) ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-book-open"></i>
                        <span>पेज CMS (About, Terms...)</span>
                    </div>
                </a>

                <a href="settings.php" class="nav-item <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-sliders"></i>
                        <span>साइट सेटिंग्स (Settings)</span>
                    </div>
                </a>

                <a href="advertisements.php" class="nav-item <?= $currentPage === 'advertisements.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-rectangle-ad"></i>
                        <span>विज्ञापन प्रबंधन (Ads)</span>
                    </div>
                </a>

                <a href="messages.php" class="nav-item <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>संपर्क संदेश (Messages)</span>
                    </div>
                    <?php if ($pendingMessages > 0): ?>
                        <span class="nav-badge"><?= $pendingMessages ?></span>
                    <?php endif; ?>
                </a>

                <span class="menu-label">अलर्ट एवं यूज़र्स</span>

                <a href="notifications.php" class="nav-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-bell"></i>
                        <span>पुश नोटिफिकेशन (Alerts)</span>
                    </div>
                </a>

                <a href="subscribers.php" class="nav-item <?= $currentPage === 'subscribers.php' ? 'active' : '' ?>">
                    <div class="nav-item-content">
                        <i class="fas fa-users-gear"></i>
                        <span>सब्सक्राइबर्स (Subscribers)</span>
                    </div>
                    <?php if ($totalSubscribers > 0): ?>
                        <span class="nav-badge"><?= $totalSubscribers ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>" target="_blank" class="view-site-btn">
                <i class="fas fa-arrow-up-right-from-square"></i>
                <span>वेबसाइट लाइव देखें</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" class="btn-icon" id="sidebarToggleBtn" style="display: none;" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="topbar-title"><?= $adminHeading ?? 'एडमिन डैशबोर्ड' ?></h1>
            </div>
            <div class="topbar-right">
                <a href="/admin/post-edit.php" class="topbar-btn">
                    <i class="fas fa-plus"></i>
                    <span>नई खबर जोड़ें</span>
                </a>
                
                <a href="/admin/profile.php" class="topbar-btn topbar-btn-secondary" title="मेरी प्रोफाइल (<?= sanitize($currentUser['name']) ?>)">
                    <i class="fas fa-user-circle"></i>
                    <span><?= sanitize($currentUser['name']) ?></span>
                </a>

                <?php if ($isAdmin): ?>
                    <a href="/admin/settings.php" class="topbar-btn topbar-btn-secondary" title="सेटिंग्स">
                        <i class="fas fa-gear"></i>
                    </a>
                <?php endif; ?>

                <a href="/admin/logout.php" class="topbar-btn topbar-btn-secondary" title="लॉगआउट करें (Logout)" style="color: #DC2626; border-color: #FECACA; background: #FEF2F2;" onclick="return confirm('क्या आप वाकई लॉगआउट करना चाहते हैं?')">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </header>

        <!-- Admin Content Body -->
        <main class="admin-content">
            <?php if (!empty($flashMessage)): ?>
                <div class="alert alert-<?= $flashType ?>">
                    <div><i class="fas fa-circle-info"></i> <?= $flashMessage ?></div>
                    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

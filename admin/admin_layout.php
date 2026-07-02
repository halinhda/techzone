<?php
/**
 * TECHZONE ADMIN LAYOUT
 * ---------------------
 * Sidebar + Topbar layout dùng riêng cho admin panel.
 * Cách dùng:
 *   $pageTitle = 'Tên trang';
 *   $currentPage = 'dashboard'; // key để highlight sidebar
 *   require_once __DIR__ . '/admin_layout.php';
 *   // Sau đó viết HTML nội dung chính
 */

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php');
    exit;
}

$adminName = htmlspecialchars($_SESSION['user']['fullname'] ?? 'Admin');
$pageTitle = $pageTitle ?? 'Admin Panel';
$currentPage = $currentPage ?? '';

$menuItems = [
    ['key' => 'dashboard',  'icon' => '📊', 'label' => 'Dashboard',     'url' => '/bainhom/admin/index.php'],
    ['key' => 'products',   'icon' => '📦', 'label' => 'Sản phẩm',     'url' => '/bainhom/admin/products.php'],
    ['key' => 'categories', 'icon' => '📁', 'label' => 'Danh mục',     'url' => '/bainhom/admin/categories.php'],
    ['key' => 'orders',     'icon' => '📋', 'label' => 'Đơn hàng',     'url' => '/bainhom/admin/orders.php'],
    ['key' => 'users',      'icon' => '👥', 'label' => 'Khách hàng',   'url' => '/bainhom/admin/users.php'],
    ['key' => 'reviews',    'icon' => '⭐', 'label' => 'Đánh giá',     'url' => '/bainhom/admin/reviews.php'],
    ['key' => 'tickets',    'icon' => '🎫', 'label' => 'Hỗ trợ',       'url' => '/bainhom/admin/tickets.php'],
    ['key' => 'reports',    'icon' => '📈', 'label' => 'Báo cáo',      'url' => '/bainhom/admin/reports.php'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — TechZone Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 260px;
            --topbar-h: 64px;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --text-sub: #64748b;
            --border: #e2e8f0;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: rgba(99, 102, 241, 0.15);
            --radius: 12px;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .admin-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.4);
        }

        .sidebar-brand-text {
            color: #fff;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .sidebar-brand-text span {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: var(--text-sub);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background: var(--sidebar-hover);
            color: #e2e8f0;
        }

        .nav-item.active {
            background: var(--sidebar-active);
            color: var(--primary-light);
            font-weight: 600;
        }

        .nav-item .nav-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 12px 8px;
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-footer .nav-item { color: #64748b; font-size: 13px; }

        /* ===== MAIN AREA ===== */
        .admin-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
        }

        .admin-topbar {
            height: var(--topbar-h);
            background: var(--card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(8px);
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .topbar-avatar {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-topbar {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-logout {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-logout:hover {
            background: #fecaca;
        }

        /* ===== CONTENT ===== */
        .admin-content {
            padding: 28px 32px;
            max-width: 1400px;
        }

        /* ===== COMMON COMPONENTS ===== */

        /* Card */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }

        .card-body { padding: 24px; }

        /* Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 22px 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: #eff6ff; color: #2563eb; }
        .stat-icon.green { background: #f0fdf4; color: #16a34a; }
        .stat-icon.purple { background: #faf5ff; color: #7c3aed; }
        .stat-icon.orange { background: #fff7ed; color: #ea580c; }
        .stat-icon.red { background: #fef2f2; color: #dc2626; }
        .stat-icon.indigo { background: #eef2ff; color: #4f46e5; }

        .stat-info { flex: 1; }

        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-sub);
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.2;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.data-table thead {
            background: #f8fafc;
        }

        table.data-table th {
            padding: 13px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            border-bottom: 2px solid var(--border);
        }

        table.data-table td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        table.data-table tbody tr {
            transition: background 0.15s;
        }

        table.data-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); }

        .btn-success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            box-shadow: 0 4px 12px rgba(34,197,94,0.3);
        }
        .btn-success:hover { transform: translateY(-1px); }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-danger:hover { background: #fecaca; }

        .btn-outline {
            background: #fff;
            border: 1px solid var(--border);
            color: var(--text);
        }
        .btn-outline:hover { background: #f8fafc; box-shadow: var(--shadow); }

        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 8px; }

        .btn-icon {
            width: 34px; height: 34px;
            padding: 0;
            justify-content: center;
            border-radius: 8px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #f1f5f9; color: #475569; }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            background: #fff;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-sub);
        }

        .empty-state .empty-icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state .empty-title { font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .empty-state .empty-desc { font-size: 14px; }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .content-grid-equal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Product Image */
        .product-thumb {
            width: 44px; height: 44px;
            border-radius: 10px;
            object-fit: cover;
            background: #f1f5f9;
            border: 1px solid var(--border);
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-cell-name {
            font-weight: 600;
            color: var(--text);
        }

        .product-cell-brand {
            font-size: 12px;
            color: var(--text-sub);
        }

        .price { font-weight: 700; color: #dc2626; }

        /* Stock warning */
        .stock-low { color: #dc2626; font-weight: 700; }
        .stock-ok { color: #16a34a; font-weight: 600; }

        /* Page header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .admin-sidebar { width: 220px; }
            .admin-main { margin-left: 220px; }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .admin-main { margin-left: 0; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">⚡</div>
        <div class="sidebar-brand-text">
            TechZone
            <span>Admin Panel</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?= $item['url'] ?>" class="nav-item <?= $currentPage === $item['key'] ? 'active' : '' ?>">
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <a href="/bainhom/admin/export_orders.php" class="nav-item">
            <span class="nav-icon">📥</span>
            Xuất báo cáo CSV
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/bainhom/index.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            Về trang chủ
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="admin-main">
    <header class="admin-topbar">
        <h2 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h2>
        <div class="topbar-right">
            <div class="topbar-user">
                <div class="topbar-avatar"><?= mb_substr($adminName, 0, 1, 'UTF-8') ?></div>
                <?= $adminName ?>
            </div>
            <a href="/bainhom/controllers/auth.php?action=logout" class="btn-topbar btn-logout">Đăng xuất</a>
        </div>
    </header>

    <main class="admin-content">

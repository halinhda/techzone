<?php
// includes/header.php – Header dùng chung cho tất cả trang
require_once __DIR__ . '/config.php';

$pdo = getDB();
$user = currentUser();
$cartCount = 0;

// Đếm số lượng sản phẩm trong giỏ hàng
$sid = cartSessionId();
$stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) as cnt FROM cart_items WHERE session_id = ?');
$stmt->execute([$sid]);
$cartCount = (int) $stmt->fetchColumn();

// Lấy danh mục
$categories = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?? SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&display=swap"
    rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/bainhom/assets/css/style.css">
</head>

<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col antialiased overflow-x-hidden">



  <!-- TOP ANNOUNCEMENT BAR -->
  <div class="announcement-bar">
    🔥 CHÀO MỪNG ĐẾN VỚI TECHZONE – MIỄN PHÍ VẬN CHUYỂN CHO ĐƠN HÀNG TỪ <?= formatVND(FREE_SHIP_MIN) ?>!
  </div>

  <!-- MAIN HEADER -->
  <header class="main-header">
    <div class="header-inner">

      <!-- Logo -->
      <a href="/bainhom/index.php" class="logo-brand">
        <span class="logo-icon"><i data-lucide="cpu"></i></span>
        <div>
          <h1 class="logo-title">TechZone</h1>
          <p class="logo-sub">Website thiết bị công nghệ phân phối chính hãng</p>
        </div>
      </a>

      <!-- Search -->
      <form action="/bainhom/index.php" method="GET" class="header-search">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" name="q" value="<?= clean($_GET['q'] ?? '') ?>"
          placeholder="Tìm kiếm sản phẩm, thương hiệu..." />
      </form>

      <!-- Nav -->
      <nav class="main-nav">
        <a href="/bainhom/index.php"
          class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_GET['page'])) ? 'active' : '' ?>">
          <i data-lucide="shopping-bag"></i><span>Cửa Hàng</span>
        </a>
        <a href="/bainhom/views/cart.php"
          class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'views/cart.php' ? 'active' : '' ?>">
          <i data-lucide="shopping-cart"></i><span>Giỏ Hàng</span>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>

        <?php if ($user): ?>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="/bainhom/admin/orders.php"
              class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/orders.php') !== false ? 'active' : '' ?>">
              <i data-lucide="file-text"></i><span>Quản Lý Đơn</span>
            </a>
          <?php else: ?>
            <a href="/bainhom/views/my_orders.php"
              class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'my_orders.php') !== false ? 'active' : '' ?>">
              <i data-lucide="package"></i>
              <span>Đơn Hàng Của Tôi</span>
            </a>
          <?php endif; ?>
        <?php endif; ?>

        <a href="/bainhom/views/support.php"
          class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : '' ?>">
          <i data-lucide="help-circle"></i><span>Hỗ Trợ</span>
        </a>
      </nav>

      <!-- User Controls -->
      <div class="user-controls">
        <?php if ($user): ?>
          <div class="user-chip">
            <span class="user-avatar"><?= $user['role'] === 'admin' ? '👑' : '👤' ?></span>
            <div class="user-info">
              <span class="user-name"><?= clean($user['fullname']) ?></span>
              <span class="user-role"><?= $user['role'] === 'admin' ? 'Quản Trị Viên' : 'Khách Hàng' ?></span>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
              <a href="/bainhom/admin/" class="btn-sm btn-indigo" title="Admin Panel">
                <i data-lucide="settings"></i>
              </a>
            <?php endif; ?>

            <a href="/bainhom/controllers/auth.php?action=logout" class="btn-sm btn-ghost" title="Đăng xuất"
              style="display: flex; align-items: center; gap: 4px;">
              <i data-lucide="log-out"></i>
              <span style="font-size: 12px; font-weight: 600;">Đăng xuất</span>
            </a>
          </div>
        <?php else: ?>
          <a href="/bainhom/controllers/auth.php" class="nav-link"
            style="background: #1e293b; color: #fff; padding: 8px 16px; border-radius: 6px; display: flex; align-items: center; gap: 6px; font-weight: 600;">
            <i data-lucide="log-in"></i>
            <span>Đăng Nhập</span>
          </a>
        <?php endif; ?>
      </div>

    </div>

    <!-- Category Quick Filter -->
    <div class="category-bar">
      <div class="category-bar-inner">
        <a href="/bainhom/index.php" class="cat-link <?= !isset($_GET['cat']) ? 'active' : '' ?>">Tất cả</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/bainhom/index.php?cat=<?= $cat['slug'] ?>"
            class="cat-link <?= ($_GET['cat'] ?? '') === $cat['slug'] ? 'active' : '' ?>">
            <?= $cat['icon'] ?>   <?= clean($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </header>

  <?php if ($user && $user['role'] === 'admin'): ?>
    <!-- ADMIN QUICK BAR -->
    <div class="admin-bar">
      <div class="admin-bar-inner">
        <span class="admin-bar-label">
          <span class="pulse-dot"></span>
          <i data-lucide="database"></i> ADMIN PANEL ĐANG HOẠT ĐỘNG
        </span>
        <div class="admin-bar-links">
          <a href="/bainhom/admin/index.php">📊 Thống kê</a>
          <a href="/bainhom/admin/products.php">📦 Sản phẩm</a>
          <a href="/bainhom/admin/orders.php">🧾 Đơn hàng</a>
          <a href="/bainhom/admin/users.php">👥 Người dùng</a>
          <span>📞 Hotline: <a href="tel:0909123456">0909 123 456</a></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <main class="main-content">
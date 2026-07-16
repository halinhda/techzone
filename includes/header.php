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

// Current page detection
$currentFile = basename($_SERVER['PHP_SELF']);
$isHome = ($currentFile === 'index.php' && !isset($_GET['page']));
$isCart = ($currentFile === 'cart.php');
$isOrders = (strpos($_SERVER['PHP_SELF'], 'my_orders.php') !== false);
$isSupport = ($currentFile === 'support.php');
$isProfile = ($currentFile === 'profile.php');
$isAdminOrders = (strpos($_SERVER['REQUEST_URI'], '/admin/orders.php') !== false);
$isAdminUsers = (strpos($_SERVER['REQUEST_URI'], '/admin/users.php') !== false);
?>
<!doctype html>
<html lang="vi" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="color-scheme" content="light" />
  <title><?= $pageTitle ?? SITE_NAME ?></title>
  <meta name="description" content="TechZone – Website thiết bị công nghệ phân phối chính hãng, bảo hành điện tử." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="/bainhom/assets/css/style.css">
</head>

<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col antialiased overflow-x-hidden">

  <div class="announcement-bar">
    CHÀO MỪNG ĐẾN VỚI TECHZONE – MIỄN PHÍ VẬN CHUYỂN CHO ĐƠN HÀNG TỪ <?= formatVND(FREE_SHIP_MIN) ?>!
  </div>

  <header class="main-header">
    <!-- ROW 1: Logo + Search + User Controls -->
    <div class="header-inner">

      <a href="/bainhom/index.php" class="logo-brand">
        <span class="logo-icon"><i data-lucide="cpu"></i></span>
        <div>
          <h1 class="logo-title">TechZone</h1>
          <p class="logo-sub">Website thiết bị công nghệ phân phối chính hãng</p>
        </div>
      </a>

      <form action="/bainhom/index.php" method="GET" class="header-search" role="search" style="position: relative;">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" id="search-input" name="q" value="<?= clean($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm sản phẩm, thương hiệu..." autocomplete="off" />
        <div id="search-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); margin-top: 8px; display: none; z-index: 1000; overflow: hidden; max-height: 380px;"></div>
      </form>

      <div class="user-controls">
        <?php if ($user): ?>
          <div class="user-chip">
            <span class="user-avatar"><?= mb_substr($user['fullname'], 0, 1, 'UTF-8') ?></span>
            <div class="user-info">
              <span class="user-name"><?= clean($user['fullname']) ?></span>
              <span class="user-role"><?= $user['role'] === 'admin' ? 'Quản Trị Viên' : 'Khách Hàng' ?></span>
            </div>
            <?php if ($user['role'] === 'admin'): ?>
              <a href="/bainhom/admin/" class="btn-sm btn-indigo" title="Admin Panel">
                <i data-lucide="settings"></i>
              </a>
            <?php else: ?>
              <a href="/bainhom/views/profile.php" class="btn-sm btn-ghost" title="Hồ sơ cá nhân" style="display: flex; align-items: center; gap: 4px;">
                <i data-lucide="user-circle"></i>
              </a>
            <?php endif; ?>

            <a href="/bainhom/controllers/auth.php?action=logout" class="btn-sm btn-ghost" title="Đăng xuất">
              <i data-lucide="log-out"></i>
            </a>
          </div>
        <?php else: ?>
          <a href="/bainhom/controllers/auth.php" class="nav-link" style="background: #1e293b; color: #fff; padding: 8px 16px; border-radius: 8px; display: flex; align-items: center; gap: 6px; font-weight: 600;">
            <i data-lucide="log-in"></i>
            <span>Đăng Nhập</span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile controls -->
      <button class="mobile-search-btn" id="mobile-search-toggle" type="button" aria-label="Tìm kiếm">
        <i data-lucide="search"></i>
      </button>
      <button class="hamburger-btn" id="hamburger-toggle" type="button" aria-label="Menu" aria-expanded="false">
        <i data-lucide="menu"></i>
      </button>

    </div>

    <!-- ROW 2: Navigation Bar -->
    <div class="header-nav-bar">
      <div class="header-nav-bar-inner">
        <nav class="main-nav" aria-label="Menu chính">
          <a href="/bainhom/index.php" class="nav-link <?= $isHome ? 'active' : '' ?>">
            <i data-lucide="shopping-bag"></i><span>Cửa Hàng</span>
          </a>

          <?php if (!$user || $user['role'] !== 'admin'): ?>
            <a href="/bainhom/views/cart.php" class="nav-link <?= $isCart ? 'active' : '' ?>">
              <i data-lucide="shopping-cart"></i><span>Giỏ Hàng</span>
              <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>

          <?php if ($user): ?>
            <?php if ($user['role'] === 'admin'): ?>
              <a href="/bainhom/admin/orders.php" class="nav-link <?= $isAdminOrders ? 'active' : '' ?>">
                <i data-lucide="file-text"></i><span>Quản Lý Đơn</span>
              </a>
              <a href="/bainhom/admin/users.php" class="nav-link <?= $isAdminUsers ? 'active' : '' ?>">
                <i data-lucide="users"></i><span>Quản Lý Người Dùng</span>
              </a>
            <?php else: ?>
              <a href="/bainhom/views/my_orders.php" class="nav-link <?= $isOrders ? 'active' : '' ?>">
                <i data-lucide="package"></i>
                <span>Đơn Hàng Của Tôi</span>
              </a>
            <?php endif; ?>
          <?php endif; ?>

          <a href="/bainhom/views/order_lookup.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'order_lookup.php') ? 'active' : '' ?>">
            <i data-lucide="search"></i><span>Tra Cứu Đơn</span>
          </a>

          <a href="/bainhom/views/support.php" class="nav-link <?= $isSupport ? 'active' : '' ?>">
            <i data-lucide="help-circle"></i><span>Hỗ Trợ</span>
          </a>

          <?php if ($user && $user['role'] !== 'admin'): ?>
            <a href="/bainhom/views/profile.php" class="nav-link <?= $isProfile ? 'active' : '' ?>">
              <i data-lucide="user-circle"></i><span>Hồ Sơ</span>
            </a>
          <?php endif; ?>
        </nav>
      </div>
    </div>

    <!-- Mobile search bar -->
    <div class="mobile-search-bar" id="mobile-search-bar">
      <form action="/bainhom/index.php" method="GET" role="search" style="position: relative; width: 100%;">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" id="mobile-search-input" name="q" value="<?= clean($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm sản phẩm..." autocomplete="off" />
        <div id="mobile-search-suggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); margin-top: 8px; display: none; z-index: 1000; overflow: hidden; max-height: 380px;"></div>
      </form>
    </div>

    <div class="category-bar">
      <div class="category-bar-inner">
        <a href="/bainhom/index.php" class="cat-link <?= !isset($_GET['cat']) ? 'active' : '' ?>">Tất cả</a>
        <?php foreach ($categories as $cat): ?>
          <a href="/bainhom/index.php?cat=<?= $cat['slug'] ?>" class="cat-link <?= ($_GET['cat'] ?? '') === $cat['slug'] ? 'active' : '' ?>">
            <?= $cat['icon'] ?>   <?= clean($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </header>

  <!-- Mobile Nav Overlay -->
  <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>

  <!-- Mobile Nav Drawer -->
  <aside class="mobile-nav-drawer" id="mobile-nav-drawer" aria-label="Menu di động">
    <div class="mobile-nav-header">
      <h3>Menu</h3>
      <button class="mobile-nav-close" id="mobile-nav-close" type="button" aria-label="Đóng menu">
        <i data-lucide="x"></i>
      </button>
    </div>
    <div class="mobile-nav-links">
      <a href="/bainhom/index.php" class="<?= $isHome ? 'active' : '' ?>">
        <i data-lucide="shopping-bag"></i> Cửa Hàng
      </a>
      <?php if (!$user || $user['role'] !== 'admin'): ?>
        <a href="/bainhom/views/cart.php" class="<?= $isCart ? 'active' : '' ?>">
          <i data-lucide="shopping-cart"></i> Giỏ Hàng
          <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="/bainhom/admin/index.php"><i data-lucide="layout-dashboard"></i> Dashboard Admin</a>
          <a href="/bainhom/admin/orders.php" class="<?= $isAdminOrders ? 'active' : '' ?>"><i data-lucide="file-text"></i> Quản Lý Đơn</a>
          <a href="/bainhom/admin/products.php"><i data-lucide="package"></i> Quản Lý Sản Phẩm</a>
          <a href="/bainhom/admin/users.php" class="<?= $isAdminUsers ? 'active' : '' ?>"><i data-lucide="users"></i> Quản Lý Người Dùng</a>
        <?php else: ?>
          <a href="/bainhom/views/profile.php" class="<?= $isProfile ? 'active' : '' ?>">
            <i data-lucide="user-circle"></i> Hồ Sơ Cá Nhân
          </a>
          <a href="/bainhom/views/my_orders.php" class="<?= $isOrders ? 'active' : '' ?>">
            <i data-lucide="package"></i> Đơn Hàng Của Tôi
          </a>
        <?php endif; ?>
      <?php endif; ?>
      <a href="/bainhom/views/support.php" class="<?= $isSupport ? 'active' : '' ?>">
        <i data-lucide="help-circle"></i> Hỗ Trợ
      </a>
    </div>
    <div class="mobile-nav-user">
      <?php if ($user): ?>
        <div class="user-chip">
          <span class="user-avatar"><?= mb_substr($user['fullname'], 0, 1, 'UTF-8') ?></span>
          <div class="user-info">
            <span class="user-name"><?= clean($user['fullname']) ?></span>
            <span class="user-role"><?= $user['role'] === 'admin' ? 'Quản Trị Viên' : 'Khách Hàng' ?></span>
          </div>
        </div>
        <a href="/bainhom/controllers/auth.php?action=logout" class="btn btn-danger btn-sm btn-block" style="margin-top: 12px;">
          <i data-lucide="log-out"></i> Đăng xuất
        </a>
      <?php else: ?>
        <a href="/bainhom/controllers/auth.php" class="btn btn-dark btn-block">
          <i data-lucide="log-in"></i> Đăng Nhập
        </a>
      <?php endif; ?>
    </div>
  </aside>

  <?php if ($user && $user['role'] === 'admin'): ?>
    <div class="admin-bar">
      <div class="admin-bar-inner">
        <span class="admin-bar-label">
          <span class="pulse-dot"></span>
          <i data-lucide="database"></i> ADMIN PANEL ĐANG HOẠT ĐỘNG
        </span>
        <div class="admin-bar-links">
          <a href="/bainhom/admin/index.php">Thống kê</a>
          <a href="/bainhom/admin/products.php">Sản phẩm</a>
          <a href="/bainhom/admin/categories.php">Danh mục</a>
          <span>Hotline: <a href="tel:0909123456">0909 123 456</a></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <main class="main-content">
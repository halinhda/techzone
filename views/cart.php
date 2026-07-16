<?php
require_once __DIR__ . '/../includes/config.php';
$currentUser = currentUser();
$isAdmin = isset($currentUser['role']) && $currentUser['role'] === 'admin';

// 1. Định nghĩa Tiêu đề trang tách biệt hoàn toàn
$pageTitle = $isAdmin ? 'Quản Lý Người Dùng' : 'Giỏ Hàng của bạn';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// 2. TÁCH BIỆT LOGIC XỬ LÝ DATABASE
if ($isAdmin) {
    // ---- LOGIC DÀNH RIÊNG CHO ADMIN (Không đụng gì tới giỏ hàng) ----
    $stmt = $pdo->prepare("SELECT id, fullname AS username, email, role, created_at FROM users ORDER BY id DESC");
    $stmt->execute();
    $usersList = $stmt->fetchAll();
} else {
    // ---- LOGIC DÀNH RIÊNG CHO KHÁCH HÀNG (Giỏ hàng bình thường) ----
    $sid = cartSessionId();
    $userId = $currentUser['id'] ?? null;

    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image_file, p.brand, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.session_id = ? OR (ci.user_id IS NOT NULL AND ci.user_id = ?)
    ");
    $stmt->execute([$sid, $userId]);
    $cartItems = $stmt->fetchAll();

    // cartImage removed in favor of global productImageUrl
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += (float) $item['price'] * (int) $item['quantity'];
    }
    $shipping = $subtotal >= FREE_SHIP_MIN ? 0 : SHIPPING_FEE;
    $total = $subtotal + $shipping;
}
?>

<section class="cart-page container">
  <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
      <p class="section-sub"><?= $isAdmin ? 'Hệ thống Quản trị' : 'Mua sắm' ?></p>
      <h1 class="section-title"><?= $isAdmin ? 'Quản lý người dùng' : 'Giỏ hàng của bạn' ?></h1>
    </div>
    <a href="/bainhom/index.php" class="btn btn-outline btn-sm">← Quay lại trang chủ</a>
  </div>

  <?php if ($isAdmin): ?>
    <div class="empty-state cart-empty-card" style="padding: 40px; text-align: center; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
      <span style="font-size: 48px; display: block; margin-bottom: 16px;">🔑</span>
      <h4 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Tài khoản Quản trị viên</h4>
      <p style="color: #64748b; font-size: 14px; margin-bottom: 24px; max-width: 480px; margin-left: auto; margin-right: auto; line-height: 1.6;">
        Tài khoản quản trị viên (Admin) không hỗ trợ chức năng giỏ hàng và mua sắm trực tuyến. Vui lòng chuyển sang tài khoản khách hàng để mua sắm hoặc quay lại trang Quản trị để quản lý hệ thống.
      </p>
      <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="/bainhom/admin/index.php" class="btn btn-indigo" style="background: #4f46e5; color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">Vào Dashboard Admin</a>
        <a href="/bainhom/controllers/auth.php?action=logout" class="btn btn-outline" style="border: 1px solid #cbd5e1; padding: 10px 20px; border-radius: 8px; font-weight: 600; color: #475569;">Đăng xuất</a>
      </div>
    </div>

  <?php else: ?>
    <?php if (empty($cartItems)): ?>
        <div class="empty-state cart-empty-card">
          <span class="icon"></span>
          <h4>Giỏ hàng đang trống</h4>
          <p>Hãy chọn một sản phẩm phù hợp để bắt đầu mua sắm tại TechZone.</p>
          <a href="/bainhom/index.php" class="btn btn-dark">Xem sản phẩm</a>
        </div>
    <?php else: ?>
        <div class="cart-grid">
          <div class="cart-card-list">
            <?php foreach ($cartItems as $item):
              $itemTotal = (float) $item['price'] * (int) $item['quantity'];
              $img = productImageUrl($item['image_file'] ?? '');
              ?>
              <article class="cart-item-card">
                <div class="cart-item-thumb">
                  <?php if ($img): ?>
                    <img src="<?= clean($img) ?>" alt="<?= clean($item['name']) ?>">
                  <?php else: ?>
                    <span></span>
                  <?php endif; ?>
                </div>
                <div class="cart-item-body">
                  <div>
                    <p class="cart-item-brand"><?= clean($item['brand'] ?? 'TechZone') ?></p>
                    <h3 class="cart-item-name"><?= clean($item['name']) ?></h3>
                    <p class="cart-item-price"><?= formatVND($item['price']) ?> / sản phẩm</p>
                  </div>
                  <div class="cart-item-actions">
                    <div class="qty-control">
                      <button type="button" class="qty-btn js-qty" data-pid="<?= (int) $item['product_id'] ?>"
                        data-delta="-1">−</button>
                      <span class="qty-val"><?= (int) $item['quantity'] ?></span>
                      <button type="button" class="qty-btn js-qty" data-pid="<?= (int) $item['product_id'] ?>"
                        data-delta="1">+</button>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm js-remove-cart"
                      data-pid="<?= (int) $item['product_id'] ?>">Xóa</button>
                  </div>
                </div>
                <div class="cart-item-total"><?= formatVND($itemTotal) ?></div>
              </article>
            <?php endforeach; ?>
          </div>

          <aside class="cart-summary-card">
            <h2 class="cart-summary-title">Tóm tắt đơn hàng</h2>
            <div class="cart-summary-row"><span>Tạm tính</span><strong><?= formatVND($subtotal) ?></strong></div>
            <div class="cart-summary-row"><span>Phí vận chuyển</span><strong><?= $shipping === 0 ? 'Miễn phí' : formatVND($shipping) ?></strong></div>
            <div class="cart-summary-row total"><span>Tổng cộng</span><strong><?= formatVND($total) ?></strong></div>
            <p class="cart-note">Miễn phí vận chuyển khi đơn hàng từ <?= formatVND(FREE_SHIP_MIN) ?>.</p>
            
            <a href="/bainhom/checkout.php" class="btn btn-primary btn-block">
                Tiến hành thanh toán
            </a>
          </aside>
        </div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
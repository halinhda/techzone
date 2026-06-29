<?php
$currentUser = currentUser();
$isAdmin = isset($currentUser['role']) && $currentUser['role'] === 'admin';

// 1. Định nghĩa Tiêu đề trang tách biệt hoàn toàn
$pageTitle = $isAdmin ? 'Quản Lý Người Dùng' : 'Giỏ Hàng của bạn';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();

// 2. TÁCH BIỆT LOGIC XỬ LÝ DATABASE
if ($isAdmin) {
    // ---- LOGIC DÀNH RIÊNG CHO ADMIN (Không đụng gì tới giỏ hàng) ----
    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC");
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

    function cartImage(string $file): string
    {
      if (empty($file))
        return '';
      $path = ltrim($file, '/');
      if (str_starts_with($path, 'assets/images/')) {
        $path = substr($path, strlen('assets/images/'));
      } elseif (str_starts_with($path, 'images/')) {
        $path = substr($path, strlen('images/'));
      }
      return '/bainhom/assets/images/' . $path;
    }

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
    <div class="admin-management-section" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #333;">Danh sách thành viên (<?= count($usersList) ?>)</h3>
        <a href="/bainhom/admin/user-add.php" class="btn btn-dark btn-sm">+ Thêm người dùng mới</a>
      </div>

      <?php if (empty($usersList)): ?>
        <p style="text-align: center; color: #777; padding: 20px 0;">Hệ thống chưa có người dùng nào.</p>
      <?php else: ?>
        <div class="table-responsive" style="overflow-x: auto;">
          <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
              <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px;">ID</th>
                <th style="padding: 12px;">Tên tài khoản</th>
                <th style="padding: 12px;">Email</th>
                <th style="padding: 12px;">Vai trò (Role)</th>
                <th style="padding: 12px;">Ngày đăng ký</th>
                <th style="padding: 12px; text-align: right;">Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usersList as $u): ?>
                <tr style="border-bottom: 1px solid #dee2e6;">
                  <td style="padding: 12px; font-weight: bold;"><?= (int)$u['id'] ?></td>
                  <td style="padding: 12px;"><?= clean($u['username'] ?? '') ?></td>
                  <td style="padding: 12px;"><?= clean($u['email']) ?></td>
                  <td style="padding: 12px;">
                    <span style="padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; 
                      <?= ($u['role'] === 'admin') ? 'background: #d4edda; color: #155724;' : 'background: #e2e3e5; color: #383d41;' ?>">
                      <?= clean($u['role']) ?>
                    </span>
                  </td>
                  <td style="padding: 12px; color: #666; font-size: 14px;"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                  <td style="padding: 12px; text-align: right;">
                    <a href="/bainhom/admin/user-edit.php?id=<?= $u['id'] ?>" class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 12px; margin-right: 5px;">Sửa</a>
                    <?php if (($currentUser['id'] ?? null) !== $u['id']): ?>
                      <a href="/bainhom/admin/user-delete.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 12px;" onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này không?')">Xóa</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <?php if (empty($cartItems)): ?>
        <div class="empty-state cart-empty-card">
          <span class="icon">🛒</span>
          <h4>Giỏ hàng đang trống</h4>
          <p>Hãy chọn một sản phẩm phù hợp để bắt đầu mua sắm tại TechZone.</p>
          <a href="/bainhom/index.php" class="btn btn-dark">Xem sản phẩm</a>
        </div>
    <?php else: ?>
        <div class="cart-grid">
          <div class="cart-card-list">
            <?php foreach ($cartItems as $item):
              $itemTotal = (float) $item['price'] * (int) $item['quantity'];
              $img = cartImage($item['image_file'] ?? '');
              ?>
              <article class="cart-item-card">
                <div class="cart-item-thumb">
                  <?php if ($img): ?>
                    <img src="<?= clean($img) ?>" alt="<?= clean($item['name']) ?>">
                  <?php else: ?>
                    <span>📦</span>
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
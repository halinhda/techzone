<?php
require_once __DIR__ . '/../includes/header.php'; ;
$pageTitle = 'Giỏ Hàng của bạn';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();
$sid = cartSessionId();
$userId = currentUser()['id'] ?? null;

$stmt = $pdo->prepare("
    SELECT ci.*, p.name, p.price, p.image_file, p.brand, p.stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.session_id = ? OR (ci.user_id IS NOT NULL AND ci.user_id = ?)
");
$stmt->execute([$sid, $userId]);
$cartItems = $stmt->fetchAll();

function cartImage(string $file): string {
    if (empty($file)) return '';
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
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$shipping = $subtotal >= FREE_SHIP_MIN ? 0 : SHIPPING_FEE;
$total = $subtotal + $shipping;
?>

<section class="cart-page container">
  <div class="section-header">
    <div>
      <p class="section-sub">Giỏ hàng</p>
      <h1 class="section-title">Giỏ hàng của bạn</h1>
    </div>
    <a href="/bainhom/index.php" class="btn btn-outline btn-sm">← Tiếp tục mua sắm</a>
  </div>

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
          $itemTotal = (float)$item['price'] * (int)$item['quantity'];
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
                  <button type="button" class="qty-btn js-qty" data-pid="<?= (int)$item['product_id'] ?>" data-delta="-1">−</button>
                  <span class="qty-val"><?= (int)$item['quantity'] ?></span>
                  <button type="button" class="qty-btn js-qty" data-pid="<?= (int)$item['product_id'] ?>" data-delta="1">+</button>
                </div>
                <button type="button" class="btn btn-danger btn-sm js-remove-cart" data-pid="<?= (int)$item['product_id'] ?>">Xóa</button>
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
        <a href="/bainhom/checkout.php" class="btn btn-primary btn-block">Tiến hành thanh toán</a>
      </aside>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
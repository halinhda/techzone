<?php
// checkout.php
require_once __DIR__ . '/includes/config.php';

// 1. TẠO CSRF TOKEN BẢO MẬT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Thanh Toán';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$sid = cartSessionId();
$userId = currentUser()['id'] ?? null;

// Lấy thêm p.stock để kiểm tra tồn kho luôn (Tối ưu hiệu suất)
$stmt = $pdo->prepare("
    SELECT ci.*, p.name, p.price, p.stock, p.image_file, p.brand
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.session_id = ? OR (ci.user_id IS NOT NULL AND ci.user_id = ?)
");
$stmt->execute([$sid, $userId]);
$cartItems = $stmt->fetchAll(); // Phải lấy data TRƯỚC khi dùng vòng lặp

if (empty($cartItems)) {
    redirect('/bainhom/views/cart.php');
}

// 2. KIỂM TRA TỒN KHO
foreach ($cartItems as $item) {
    if ((int) $item['quantity'] > (int) $item['stock']) {
        echo "<script>alert('Sản phẩm " . addslashes($item['name']) . " không đủ hàng. Vui lòng cập nhật lại giỏ hàng!'); window.location.href='/bainhom/views/cart.php';</script>";
        exit;
    }
}

// 3. TÍNH TOÁN COMBO (Phải tính trước khi cộng tiền)
$comboIds = [1, 2, 3]; // Thay bằng ID sản phẩm của bạn
$cartIds = array_column($cartItems, 'product_id');
$hasFullCombo = !array_diff($comboIds, $cartIds);

// 4. TÍNH TỔNG TIỀN
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}

// Nếu có combo, áp dụng giảm giá
if ($hasFullCombo) {
    $subtotal = 45000000; // Giá combo của bạn
}

$shipping = $subtotal >= FREE_SHIP_MIN ? 0 : SHIPPING_FEE;
$total = $subtotal + $shipping;

function checkoutImage(string $file): string
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
?>

<section class="container" style="padding: 24px 0 40px;">
    <div class="section-header">
        <div>
            <p class="section-sub">Thanh toán</p>
            <h1 class="section-title">Xác nhận đơn hàng</h1>
        </div>
        <a href="/bainhom/views/cart.php" class="btn btn-outline btn-sm">← Quay lại giỏ hàng</a>
    </div>

    <form id="checkout-form" method="POST" action="/bainhom/controllers/process_order.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="cart-grid">
            <article class="cart-item-card">
                <div class="cart-content">
                    <h2 class="cart-summary-title">Thông tin nhận hàng</h2>
                    <div class="form-group">
                        <label class="form-label">Họ và tên</label>
                        <input class="form-control" type="text" name="fullname"
                            value="<?= clean(currentUser()['fullname'] ?? '') ?>" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input class="form-control" type="tel" name="phone" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Địa chỉ</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán</label>
                        <div class="pay-options">
                            <label class="pay-option selected"><input type="radio" name="pay" value="cod" checked>
                                <strong>COD</strong></label>
                            <label class="pay-option"><input type="radio" name="pay" value="transfer"> <strong>Chuyển
                                    khoản</strong></label>
                        </div>
                    </div>
                </div>
            </article>

            <article class="cart-item-card">
                <h2 class="cart-summary-title">Sản phẩm trong đơn</h2>

                <?php if ($hasFullCombo): ?>
                    <div
                        style="background-color: #f8f9fa; border: 1px dashed #007bff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h4 style="color: #007bff; margin-top: 0;">🎁 Bạn đang sở hữu trọn bộ Combo</h4>
                        <p style="font-size: 0.9em; color: #555;">Bao gồm: Laptop Dell, Chuột không dây, Túi chống sốc.</p>
                        <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                            <span>Giá lẻ: <del>50.000.000 đ</del></span>
                            <strong style="color: #d9534f;">Giá Combo: 45.000.000 đ</strong>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="cart-products">
                    <?php foreach ($cartItems as $item):
                        $img = checkoutImage($item['image_file'] ?? '');
                        ?>
                        <div class="product-item">
                            <?php if ($img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="<?= clean($item['name']) ?>"
                                    style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 4px;">
                            <?php endif; ?>
                            <div class="product-info">
                                <strong><?= clean($item['name']) ?></strong>
                                <p>SL: <?= (int) $item['quantity'] ?> × <?= formatVND($item['price']) ?></p>
                            </div>
                            <strong><?= formatVND((float) $item['price'] * (int) $item['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary-row"><span>Tạm tính</span><strong><?= formatVND($subtotal) ?></strong></div>
                <div class="cart-summary-row"><span>Phí vận
                        chuyển</span><strong><?= $shipping === 0 ? 'Miễn phí' : formatVND($shipping) ?></strong></div>
                <div class="cart-summary-row total"><span>Tổng cộng</span><strong><?= formatVND($total) ?></strong>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">Đặt hàng ngay</button>
            </article>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
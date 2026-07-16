<?php 
require_once __DIR__ . '/../includes/header.php'; 
$orderCode = $_GET['code'] ?? 'Đang cập nhật...';

// Kiểm tra nếu là đơn hàng khách vãng lai
$isGuestOrder = isset($_SESSION['guest_order']) && $_SESSION['guest_order'] === true;
unset($_SESSION['guest_order']); // Xóa flag sau khi đọc
?>

<section class="checkout-success-page">
    <div class="success-card">
        <div class="success-icon">✓</div>
        
        <h1 class="success-title">Đặt hàng thành công!</h1>
        <p class="success-desc">Cảm ơn bạn đã mua hàng tại TechZone. Đơn hàng của bạn đã được ghi nhận và đang được xử lý.</p>
        
        <div class="success-order-code">
            <p class="label">Mã đơn hàng của bạn</p>
            <strong class="code"><?= htmlspecialchars($orderCode) ?></strong>
        </div>

        <?php if ($isGuestOrder): ?>
        <div style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1px solid #fcd34d; padding: 16px 20px; border-radius: 12px; margin: 20px 0; text-align: left;">
            <p style="color: #92400e; font-size: 14px; font-weight: 600; margin-bottom: 6px;">
                📋 Bạn đã mua hàng với tư cách Khách
            </p>
            <p style="color: #a16207; font-size: 13px; line-height: 1.5;">
                Hãy lưu lại mã đơn hàng <strong><?= htmlspecialchars($orderCode) ?></strong> để theo dõi. 
                Bạn có thể tự <a href="/bainhom/views/order_lookup.php?code=<?= urlencode($orderCode) ?>" style="color: #b45309; font-weight: 700; text-decoration: underline;">tra cứu tiến độ đơn hàng tại đây</a>.
                Hoặc <a href="/bainhom/controllers/auth.php?mode=register" style="color: #92400e; font-weight: 700; text-decoration: underline;">Đăng ký tài khoản</a> 
                để quản lý đơn hàng và nhận nhiều ưu đãi hơn!
            </p>
        </div>
        <?php endif; ?>

        <div class="success-actions">
            <?php if (!$isGuestOrder): ?>
            <a href="/bainhom/views/my_orders.php" class="btn btn-outline btn-lg">
                <i data-lucide="package"></i> Xem đơn hàng
            </a>
            <?php else: ?>
            <a href="/bainhom/views/order_lookup.php?code=<?= urlencode($orderCode) ?>" class="btn btn-outline btn-lg">
                <i data-lucide="search"></i> Tra cứu đơn hàng
            </a>
            <?php endif; ?>
            <a href="/bainhom/index.php" class="btn btn-primary btn-lg">
                <i data-lucide="shopping-bag"></i> Tiếp tục mua sắm
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
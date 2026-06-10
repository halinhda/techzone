<?php 
require_once __DIR__ . '/../includes/header.php'; 
$orderCode = $_GET['code'] ?? 'Đang cập nhật...';
?>

<section class="container" style="max-width: 600px; padding: 60px 20px; text-align: center;">
    <div style="background: #f0fdf4; padding: 40px; border-radius: 24px; border: 1px solid #bbf7d0;">
        <div style="font-size: 50px; color: #22c55e; margin-bottom: 20px;">✓</div>
        
        <h1 style="font-size: 28px; color: #0f172a; margin-bottom: 10px;">Đặt hàng thành công!</h1>
        <p style="color: #64748b; margin-bottom: 30px;">Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đã được ghi nhận.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 30px;">
            <p style="margin: 0; color: #64748b; font-size: 14px;">Mã đơn hàng của bạn</p>
            <strong style="font-size: 20px; color: #6366f1;"><?= htmlspecialchars($orderCode) ?></strong>
        </div>

        <a href="/bainhom/index.php" class="btn btn-primary" style="padding: 12px 30px;">Tiếp tục mua sắm</a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 
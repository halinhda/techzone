<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// SỬA SQL: Chỉ lấy từ bảng order_items, không JOIN vì bảng này đã có sẵn tên và emoji
$sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="padding: 40px 0; min-height: 400px;">
    <h2>Chi tiết đơn hàng #<?= htmlspecialchars($orderId) ?></h2>
    
    <?php if (!$items): ?>
        <p>Không tìm thấy sản phẩm nào trong đơn hàng này.</p>
    <?php else: ?>
        <div class="order-items-list">
            <?php foreach ($items as $item): ?>
                <div class="product-item" style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <div style="font-size: 40px; width: 60px; text-align: center;">
                        <?= htmlspecialchars($item['image_emoji']) ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 style="margin:0;"><?= htmlspecialchars($item['name']) ?></h4>
                        <p style="margin:5px 0;">Số lượng: <?= htmlspecialchars($item['quantity']) ?></p>
                        <p style="margin:0; font-weight:bold; color:#e11d48;">Giá: <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="my_orders.php" class="btn btn-secondary">Quay lại danh sách đơn hàng</a>
    </div>
</div>

<?php 
require_once __DIR__ . '/../includes/footer.php'; 
?>
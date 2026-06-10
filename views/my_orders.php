<?php 
require_once __DIR__ . '/../includes/header.php'; 

// 1. Mở thẻ PHP đúng cách
// 2. Đảm bảo $pdo và $user đã tồn tại (thường được khởi tạo trong header.php)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();
?>

<style>
    .orders-container { max-width: 900px; margin: 40px auto; padding: 20px; }
    .order-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .order-table th { background: #f4f4f4; padding: 15px; text-align: left; }
    .order-table td { padding: 15px; border-bottom: 1px solid #eee; }
    .status-pending { color: #d97706; font-weight: bold; }
    .btn-detail { padding: 8px 15px; background: #6366f1; color: white; border-radius: 5px; text-decoration: none; font-size: 14px; }
</style>

<div class="orders-container">
    <h1>Đơn hàng của tôi</h1>
    <table class="order-table">
        <thead>
            <tr>
                <th>Mã đơn</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;">Bạn chưa có đơn hàng nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_code']) ?></td>
                    <td><?= number_format($order['total_price'], 0, ',', '.') ?> đ</td>
                    <td><span class="status-pending"><?= htmlspecialchars($order['status']) ?></span></td>
                    <td><a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-detail">Xem chi tiết</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
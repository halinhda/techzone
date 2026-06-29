<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($user)) {
    header("Location: login.php");
    exit;
}

$pdo = getDB();
$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. Lấy thông tin đơn hàng và kiểm tra quyền
$stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='container'><h2>Lỗi</h2><p>Đơn hàng không tồn tại hoặc bạn không có quyền xem.</p>
          <a href='my_orders.php'>Quay lại danh sách</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. Lấy sản phẩm trong đơn hàng
$sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="padding: 40px 0; min-height: 400px;">
    <h2>Chi tiết đơn hàng #<?= htmlspecialchars($orderId) ?></h2>

    <?php if ($order['status'] !== 'cancelled'): ?>
        <?php
        $steps = ['pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'shipped' => 'Đã giao hàng'];
        $current_status = $order['status'];
        $status_keys = array_keys($steps);
        $current_index = array_search($current_status, $status_keys);
        ?>
        <ul class="order-timeline">
            <?php foreach ($steps as $key => $label):
                $is_active = ($current_index !== false && array_search($key, $status_keys) <= $current_index) ? 'active' : '';
                ?>
                <li class="step <?= $is_active ?>">
                    <div class="step-icon">✓</div>
                    <div class="step-label"><?= $label ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-danger"
            style="margin: 20px 0; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px;">
            Đơn hàng này đã bị hủy.
        </div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <p>Không tìm thấy sản phẩm nào trong đơn hàng này.</p>
    <?php else: ?>
        <div class="order-items-list">
            <?php foreach ($items as $item): ?>
                <div class="product-item"
                    style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <div style="font-size: 40px; width: 60px; text-align: center;">
                        <?= htmlspecialchars($item['image_emoji'] ?? '📦') ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 style="margin:0;"><?= htmlspecialchars($item['name']) ?></h4>
                        <p style="margin:5px 0;">Số lượng: <?= htmlspecialchars($item['quantity']) ?></p>
                        <p style="margin:0; font-weight:bold; color:#e11d48;">Giá:
                            <?= number_format($item['price'], 0, ',', '.') ?> đ</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="my_orders.php" class="btn btn-secondary">Quay lại danh sách đơn hàng</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
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
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div class='order-detail-page'>
        <div class='empty-state'>
            <span class='icon'>🔒</span>
            <h4>Không tìm thấy đơn hàng</h4>
            <p>Đơn hàng không tồn tại hoặc bạn không có quyền xem.</p>
            <a href='my_orders.php' class='btn btn-primary'><i data-lucide='arrow-left'></i> Quay lại</a>
        </div>
    </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. Lấy sản phẩm trong đơn hàng
$sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Timeline status mapping (phù hợp với DB)
$steps = [
    'Chờ xử lý' => ['label' => 'Chờ xử lý', 'icon' => '📋'],
    'Đang giao' => ['label' => 'Đang giao hàng', 'icon' => '🚚'],
    'Đã hoàn thành' => ['label' => 'Đã hoàn thành', 'icon' => '✅'],
];
$currentStatus = $order['status'];
$stepKeys = array_keys($steps);
$currentIndex = array_search($currentStatus, $stepKeys);
?>

<div class="order-detail-page">
    <div class="section-header" style="margin-bottom: 24px;">
        <div>
            <p class="eyebrow">Chi tiết đơn hàng</p>
            <h1 class="section-title">
                <i data-lucide="file-text"></i>
                Đơn hàng #<?= htmlspecialchars($order['order_code'] ?? $orderId) ?>
            </h1>
        </div>
        <a href="my_orders.php" class="btn btn-outline btn-sm">
            <i data-lucide="arrow-left"></i> Danh sách đơn hàng
        </a>
    </div>

    <!-- Order Timeline -->
    <?php if ($currentStatus !== 'Đã hủy'): ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body">
                <div class="order-timeline-modern">
                    <?php foreach ($steps as $key => $step):
                        $idx = array_search($key, $stepKeys);
                        $stepClass = '';
                        if ($currentIndex !== false && $idx < $currentIndex) $stepClass = 'done';
                        elseif ($currentIndex !== false && $idx === $currentIndex) $stepClass = 'active';
                    ?>
                        <div class="timeline-step <?= $stepClass ?>">
                            <div class="timeline-step-icon"><?= $stepClass === 'done' ? '✓' : $step['icon'] ?></div>
                            <span class="timeline-step-label"><?= $step['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-error" style="margin-bottom: 24px;">
            <i data-lucide="x-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            Đơn hàng này đã bị hủy.
        </div>
    <?php endif; ?>

    <!-- Order Items -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body">
            <h3 style="font-size:16px;font-weight:900;color:#0f172a;margin-bottom:16px;">Sản phẩm trong đơn</h3>
            <?php if (!$items): ?>
                <p style="color:#94a3b8;">Không tìm thấy sản phẩm nào trong đơn hàng này.</p>
            <?php else: ?>
                <?php 
                $orderTotal = 0;
                foreach ($items as $item):
                    $itemTotal = (float)$item['price'] * (int)$item['quantity'];
                    $orderTotal += $itemTotal;
                ?>
                    <div style="display:flex;gap:16px;align-items:center;padding:14px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="font-size:36px;width:56px;height:56px;background:#f8fafc;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <?= htmlspecialchars($item['image_emoji'] ?? '📦') ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;"><?= htmlspecialchars($item['name']) ?></h4>
                            <p style="margin:4px 0 0;font-size:12px;color:#64748b;">
                                SL: <?= htmlspecialchars($item['quantity']) ?> × <?= number_format($item['price'], 0, ',', '.') ?> đ
                            </p>
                        </div>
                        <strong style="font-size:14px;font-weight:900;color:#dc2626;white-space:nowrap;">
                            <?= number_format($itemTotal, 0, ',', '.') ?> đ
                        </strong>
                    </div>
                <?php endforeach; ?>
                
                <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0 0;margin-top:8px;">
                    <span style="font-size:14px;font-weight:700;color:#475569;">Tổng cộng:</span>
                    <strong style="font-size:18px;font-weight:900;color:#0f172a;"><?= number_format($order['total_price'] ?? $orderTotal, 0, ',', '.') ?> đ</strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <a href="my_orders.php" class="btn btn-outline">
        <i data-lucide="arrow-left"></i> Quay lại danh sách đơn hàng
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
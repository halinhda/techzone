<?php
session_start();

// 1. CHẶN NGAY TỪ ĐẦU
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để tra đơn";
    header("Location: ../controllers/auth.php");
    exit;
}

// 2. INCLUDE SAU KHI CHECK LOGIN
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';

$userId = $_SESSION['user']['id'];

// 3. LẤY ĐƠN HÀNG
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// Ánh xạ trạng thái
$statusClasses = [
    'Chờ xử lý' => 'pending',
    'Đang giao' => 'shipping',
    'Đã hoàn thành' => 'completed',
    'Đã hủy' => 'cancelled',
];
?>

<div class="orders-page">
    <div class="section-header">
        <div>
            <p class="eyebrow">Tài khoản</p>
            <h1 class="section-title">
                <i data-lucide="package"></i>
                Đơn hàng của tôi
            </h1>
        </div>
        <span class="results-count"><?= count($orders) ?> đơn hàng</span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <span class="icon"></span>
            <h4>Chưa có đơn hàng nào</h4>
            <p>Bạn chưa đặt đơn hàng nào. Hãy khám phá cửa hàng và mua sắm ngay!</p>
            <a href="/bainhom/index.php" class="btn btn-primary">
                <i data-lucide="shopping-bag"></i> Mua sắm ngay
            </a>
        </div>
    <?php else: ?>
        <div class="orders-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $status = $order['status'];
                        $cssClass = $statusClasses[$status] ?? 'pending';
                    ?>
                        <tr>
                            <td data-label="Mã đơn" class="order-code-cell"><?= htmlspecialchars($order['order_code']) ?></td>
                            <td data-label="Tổng tiền" class="order-price-cell"><?= number_format($order['total_price'], 0, ',', '.') ?> đ</td>
                            <td data-label="Trạng thái">
                                <span class="order-status-badge <?= $cssClass ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td data-label="Ngày đặt" style="font-size:12px;color:#64748b;">
                                <?= isset($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '—' ?>
                            </td>
                            <td data-label="Thao tác">
                                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-view-order">
                                    <i data-lucide="eye" style="width:14px;height:14px;"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
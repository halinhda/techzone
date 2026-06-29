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
?>

<style>
    .orders-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 20px;
    }

    .order-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .order-table th {
        background: #f4f4f4;
        padding: 15px;
        text-align: left;
    }

    .order-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    /* Các class trạng thái */
    .status-pending {
        color: #d97706;
        font-weight: bold;
    }

    /* Vàng/Cam */
    .status-shipping {
        color: #3b82f6;
        font-weight: bold;
    }

    /* Xanh dương */
    .status-completed {
        color: #10b981;
        font-weight: bold;
    }

    /* Xanh lá */
    .status-cancelled {
        color: #ef4444;
        font-weight: bold;
    }

    /* Đỏ */

    .btn-detail {
        padding: 8px 15px;
        background: #6366f1;
        color: white;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
    }
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
                <?php
                // Định nghĩa ánh xạ trạng thái sang class (Điều chỉnh key theo đúng dữ liệu trong DB của bạn)
                $statusClasses = [
                    'Chờ xử lý' => 'status-pending',
                    'Đang giao' => 'status-shipping',
                    'Đã hoàn thành' => 'status-completed',
                    'Đã hủy' => 'status-cancelled',
                ];
                ?>
                <?php foreach ($orders as $order): ?>
                    <?php
                    $status = $order['status'];
                    // Lấy class tương ứng, mặc định là status-pending nếu không tìm thấy
                    $cssClass = $statusClasses[$status] ?? 'status-pending';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_code']) ?></td>
                        <td><?= number_format($order['total_price'], 0, ',', '.') ?> đ</td>
                        <td><span class="<?= $cssClass ?>"><?= htmlspecialchars($status) ?></span></td>
                        <td><a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-detail">Xem chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
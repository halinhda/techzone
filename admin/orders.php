<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Quản lý đơn hàng';
$currentPage = 'orders';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

$pdo = getDB();
$statusFilter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>📋 Đơn hàng <span class="badge badge-secondary"><?= count($orders) ?></span></h1>
    <a href="export_orders.php" class="btn btn-outline">📥 Xuất CSV</a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
        <form method="GET" class="filter-bar">
            <span style="font-weight:600;font-size:14px;">Lọc theo trạng thái:</span>
            <select name="status" class="form-control" style="max-width:200px;" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="Chờ xử lý" <?= $statusFilter === 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử lý</option>
                <option value="Đang giao" <?= $statusFilter === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                <option value="Đã hoàn thành" <?= $statusFilter === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                <option value="Đã hủy" <?= $statusFilter === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
            </select>
        </form>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
    <div class="alert alert-success">✅ Cập nhật trạng thái đơn hàng thành công!</div>
<?php endif; ?>

<!-- Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Số ĐT</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-sub);">Chưa có đơn hàng nào.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($orders as $order): 
                        $statusClass = match($order['status']) {
                            'Đã hoàn thành' => 'badge-success',
                            'Đang giao' => 'badge-info',
                            'Đã hủy' => 'badge-danger',
                            default => 'badge-warning',
                        };
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['order_code']) ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td class="price"><?= number_format($order['total_price'], 0, ',', '.') ?>đ</td>
                            <td><span class="badge <?= $statusClass ?>"><?= $order['status'] ?></span></td>
                            <td>
                                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-sm">Xem / Xử lý</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
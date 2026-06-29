<?php
// admin/orders.php
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /bainhom/index.php");
    exit();
}

$pageTitle = "Quản lý đơn hàng";
require_once __DIR__ . '/../includes/header.php';

// Lấy danh sách đơn hàng
$sql = "SELECT o.*, u.fullname 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC";
$stmt = getDB()->query($sql);

$orders = $stmt->fetchAll();
?>

<div class="container">
    <div class="admin-card">
        <div class="section-header">
            <h1 class="section-title">Quản lý đơn hàng</h1>
            <span class="results-count"><?= count($orders) ?> đơn hàng</span>
        </div>

        <div class="table-container">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($o['id']) ?></td>

                            <td><?= htmlspecialchars($o['fullname'] ?? 'Khách lẻ') ?></td>

                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><?= number_format($o['total_price'], 0, ',', '.') ?>đ</td>
                            <td>
                                <form action="update_order_status.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" onchange="this.form.submit()" class="form-select">
                                        <option value="Chờ xử lý" <?= $o['status'] == 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử
                                            lý</option>
                                        <option value="Đang giao" <?= $o['status'] == 'Đang giao' ? 'selected' : '' ?>>Đang
                                            giao</option>
                                        <option value="Đã hoàn thành" <?= $o['status'] == 'Đã hoàn thành' ? 'selected' : '' ?>>
                                            Đã hoàn thành</option>
                                        <option value="Đã hủy" <?= $o['status'] == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy
                                        </option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
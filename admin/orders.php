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
$stmt = getDB()->query("SELECT * FROM orders ORDER BY created_at DESC");
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
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?= $o['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td><?= number_format($o['total_price']) ?>đ</td>
                        <td>
                            <span class="badge badge-<?= strtolower($o['status']) ?>">
                                <?= clean($o['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
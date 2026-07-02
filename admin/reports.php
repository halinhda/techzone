<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Báo cáo thống kê';
$currentPage = 'reports';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

$pdo = getDB();

// 1. Doanh thu theo tháng (6 tháng gần nhất)
$monthlyRev = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%m/%Y') AS mth, SUM(total_price) AS revenue
    FROM orders
    WHERE status = 'Đã hoàn thành'
    GROUP BY DATE_FORMAT(created_at, '%m/%Y'), DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY DATE_FORMAT(created_at, '%Y-%m') DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$monthlyRev = array_reverse($monthlyRev);
$months = array_column($monthlyRev, 'mth');
$revenues = array_column($monthlyRev, 'revenue');

// 2. Doanh thu theo danh mục
$catRev = $pdo->query("
    SELECT c.name, SUM(oi.price * oi.quantity) AS revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    WHERE o.status = 'Đã hoàn thành'
    GROUP BY c.id
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Top 5 khách hàng mua nhiều nhất
$topCustomers = $pdo->query("
    SELECT u.fullname, u.email, SUM(o.total_price) AS total_spent, COUNT(o.id) as total_orders
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'Đã hoàn thành'
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>📈 Báo cáo thống kê</h1>
</div>

<div class="content-grid-equal" style="margin-bottom:24px;">
    <!-- Biểu đồ doanh thu 6 tháng -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Doanh thu 6 tháng gần nhất</h3>
        </div>
        <div class="card-body">
            <?php if (empty($months)): ?>
                <div class="empty-state"><div class="empty-desc">Chưa có dữ liệu</div></div>
            <?php else: ?>
                <canvas id="monthlyChart" height="250"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Doanh thu theo danh mục (Pie Chart) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📁 Tỷ trọng doanh thu theo danh mục</h3>
        </div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center;">
            <?php if (empty($catRev)): ?>
                <div class="empty-state"><div class="empty-desc">Chưa có dữ liệu</div></div>
            <?php else: ?>
                <div style="width: 250px;">
                    <canvas id="categoryChart" height="250"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">🏆 Top 5 khách hàng chi tiêu cao nhất</h3>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hạng</th>
                    <th>Khách hàng</th>
                    <th>Email</th>
                    <th>Số đơn hàng</th>
                    <th style="text-align:right;">Tổng chi tiêu</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topCustomers)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:30px;">Chưa có dữ liệu</td></tr>
                <?php else: ?>
                    <?php foreach ($topCustomers as $i => $c): ?>
                        <tr>
                            <td><strong style="color:var(--primary);">#<?= $i+1 ?></strong></td>
                            <td><strong><?= htmlspecialchars($c['fullname']) ?></strong></td>
                            <td><span style="color:var(--text-sub);"><?= htmlspecialchars($c['email']) ?></span></td>
                            <td><?= $c['total_orders'] ?> đơn</td>
                            <td style="text-align:right;font-weight:700;color:#dc2626;">
                                <?= number_format($c['total_spent'], 0, ',', '.') ?>đ
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
<?php if (!empty($months)): ?>
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode($revenues) ?>,
            backgroundColor: 'rgba(99,102,241,0.85)',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(ctx.raw)
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: v => (v/1000000).toFixed(0) + 'M' }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($catRev)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($catRev, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($catRev, 'revenue')) ?>,
            backgroundColor: [
                '#6366f1', '#22c55e', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(ctx.raw)
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

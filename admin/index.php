<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

$pdo = getDB();

// === STATS ===
$revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status='Đã hoàn thành'")->fetchColumn();
$totalOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'Đã hủy'")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Chờ xử lý'")->fetchColumn();
$todayOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Low stock products (stock < 5)
$lowStock = $pdo->query("SELECT id, name, stock, image_file FROM products WHERE stock < 5 AND stock > 0 ORDER BY stock ASC LIMIT 10")->fetchAll();
$outOfStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

// Top selling products
$topProducts = $pdo->query("
    SELECT oi.name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'Đã hoàn thành'
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 10
")->fetchAll();

// Chart data (7 days)
$chartData = $pdo->query("
    SELECT DATE(created_at) AS d, SUM(total_price) AS r, COUNT(*) AS cnt
    FROM orders
    WHERE status = 'Đã hoàn thành'
    GROUP BY DATE(created_at)
    ORDER BY d DESC
    LIMIT 7
")->fetchAll();

$chartData = array_reverse($chartData);
$dates = array_map(fn($r) => date('d/m', strtotime($r['d'])), $chartData);
$revenues = array_map(fn($r) => (float)$r['r'], $chartData);
$orderCounts = array_map(fn($r) => (int)$r['cnt'], $chartData);

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.*, u.fullname 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/admin_layout.php';
?>

<!-- Stat Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">💰</div>
        <div class="stat-info">
            <div class="stat-label">Tổng doanh thu</div>
            <div class="stat-value"><?= number_format($revenue, 0, ',', '.') ?>đ</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📋</div>
        <div class="stat-info">
            <div class="stat-label">Tổng đơn hàng</div>
            <div class="stat-value"><?= $totalOrders ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">📦</div>
        <div class="stat-info">
            <div class="stat-label">Sản phẩm</div>
            <div class="stat-value"><?= $totalProducts ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon indigo">👥</div>
        <div class="stat-info">
            <div class="stat-label">Khách hàng</div>
            <div class="stat-value"><?= $totalCustomers ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">⏳</div>
        <div class="stat-info">
            <div class="stat-label">Đơn chờ xử lý</div>
            <div class="stat-value"><?= $pendingOrders ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">📅</div>
        <div class="stat-info">
            <div class="stat-label">Đơn hôm nay</div>
            <div class="stat-value"><?= $todayOrders ?></div>
        </div>
    </div>
</div>

<!-- Charts + Top Products -->
<div class="content-grid" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📈 Xu hướng doanh thu (7 ngày)</h3>
        </div>
        <div class="card-body">
            <canvas id="revenueChart" height="220"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏆 Top sản phẩm bán chạy</h3>
        </div>
        <div class="card-body" style="max-height:320px; overflow-y:auto;">
            <?php if (empty($topProducts)): ?>
                <div class="empty-state" style="padding:30px 0;">
                    <div class="empty-icon">📊</div>
                    <div class="empty-desc">Chưa có dữ liệu bán hàng</div>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Sản phẩm</th><th style="text-align:right;">Đã bán</th></tr></thead>
                    <tbody>
                        <?php foreach ($topProducts as $i => $p): ?>
                            <tr>
                                <td>
                                    <span style="color:var(--primary);font-weight:700;margin-right:8px;">#<?= $i+1 ?></span>
                                    <?= htmlspecialchars($p['name']) ?>
                                </td>
                                <td style="text-align:right;font-weight:700;"><?= $p['total_sold'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Orders + Low Stock -->
<div class="content-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Đơn hàng gần đây</h3>
            <a href="/bainhom/admin/orders.php" class="btn btn-outline btn-sm">Xem tất cả →</a>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Mã ĐH</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): 
                            $statusClass = match($o['status']) {
                                'Đã hoàn thành' => 'badge-success',
                                'Đang giao' => 'badge-info',
                                'Đã hủy' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        ?>
                            <tr>
                                <td><strong>#<?= $o['id'] ?></strong></td>
                                <td><?= htmlspecialchars($o['fullname'] ?? $o['customer_name'] ?? 'Khách lẻ') ?></td>
                                <td class="price"><?= number_format($o['total_price'], 0, ',', '.') ?>đ</td>
                                <td><span class="badge <?= $statusClass ?>"><?= $o['status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⚠️ Sắp hết hàng</h3>
            <?php if ($outOfStock > 0): ?>
                <span class="badge badge-danger"><?= $outOfStock ?> hết hàng</span>
            <?php endif; ?>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($lowStock)): ?>
                <div class="empty-state" style="padding:30px 0;">
                    <div class="empty-icon">✅</div>
                    <div class="empty-desc">Tất cả sản phẩm đủ hàng</div>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Sản phẩm</th><th style="text-align:right;">Tồn kho</th></tr></thead>
                    <tbody>
                        <?php foreach ($lowStock as $p): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="/bainhom/assets/images/<?= htmlspecialchars($p['image_file'] ?: 'no-image.png') ?>" class="product-thumb" style="width:32px;height:32px;">
                                        <span><?= htmlspecialchars($p['name']) ?></span>
                                    </div>
                                </td>
                                <td style="text-align:right;" class="stock-low"><?= $p['stock'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode($revenues) ?>,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: '#6366f1',
            pointHoverRadius: 8,
            borderWidth: 2.5
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
                ticks: {
                    callback: v => (v/1000000).toFixed(0) + 'M'
                },
                grid: { color: 'rgba(0,0,0,0.04)' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
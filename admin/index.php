<?php
// Bắt đầu session để kiểm tra đăng nhập
session_start();

// Kiểm tra vị trí file để lấy config
if (file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
} else {
    require_once __DIR__ . '/../includes/config.php';
}

// Kiểm tra quyền Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php'); // Nếu không phải admin thì cút ra ngoài
    exit;
}

$pdo = getDB();

// Lấy dữ liệu
$revenue = (float) $pdo->query("
    SELECT COALESCE(SUM(total_price), 0)
    FROM orders
    WHERE status = 'Đã hoàn thành'
")->fetchColumn();
$totalOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE status != 'Đã hủy'
")->fetchColumn();
$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Top sản phẩm
$topProducts = $pdo->query("
    SELECT oi.name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'Đã hoàn thành'
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 10
")->fetchAll();

// Dữ liệu biểu đồ
$chartData = $pdo->query("
    SELECT DATE(created_at) AS d, SUM(total_price) AS r
    FROM orders
    WHERE status = 'Đã hoàn thành'
    GROUP BY DATE(created_at)
    ORDER BY d DESC
    LIMIT 7
")->fetchAll();

$chartData = array_reverse($chartData);
$dates = array_map(fn($r) => date('d/m', strtotime($r['d'])), $chartData);
$revenues = array_map(fn($r) => (float) $r['r'], $chartData);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Menu điều hướng */
        .admin-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .btn-nav {
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-primary {
            background: #6366f1;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-nav:hover {
            opacity: 0.8;
        }

        /* Card */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #6366f1;
        }

        .card-value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Layout 2 cột */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .table-wrapper {
            flex: 1;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-nav">
            <a href="/bainhom/admin/index.php" class="btn-nav" style="background:#475569">Dashboard</a>
            <a href="/bainhom/admin/orders.php" class="btn-nav btn-primary">📋 Đơn hàng</a>
            <a href="/bainhom/admin/products.php" class="btn-nav btn-primary">📦 Sản phẩm</a>

            <a href="/bainhom/admin/export_orders.php" class="btn-nav" style="background:#10b981; color:white;">📥 Xuất
                Báo Cáo CSV</a>

            <a href="/bainhom/index.php" class="btn-nav" style="background:#64748b">Về trang chủ</a>

            <a href="/bainhom/controllers/auth.php?action=logout" class="btn-nav btn-danger"
                style="margin-left: auto;">Đăng xuất</a>
        </div>

        <h1>📊 Bảng điều khiển</h1>
        <div class="card-grid">
            <div class="card">
                <div class="card-title">Doanh thu</div>
                <div class="card-value"><?= number_format($revenue, 0, ',', '.') ?> đ</div>
            </div>
            <div class="card">
                <div class="card-title">Đơn hàng</div>
                <div class="card-value"><?= $totalOrders ?></div>
            </div>
            <div class="card">
                <div class="card-title">Sản phẩm</div>
                <div class="card-value"><?= $totalProducts ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="box">
                <h3>📈 Xu hướng doanh thu</h3>
                <canvas id="myChart"></canvas>
            </div>
            <div class="box">
                <h3>🏆 Top sản phẩm</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Số lượng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= $p['total_sold'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('myChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: 'Doanh thu',
                    data: <?= json_encode($revenues) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.15)',
                    tension: 0.4,        // làm mượt đường
                    fill: true,
                    pointRadius: 5,      // hiện rõ điểm
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false   // 🔥 CÁI QUAN TRỌNG NHẤT
                    }
                }
            }
        });
    </script>
</body>

</html>
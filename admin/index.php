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
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --bg: #f3f5fb;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-sub: #475569;
        }

        /* ===== BASE ===== */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background:
                radial-gradient(circle at top, #e0e7ff 0%, transparent 40%),
                linear-gradient(180deg, #f8fafc, #eef2f7);
            margin: 0;
            padding: 28px;
            color: var(--text-main);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ===== ADMIN NAV ===== */
        .admin-nav {
            display: flex;
            gap: 14px;
            margin-bottom: 26px;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: 18px;
            box-shadow:
                0 20px 40px rgba(99, 102, 241, 0.12),
                0 6px 14px rgba(0, 0, 0, 0.06);
        }

        .btn-nav {
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .3px;
            text-decoration: none;
            transition: all .25s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 8px 18px rgba(99, 102, 241, 0.45);
        }

        .btn-danger {
            background: linear-gradient(135deg, #fb7185, var(--danger));
            color: #fff;
            box-shadow: 0 8px 18px rgba(239, 68, 68, 0.4);
        }

        .btn-nav:hover {
            transform: translateY(-2px) scale(1.03);
        }

        /* ===== CARD GRID ===== */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 22px;
            margin-bottom: 28px;
        }

        .card {
            position: relative;
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.08);
            transition: all .3s ease;
        }

        .card::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 20px;
            border: 2px solid transparent;
            background: linear-gradient(135deg, var(--primary), transparent) border-box;
            -webkit-mask:
                linear-gradient(#fff 0 0) padding-box,
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            opacity: 0;
            transition: .3s;
        }

        .card:hover::after {
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .card-value {
            font-size: 30px;
            font-weight: 800;
            margin-top: 8px;
            color: var(--primary-dark);
        }

        /* ===== CONTENT GRID ===== */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
        }

        /* ===== BOX ===== */
        .box {
            background: var(--card-bg);
            padding: 22px;
            border-radius: 20px;
            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.08);
            height: 420px;
            display: flex;
            flex-direction: column;
        }

        .box h3 {
            margin: 0 0 14px;
            font-size: 17px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        /* ===== TABLE ===== */
        .table-wrapper {
            flex: 1;
            overflow-y: auto;
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            position: sticky;
            top: 0;
            background: linear-gradient(180deg, #eef2ff, #e0e7ff);
            font-size: 13px;
            font-weight: 800;
            color: #3730a3;
        }

        th,
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        tr:hover td {
            background: #eef2ff;
        }

        .btn-home {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            box-shadow: 0 8px 18px rgba(34, 197, 94, .45);
        }

        .btn-home:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 14px 28px rgba(34, 197, 94, .6);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-nav">
            <a href="/bainhom/admin/orders.php" class="btn-nav btn-primary">📋 Đơn hàng</a>
            <a href="/bainhom/admin/products.php" class="btn-nav btn-primary">📦 Sản phẩm</a>
            <a href="/bainhom/admin/categories.php" class="btn-nav btn-primary">📁 Danh mục</a>

            <a href="/bainhom/admin/export_orders.php" class="btn-nav" style="background:#10b981; color:white;">📥 Xuất
                Báo Cáo CSV</a>

            <a href="/bainhom/index.php" class="btn-nav btn-home">
                Về trang chủ
            </a>

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
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>

</html>
<?php
// admin/products.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php');
    exit;
}

$pdo = getDB();

// Lấy sản phẩm (giả sử có cột image)
$stmt = $pdo->query("SELECT id, name, price, image_file FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>

    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 40px;
            color: #0f172a;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: #fff;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, .08);
        }

        h1 {
            margin: 0 0 24px;
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: .2s ease;
        }

        .btn-add {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .35);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(37, 99, 235, .45);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            text-align: left;
        }

        td {
            padding: 18px 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        tbody tr {
            transition: .2s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.003);
        }

        /* PRODUCT CELL */
        .product-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .product-img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
            background: #e5e7eb;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .15);
        }

        .product-name {
            font-weight: 600;
            color: #0f172a;
        }

        .price {
            font-weight: 700;
            color: #dc2626;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #0f172a;
        }

        .btn-edit:hover {
            background: #f1f5f9;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            box-shadow: 0 6px 14px rgba(220, 38, 38, .25);
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Quản lý sản phẩm</h1>

        <div class="top-bar">
            <a href="/bainhom/index.php" class="btn btn-edit">← Trang chủ</a>
            <a href="product_add.php" class="btn btn-add">+ Thêm sản phẩm</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td>
                            <div class="product-cell">
                                <img src="/bainhom/assets/images/<?= htmlspecialchars($p['image_file'] ?: 'no-image.png') ?>"
                                    class="product-img">
                                <span class="product-name">
                                    <?= htmlspecialchars($p['name']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="price"><?= number_format($p['price']) ?>đ</td>
                        <td>
                            <div class="actions">
                                <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-edit">Sửa</a>
                                <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn btn-delete"
                                    onclick="return confirm('Xóa sản phẩm này?')">
                                    Xóa
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

</body>

</html>
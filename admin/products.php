<?php
// admin/products.php
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('/bainhom/index.php');
}

$pageTitle = "Quản lý sản phẩm";
require_once __DIR__ . '/../includes/header.php';

// Lấy danh sách sản phẩm từ DB
$stmt = getDB()->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<div class="container" style="padding: 40px 0;">
    <h1>Danh sách sản phẩm</h1>
    <a href="product_add.php" class="btn btn-primary" style="margin-bottom: 20px;">+ Thêm sản phẩm mới</a>
    
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f1f5f9; text-align: left;">
                <th style="padding: 12px;">ID</th>
                <th style="padding: 12px;">Tên sản phẩm</th>
                <th style="padding: 12px;">Giá</th>
                <th style="padding: 12px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><?= $p['id'] ?></td>
                <td style="padding: 12px;"><?= clean($p['name']) ?></td>
                <td style="padding: 12px;"><?= number_format($p['price']) ?>đ</td>
                <td style="padding: 12px;">
                    <a href="product_edit.php?id=<?= $p['id'] ?>">Sửa</a> | 
                    <a href="product_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Xóa thật không?')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
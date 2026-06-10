<?php
// admin/product_edit.php
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('/bainhom/index.php');
}

$id = $_GET['id'] ?? 0;
$pdo = getDB();

// 1. Xử lý khi nhấn nút Lưu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (int)($_POST['price'] ?? 0);

    if ($name && $price > 0) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);
        header('Location: products.php');
        exit;
    } else {
        $error = "Vui lòng nhập đầy đủ thông tin hợp lệ!";
    }
}

// 2. Lấy dữ liệu sản phẩm cũ để hiển thị
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

$pageTitle = "Sửa sản phẩm";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 0; max-width: 600px;">
    <h1>Sửa sản phẩm: <?= clean($product['name']) ?></h1>
    
    <?php if (isset($error)): ?>
        <div style="color: red; margin-bottom: 20px;"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="product_edit.php?id=<?= $id ?>">
        <div style="margin-bottom: 15px;">
            <label>Tên sản phẩm</label><br>
            <input type="text" name="name" value="<?= clean($product['name']) ?>" required style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Giá bán (VNĐ)</label><br>
            <input type="number" name="price" value="<?= $product['price'] ?>" required style="width: 100%; padding: 8px;">
        </div>
        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        <a href="products.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
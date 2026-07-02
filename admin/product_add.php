<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Thêm sản phẩm';
$currentPage = 'products';

$pdo = getDB();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('/bainhom/index.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;

    if ($name === '' || $price <= 0 || $category_id <= 0) {
        $error = 'Vui lòng điền đầy đủ: Tên, Giá, Danh mục.';
    } else {
        // Xử lý ảnh
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $imagePath = 'prod_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/' . $imagePath);
            }
        }

        try {
            $sql = "INSERT INTO products (name, price, brand, stock, description, image_file, category_id, featured)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $price, $brand, $stock, $desc, $imagePath, $category_id, $featured]);

            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Lỗi Database: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>+ Thêm sản phẩm</h1>
    <a href="products.php" class="btn btn-outline">← Quay lại</a>
</div>

<div class="card" style="max-width:720px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Tên sản phẩm *</label>
                <input type="text" name="name" class="form-control" required placeholder="Ví dụ: MacBook Air M2" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Giá (VNĐ) *</label>
                    <input type="number" name="price" class="form-control" required min="0" placeholder="27490000" value="<?= $_POST['price'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Thương hiệu</label>
                    <input type="text" name="brand" class="form-control" placeholder="Apple, Samsung..." value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Danh mục *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">— Chọn danh mục —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng kho</label>
                    <input type="number" name="stock" class="form-control" min="0" value="<?= $_POST['stock'] ?? '0' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ảnh sản phẩm</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" placeholder="Mô tả chi tiết sản phẩm..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="featured" value="1" <?= !empty($_POST['featured']) ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0;">⭐ Sản phẩm nổi bật</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">💾 Lưu sản phẩm</button>
                <a href="products.php" class="btn btn-outline">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
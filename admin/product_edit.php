<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Sửa sản phẩm';
$currentPage = 'products';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('/bainhom/index.php');
}

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;

    if ($name === '' || $price <= 0) {
        $error = 'Vui lòng nhập tên và giá hợp lệ.';
    } else {
        // Xử lý ảnh mới (nếu có)
        $imagePath = $product['image_file'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (in_array($ext, $allowed)) {
                $imagePath = 'prod_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/' . $imagePath);
            }
        }

        try {
            $sql = "UPDATE products SET name=?, price=?, brand=?, stock=?, description=?, image_file=?, category_id=?, featured=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $price, $brand, $stock, $desc, $imagePath, $category_id, $featured, $id]);

            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>✏️ Sửa sản phẩm</h1>
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
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Giá (VNĐ) *</label>
                    <input type="number" name="price" class="form-control" required min="0" value="<?= $product['price'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Thương hiệu</label>
                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand']) ?>">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-control">
                        <option value="">— Chọn danh mục —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng kho</label>
                    <input type="number" name="stock" class="form-control" min="0" value="<?= $product['stock'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Ảnh sản phẩm</label>
                <?php if ($product['image_file']): ?>
                    <div style="margin-bottom:10px;">
                        <img src="/bainhom/assets/images/<?= htmlspecialchars($product['image_file']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">
                        <span style="font-size:12px;color:var(--text-sub);margin-left:8px;"><?= htmlspecialchars($product['image_file']) ?></span>
                    </div>
                <?php endif; ?>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small style="color:var(--text-sub);">Để trống nếu không muốn đổi ảnh</small>
            </div>

            <div class="form-group">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="featured" value="1" <?= $product['featured'] ? 'checked' : '' ?>>
                    <span class="form-label" style="margin:0;">⭐ Sản phẩm nổi bật</span>
                </label>
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                <a href="products.php" class="btn btn-outline">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
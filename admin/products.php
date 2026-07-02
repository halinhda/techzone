<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Quản lý sản phẩm';
$currentPage = 'products';

$pdo = getDB();

// Filters
$search = trim($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);

$sql = "SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $catFilter;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>📦 Sản phẩm <span class="badge badge-secondary"><?= count($products) ?></span></h1>
    <a href="product_add.php" class="btn btn-primary">+ Thêm sản phẩm</a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:16px 20px;">
        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="🔍 Tìm tên, thương hiệu..." value="<?= htmlspecialchars($search) ?>">
            <select name="cat" class="form-control" style="max-width:200px;">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline">Lọc</button>
            <?php if ($search || $catFilter): ?>
                <a href="products.php" class="btn btn-sm" style="color:var(--text-sub);">✕ Xóa bộ lọc</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Nổi bật</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-sub);">Không tìm thấy sản phẩm nào.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="color:var(--text-sub);">#<?= $p['id'] ?></td>
                            <td>
                                <div class="product-cell">
                                    <img src="/bainhom/assets/images/<?= htmlspecialchars($p['image_file'] ?: 'no-image.png') ?>" class="product-thumb">
                                    <div>
                                        <div class="product-cell-name"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="product-cell-brand"><?= htmlspecialchars($p['brand']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></span></td>
                            <td class="price"><?= number_format($p['price'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php if ($p['stock'] == 0): ?>
                                    <span class="badge badge-danger">Hết hàng</span>
                                <?php elseif ($p['stock'] < 5): ?>
                                    <span class="stock-low"><?= $p['stock'] ?> ⚠️</span>
                                <?php else: ?>
                                    <span class="stock-ok"><?= $p['stock'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['featured']): ?>
                                    <span class="badge badge-warning">⭐ Featured</span>
                                <?php else: ?>
                                    <span style="color:var(--text-sub);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Sửa</a>
                                    <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
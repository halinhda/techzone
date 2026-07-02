<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Quản lý danh mục';
$currentPage = 'categories';

$pdo = getDB();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php'); exit;
}

$message = '';

// Delete category
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    // Check if category has products
    $count = (int)$pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $checkStmt->execute([$id]);
    $prodCount = (int)$checkStmt->fetchColumn();

    if ($prodCount > 0) {
        $message = "⚠️ Không thể xóa danh mục này vì có $prodCount sản phẩm đang thuộc danh mục.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $message = $stmt->execute([$id]) ? '✅ Xóa danh mục thành công' : '❌ Có lỗi xảy ra';
    }
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id 
    GROUP BY c.id 
    ORDER BY c.id DESC
")->fetchAll();

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>📁 Danh mục <span class="badge badge-secondary"><?= count($categories) ?></span></h1>
    <a href="category_add.php" class="btn btn-primary">+ Thêm danh mục</a>
</div>

<?php if ($message): ?>
    <div class="alert <?= str_contains($message, '✅') ? 'alert-success' : 'alert-warning' ?>"><?= $message ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Icon</th>
                        <th>Tên danh mục</th>
                        <th>Slug</th>
                        <th>Số sản phẩm</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="color:var(--text-sub);">#<?= $cat['id'] ?></td>
                            <td><span style="font-size:24px;"><?= $cat['icon'] ?></span></td>
                            <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                            <td><code style="background:#f1f5f9;padding:4px 8px;border-radius:6px;font-size:12px;"><?= htmlspecialchars($cat['slug']) ?></code></td>
                            <td>
                                <span class="badge badge-info"><?= $cat['product_count'] ?> SP</span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-outline btn-sm">Sửa</a>
                                    <a href="?action=delete&id=<?= $cat['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Xóa danh mục này?')">Xóa</a>
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
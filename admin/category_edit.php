<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Sửa danh mục';
$currentPage = 'categories';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$pdo = getDB();
$error = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Thiếu ID danh mục');
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die('Danh mục không tồn tại');
}

function makeSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^a-z0-9]+/u', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $str));
    return trim($str, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = trim($_POST['slug']);

    if ($name === '') {
        $error = 'Tên danh mục không được để trống';
    } else {
        $slug = $slug === '' ? makeSlug($name) : makeSlug($slug);

        $check = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);

        if ($check->fetch()) {
            $error = 'Slug đã tồn tại';
        } else {
            $pdo->prepare("UPDATE categories SET name = ?, icon = ?, slug = ? WHERE id = ?")
                ->execute([$name, $icon, $slug, $id]);

            header("Location: categories.php");
            exit;
        }
    }
}

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>✏️ Sửa danh mục: <?= htmlspecialchars($category['name']) ?></h1>
    <a href="categories.php" class="btn btn-outline">← Quay lại</a>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Tên danh mục *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($category['name']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Icon (Emoji)</label>
                <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($category['icon']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($category['slug']) ?>">
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                <a href="categories.php" class="btn btn-outline">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
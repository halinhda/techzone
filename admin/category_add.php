<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Thêm danh mục';
$currentPage = 'categories';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php'); exit;
}

$pdo = getDB();
$error = '';

function slugify($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = !empty($_POST['slug']) ? slugify($_POST['slug']) : slugify($name);

    if (empty($name)) {
        $error = 'Vui lòng nhập tên danh mục!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, slug) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $icon, $slug])) {
            header('Location: categories.php');
            exit;
        } else {
            $error = 'Tên danh mục hoặc slug có thể bị trùng!';
        }
    }
}

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>+ Thêm danh mục</h1>
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
                <input type="text" name="name" class="form-control" required placeholder="Ví dụ: Laptop, Điện thoại">
            </div>
            <div class="form-group">
                <label class="form-label">Icon (Emoji)</label>
                <input type="text" name="icon" class="form-control" placeholder="💻 📱 🎧">
            </div>
            <div class="form-group">
                <label class="form-label">Slug (bỏ trống tự sinh)</label>
                <input type="text" name="slug" class="form-control" placeholder="laptop, dien-thoai">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">💾 Lưu danh mục</button>
                <a href="categories.php" class="btn btn-outline">Hủy</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
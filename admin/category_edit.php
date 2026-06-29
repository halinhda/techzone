<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

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
            $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?")
                ->execute([$name, $slug, $id]);

            header("Location: categories.php?updated=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Sửa danh mục</title>

<style>
:root{
    --primary:#2563eb;
    --primary-dark:#1e40af;
    --bg:#f1f5f9;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --border:#e5e7eb;
    --danger:#dc2626;
}

*{box-sizing:border-box}

body{
    margin:0;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:
        radial-gradient(1200px 500px at top,#e0e7ff 0%,transparent 60%),
        var(--bg);
    font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
    color:var(--text);
}

/* CARD */
.card{
    width:100%;
    max-width:520px;
    background:var(--card);
    border-radius:18px;
    padding:32px;
    box-shadow:
        0 20px 40px rgba(15,23,42,.1),
        0 8px 16px rgba(15,23,42,.06);
    animation:fadeUp .35s ease;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(12px)}
    to{opacity:1;transform:none}
}

/* HEADER */
.header{
    text-align:center;
    margin-bottom:28px;
}

.header h1{
    margin:0;
    font-size:28px;
    font-weight:800;
    letter-spacing:-.03em;
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.header p{
    margin-top:6px;
    font-size:14px;
    color:var(--muted);
}

/* FORM */
label{
    display:block;
    margin-bottom:6px;
    font-size:14px;
    font-weight:600;
}

input{
    width:100%;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid var(--border);
    font-size:14px;
    margin-bottom:18px;
    transition:.2s ease;
}

input:focus{
    outline:none;
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(37,99,235,.15);
}

/* ERROR */
.error{
    background:#fee2e2;
    color:#7f1d1d;
    padding:12px 14px;
    border-radius:10px;
    font-size:14px;
    margin-bottom:18px;
}

/* ACTIONS */
.actions{
    display:flex;
    gap:12px;
    margin-top:10px;
}

button{
    flex:1;
    padding:12px;
    border:none;
    border-radius:12px;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:.2s ease;
}

.btn-save{
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff;
    box-shadow:0 10px 20px rgba(37,99,235,.35);
}

.btn-save:hover{
    transform:translateY(-2px);
    box-shadow:0 16px 30px rgba(37,99,235,.45);
}

.btn-back{
    background:#f8fafc;
    border-radius: 15px;
    color:var(--text);
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

.btn-back:hover{
    background:#eef2ff;
    background-size: 20px;
}
</style>
</head>

<body>

<div class="card">
    <div class="header">
        <h1>Sửa danh mục</h1>
        <p>Cập nhật thông tin danh mục sản phẩm</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Tên danh mục</label>
        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>

        <label>Slug (có thể để trống)</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($category['slug']) ?>">

        <div class="actions">
            <button type="submit" class="btn-save">Lưu thay đổi</button>
            <a href="categories.php" class="btn-back">Quay lại</a>
        </div>
    </form>
</div>

</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php');
    exit;
}

$pdo = getDB();
$error = '';

function slugify($text)
{
    // Bỏ dấu tiếng Việt
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

    // Chuyển thường
    $text = strtolower($text);

    // Chỉ giữ a-z, 0-9, thay còn lại bằng dấu -
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Xóa dấu - dư ở đầu/cuối
    return trim($text, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $slug = !empty($_POST['slug'])
        ? slugify($_POST['slug'])
        : slugify($name);

    if (empty($name)) {
        $error = 'Vui lòng nhập tên danh mục!';
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, slug) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $icon, $slug])) {
            header('Location: /bainhom/admin/categories.php');
            exit;
        } else {
            $error = 'Có lỗi xảy ra, tên danh mục hoặc slug có thể bị trùng!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm danh mục</title>
</head>

<body style="
    margin:0;
    padding:40px;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
    background:radial-gradient(1000px 400px at top,#e0e7ff 0%,transparent 60%),#f1f5f9;
">

    <div style="
    max-width:520px;
    margin:60px auto;
    background:#fff;
    padding:36px;
    border-radius:18px;
    box-shadow:0 20px 40px rgba(15,23,42,.1);
">

        <h2 style="
        text-align:center;
        margin:0 0 24px;
        font-size:26px;
        font-weight:800;
        background:linear-gradient(135deg,#6366f1,#7c3aed);
        -webkit-background-clip:text;
        -webkit-text-fill-color:transparent;
    ">
            ➕ Thêm danh mục mới
        </h2>

        <?php if ($error): ?>
            <div style="
            padding:14px 16px;
            margin-bottom:20px;
            border-radius:12px;
            background:#fee2e2;
            color:#991b1b;
            font-weight:600;
        ">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div style="margin-bottom:18px;">
                <label style="font-weight:600; display:block; margin-bottom:6px;">
                    Tên danh mục
                </label>
                <input type="text" name="name" required placeholder="Ví dụ: Laptop, Điện thoại" style="
                    width:100%;
                    padding:12px 14px;
                    border-radius:10px;
                    border:1px solid #e5e7eb;
                ">
            </div>

            <div style="margin-bottom:18px;">
                <label style="font-weight:600; display:block; margin-bottom:6px;">
                    Icon (Emoji hoặc mã HTML)
                </label>
                <input type="text" name="icon" placeholder="💻 📱" style="
                    width:100%;
                    padding:12px 14px;
                    border-radius:10px;
                    border:1px solid #e5e7eb;
                ">
            </div>

            <div style="margin-bottom:22px;">
                <label style="font-weight:600; display:block; margin-bottom:6px;">
                    Slug (bỏ trống tự sinh)
                </label>
                <input type="text" name="slug" placeholder="laptop, dien-thoai" style="
                    width:100%;
                    padding:12px 14px;
                    border-radius:10px;
                    border:1px solid #e5e7eb;
                ">
            </div>

            <div style="display:flex; gap:14px; align-items:center;">
                <button type="submit" style="
                background:linear-gradient(135deg,#6366f1,#4f46e5);
                color:#fff;
                border:none;
                padding:12px 22px;
                border-radius:12px;
                font-weight:700;
                cursor:pointer;
                box-shadow:0 10px 22px rgba(99,102,241,.4);
            ">
                    Lưu danh mục
                </button>

                <a href="/bainhom/admin/categories.php" style="color:#64748b; font-weight:600; text-decoration:none;">
                    Hủy
                </a>
            </div>

        </form>
    </div>

</body>

</html>
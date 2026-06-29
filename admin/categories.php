<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php');
    exit;
}

$pdo = getDB();
$message = '';

// Delete category
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $message = $stmt->execute([$id])
        ? 'Xóa danh mục thành công'
        : 'Có lỗi xảy ra';
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <style>
        /* ===== THEME ===== */
        :root {
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e5e7eb;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --danger: #dc2626;
        }

        /* RESET */
        * {
            box-sizing: border-box
        }

        /* PAGE */
        body {
            margin: 0;
            padding: 40px;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background:
                radial-gradient(1200px 500px at top, #e0e7ff 0%, transparent 60%),
                var(--bg);
            color: var(--text);
        }

        /* CARD */
        .container {
            max-width: 1100px;
            margin: auto;
            background: var(--card);
            border-radius: 18px;
            padding: 32px;
            box-shadow:
                0 20px 40px rgba(15, 23, 42, .08),
                0 6px 12px rgba(15, 23, 42, .05);
            transition: .25s ease;
        }

        .container:hover {
            transform: translateY(-2px);
            box-shadow:
                0 32px 64px rgba(15, 23, 42, .12),
                0 12px 20px rgba(15, 23, 42, .08);
        }

        /* HEADER */
        /* HEADER CENTER REAL */
        .header {
            position: relative;
            margin-bottom: 32px;
        }

        /* TITLE CENTER */
        .page-title {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0;

            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;

            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;

            text-shadow: 0 4px 12px rgba(37, 99, 235, .25);
        }

        /* BUTTON RIGHT FIX */
        .add-btn {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        /* BUTTON */
        .btn {
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: .18s ease;
        }

        /* ADD */
        .btn-add {
            background: linear-gradient(135deg, var(--primary), #1e40af);
            color: #fff;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .35);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(37, 99, 235, .45);
        }

        /* EDIT */
        .btn-edit {
            background: #fff;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-edit:hover {
            background: #f8fafc;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
            transform: translateY(-1px);
        }

        /* DELETE */
        .btn-delete {
            background: none;
            color: var(--danger);
        }

        .btn-delete:hover {
            background: #fee2e2;
            box-shadow: 0 6px 14px rgba(220, 38, 38, .25);
            transform: translateY(-1px);
        }

        .btn-back-home {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background: #f8fafc;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-back-home:hover {
            background: #eef2ff;
            box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
            transform: translateY(-50%) translateY(-1px);
        }

        /* ALERT */
        .alert {
            padding: 16px 20px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
            color: #065f46;
            border: 1px solid #bbf7d0;
            box-shadow: 0 8px 18px rgba(22, 163, 74, .18);
            margin-bottom: 24px;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        thead {
            background: #f8fafc;
        }

        th {
            padding: 16px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
        }

        td {
            padding: 18px 16px;
            border-top: 1px solid var(--border);
            font-size: 14px;
            transition: .18s ease;
        }

        /* ROW HOVER */
        tbody tr {
            transition: .22s ease;
        }

        tbody tr:hover {
            background: #f8fafc;
            transform: scale(1.004);
        }

        tbody tr:hover td {
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, .7),
                inset 0 -1px 0 rgba(0, 0, 0, .02);
        }

        /* ICON */
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            color: #3730a3;
            font-weight: 600;
            box-shadow: 0 6px 14px rgba(55, 48, 163, .3);
        }

        /* ACTIONS */
        .actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header">

            <!-- NÚT QUAY LẠI TRANG CHỦ -->
            <a href="/bainhom/admin/index.php" class="btn btn-back-home">
                ← Trang chủ
            </a>

            <h1 class="page-title">Quản lý danh mục</h1>

            <a href="/bainhom/admin/category_add.php" class="btn btn-add add-btn">
                Thêm danh mục
            </a>

        </div>

        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Icon</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['id'] ?></td>
                        <td><span class="icon"><?= $cat['icon'] ?></span></td>
                        <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <div class="actions">
                                <a href="/bainhom/admin/category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-edit">Sửa</a>
                                <a href="?action=delete&id=<?= $cat['id'] ?>" class="btn btn-delete"
                                    onclick="return confirm('Xóa danh mục này?')">
                                    Xóa
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

</body>

</html>
<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

/* ===============================
   KIỂM TRA QUYỀN ADMIN
================================ */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /bainhom/index.php");
    exit;
}

/* ===============================
   KHÓA / MỞ USER
   PHẢI ĐẶT TRƯỚC HEADER
================================ */
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];

    $stmt = $pdo->prepare("
        UPDATE users
        SET status = IF(status = 1, 0, 1)
        WHERE id = ? AND role != 'admin'
    ");
    $stmt->execute([$id]);

    header("Location: users.php");
    exit;
}

/* ===============================
   LẤY DANH SÁCH USER
================================ */
$stmt = $pdo->query("
    SELECT id, fullname, email, role, status, created_at
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 20px 0;">
    <h2>Quản lý người dùng</h2>
    <table class="table" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Ngày đăng ký</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['fullname'] ?? 'Chưa cập nhật') ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>

                    <!-- VAI TRÒ -->
                    <td>
                        <span class="badge" style="padding:5px 10px;border-radius:4px;
                background:<?= $u['role'] === 'admin' ? '#fee2e2' : '#dcfce7' ?>">
                            <?= $u['role'] ?>
                        </span>
                    </td>

                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>

                    <!-- TRẠNG THÁI -->
                    <td>
                        <span style="
                            display:inline-flex;
                            align-items:center;
                            gap:6px;
                            padding:6px 12px;
                            border-radius:999px;
                            font-size:13px;
                            font-weight:600;
                            background: <?= $u['status'] ? '#dcfce7' : '#fee2e2' ?>;
                            color: <?= $u['status'] ? '#166534' : '#991b1b' ?>;
">
                            <?= $u['status'] ? '🟢 Hoạt động' : '🔴 Bị khóa' ?>
                        </span>
                    </td>

                    <!-- HÀNH ĐỘNG -->
                    <td>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <a href="users.php?toggle=<?= $u['id'] ?>" onclick="return confirm('Khóa / mở tài khoản này?')"
                                style="
                                    display:inline-block;
                                    padding:6px 14px;
                                    border-radius:8px;
                                    font-size:13px;
                                    font-weight:600;
                                    background:<?= $u['status'] ? '#dc2626' : '#16a34a' ?>;
                                    color:white;
                                    text-decoration:none;
                                    transition:0.2s;
                                    " onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                <?= $u['status'] ? '🔒 Khóa' : '🔓 Mở' ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#94a3b8;font-size:13px;">Admin</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
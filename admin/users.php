<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Quản lý khách hàng';
$currentPage = 'users';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /bainhom/index.php");
    exit;
}

$pdo = getDB();

/* ===============================
   KHÓA / MỞ USER
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
    SELECT u.id, u.fullname, u.email, u.phone, u.role, u.status, u.created_at,
           COUNT(o.id) as total_orders,
           SUM(CASE WHEN o.status = 'Đã hoàn thành' THEN o.total_price ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>👥 Khách hàng <span class="badge badge-secondary"><?= count($users) ?></span></h1>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên hiển thị</th>
                        <th>Liên hệ</th>
                        <th>Vai trò</th>
                        <th>Đơn hàng</th>
                        <th>Tổng chi tiêu</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td style="color:var(--text-sub);">#<?= $u['id'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--primary-light);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;">
                                        <?= mb_substr(htmlspecialchars($u['fullname'] ?? 'U'), 0, 1, 'UTF-8') ?>
                                    </div>
                                    <strong><?= htmlspecialchars($u['fullname'] ?? 'Chưa cập nhật') ?></strong>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></div>
                                <div style="font-size:12px;color:var(--text-sub);"><?= htmlspecialchars($u['phone'] ?? '—') ?></div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Khách hàng</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= $u['total_orders'] ?></strong> đơn</td>
                            <td class="price"><?= number_format($u['total_spent'], 0, ',', '.') ?>đ</td>
                            <td>
                                <?php if ($u['status']): ?>
                                    <span class="badge badge-success">🟢 Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">🔴 Bị khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <a href="users.php?toggle=<?= $u['id'] ?>" onclick="return confirm('<?= $u['status'] ? 'Khóa' : 'Mở' ?> tài khoản này?')" class="btn btn-sm <?= $u['status'] ? 'btn-danger' : 'btn-success' ?>">
                                        <?= $u['status'] ? '🔒 Khóa' : '🔓 Mở khóa' ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-sub);font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
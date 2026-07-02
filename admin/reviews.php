<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Quản lý đánh giá';
$currentPage = 'reviews';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

$pdo = getDB();

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: reviews.php?msg=deleted");
    exit;
}

// Fetch Reviews
$stmt = $pdo->query("
    SELECT r.*, p.name AS product_name, p.image_file, u.fullname, u.email
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>⭐ Đánh giá sản phẩm <span class="badge badge-secondary"><?= count($reviews) ?></span></h1>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">✅ Đã xóa đánh giá thành công!</div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Sản phẩm</th>
                        <th>Đánh giá</th>
                        <th>Nội dung</th>
                        <th>Ngày gửi</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-sub);">Chưa có đánh giá nào.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($r['fullname'] ?? 'Khách') ?></strong><br>
                                <span style="font-size:12px;color:var(--text-sub);"><?= htmlspecialchars($r['email']) ?></span>
                            </td>
                            <td>
                                <div class="product-cell">
                                    <img src="/bainhom/assets/images/<?= htmlspecialchars($r['image_file'] ?: 'no-image.png') ?>" class="product-thumb">
                                    <div class="product-cell-name"><?= htmlspecialchars($r['product_name']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="color:#f59e0b;font-size:14px;">
                                    <?= str_repeat('★', $r['rating']) ?><?= str_repeat('☆', 5 - $r['rating']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="max-width:300px;font-size:13px;line-height:1.4;">
                                    <?= nl2br(htmlspecialchars($r['comment'])) ?>
                                </div>
                            </td>
                            <td><span style="font-size:13px;color:var(--text-sub);"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span></td>
                            <td>
                                <a href="?action=delete&id=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

<?php
// product_detail.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. Lấy dữ liệu sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='container' style='padding: 50px; text-align: center;'><h2>Sản phẩm không tồn tại.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// 2. Lấy danh sách đánh giá
if (!$pdo) {
    die("Lỗi: Không kết nối được Database. Kiểm tra lại file config.php");
}
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// 3. Lấy 4 sản phẩm liên quan
$relatedStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
$relatedStmt->execute([$product['category_id'], $id]);
$relatedProducts = $relatedStmt->fetchAll();
?>

<div class="container" style="padding: 40px 0;">
    <div class="row" style="display: flex; gap: 40px; flex-wrap: wrap;">
        <div class="product-image" style="flex: 1; min-width: 350px;">
            <img src="assets/images/<?= htmlspecialchars($product['image_file'] ?? 'default.png') ?>"
                style="width: 100%; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        </div>

        <div class="product-info" style="flex: 1; min-width: 350px;">
            <h1 style="margin: 0;"><?= htmlspecialchars($product['name']) ?></h1>

            <div style="margin: 10px 0; color: #f1c40f; font-size: 18px;">
                <?php
                $rating = (int) round($product['rating']);
                $rating = max(0, min(5, $rating));
                ?>
                <?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?>
                <span style="color: #666; font-size: 14px;">(<?= number_format($product['rating'], 1) ?> sao)</span>
            </div>

            <p style="font-size: 30px; color: #e74c3c; font-weight: bold; margin: 20px 0;">
                <?= number_format($product['price'], 0, ',', '.') ?> VNĐ
            </p>

            <form action="cart.php?action=add" method="POST"
                style="display: flex; gap: 15px; align-items: center; margin-bottom: 20px;">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="number" name="qty" value="1" min="1" max="10"
                    style="width: 60px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <button type="submit" class="btn btn-primary"
                    style="padding: 10px 30px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Thêm vào giỏ hàng
                </button>
            </form>

            <p><strong>Thương hiệu:</strong> <?= htmlspecialchars($product['brand'] ?? 'N/A') ?></p>
            <p><strong>Tình trạng:</strong>
                <?= $product['stock'] > 0 ? '<span style="color:green;">Còn hàng (Còn ' . $product['stock'] . ' sản phẩm)</span>' : '<span style="color:red;">Hết hàng</span>' ?>
            </p>
        </div>
    </div>

    <div style="margin-top: 50px; background: #f9f9f9; padding: 30px; border-radius: 10px;">
        <h3>Mô tả chi tiết</h3>
        <p style="line-height: 1.8;"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
    </div>

    <div style="margin-top: 50px;">
        <h3>Đánh giá sản phẩm</h3>

        <?php if (isset($user)): ?>
            <form action="process_review.php" method="POST"
                style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div style="margin-bottom: 10px;">
                    <label>Số sao:</label>
                    <select name="rating" required>
                        <option value="5">5 Sao</option>
                        <option value="4">4 Sao</option>
                        <option value="3">3 Sao</option>
                        <option value="2">2 Sao</option>
                        <option value="1">1 Sao</option>
                    </select>
                </div>
                <textarea name="comment" required placeholder="Viết đánh giá của bạn..."
                    style="width: 100%; height: 80px; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
                <button type="submit"
                    style="margin-top: 10px; padding: 8px 20px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer;">Gửi
                    đánh giá</button>
            </form>
        <?php else: ?>
            <p><a href="login.php">Đăng nhập</a> để gửi đánh giá.</p>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <p>Chưa có đánh giá nào.</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                    <strong><?= htmlspecialchars($review['fullname']) ?></strong>
                    <span style="color: #f1c40f;">(<?= (int) $review['rating'] ?> sao)</span>
                    <p style="margin: 5px 0;"><?= htmlspecialchars($review['comment']) ?></p>
                    <small style="color: #999;"><?= $review['created_at'] ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($relatedProducts): ?>
        <div style="margin-top: 50px;">
            <h3>Sản phẩm liên quan</h3>
            <div
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php foreach ($relatedProducts as $item): ?>
                    <a href="product_detail.php?id=<?= $item['id'] ?>" style="text-decoration: none; color: #333;">
                        <div style="border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                            <img src="assets/images/<?= htmlspecialchars($item['image_file'] ?? 'default.png') ?>"
                                style="width: 100%; border-radius: 5px;">
                            <p style="font-weight: bold; margin: 10px 0 0;"><?= htmlspecialchars($item['name']) ?></p>
                            <p style="color: #e74c3c;"><?= number_format($item['price'], 0, ',', '.') ?> VNĐ</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
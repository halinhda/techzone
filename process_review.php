<?php
/**
 * process_review.php – Xử lý lưu đánh giá sản phẩm từ người dùng
 */

require_once __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /bainhom/index.php");
    exit;
}

// 1. Kiểm tra đăng nhập
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? '/bainhom/index.php';
    header("Location: /bainhom/controllers/auth.php?mode=login");
    exit;
}

$user = currentUser();
$userId = $user['id'];

// 2. Kiểm tra CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    die("Yêu cầu không hợp lệ (CSRF detected)!");
}

$pdo = getDB();

// 3. Lấy dữ liệu và validate
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
$comment = htmlspecialchars(trim($_POST['comment'] ?? ''), ENT_QUOTES, 'UTF-8');

// Giới hạn rating từ 1 đến 5 sao
$rating = max(1, min(5, $rating));

// Kiểm tra xem sản phẩm có tồn tại không
$productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$productStmt->execute([$productId]);
if (!$productStmt->fetch()) {
    die("Sản phẩm không tồn tại.");
}

if (empty($comment)) {
    echo "<script>
        alert('Vui lòng nhập nội dung đánh giá.');
        window.history.back();
    </script>";
    exit;
}

try {
    // 4. Lưu đánh giá vào database
    $insertStmt = $pdo->prepare("
        INSERT INTO reviews (product_id, user_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $insertStmt->execute([$productId, $userId, $rating, $comment]);

    // 5. Tính toán lại rating trung bình cho sản phẩm
    $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
    $avgStmt->execute([$productId]);
    $avgData = $avgStmt->fetch();
    $newAvgRating = $avgData['avg_rating'] ? round((float)$avgData['avg_rating'], 1) : $rating;

    // Cập nhật điểm rating mới vào bảng products
    $updateStmt = $pdo->prepare("UPDATE products SET rating = ? WHERE id = ?");
    $updateStmt->execute([$newAvgRating, $productId]);

    // 6. Chuyển hướng về trang chi tiết sản phẩm kèm thông báo
    echo "<script>
        alert('Đăng đánh giá thành công! Cảm ơn bạn.');
        window.location.href = 'product_detail.php?id=" . $productId . "';
    </script>";
    exit;

} catch (PDOException $e) {
    die("Lỗi lưu đánh giá: " . $e->getMessage());
}

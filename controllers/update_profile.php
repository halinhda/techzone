<?php
/**
 * controllers/update_profile.php – Xử lý cập nhật hồ sơ cá nhân
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';

// Chặn chưa đăng nhập
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để cập nhật hồ sơ.";
    header("Location: /bainhom/controllers/auth.php?mode=login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /bainhom/views/profile.php");
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['profile_error'] = "Yêu cầu không hợp lệ. Vui lòng thử lại.";
    header("Location: /bainhom/views/profile.php");
    exit;
}

$pdo = getDB();
$userId = $_SESSION['user']['id'];

// Lấy và validate dữ liệu
$fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
$phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
$address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
$paymentMethod = $_POST['payment_method'] ?? null;

// Validate
$errors = [];

if (empty($fullname)) {
    $errors[] = "Họ và tên không được để trống.";
}

if (mb_strlen($fullname) > 100) {
    $errors[] = "Họ và tên tối đa 100 ký tự.";
}

if (!empty($phone) && (strlen($phone) < 9 || strlen($phone) > 11)) {
    $errors[] = "Số điện thoại phải từ 9 đến 11 chữ số.";
}

// Validate payment method
$allowedPayments = ['cod', 'momo', 'transfer', 'qr', 'credit_card', 'bank_card'];
if ($paymentMethod && !in_array($paymentMethod, $allowedPayments)) {
    $paymentMethod = null;
}

if (!empty($errors)) {
    $_SESSION['profile_error'] = implode(' ', $errors);
    header("Location: /bainhom/views/profile.php");
    exit;
}

// Cập nhật database
try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET fullname = ?,
            phone = ?,
            address = ?,
            payment_method = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $fullname,
        $phone ?: null,
        $address ?: null,
        $paymentMethod,
        $userId
    ]);

    // Cập nhật session
    $_SESSION['user']['fullname'] = $fullname;

    $_SESSION['profile_success'] = "Hồ sơ cá nhân đã được cập nhật thành công!";
} catch (PDOException $e) {
    $_SESSION['profile_error'] = "Có lỗi xảy ra khi cập nhật. Vui lòng thử lại.";
}

header("Location: /bainhom/views/profile.php");
exit;

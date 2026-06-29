<?php
require_once __DIR__ . '/../includes/config.php';
// Đảm bảo session đã được khởi tạo để dùng CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDB();
$sid = cartSessionId();
$user = currentUser();

// 🚫 CHẶN CHƯA ĐĂNG NHẬP
if (!$user || empty($user['id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để đặt hàng!";
    header("Location: /bainhom/views/login.php");
    exit;
}

// 🚫 CHẶN ADMIN MUA HÀNG
if ($user && in_array($user['role'], ['admin', 'staff'])) {
    $_SESSION['error'] = "Tài khoản quản trị không được phép mua hàng.";
    header("Location: /bainhom/views/cart.php");
    exit;
}
$userId = $user['id'];

// LẤY GIỎ HÀNG TỪ DATABASE
$stmt = $pdo->prepare("
    SELECT c.product_id, c.quantity, p.price, p.stock, p.name, p.image_emoji
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.session_id = ? OR (c.user_id IS NOT NULL AND c.user_id = ?)
");
$stmt->execute([$sid, $userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CHỐNG CSRF: Kiểm tra Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Yêu cầu không hợp lệ (CSRF detected)!");
    }

    // 2. LỌC DỮ LIỆU: Chỉ cho phép ký tự an toàn
    $customer_name = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
    // Chỉ lấy số và đảm bảo SĐT có độ dài hợp lý (ví dụ: từ 9 đến 11 số)
    $customer_phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $customer_address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    // Danh sách tất cả các phương thức mà Database của bạn hỗ trợ
    $allowed_methods = ['cod', 'qr', 'momo', 'transfer'];

    // Kiểm tra xem dữ liệu gửi lên có nằm trong danh sách cho phép không
    $payment_method = in_array($_POST['pay'] ?? '', $allowed_methods) ? $_POST['pay'] : 'cod';
    // Kiểm tra thông tin bắt buộc
    if (empty($customer_name) || strlen($customer_phone) < 9 || empty($customer_address)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin (Họ tên, Địa chỉ và SĐT phải có ít nhất 9 số).";
        header("Location: /bainhom/views/checkout.php"); // Thay bằng đường dẫn trang thanh toán của bạn
        exit;
    }

    // 1️⃣ CHECK GIỎ HÀNG
if (empty($cartItems)) {
    $_SESSION['error'] = "Giỏ hàng trống!";
    header("Location: /bainhom/views/cart.php");
    exit;
}

// 2️⃣ CHECK TỒN KHO
foreach ($cartItems as $item) {
    if ((int)$item['quantity'] > (int)$item['stock']) {
        $_SESSION['error'] = "Sản phẩm {$item['name']} không đủ hàng!";
        header("Location: /bainhom/views/cart.php");
        exit;
    }
}

// 3️⃣ TÍNH TIỀN (CHỈ 1 LẦN)
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

$shipping_fee = ($subtotal >= FREE_SHIP_MIN) ? 0 : SHIPPING_FEE;
$total_price = $subtotal + $shipping_fee;

// 4️⃣ CHẶN ĐƠN 0 ĐỒNG
if ($total_price <= 0) {
    $_SESSION['error'] = "Đơn hàng không hợp lệ!";
    header("Location: /bainhom/views/cart.php");
    exit;
}

// 5️⃣ TẠO MÃ ĐƠN
$order_code = 'DH' . date('YmdHis') . rand(100, 999);

// 6️⃣ TRANSACTION
try {
    $pdo->beginTransaction();

    $pdo->prepare("
        INSERT INTO orders 
        (order_code, user_id, customer_name, customer_phone, customer_address, subtotal, shipping_fee, total_price, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $order_code,
        $userId,
        $customer_name,
        $customer_phone,
        $customer_address,
        $subtotal,
        $shipping_fee,
        $total_price,
        $payment_method
    ]);

    $orderId = $pdo->lastInsertId();

    $insertItemStmt = $pdo->prepare("
        INSERT INTO order_items 
        (order_id, product_id, quantity, price, name, image_emoji) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $updateStockStmt = $pdo->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ?
    ");

    foreach ($cartItems as $item) {
        $insertItemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['name'],
            $item['image_emoji']
        ]);
        $updateStockStmt->execute([(int)$item['quantity'], (int)$item['product_id']]);
    }

    $pdo->prepare("
        DELETE FROM cart_items WHERE session_id = ? OR user_id = ?
    ")->execute([$sid, $userId]);

    $pdo->commit();
    header("Location: /bainhom/views/checkout_success.php?code=$order_code");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Có lỗi xảy ra, vui lòng thử lại.");
}}
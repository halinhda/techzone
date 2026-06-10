<?php
require_once __DIR__ . '/../includes/config.php';
// Đảm bảo session đã được khởi tạo để dùng CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDB();
$sid = cartSessionId();
$user = currentUser();
$userId = $user['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CHỐNG CSRF: Kiểm tra Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Yêu cầu không hợp lệ (CSRF detected)!");
    }

    // 2. LỌC DỮ LIỆU: Chỉ cho phép ký tự an toàn
    $customer_name = htmlspecialchars(trim($_POST['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $customer_phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''); // Chỉ lấy số
    $customer_address = htmlspecialchars(trim($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8');
    $payment_method = in_array($_POST['pay'] ?? '', ['cod', 'transfer']) ? $_POST['pay'] : 'cod';

    if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
        die("Vui lòng điền đầy đủ thông tin!");
    }

    // 3. TÍNH TOÁN DỮ LIỆU TỪ CSDL (Đã an toàn - không tin dữ liệu từ POST)
    $cartStmt = $pdo->prepare("SELECT c.product_id, c.quantity, p.price, p.name, p.image_emoji, p.stock 
                                FROM cart_items c 
                                JOIN products p ON c.product_id = p.id 
                                WHERE c.session_id = ? OR (c.user_id IS NOT NULL AND c.user_id = ?)");
    $cartStmt->execute([$sid, $userId]);
    $cartItems = $cartStmt->fetchAll();

    if (empty($cartItems)) {
        die("Giỏ hàng trống!");
    }

    foreach ($cartItems as $item) {
        if ((int) $item['quantity'] > (int) $item['stock']) {
            die("Sản phẩm " . htmlspecialchars($item['name']) . " không đủ hàng!");
        }
    }

    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += (float) $item['price'] * (int) $item['quantity'];
    }
    $shipping_fee = ($subtotal >= FREE_SHIP_MIN) ? 0 : SHIPPING_FEE;
    $total_price = $subtotal + $shipping_fee;
    $order_code = 'DH' . date('YmdHis') . rand(100, 999);

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO orders (order_code, user_id, customer_name, customer_phone, customer_address, subtotal, shipping_fee, total_price, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$order_code, $userId, $customer_name, $customer_phone, $customer_address, $subtotal, $shipping_fee, $total_price, $payment_method]);

        $orderId = $pdo->lastInsertId();

        $insertItemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, name, image_emoji) VALUES (?, ?, ?, ?, ?, ?)");
        $updateStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($cartItems as $item) {
            $insertItemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price'], $item['name'], $item['image_emoji']]);
            $updateStockStmt->execute([(int) $item['quantity'], (int) $item['product_id']]);
        }

        $pdo->prepare("DELETE FROM cart_items WHERE session_id = ? OR (user_id IS NOT NULL AND user_id = ?)")->execute([$sid, $userId]);

        $pdo->commit();
        header("Location: /bainhom/views/checkout_success.php?code=" . $order_code);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Có lỗi xảy ra, vui lòng thử lại sau."); // Không nên in $e->getMessage() ra màn hình cho người dùng thấy trong thực tế
    }
}
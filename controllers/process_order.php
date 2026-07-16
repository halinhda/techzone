<?php
require_once __DIR__ . '/../includes/config.php';
// Đảm bảo session đã được khởi tạo để dùng CSRF Token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDB();
$sid = cartSessionId();
$user = currentUser();

// Xác định khách vãng lai hay user đã đăng nhập
$isGuest = isset($_POST['is_guest']) && $_POST['is_guest'] === '1';
$userId = $user['id'] ?? null;

// 🚫 CHẶN ADMIN MUA HÀNG
if ($user && in_array($user['role'], ['admin', 'staff'])) {
    $_SESSION['error'] = "Tài khoản quản trị không được phép mua hàng.";
    header("Location: /bainhom/views/cart.php");
    exit;
}

// LẤY GIỎ HÀNG TỪ DATABASE
$stmt = $pdo->prepare("
    SELECT c.product_id, c.quantity, p.price, p.stock, p.name, p.image_emoji
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.session_id = ? OR (c.user_id IS NOT NULL AND c.user_id = ?)
");
$stmt->execute([$sid, $userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ĐẾM SỐ LOẠI SẢN PHẨM
$distinctProducts = count($cartItems);

// KIỂM TRA: Nếu ≥2 loại SP mà chưa đăng nhập → chặn
if ($distinctProducts >= 2 && (!$user || empty($user['id']))) {
    $_SESSION['error'] = "Bạn cần đăng nhập để mua từ 2 sản phẩm trở lên!";
    header("Location: /bainhom/controllers/auth.php?mode=login");
    exit;
}

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
    $allowed_methods = ['cod', 'qr', 'momo', 'transfer', 'credit_card', 'bank_card'];

    // Kiểm tra xem dữ liệu gửi lên có nằm trong danh sách cho phép không
    $payment_method = in_array($_POST['pay'] ?? '', $allowed_methods) ? $_POST['pay'] : 'cod';
    // Kiểm tra thông tin bắt buộc
    if (empty($customer_name) || strlen($customer_phone) < 9 || empty($customer_address)) {
        $_SESSION['error'] = "Vui lòng nhập đầy đủ thông tin (Họ tên, Địa chỉ và SĐT phải có ít nhất 9 số).";
        header("Location: /bainhom/checkout.php");
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

// === XỬ LÝ VOUCHER (MÃ GIẢM GIÁ) ===
$voucherCode = !empty($_POST['voucher_code']) ? strtoupper(trim($_POST['voucher_code'])) : null;
$discountAmount = 0;

if ($voucherCode) {
    $vStmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? LIMIT 1");
    $vStmt->execute([$voucherCode]);
    $voucher = $vStmt->fetch();

    if ($voucher) {
        $isExpired = strtotime($voucher['expiry_date']) < time();
        $isLimitReached = $voucher['used_count'] >= $voucher['usage_limit'];
        $isMinNotMet = $subtotal < (float)$voucher['min_order_value'];

        if (!$isExpired && !$isLimitReached && !$isMinNotMet) {
            if ($voucher['discount_type'] === 'fixed') {
                $discountAmount = (float)$voucher['discount_value'];
            } else if ($voucher['discount_type'] === 'percent') {
                $discountAmount = $subtotal * ((float)$voucher['discount_value'] / 100.0);
                if ($voucher['max_discount'] !== null) {
                    $discountAmount = min($discountAmount, (float)$voucher['max_discount']);
                }
            }
            $discountAmount = min($discountAmount, $subtotal);
            $total_price = max(0, $total_price - $discountAmount);
        } else {
            $voucherCode = null;
        }
    } else {
        $voucherCode = null;
    }
}

// 4️⃣ CHẶN ĐƠN ÂM TIỀN
if ($total_price < 0) {
    $_SESSION['error'] = "Đơn hàng không hợp lệ!";
    header("Location: /bainhom/views/cart.php");
    exit;
}

// 5️⃣ TẠO MÃ ĐƠN
$order_code = 'DH' . date('YmdHis') . rand(100, 999);

// 6️⃣ XỬ LÝ TÀI KHOẢN KHÁCH VÃNG LAI HOẶC CẬP NHẬT PROFILE
$isGuestOrder = false;

if ($isGuest && (!$user || empty($user['id']))) {
    // === KHÁCH VÃNG LAI: Tạo tài khoản guest ===
    $guestEmail = 'guest_' . time() . '_' . rand(1000, 9999) . '@techzone.guest';
    
    $insertGuestStmt = $pdo->prepare("
        INSERT INTO users (fullname, email, password, role, phone, address, is_guest, payment_method, created_at) 
        VALUES (?, ?, '', 'customer', ?, ?, 1, ?, NOW())
    ");
    $insertGuestStmt->execute([
        $customer_name,
        $guestEmail,
        $customer_phone,
        $customer_address,
        $payment_method
    ]);
    
    $userId = $pdo->lastInsertId();
    $isGuestOrder = true;
    
} else {
    // === USER ĐÃ ĐĂNG NHẬP: Cập nhật thông tin profile ===
    $userId = $user['id'];
    
    $updateProfileStmt = $pdo->prepare("
        UPDATE users 
        SET phone = COALESCE(NULLIF(?, ''), phone, ?),
            address = COALESCE(NULLIF(?, ''), address, ?),
            fullname = CASE WHEN ? != '' THEN ? ELSE fullname END,
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateProfileStmt->execute([
        $customer_phone, $customer_phone,
        $customer_address, $customer_address,
        $customer_name, $customer_name,
        $userId
    ]);
    
    // Cập nhật lại session
    $_SESSION['user']['fullname'] = $customer_name ?: $user['fullname'];
}

// 7️⃣ TRANSACTION - TẠO ĐƠN HÀNG
try {
    $pdo->beginTransaction();

    $pdo->prepare("
        INSERT INTO orders 
        (order_code, user_id, customer_name, customer_phone, customer_address, subtotal, shipping_fee, total_price, payment_method, voucher_code, discount_amount, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $order_code,
        $userId,
        $customer_name,
        $customer_phone,
        $customer_address,
        $subtotal,
        $shipping_fee,
        $total_price,
        $payment_method,
        $voucherCode,
        $discountAmount,
        date('Y-m-d H:i:s')
    ]);

    // Cập nhật số lượt dùng của voucher
    if ($voucherCode) {
        $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE code = ?")->execute([$voucherCode]);
    }

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
    
    // Đánh dấu nếu là đơn hàng khách vãng lai (dùng cho trang success)
    if ($isGuestOrder) {
        $_SESSION['guest_order'] = true;
    }
    
    header("Location: /bainhom/views/checkout_success.php?code=$order_code");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Có lỗi xảy ra, vui lòng thử lại.");
}}
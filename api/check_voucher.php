<?php
/**
 * api/check_voucher.php – Kiểm tra và tính toán giảm giá của voucher
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$pdo = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$code = strtoupper(trim($input['code'] ?? ''));
$subtotal = (float) ($input['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
    exit;
}

try {
    // Tìm voucher còn hiệu lực
    $stmt = $pdo->prepare("
        SELECT * FROM vouchers 
        WHERE code = ? LIMIT 1
    ");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không hợp lệ.']);
        exit;
    }

    // 1. Kiểm tra ngày hết hạn
    if (strtotime($voucher['expiry_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết hạn sử dụng.']);
        exit;
    }

    // 2. Kiểm tra giới hạn lượt sử dụng
    if ($voucher['used_count'] >= $voucher['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã đạt giới hạn lượt sử dụng tối đa.']);
        exit;
    }

    // 3. Kiểm tra giá trị đơn hàng tối thiểu
    if ($subtotal < (float)$voucher['min_order_value']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . formatVND($voucher['min_order_value']) . ' để dùng mã này.'
        ]);
        exit;
    }

    // 4. Tính toán số tiền giảm giá
    $discount = 0;
    if ($voucher['discount_type'] === 'fixed') {
        $discount = (float)$voucher['discount_value'];
    } else if ($voucher['discount_type'] === 'percent') {
        $discount = $subtotal * ((float)$voucher['discount_value'] / 100.0);
        if ($voucher['max_discount'] !== null) {
            $discount = min($discount, (float)$voucher['max_discount']);
        }
    }

    // Giới hạn giảm tối đa bằng tổng tiền tạm tính
    $discount = min($discount, $subtotal);

    echo json_encode([
        'success' => true,
        'code' => $voucher['code'],
        'discount_amount' => $discount,
        'message' => 'Áp dụng mã giảm giá thành công! Được giảm ' . formatVND($discount)
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
    exit;
}

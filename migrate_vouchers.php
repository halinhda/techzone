<?php
/**
 * Migration: Thêm bảng vouchers và tích hợp vào đơn hàng
 * 
 * Chạy 1 lần duy nhất:
 *   php migrate_vouchers.php
 * hoặc truy cập qua trình duyệt:
 *   http://localhost/bainhom/migrate_vouchers.php
 */

require_once __DIR__ . '/includes/config.php';

$pdo = getDB();
$migrations = [];

// 1. Tạo bảng vouchers
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `vouchers` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `code` VARCHAR(50) UNIQUE NOT NULL,
          `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'fixed',
          `discount_value` DECIMAL(15,0) NOT NULL,
          `min_order_value` DECIMAL(15,0) DEFAULT 0,
          `max_discount` DECIMAL(15,0) DEFAULT NULL,
          `expiry_date` DATETIME NOT NULL,
          `usage_limit` INT NOT NULL DEFAULT 100,
          `used_count` INT NOT NULL DEFAULT 0,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    $migrations[] = "✅ Đã tạo bảng `vouchers` thành công.";
} catch (PDOException $e) {
    $migrations[] = "❌ Lỗi tạo bảng `vouchers`: " . $e->getMessage();
}

// 2. Thêm cột voucher_code vào bảng orders
try {
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `voucher_code` VARCHAR(50) DEFAULT NULL AFTER `payment_method`");
    $migrations[] = "✅ Đã thêm cột `voucher_code` vào bảng `orders`";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭️ Cột `voucher_code` đã tồn tại, bỏ qua.";
    } else {
        $migrations[] = "❌ Lỗi thêm `voucher_code`: " . $e->getMessage();
    }
}

// 3. Thêm cột discount_amount vào bảng orders
try {
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `discount_amount` DECIMAL(15,0) DEFAULT 0 AFTER `voucher_code`");
    $migrations[] = "✅ Đã thêm cột `discount_amount` vào bảng `orders`";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭️ Cột `discount_amount` đã tồn tại, bỏ qua.";
    } else {
        $migrations[] = "❌ Lỗi thêm `discount_amount`: " . $e->getMessage();
    }
}

// 4. Seed dữ liệu voucher mẫu nếu chưa có
try {
    $check = $pdo->query("SELECT COUNT(*) FROM vouchers")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_discount, expiry_date, usage_limit, used_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        
        // 1. Voucher giảm 50k cho đơn từ 200k
        $stmt->execute(['TECHZONE50', 'fixed', 50000, 200000, null, date('Y-m-d H:i:s', strtotime('+1 year')), 100]);
        // 2. Voucher giảm 10% tối đa 200k cho đơn từ 500k
        $stmt->execute(['SALE10', 'percent', 10, 500000, 200000, date('Y-m-d H:i:s', strtotime('+1 year')), 50]);
        // 3. Voucher hết hạn để test
        $stmt->execute(['EXPIRED', 'fixed', 100000, 100000, null, date('Y-m-d H:i:s', strtotime('-1 day')), 100]);
        
        $migrations[] = "✅ Đã seed thành công 3 voucher mẫu: `TECHZONE50` (Giảm 50k), `SALE10` (Giảm 10%), và `EXPIRED` (Đã hết hạn)";
    } else {
        $migrations[] = "⏭️ Bảng `vouchers` đã có dữ liệu, bỏ qua bước seed.";
    }
} catch (PDOException $e) {
    $migrations[] = "❌ Lỗi seed vouchers: " . $e->getMessage();
}

// Hiển thị kết quả
echo "<h2>🔧 Migration: Vouchers & Order Update</h2>";
echo "<ul>";
foreach ($migrations as $m) {
    echo "<li style='margin:8px 0;font-family:monospace;font-size:14px;'>$m</li>";
}
echo "</ul>";
echo "<br><a href='/bainhom/index.php' style='color:#4f46e5;font-weight:bold;'>← Quay lại trang chủ</a>";

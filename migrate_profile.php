<?php
/**
 * Migration: Thêm cột hỗ trợ khách vãng lai và hồ sơ cá nhân
 * 
 * Chạy 1 lần duy nhất:
 *   php migrate_profile.php
 * hoặc truy cập qua trình duyệt:
 *   http://localhost/bainhom/migrate_profile.php
 */

require_once __DIR__ . '/includes/config.php';

$pdo = getDB();

$migrations = [];

// 1. Thêm cột is_guest vào bảng users
try {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_guest` TINYINT(1) NOT NULL DEFAULT 0 AFTER `facebook_id`");
    $migrations[] = "✅ Đã thêm cột `is_guest` vào bảng `users`";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭️ Cột `is_guest` đã tồn tại, bỏ qua.";
    } else {
        $migrations[] = "❌ Lỗi thêm `is_guest`: " . $e->getMessage();
    }
}

// 2. Thêm cột payment_method vào bảng users (phương thức thanh toán yêu thích)
try {
    $pdo->exec("ALTER TABLE `users` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `is_guest`");
    $migrations[] = "✅ Đã thêm cột `payment_method` vào bảng `users`";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        $migrations[] = "⏭️ Cột `payment_method` đã tồn tại, bỏ qua.";
    } else {
        $migrations[] = "❌ Lỗi thêm `payment_method`: " . $e->getMessage();
    }
}

// 3. Mở rộng ENUM payment_method của bảng orders để hỗ trợ thêm credit_card, bank_card
try {
    $pdo->exec("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('qr','cod','momo','transfer','credit_card','bank_card') NOT NULL DEFAULT 'cod'");
    $migrations[] = "✅ Đã mở rộng ENUM `payment_method` của bảng `orders`";
} catch (PDOException $e) {
    $migrations[] = "❌ Lỗi mở rộng ENUM orders.payment_method: " . $e->getMessage();
}

// Hiển thị kết quả
echo "<h2>🔧 Migration: Profile & Guest User</h2>";
echo "<ul>";
foreach ($migrations as $m) {
    echo "<li style='margin:8px 0;font-family:monospace;font-size:14px;'>$m</li>";
}
echo "</ul>";
echo "<br><a href='/bainhom/index.php' style='color:#4f46e5;font-weight:bold;'>← Quay lại trang chủ</a>";

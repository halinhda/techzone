<?php
require_once __DIR__ . '/includes/config.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL;");
    echo "<h1>Đã thêm cột payment_method thành công!</h1>";
    echo "<p>Vui lòng quay lại trang thanh toán và thử lại.</p>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<h1>Cột payment_method đã tồn tại.</h1>";
        echo "<p>Lỗi có thể do nguyên nhân khác hoặc bạn đã chạy file này rồi.</p>";
    } else {
        echo "<h1>Có lỗi xảy ra:</h1><pre>" . $e->getMessage() . "</pre>";
    }
}
?>

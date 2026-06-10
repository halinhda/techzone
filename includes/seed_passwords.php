<?php
/**
 * Chạy file này 1 lần để cập nhật mật khẩu đúng hash bcrypt vào DB.
 * Truy cập: http://localhost/bainhom/seed_passwords.php
 * Sau đó xóa file này đi.
 */

// Gọi file config từ thư mục includes ra ngoài thư mục gốc
require_once __DIR__ . '/config.php';

$pdo = getDB();

$users = [
    ['email' => 'admin@techzone.com', 'password' => '123456'],
    ['email' => 'user@techzone.com',  'password' => '123456'],
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $u['email']]);
    echo "✅ Updated: {$u['email']}<br>";
}

echo "<br><strong>Xong! Hãy xóa file này ngay.</strong>";
?>
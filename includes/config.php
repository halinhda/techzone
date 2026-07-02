<?php
// ================================================================
// TECHZONE – Cấu hình kết nối Database & hằng số hệ thống
// ================================================================
// Định nghĩa số điện thoại là một hằng số
define('HOTLINE', '0909 123 456');


// Tự động phát hiện môi trường Local hay Hosting (InfinityFree)
// Sẽ tự động dùng localhost nếu chạy bằng start.bat, và dùng sql206 nếu đẩy lên InfinityFree.
if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8000', '127.0.0.1'])) {
    // Local (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'techzone_db');
} else {
    // InfinityFree Hosting
    define('DB_HOST', 'sql206.infinityfree.com');
    define('DB_USER', 'if0_42297161');
    define('DB_PASS', 'iyBjXwMhaFRU');
    define('DB_NAME', 'if0_42297161_techzone');
}
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'TechZone');
define('FREE_SHIP_MIN', 5_000_000);   // đơn từ 5tr miễn phí vận chuyển
define('SHIPPING_FEE',     50_000);   // phí vận chuyển mặc định

// Kết nối PDO (dùng chung cho toàn bộ dự án)
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Không thể kết nối CSDL: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Khởi tạo session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => false,      // true nếu dùng HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Helpers ---- //

/** Định dạng số tiền VNĐ */
function formatVND(float|int $amount): string {
    return number_format($amount, 0, ',', '.') . ' đ';
}

/** Lấy thông tin người dùng hiện tại từ session */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/** Kiểm tra đã đăng nhập */
function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

/** Kiểm tra quyền Admin */
function isAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/** Redirect và dừng thực thi */
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

/** Trả JSON và dừng */
function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Tạo mã đơn hàng ngẫu nhiên */
function generateOrderCode(): string {
    return 'ORD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
}

/** Làm sạch đầu vào */
function clean(string $s): string {
    return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8');
}

/** Lấy session_id ổn định cho giỏ hàng khách vãng lai */
function cartSessionId(): string {
    if (empty($_SESSION['cart_sid'])) {
        $_SESSION['cart_sid'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_sid'];
}
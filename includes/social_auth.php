<?php
// ================================================================
// TECHZONE – Cấu hình Đăng nhập qua mạng xã hội (Google, Facebook)
// ================================================================

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . $domain;

// Phát hiện môi trường local để tắt SSL verify (XAMPP không có chứng chỉ CA)
$isLocalhost = in_array($domain, ['localhost', 'localhost:8000', '127.0.0.1']);

// ---- GOOGLE OAUTH CONFIG ----
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', $baseUrl . '/bainhom/controllers/google_login.php');

// ---- FACEBOOK OAUTH CONFIG ----
// 👉 Thay bằng App ID & Secret thật từ https://developers.facebook.com
define('FACEBOOK_APP_ID', getenv('FACEBOOK_APP_ID') ?: 'YOUR_FACEBOOK_APP_ID_HERE');
define('FACEBOOK_APP_SECRET', getenv('FACEBOOK_APP_SECRET') ?: 'YOUR_FACEBOOK_APP_SECRET_HERE');
define('FACEBOOK_REDIRECT_URI', $baseUrl . '/bainhom/controllers/facebook_login.php');
define('FACEBOOK_GRAPH_VERSION', 'v19.0');

/**
 * Tạo mật khẩu ngẫu nhiên an toàn cho các tài khoản tạo qua mạng xã hội
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

/**
 * Thực hiện gọi cURL GET
 * Tự động tắt SSL verify trên localhost (XAMPP không có CA bundle)
 */
function curlGet($url, $headers = []) {
    global $isLocalhost;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($isLocalhost) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL GET Error: ' . curl_error($ch) . ' – URL: ' . $url);
    }
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

/**
 * Thực hiện gọi cURL POST
 * Tự động tắt SSL verify trên localhost (XAMPP không có CA bundle)
 */
function curlPost($url, $data) {
    global $isLocalhost;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($isLocalhost) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('cURL POST Error: ' . curl_error($ch) . ' – URL: ' . $url);
    }
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

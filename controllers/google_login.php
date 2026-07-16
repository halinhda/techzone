<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['code'])) {
    redirect('/bainhom/controllers/auth.php');
}

$code = $_GET['code'];

// 1. Lấy Access Token từ Google
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$tokenResponse = curlPost($tokenUrl, $tokenData);

if (isset($tokenResponse['error']) || !isset($tokenResponse['access_token'])) {
    // Đăng nhập thất bại do token không hợp lệ
    redirect('/bainhom/controllers/auth.php?error=google_auth_failed');
}

$accessToken = $tokenResponse['access_token'];

// 2. Lấy thông tin user từ Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$userInfo = curlGet($userInfoUrl, ["Authorization: Bearer $accessToken"]);

if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
    redirect('/bainhom/controllers/auth.php?error=google_profile_failed');
}

$googleId = $userInfo['id'];
$email = $userInfo['email'];
$name = $userInfo['name'] ?? 'Google User';

$pdo = getDB();

// 3. Kiểm tra user trong DB (theo google_id HOẶC email)
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if ($user) {
    // Đã có tài khoản
    if (isset($user['status']) && $user['status'] == 0) {
        redirect('/bainhom/controllers/auth.php?error=account_locked');
    }

    // Nếu chưa có google_id thì cập nhật để liên kết tài khoản
    if (empty($user['google_id'])) {
        $updateStmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $updateStmt->execute([$googleId, $user['id']]);
    }

    $userId = $user['id'];
    $role = $user['role'];
    $fullname = $user['fullname'];

} else {
    // Chưa có tài khoản -> Tạo mới
    $randomPassword = password_hash(generateRandomPassword(), PASSWORD_BCRYPT);
    $role = 'customer';
    
    $insertStmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role, google_id) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->execute([$name, $email, $randomPassword, $role, $googleId]);
    $userId = $pdo->lastInsertId();
    $fullname = $name;
}

// 4. Set Session & Đăng nhập
$_SESSION['user'] = [
    'id' => $userId,
    'fullname' => $fullname,
    'email' => $email,
    'role' => $role,
];

// Gộp giỏ hàng khách vãng lai nếu có
$sid = cartSessionId();
$pdo->prepare('UPDATE cart_items SET user_id = ? WHERE session_id = ? AND user_id IS NULL')
    ->execute([$userId, $sid]);

// 5. Redirect
$redirect = $_SESSION['redirect_after_login'] ?? '/bainhom/index.php';
unset($_SESSION['redirect_after_login']);

if (!str_starts_with($redirect, '/bainhom/')) {
    $redirect = '/bainhom/index.php';
}

redirect($redirect);

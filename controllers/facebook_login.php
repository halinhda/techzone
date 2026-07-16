<?php
/**
 * controllers/facebook_login.php – Xử lý callback OAuth từ Facebook
 * 
 * Flow: User nhấn "Đăng nhập Facebook" trên auth.php
 *       → Facebook redirect về đây kèm ?code=...&state=...
 *       → Lấy access_token → Lấy thông tin user → Đăng nhập/Tạo tài khoản
 */
require_once __DIR__ . '/../includes/config.php';

// ============================================================
// 0. Kiểm tra Facebook trả về lỗi (user cancel hoặc deny)
// ============================================================
if (isset($_GET['error'])) {
    error_log('Facebook OAuth Error: ' . ($_GET['error_description'] ?? $_GET['error']));
    redirect('/bainhom/controllers/auth.php?error=facebook_auth_failed');
}

if (!isset($_GET['code'])) {
    redirect('/bainhom/controllers/auth.php');
}

// ============================================================
// 1. Xác thực CSRF state token
// ============================================================
$stateFromFB = $_GET['state'] ?? '';
$stateFromSession = $_SESSION['fb_oauth_state'] ?? '';

// Xóa state khỏi session sau khi dùng (one-time use)
unset($_SESSION['fb_oauth_state']);

if (empty($stateFromFB) || empty($stateFromSession) || !hash_equals($stateFromSession, $stateFromFB)) {
    error_log('Facebook CSRF state mismatch. Expected: ' . $stateFromSession . ' Got: ' . $stateFromFB);
    redirect('/bainhom/controllers/auth.php?error=facebook_csrf_failed');
}

$code = $_GET['code'];

// ============================================================
// 2. Lấy Access Token từ Facebook
// ============================================================
$tokenUrl = 'https://graph.facebook.com/' . FACEBOOK_GRAPH_VERSION . '/oauth/access_token?' . http_build_query([
    'client_id' => FACEBOOK_APP_ID,
    'client_secret' => FACEBOOK_APP_SECRET,
    'redirect_uri' => FACEBOOK_REDIRECT_URI,
    'code' => $code
]);

$tokenResponse = curlGet($tokenUrl);

if (isset($tokenResponse['error']) || !isset($tokenResponse['access_token'])) {
    $errMsg = $tokenResponse['error']['message'] ?? 'Unknown token error';
    error_log('Facebook Token Error: ' . $errMsg);
    redirect('/bainhom/controllers/auth.php?error=facebook_auth_failed');
}

$accessToken = $tokenResponse['access_token'];

// ============================================================
// 3. Lấy thông tin user từ Facebook Graph API
//    Yêu cầu: id, name, email, picture (avatar)
// ============================================================
$userInfoUrl = 'https://graph.facebook.com/' . FACEBOOK_GRAPH_VERSION . '/me?' . http_build_query([
    'fields' => 'id,name,email,picture.width(200).height(200)',
    'access_token' => $accessToken
]);

$userInfo = curlGet($userInfoUrl);

if (!isset($userInfo['id'])) {
    error_log('Facebook Profile Error: Could not fetch user ID. Response: ' . json_encode($userInfo));
    redirect('/bainhom/controllers/auth.php?error=facebook_profile_failed');
}

$facebookId = $userInfo['id'];
$name = $userInfo['name'] ?? 'Facebook User';

// Có thể Facebook không trả về email nếu user đăng ký bằng số điện thoại
// Nếu không có email, tạo email ảo dựa trên Facebook ID
$email = $userInfo['email'] ?? ($facebookId . '@facebook.techzone.com');

// Lấy URL avatar từ Facebook (ảnh profile 200x200)
$avatarUrl = $userInfo['picture']['data']['url'] ?? null;

$pdo = getDB();

// ============================================================
// 4. Kiểm tra user trong DB (theo facebook_id HOẶC email)
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE facebook_id = ? OR email = ? LIMIT 1");
$stmt->execute([$facebookId, $email]);
$user = $stmt->fetch();

if ($user) {
    // ---- Đã có tài khoản ----

    // Kiểm tra tài khoản bị khóa
    if (isset($user['status']) && $user['status'] == 0) {
        redirect('/bainhom/controllers/auth.php?error=account_locked');
    }

    // Nếu chưa có facebook_id → liên kết tài khoản
    if (empty($user['facebook_id'])) {
        $updateStmt = $pdo->prepare("UPDATE users SET facebook_id = ? WHERE id = ?");
        $updateStmt->execute([$facebookId, $user['id']]);
    }

    // Cập nhật avatar nếu user chưa có avatar và Facebook có trả về
    if (empty($user['avatar']) && $avatarUrl) {
        $updateAvatar = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $updateAvatar->execute([$avatarUrl, $user['id']]);
    }

    $userId = $user['id'];
    $role = $user['role'];
    $fullname = $user['fullname'];

} else {
    // ---- Chưa có tài khoản → Tạo mới ----
    $randomPassword = password_hash(generateRandomPassword(), PASSWORD_BCRYPT);
    $role = 'customer';
    
    $insertStmt = $pdo->prepare(
        "INSERT INTO users (fullname, email, password, role, facebook_id, avatar) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $insertStmt->execute([$name, $email, $randomPassword, $role, $facebookId, $avatarUrl]);
    $userId = $pdo->lastInsertId();
    $fullname = $name;
}

// ============================================================
// 5. Set Session & Đăng nhập
// ============================================================
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

// ============================================================
// 6. Redirect về trang trước đó hoặc trang chủ
// ============================================================
$redirect = $_SESSION['redirect_after_login'] ?? '/bainhom/index.php';
unset($_SESSION['redirect_after_login']);

if (!str_starts_with($redirect, '/bainhom/')) {
    $redirect = '/bainhom/index.php';
}

redirect($redirect);

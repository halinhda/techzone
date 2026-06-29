<?php
// Kiểm tra nếu session chưa bắt đầu thì mới bắt đầu
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['redirect_after_login']) && isset($_SERVER['HTTP_REFERER'])) {
  $ref = $_SERVER['HTTP_REFERER'];

  // Chỉ nhận redirect nội bộ (an toàn)
  if (str_contains($ref, '/bainhom/')) {
    $_SESSION['redirect_after_login'] = $ref;
  }
}

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

// ---- LOGOUT ----
if (($_GET['action'] ?? '') === 'logout') {
  // Xóa cụ thể user trước
  if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
  }
  session_destroy();

  // SAU LOGIN THÀNH CÔNG
  $redirect = $_SESSION['redirect_after_login'] ?? '/bainhom/index.php';
  unset($_SESSION['redirect_after_login']);

  header("Location: $redirect");
  exit();
}

// ---- VARS ----
$mode = $_GET['mode'] ?? 'login';   // 'login' | 'register'
$errors = [];
$flash = '';

// ---- HANDLE POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'login';

  // =========================
  // LOGIN
  // =========================
  if ($action === 'login') {

    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$email || !$pass) {
      $errors[] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
      $mode = 'login';

    } else {

      $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      $user = $stmt->fetch();

      if ($user && password_verify($pass, $user['password'])) {

        // 🔒 Check khóa tài khoản
        if (isset($user['status']) && $user['status'] == 0) {
          $errors[] = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
          $mode = 'login';

        } else {

          // =========================
          // SET SESSION USER
          // =========================
          $_SESSION['user'] = [
            'id' => $user['id'],
            'fullname' => $user['fullname'],
            'email' => $user['email'],
            'role' => $user['role'],
          ];

          // =========================
          // MERGE CART
          // =========================
          $sid = cartSessionId();
          $pdo->prepare('UPDATE cart_items SET user_id = ? WHERE session_id = ? AND user_id IS NULL')
            ->execute([$user['id'], $sid]);

          // =========================
          // REDIRECT LOGIC
          // =========================
          $redirect = $_SESSION['redirect_after_login'] ?? null;

          if (!$redirect) {
            $redirect = ($user['role'] === 'admin')
              ? '/bainhom/admin/index.php'
              : '/bainhom/index.php';
          }

          // chống redirect bậy
          if (!str_starts_with($redirect, '/bainhom/')) {
            $redirect = '/bainhom/index.php';
          }

          unset($_SESSION['redirect_after_login']);

          header("Location: $redirect");
          exit();
        }

      } else {
        $errors[] = 'Email hoặc mật khẩu không đúng.';
        $mode = 'login';
      }
    }

  // =========================
  // REGISTER
  // =========================
  } elseif ($action === 'register') {

    $fullname = clean($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$fullname || !$email || !$pass) {
      $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Email không hợp lệ.';
    }

    if (strlen($pass) < 6) {
      $errors[] = 'Mật khẩu tối thiểu 6 ký tự.';
    }

    if ($pass !== $pass2) {
      $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    // check trùng email
    if (empty($errors)) {
      $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
      $chk->execute([$email]);
      if ($chk->fetch()) {
        $errors[] = 'Email này đã được đăng ký.';
      }
    }

    // tạo user
    if (empty($errors)) {

      $hash = password_hash($pass, PASSWORD_BCRYPT);

      $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password, role) VALUES (?,?,?,?)');
      $stmt->execute([$fullname, $email, $hash, 'user']);

      $uid = $pdo->lastInsertId();

      $_SESSION['user'] = [
        'id' => $uid,
        'fullname' => $fullname,
        'email' => $email,
        'role' => 'user',
      ];

      // redirect sau register
      $redirect = $_SESSION['redirect_after_login'] ?? '/bainhom/index.php?register=success';
      unset($_SESSION['redirect_after_login']);

      header("Location: $redirect");
      exit();
    }

    $mode = 'register';
  }
}

$pageTitle = ($mode === 'register') ? 'Đăng Ký – ' . SITE_NAME : 'Đăng Nhập – ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
  <div class="auth-box">
    <?php if (!empty($flash)): ?>
      <div id="flash-data" data-msg="<?= clean($flash) ?>" data-type="success" hidden></div>
    <?php endif; ?>

    <div class="auth-icon-wrap">
      <i data-lucide="<?= $mode === 'register' ? 'user-plus' : 'lock' ?>"></i>
    </div>
    <h1 class="auth-title"><?= $mode === 'register' ? 'Tạo Tài Khoản' : 'Đăng Nhập Hệ Thống' ?></h1>
    <p class="auth-sub">
      <?= $mode === 'register' ? 'Điền thông tin để đăng ký tài khoản TechZone' : 'Nhập tài khoản của bạn để tiếp tục trải nghiệm' ?>
    </p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><i data-lucide="alert-circle"
          style="width:16px;height:16px;flex-shrink:0"></i><?= $err ?></div>
    <?php endforeach; ?>

    <?php if ($mode === 'login'): ?>
      <form method="POST" action="auth.php">
        <input type="hidden" name="action" value="login" />
        <div class="form-group">
          <label class="form-label">Email truy cập</label>
          <input type="email" name="email" class="form-control" placeholder="your@email.com" required
            value="<?= clean($_POST['email'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label class="form-label">Mật khẩu bảo mật</label>
          <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required />
        </div>
        <button type="submit" class="btn btn-dark btn-block btn-lg">
          <i data-lucide="log-in"></i> Xác Thực Đăng Nhập
        </button>
        <p style="text-align:center;font-size:12px;margin-top:14px;color:#64748b;font-weight:600">
          Chưa có tài khoản?
          <a href="auth.php?mode=register" style="color:#4f46e5;font-weight:800">Đăng ký ngay</a>
        </p>
      </form>
    <?php else: ?>
      <form method="POST" action="auth.php?mode=register">
        <input type="hidden" name="action" value="register" />
        <div class="form-group">
          <label class="form-label">Họ và tên</label>
          <input type="text" name="fullname" class="form-control" placeholder="Nguyễn Văn A" required
            value="<?= clean($_POST['fullname'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label class="form-label">Email truy cập</label>
          <input type="email" name="email" class="form-control" placeholder="your@email.com" required
            value="<?= clean($_POST['email'] ?? '') ?>" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required />
          </div>
          <div class="form-group">
            <label class="form-label">Xác nhận mật khẩu</label>
            <input type="password" name="password2" class="form-control" placeholder="Nhập lại mật khẩu" required />
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i data-lucide="user-plus"></i> Tạo Tài Khoản
        </button>
        <p style="text-align:center;font-size:12px;margin-top:14px;color:#64748b;font-weight:600">
          Đã có tài khoản?
          <a href="auth.php" style="color:#4f46e5;font-weight:800">Đăng nhập ngay</a>
        </p>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
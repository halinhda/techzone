<?php
// views/profile.php – Trang hồ sơ cá nhân
session_start();

// Chặn chưa đăng nhập
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để xem hồ sơ cá nhân.";
    header("Location: /bainhom/controllers/auth.php?mode=login");
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();
$userId = $_SESSION['user']['id'];

// Lấy thông tin đầy đủ từ database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    $_SESSION['error'] = "Không tìm thấy thông tin tài khoản.";
    header("Location: /bainhom/index.php");
    exit;
}

// Lấy thống kê đơn hàng
$orderStmt = $pdo->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_price), 0) as total_spent FROM orders WHERE user_id = ?");
$orderStmt->execute([$userId]);
$orderStats = $orderStmt->fetch();

$pageTitle = 'Hồ Sơ Cá Nhân – ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

// Flash messages
$successMsg = $_SESSION['profile_success'] ?? '';
$errorMsg = $_SESSION['profile_error'] ?? '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Payment method mapping
$paymentMethods = [
    'cod' => ['label' => 'Thanh toán khi nhận hàng (COD)', 'icon' => '💵'],
    'momo' => ['label' => 'Ví MoMo', 'icon' => '💜'],
    'transfer' => ['label' => 'Chuyển khoản ngân hàng', 'icon' => '🏦'],
    'qr' => ['label' => 'Quét mã QR', 'icon' => '📱'],
    'credit_card' => ['label' => 'Thẻ tín dụng', 'icon' => '💳'],
    'bank_card' => ['label' => 'Thẻ ngân hàng', 'icon' => '🏧'],
];

$currentPayment = $profile['payment_method'] ?? 'cod';
?>

<style>
.profile-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 20px 60px;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 32px;
    padding: 32px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
    border-radius: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
    position: relative;
    z-index: 1;
}

.profile-header-info {
    position: relative;
    z-index: 1;
}

.profile-header-info h1 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 4px;
}

.profile-header-info p {
    font-size: 14px;
    color: #94a3b8;
}

.profile-stats {
    display: flex;
    gap: 24px;
    margin-top: 12px;
}

.profile-stat {
    text-align: center;
    padding: 8px 16px;
    background: rgba(255,255,255,0.08);
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.profile-stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
}

.profile-stat-label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-form-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: box-shadow 0.3s;
}

.profile-form-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.profile-form-card h2 {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-form-card h2 i {
    width: 20px;
    height: 20px;
    color: #6366f1;
}

.profile-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.profile-form-grid .form-group.full-width {
    grid-column: 1 / -1;
}

.profile-form-card .form-control {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.2s;
}

.profile-form-card .form-control:focus {
    background: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    outline: none;
}

.profile-form-card .form-control[readonly] {
    background: #f1f5f9;
    color: #64748b;
    cursor: not-allowed;
}

.payment-options-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.payment-option {
    position: relative;
    cursor: pointer;
}

.payment-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-option-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.25s;
    text-align: center;
}

.payment-option input:checked + .payment-option-label {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border-color: #6366f1;
    box-shadow: 0 2px 12px rgba(99, 102, 241, 0.15);
}

.payment-option-label:hover {
    border-color: #a5b4fc;
    background: #f0f0ff;
    transform: translateY(-2px);
}

.payment-option-icon {
    font-size: 28px;
}

.payment-option-text {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    line-height: 1.3;
}

.profile-alert {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    animation: slideDown 0.3s ease;
}

.profile-alert.success {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    color: #166534;
    border: 1px solid #86efac;
}

.profile-alert.error {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.profile-save-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
}

.profile-save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
}

.profile-save-btn:active {
    transform: translateY(0);
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 24px;
    }
    .profile-stats {
        justify-content: center;
    }
    .profile-form-grid {
        grid-template-columns: 1fr;
    }
    .payment-options-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="profile-page">

    <?php if ($successMsg): ?>
        <div class="profile-alert success">
            <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            <?= clean($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="profile-alert error">
            <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            <?= clean($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?= mb_substr($profile['fullname'], 0, 1, 'UTF-8') ?>
        </div>
        <div class="profile-header-info">
            <h1><?= clean($profile['fullname']) ?></h1>
            <p><?= clean($profile['email']) ?></p>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= (int)$orderStats['total_orders'] ?></div>
                    <div class="profile-stat-label">Đơn hàng</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value"><?= formatVND((float)$orderStats['total_spent']) ?></div>
                    <div class="profile-stat-label">Tổng chi tiêu</div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-value">
                        <?= $profile['role'] === 'admin' ? '👑 Admin' : '🛒 Khách hàng' ?>
                    </div>
                    <div class="profile-stat-label">Vai trò</div>
                </div>
            </div>
        </div>
    </div>

    <!-- PROFILE FORM -->
    <form method="POST" action="/bainhom/controllers/update_profile.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Thông tin cá nhân -->
        <div class="profile-form-card">
            <h2><i data-lucide="user"></i> Thông tin cá nhân</h2>
            <div class="profile-form-grid">
                <div class="form-group">
                    <label class="form-label" for="profile-fullname">Họ và tên</label>
                    <input class="form-control" type="text" id="profile-fullname" name="fullname"
                        value="<?= clean($profile['fullname']) ?>" required autocomplete="name" />
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-email">Email</label>
                    <input class="form-control" type="email" id="profile-email" name="email"
                        value="<?= clean($profile['email']) ?>" readonly />
                    <small style="color:#94a3b8;font-size:11px;margin-top:4px;display:block;">Email không thể thay đổi</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-phone">Số điện thoại</label>
                    <input class="form-control" type="tel" id="profile-phone" name="phone"
                        value="<?= clean($profile['phone'] ?? '') ?>" 
                        autocomplete="tel" inputmode="tel" pattern="[0-9]{9,11}"
                        placeholder="Nhập số điện thoại" />
                </div>
                <div class="form-group">
                    <label class="form-label" for="profile-created">Ngày tham gia</label>
                    <input class="form-control" type="text" id="profile-created"
                        value="<?= date('d/m/Y H:i', strtotime($profile['created_at'])) ?>" readonly />
                </div>
                <div class="form-group full-width">
                    <label class="form-label" for="profile-address">Địa chỉ</label>
                    <textarea class="form-control" id="profile-address" name="address" rows="3"
                        autocomplete="street-address"
                        placeholder="Nhập địa chỉ nhận hàng"><?= clean($profile['address'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="profile-form-card">
            <h2><i data-lucide="credit-card"></i> Phương thức thanh toán yêu thích</h2>
            <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Chọn phương thức sẽ được tự động áp dụng khi bạn thanh toán đơn hàng.</p>
            <div class="payment-options-grid">
                <?php foreach ($paymentMethods as $value => $method): ?>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="<?= $value ?>" 
                            <?= $currentPayment === $value ? 'checked' : '' ?>>
                        <div class="payment-option-label">
                            <span class="payment-option-icon"><?= $method['icon'] ?></span>
                            <span class="payment-option-text"><?= $method['label'] ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Save Button -->
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="/bainhom/index.php" class="btn btn-outline" style="padding: 14px 24px; border-radius: 12px; font-weight: 600;">
                ← Quay lại
            </a>
            <button type="submit" class="profile-save-btn">
                <i data-lucide="save" style="width:18px;height:18px;"></i>
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

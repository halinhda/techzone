<?php
// checkout.php
require_once __DIR__ . '/includes/config.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();
if (isset($_SESSION['error'])): ?>
    <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?= $_SESSION['error']; ?>
    </div>
    <?php
    unset($_SESSION['error']); // Xóa lỗi sau khi đã hiển thị
endif;

// 1. TẠO CSRF TOKEN BẢO MẬT
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = currentUser();
$pdo = getDB();
$sid = cartSessionId();
$userId = $user['id'] ?? null;

// Lấy giỏ hàng (cần check trước khi quyết định login/guest)
$stmt = $pdo->prepare("
    SELECT ci.*, p.name, p.price, p.stock, p.image_file, p.brand
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.session_id = ? OR (ci.user_id IS NOT NULL AND ci.user_id = ?)
");
$stmt->execute([$sid, $userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: /bainhom/views/cart.php');
    exit;
}

// ĐẾM SỐ LOẠI SẢN PHẨM (không phải quantity)
$distinctProducts = count($cartItems);

// LOGIC ĐĂNG NHẬP:
// - Nếu có ≥2 loại SP → bắt buộc đăng nhập
// - Nếu có 1 loại SP → cho phép mua mà không đăng nhập (khách vãng lai)
$isGuest = false;
if (!$user || empty($user['id'])) {
    if ($distinctProducts >= 2) {
        // Bắt buộc đăng nhập khi mua từ 2 SP trở lên
        $_SESSION['redirect_after_login'] = '/bainhom/checkout.php';
        $_SESSION['error'] = 'Bạn cần đăng nhập để mua từ 2 sản phẩm trở lên!';
        header('Location: /bainhom/controllers/auth.php?mode=login');
        exit;
    } else {
        // Cho phép mua 1 SP mà không cần đăng nhập
        $isGuest = true;
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Thanh Toán';
require_once __DIR__ . '/includes/header.php';

// 2. KIỂM TRA TỒN KHO
foreach ($cartItems as $item) {
    if ((int) $item['quantity'] > (int) $item['stock']) {
        $safeName = json_encode($item['name'], JSON_UNESCAPED_UNICODE);
        echo "<script>alert('Sản phẩm ' + " . $safeName . " + ' không đủ hàng. Vui lòng cập nhật lại giỏ hàng!'); window.location.href='/bainhom/views/cart.php';</script>";
        exit;
    }
}

// 3. TÍNH TOÁN COMBO (Phải tính trước khi cộng tiền)
$comboIds = [1, 2, 3]; // Thay bằng ID sản phẩm của bạn
$cartIds = array_column($cartItems, 'product_id');
$hasFullCombo = !array_diff($comboIds, $cartIds);

// 4. TÍNH TỔNG TIỀN
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}

// Nếu có combo, áp dụng giảm giá
if ($hasFullCombo) {
    $subtotal = 45000000; // Giá combo của bạn
}

$shipping = $subtotal >= FREE_SHIP_MIN ? 0 : SHIPPING_FEE;
$total = $subtotal + $shipping;

// Using global productImageUrl instead of local checkoutImage
// Lấy thông tin profile đã lưu (nếu đã đăng nhập)
$savedPhone = '';
$savedAddress = '';
$savedPayment = 'cod';
if ($user && !empty($user['id'])) {
    $profileStmt = $pdo->prepare("SELECT phone, address, payment_method FROM users WHERE id = ?");
    $profileStmt->execute([$user['id']]);
    $profile = $profileStmt->fetch();
    if ($profile) {
        $savedPhone = $profile['phone'] ?? '';
        $savedAddress = $profile['address'] ?? '';
        $savedPayment = $profile['payment_method'] ?? 'cod';
    }
}
?>

<section class="container" style="padding: 24px 0 40px;">
    <!-- Checkout Step Indicator -->
    <div class="checkout-steps">
        <div class="checkout-step done">
            <span class="checkout-step-num">✓</span>
            <span>Giỏ hàng</span>
        </div>
        <div class="checkout-step-divider"></div>
        <div class="checkout-step active">
            <span class="checkout-step-num">2</span>
            <span>Thanh toán</span>
        </div>
        <div class="checkout-step-divider"></div>
        <div class="checkout-step">
            <span class="checkout-step-num">3</span>
            <span>Hoàn tất</span>
        </div>
    </div>

    <div class="section-header">
        <div>
            <p class="section-sub">Thanh toán</p>
            <h1 class="section-title">Xác nhận đơn hàng</h1>
        </div>
        <a href="/bainhom/views/cart.php" class="btn btn-outline btn-sm">← Quay lại giỏ hàng</a>
    </div>

    <?php if ($isGuest): ?>
    <div style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #93c5fd; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i data-lucide="info" style="width: 20px; height: 20px; color: #2563eb; flex-shrink: 0;"></i>
        <div>
            <strong style="color: #1e40af; font-size: 14px;">Bạn đang mua hàng với tư cách Khách</strong>
            <p style="color: #3b82f6; font-size: 13px; margin: 4px 0 0;">
                Đăng nhập để theo dõi đơn hàng và tích lũy ưu đãi. 
                <a href="/bainhom/controllers/auth.php?mode=login" style="color: #1e40af; font-weight: 700; text-decoration: underline;">Đăng nhập ngay</a>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <form id="checkout-form" method="POST" action="/bainhom/controllers/process_order.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="is_guest" value="<?= $isGuest ? '1' : '0' ?>">

        <div class="cart-grid">
            <article class="cart-item-card">
                <div class="cart-content">
                    <h2 class="cart-summary-title">Thông tin nhận hàng</h2>
                    <div class="form-group">
                        <label class="form-label" for="checkout-fullname">Họ và tên</label>
                        <input class="form-control" type="text" id="checkout-fullname" name="fullname"
                            autocomplete="name"
                            value="<?= clean($user['fullname'] ?? '') ?>" required />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="checkout-phone">Số điện thoại</label>
                        <input class="form-control" type="tel" id="checkout-phone" name="phone" required
                            autocomplete="tel" inputmode="tel" pattern="[0-9]{9,11}"
                            value="<?= clean($savedPhone) ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="checkout-address">Địa chỉ</label>
                        <textarea class="form-control" id="checkout-address" name="address" rows="3" required
                            autocomplete="street-address"><?= clean($savedAddress) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán</label>
                        <style>
                            .option-label { display: flex; flex-direction: column; align-items: center; gap: 8px; justify-content: center; width: 100%; font-weight: 600; font-size: 14px; text-align: center; } 
                            .option-label svg { width: 40px; height: 40px; flex-shrink: 0; } 
                            .pay-option { justify-content: center; }
                        </style>
                        <div class="pay-options">
                            <label class="pay-option <?= $savedPayment === 'cod' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="cod" <?= $savedPayment === 'cod' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M55 45H90V20H55V45Z" fill="#94A3B8" stroke="#1E293B" stroke-width="4" stroke-linejoin="round"/>
                                      <rect x="65" y="27" width="15" height="10" rx="2" fill="#E2E8F0" stroke="#1E293B" stroke-width="3"/>
                                      <path d="M90 35C70 35 60 50 40 50C30 50 20 40 20 40H10V15H30" fill="#60A5FA" stroke="#1E293B" stroke-width="4" stroke-linejoin="round"/>
                                      <path d="M30 55H65V85H30V55Z" fill="#D4A373" stroke="#1E293B" stroke-width="4" stroke-linejoin="round"/>
                                      <path d="M42 55V65L47.5 60L53 65V55" fill="#EF4444" stroke="#1E293B" stroke-width="3" stroke-linejoin="round"/>
                                      <path d="M10 65C30 65 40 80 60 80C70 80 80 70 80 70H90V95H70" fill="#34D399" stroke="#1E293B" stroke-width="4" stroke-linejoin="round"/>
                                    </svg>
                                    <span>COD</span>
                                </div>
                            </label>

                            <label class="pay-option <?= $savedPayment === 'transfer' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="transfer" <?= $savedPayment === 'transfer' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M50 15L15 40H85L50 15Z" fill="#FBBF24" stroke="#1E293B" stroke-width="5" stroke-linejoin="round"/>
                                      <rect x="15" y="40" width="70" height="8" fill="#F8FAFC" stroke="#1E293B" stroke-width="5"/>
                                      <rect x="25" y="48" width="8" height="28" fill="#FBBF24" stroke="#1E293B" stroke-width="5"/>
                                      <rect x="67" y="48" width="8" height="28" fill="#FBBF24" stroke="#1E293B" stroke-width="5"/>
                                      <circle cx="50" cy="62" r="12" fill="#FDE047" stroke="#1E293B" stroke-width="4"/>
                                      <path d="M50 56V68M47 59H50C52 59 52 62 50 62C48 62 48 65 50 65H53" stroke="#1E293B" stroke-width="3" stroke-linecap="round"/>
                                      <rect x="10" y="76" width="80" height="8" fill="#F8FAFC" stroke="#1E293B" stroke-width="5"/>
                                    </svg>
                                    <span>Chuyển khoản</span>
                                </div>
                            </label>

                            <label class="pay-option <?= $savedPayment === 'qr' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="qr" <?= $savedPayment === 'qr' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <rect x="30" y="15" width="40" height="65" rx="5" fill="none" stroke="#1E293B" stroke-width="5"/>
                                      <path d="M20 30C30 30 35 40 35 45M20 45C30 45 35 55 35 60M20 60C30 60 35 70 35 75" stroke="#1E293B" stroke-width="5" stroke-linecap="round"/>
                                      <path d="M70 30C75 30 80 40 85 50C90 60 90 70 80 80L60 95H40" fill="none" stroke="#1E293B" stroke-width="5" stroke-linecap="round"/>
                                      <rect x="40" y="32" width="6" height="6" fill="#1E293B"/><rect x="54" y="32" width="6" height="6" fill="#1E293B"/><rect x="40" y="46" width="6" height="6" fill="#1E293B"/><rect x="54" y="46" width="6" height="6" fill="#1E293B"/>
                                    </svg>
                                    <span>Quét mã QR</span>
                                </div>
                            </label>

                            <label class="pay-option <?= $savedPayment === 'momo' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="momo" <?= $savedPayment === 'momo' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <rect width="100" height="100" rx="20" fill="#ae2070"/>
                                      <path d="M22 50V35C22 23 32 23 32 35V50M32 35C32 23 42 23 42 35V50" stroke="white" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                                      <circle cx="70" cy="38" r="10" stroke="white" stroke-width="9" fill="none"/>
                                      <path d="M22 84V69C22 57 32 57 32 69V84M32 69C32 57 42 57 42 69V84" stroke="white" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                                      <circle cx="70" cy="72" r="10" stroke="white" stroke-width="9" fill="none"/>
                                    </svg>
                                    <span>Ví Momo</span>
                                </div>
                            </label>

                            <label class="pay-option <?= $savedPayment === 'credit_card' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="credit_card" <?= $savedPayment === 'credit_card' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M20 35 L90 20 L100 65 L30 80 Z" fill="#EF4444"/>
                                      <path d="M10 40 L85 25 L90 40 L15 55 Z" fill="#374151"/>
                                      <rect x="5" y="45" width="85" height="50" rx="8" fill="#3B82F6"/>
                                      <rect x="15" y="55" width="16" height="12" rx="3" fill="#FBBF24"/>
                                      <rect x="15" y="75" width="40" height="6" rx="2" fill="white"/>
                                      <rect x="15" y="85" width="60" height="6" rx="2" fill="white"/>
                                    </svg>
                                    <span>Thẻ tín dụng</span>
                                </div>
                            </label>

                            <label class="pay-option <?= $savedPayment === 'bank_card' ? 'selected' : '' ?>">
                                <input type="radio" name="pay" value="bank_card" <?= $savedPayment === 'bank_card' ? 'checked' : '' ?>>
                                <div class="option-label">
                                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                      <rect x="10" y="25" width="80" height="50" rx="8" fill="#10B981" stroke="#1E293B" stroke-width="4"/>
                                      <rect x="10" y="35" width="80" height="10" fill="#1E293B"/>
                                      <rect x="20" y="55" width="16" height="10" rx="2" fill="#FBBF24"/>
                                      <rect x="45" y="55" width="35" height="4" rx="2" fill="white"/>
                                      <rect x="45" y="65" width="20" height="4" rx="2" fill="white"/>
                                    </svg>
                                    <span>Thẻ ATM nội địa</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </article>

            <article class="cart-item-card">
                <h2 class="cart-summary-title">Sản phẩm trong đơn</h2>

                <?php if ($hasFullCombo): ?>
                    <div class="alert alert-info" style="margin-bottom: 16px;">
                        <strong>🎁 Bạn đang sở hữu trọn bộ Combo</strong><br>
                        Bao gồm: Laptop Dell, Chuột không dây, Túi chống sốc.<br>
                        Giá lẻ: <del>50.000.000 đ</del> → <strong style="color: #dc2626;">Giá Combo: 45.000.000 đ</strong>
                    </div>
                <?php endif; ?>

                <div class="cart-products">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="product-item">
                            <?php if ($item['image_file']): ?>
                                <img src="<?= htmlspecialchars(productImageUrl($item['image_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= clean($item['name']) ?>"
                                    style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 10px;">
                            <?php endif; ?>
                            <div class="product-info" style="flex: 1;">
                                <strong><?= clean($item['name']) ?></strong>
                                <p style="font-size:12px;color:#64748b;margin-top:4px;">SL: <?= (int) $item['quantity'] ?> × <?= formatVND($item['price']) ?></p>
                            </div>
                            <strong style="white-space:nowrap;"><?= formatVND((float) $item['price'] * (int) $item['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Voucher Input Block -->
                <div class="voucher-box" style="margin-top: 16px; margin-bottom: 20px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                    <label class="form-label" style="font-weight: 700; font-size: 13px; color: #475569; display: block; margin-bottom: 8px;">Mã giảm giá (Voucher)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="voucher-code" class="form-control" placeholder="TECHZONE50, SALE10..." style="text-transform: uppercase; padding: 10px 12px; font-size: 14px; flex: 1;" autocomplete="off">
                        <button type="button" id="btn-apply-voucher" class="btn btn-outline btn-sm" style="padding: 10px 16px; border: 1.5px solid #cbd5e1; font-weight: 700; white-space: nowrap; height: auto;">Áp dụng</button>
                    </div>
                    <div id="voucher-message" style="font-size: 12px; margin-top: 8px; font-weight: 600; display: none;"></div>
                </div>

                <input type="hidden" name="voucher_code" id="hidden-voucher-code" value="">

                <div class="cart-summary-row"><span>Tạm tính</span><strong><?= formatVND($subtotal) ?></strong></div>
                <!-- Discount Row (Hidden by default) -->
                <div class="cart-summary-row" id="discount-row" style="display: none; color: #16a34a;">
                    <span>Giảm giá</span>
                    <strong id="discount-val">-0 đ</strong>
                </div>
                <div class="cart-summary-row"><span>Phí vận chuyển</span><strong><?= $shipping === 0 ? 'Miễn phí' : formatVND($shipping) ?></strong></div>
                <div class="cart-summary-row total"><span>Tổng cộng</span><strong id="total-val" data-orig="<?= $total ?>"><?= formatVND($total) ?></strong></div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">Đặt hàng ngay</button>
            </article>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnApply = document.getElementById('btn-apply-voucher');
    const voucherInput = document.getElementById('voucher-code');
    const hiddenVoucher = document.getElementById('hidden-voucher-code');
    const voucherMsg = document.getElementById('voucher-message');
    const discountRow = document.getElementById('discount-row');
    const discountVal = document.getElementById('discount-val');
    const totalVal = document.getElementById('total-val');
    
    const subtotal = <?= (float)$subtotal ?>;
    const shipping = <?= (float)$shipping ?>;
    const originalTotal = <?= (float)$total ?>;

    btnApply.addEventListener('click', async function () {
        const code = voucherInput.value.trim().toUpperCase();
        voucherMsg.style.display = 'none';
        voucherMsg.textContent = '';
        
        if (!code) {
            voucherMsg.style.color = '#dc2626';
            voucherMsg.textContent = 'Vui lòng nhập mã giảm giá!';
            voucherMsg.style.display = 'block';
            resetVoucher();
            return;
        }

        btnApply.disabled = true;
        btnApply.textContent = 'Đang kiểm...';

        try {
            const response = await fetch('/bainhom/api/check_voucher.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: code, subtotal: subtotal })
            });

            const data = await response.json();
            
            if (data.success) {
                voucherMsg.style.color = '#16a34a';
                voucherMsg.textContent = data.message;
                voucherMsg.style.display = 'block';
                
                const discountAmt = parseFloat(data.discount_amount);
                hiddenVoucher.value = data.code;
                
                // Hiển thị dòng discount
                discountVal.textContent = '-' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(discountAmt);
                discountRow.style.display = 'flex';
                
                // Tính lại tổng tiền
                const newTotal = Math.max(0, subtotal + shipping - discountAmt);
                totalVal.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(newTotal);
            } else {
                voucherMsg.style.color = '#dc2626';
                voucherMsg.textContent = data.message;
                voucherMsg.style.display = 'block';
                resetVoucher();
            }
        } catch (error) {
            voucherMsg.style.color = '#dc2626';
            voucherMsg.textContent = 'Có lỗi kết nối hệ thống!';
            voucherMsg.style.display = 'block';
            resetVoucher();
        } finally {
            btnApply.disabled = false;
            btnApply.textContent = 'Áp dụng';
        }
    });

    function resetVoucher() {
        hiddenVoucher.value = '';
        discountRow.style.display = 'none';
        totalVal.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(originalTotal);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
/**
 * views/order_lookup.php – Tra cứu trạng thái đơn hàng (đặc biệt cho khách vãng lai)
 */

require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Tra Cứu Đơn Hàng – ' . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';

$pdo = getDB();
$orderCode = clean($_POST['order_code'] ?? $_GET['code'] ?? '');
$phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');

$order = null;
$items = [];
$errorMsg = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['code']) && !empty($_GET['code']))) {
    $searched = true;
    
    if (empty($orderCode)) {
        $errorMsg = 'Vui lòng nhập Mã đơn hàng.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($phone)) {
        $errorMsg = 'Vui lòng nhập Số điện thoại nhận hàng.';
    } else {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? AND customer_phone = ? LIMIT 1");
                $stmt->execute([$orderCode, $phone]);
            } else {
                // Hỗ trợ click từ link hoàn tất đơn hàng
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
                $stmt->execute([$orderCode]);
            }
            $order = $stmt->fetch();

            if ($order) {
                // Lấy sản phẩm của đơn hàng
                $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $itemStmt->execute([$order['id']]);
                $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $errorMsg = 'Không tìm thấy đơn hàng phù hợp với thông tin đã nhập.';
            }
        } catch (PDOException $e) {
            $errorMsg = 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}

// Timeline status mapping
$steps = [
    'Chờ xử lý' => ['label' => 'Chờ xử lý', 'icon' => '📋'],
    'Đang giao' => ['label' => 'Đang giao hàng', 'icon' => '🚚'],
    'Đã hoàn thành' => ['label' => 'Đã hoàn thành', 'icon' => '✅'],
];
$currentStatus = $order['status'] ?? '';
$stepKeys = array_keys($steps);
$currentIndex = array_search($currentStatus, $stepKeys);
?>

<style>
.lookup-page {
    max-width: 800px;
    margin: 40px auto 60px;
    padding: 0 20px;
}

.lookup-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}

.lookup-card-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lookup-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.lookup-alert {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lookup-alert.error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 640px) {
    .lookup-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="lookup-page">
    <div class="section-header" style="margin-bottom: 30px;">
        <div>
            <p class="section-sub">Dịch vụ khách hàng</p>
            <h1 class="section-title">Tra cứu đơn hàng</h1>
        </div>
        <a href="/bainhom/index.php" class="btn btn-outline btn-sm">← Quay lại cửa hàng</a>
    </div>

    <!-- FORM TRA CỨU -->
    <div class="lookup-card" style="margin-bottom: 32px;">
        <h2 class="lookup-card-title"><i data-lucide="search" style="width:20px;height:20px;color:#4f46e5;"></i> Nhập thông tin tra cứu</h2>
        <form method="POST" action="order_lookup.php">
            <div class="lookup-grid">
                <div class="form-group">
                    <label class="form-label" for="lookup-code">Mã đơn hàng</label>
                    <input class="form-control" type="text" id="lookup-code" name="order_code" 
                        value="<?= htmlspecialchars($orderCode) ?>" placeholder="Ví dụ: DH2026..." required />
                </div>
                <div class="form-group">
                    <label class="form-label" for="lookup-phone">Số điện thoại nhận hàng</label>
                    <input class="form-control" type="tel" id="lookup-phone" name="phone" 
                        value="<?= htmlspecialchars($phone) ?>" placeholder="Nhập số điện thoại đặt hàng" required />
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 28px; border-radius: 10px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="search" style="width:16px;height:16px;"></i> Tra cứu đơn hàng
                </button>
            </div>
        </form>
    </div>

    <!-- THÔNG BÁO LỖI -->
    <?php if ($errorMsg): ?>
        <div class="lookup-alert error">
            <i data-lucide="alert-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- KẾT QUẢ TRA CỨU -->
    <?php if ($searched && $order): ?>
        <div class="lookup-card" style="border-top: 4px solid #4f46e5;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Chi tiết đơn hàng #<?= htmlspecialchars($order['order_code']) ?></h3>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Thời gian đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <span class="badge" style="font-size: 13px; font-weight: 700; padding: 6px 12px; border-radius: 8px; 
                    <?= $order['status'] === 'Đã hủy' ? 'background:#fee2e2;color:#991b1b;' : 'background:#f0fdf4;color:#166534;' ?>">
                    Trạng thái: <?= htmlspecialchars($order['status']) ?>
                </span>
            </div>

            <!-- Timeline trạng thái -->
            <?php if ($order['status'] !== 'Đã hủy'): ?>
                <div class="order-timeline-modern" style="margin-bottom: 32px;">
                    <?php foreach ($steps as $key => $step):
                        $idx = array_search($key, $stepKeys);
                        $stepClass = '';
                        if ($currentIndex !== false && $idx < $currentIndex) $stepClass = 'done';
                        elseif ($currentIndex !== false && $idx === $currentIndex) $stepClass = 'active';
                    ?>
                        <div class="timeline-step <?= $stepClass ?>">
                            <div class="timeline-step-icon"><?= $stepClass === 'done' ? '✓' : $step['icon'] ?></div>
                            <span class="timeline-step-label" style="font-size:12px;font-weight:700;"><?= $step['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="lookup-alert error" style="margin-bottom: 32px;">
                    <i data-lucide="x-circle" style="width:18px;height:18px;flex-shrink:0;"></i>
                    Đơn hàng này đã bị hủy bỏ bởi khách hàng hoặc người quản trị.
                </div>
            <?php endif; ?>

            <!-- Thông tin nhận hàng -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
                <div style="background:#f8fafc; padding: 16px; border-radius: 12px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">Thông tin khách hàng</h4>
                    <p style="margin: 6px 0; font-size: 13.5px;"><strong>Người nhận:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p style="margin: 6px 0; font-size: 13.5px;"><strong>Điện thoại:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                    <p style="margin: 6px 0; font-size: 13.5px;"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                </div>
                <div style="background:#f8fafc; padding: 16px; border-radius: 12px;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">Thanh toán & Giao nhận</h4>
                    <p style="margin: 6px 0; font-size: 13.5px;"><strong>Thanh toán:</strong> 
                        <?php 
                        $payments = ['cod'=>'💵 Tiền mặt (COD)', 'transfer'=>'🏦 Chuyển khoản', 'qr'=>'📱 Quét mã QR', 'momo'=>'💜 Ví Momo', 'credit_card'=>'💳 Thẻ tín dụng', 'bank_card'=>'🏧 Thẻ ATM'];
                        echo htmlspecialchars($payments[$order['payment_method']] ?? $order['payment_method']);
                        ?>
                    </p>
                    <p style="margin: 6px 0; font-size: 13.5px;"><strong>Phí ship:</strong> <?= $order['shipping_fee'] > 0 ? formatVND($order['shipping_fee']) : 'Miễn phí vận chuyển' ?></p>
                    <?php if (!empty($order['voucher_code'])): ?>
                        <p style="margin: 6px 0; font-size: 13.5px; color: #16a34a;"><strong>Mã giảm giá:</strong> <?= htmlspecialchars($order['voucher_code']) ?> (-<?= formatVND($order['discount_amount']) ?>)</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sản phẩm -->
            <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">Sản phẩm đã mua</h4>
            <div style="border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                <?php foreach ($items as $item): 
                    $itemTotal = (float)$item['price'] * (int)$item['quantity'];
                ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border-bottom: 1px solid #f1f5f9; background: #fff;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="font-size: 32px; background: #f8fafc; border-radius: 8px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"><?= htmlspecialchars($item['image_emoji'] ?? '📦') ?></span>
                            <div>
                                <strong style="font-size: 14px; color: #0f172a; display: block;"><?= htmlspecialchars($item['name']) ?></strong>
                                <span style="font-size: 12px; color: #64748b;">SL: <?= (int)$item['quantity'] ?> × <?= formatVND($item['price']) ?></span>
                            </div>
                        </div>
                        <strong style="font-size: 14px; color: #0f172a;"><?= formatVND($itemTotal) ?></strong>
                    </div>
                <?php endforeach; ?>
                
                <div style="background: #f8fafc; padding: 16px; display: flex; flex-direction: column; gap: 6px; font-size: 13.5px; border-top: 1px solid #f1f5f9;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#64748b;">Tạm tính:</span>
                        <strong><?= formatVND($order['subtotal']) ?></strong>
                    </div>
                    <?php if ((float)$order['discount_amount'] > 0): ?>
                        <div style="display:flex; justify-content:space-between; color:#16a34a;">
                            <span>Giảm giá:</span>
                            <strong>-<?= formatVND($order['discount_amount']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:#64748b;">Phí ship:</span>
                        <strong><?= $order['shipping_fee'] > 0 ? formatVND($order['shipping_fee']) : 'Miễn phí' ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:16px; font-weight:800; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 4px; color:#0f172a;">
                        <span>Tổng tiền thanh toán:</span>
                        <strong style="color: #dc2626;"><?= formatVND($order['total_price']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($searched && !$order): ?>
        <!-- Không hiển thị gì thêm vì đã có alert -->
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

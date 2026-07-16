<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Chi tiết đơn hàng';
$currentPage = 'orders';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

// Get order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Đơn hàng không tồn tại!");
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.image_file, p.brand
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>📋 Chi tiết đơn hàng: <?= htmlspecialchars($order['order_code']) ?></h1>
    <a href="orders.php" class="btn btn-outline">← Quay lại danh sách</a>
</div>

<div class="content-grid">
    <!-- Left Column: Order Items -->
    <div style="display:flex;flex-direction:column;gap:24px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sản phẩm đặt mua</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-container orders-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>SL</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td data-label="Sản phẩm">
                                        <div class="product-cell">
                                            <?php if (!empty($item['image_file'])): ?>
                                                <img src="/bainhom/assets/images/<?= htmlspecialchars($item['image_file']) ?>" class="product-thumb">
                                            <?php else: ?>
                                                <div class="product-thumb" style="display:flex;align-items:center;justify-content:center;font-size:20px;"><?= $item['image_emoji'] ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="product-cell-name"><?= htmlspecialchars($item['name']) ?></div>
                                                <div class="product-cell-brand"><?= htmlspecialchars($item['brand'] ?? '—') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Đơn giá"><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                                    <td data-label="SL"><?= $item['quantity'] ?></td>
                                    <td data-label="Thành tiền" style="font-weight:700;"><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="padding:20px 24px;background:#f8fafc;border-top:1px solid var(--border);text-align:right;">
                    <div style="margin-bottom:8px;color:var(--text-sub);">Tạm tính: <?= number_format($order['subtotal'], 0, ',', '.') ?>đ</div>
                    <div style="margin-bottom:8px;color:var(--text-sub);">Phí vận chuyển: <?= number_format($order['shipping_fee'], 0, ',', '.') ?>đ</div>
                    <div style="font-size:18px;font-weight:800;color:#dc2626;margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;">
                        Tổng cộng: <?= number_format($order['total_price'], 0, ',', '.') ?>đ
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Info & Actions -->
    <div style="display:flex;flex-direction:column;gap:24px;">
        
        <!-- Status Update Box -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cập nhật trạng thái</h3>
            </div>
            <div class="card-body">
                <form action="update_order_status.php" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="Chờ xử lý" <?= $order['status'] === 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử lý</option>
                            <option value="Đang giao" <?= $order['status'] === 'Đang giao' ? 'selected' : '' ?>>Đang giao</option>
                            <option value="Đã hoàn thành" <?= $order['status'] === 'Đã hoàn thành' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            <option value="Đã hủy" <?= $order['status'] === 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Cập nhật</button>
                </form>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Thông tin giao hàng</h3>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:16px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Khách hàng</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Số điện thoại</div>
                        <div><?= htmlspecialchars($order['customer_phone']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Địa chỉ</div>
                        <div><?= nl2br(htmlspecialchars($order['customer_address'])) ?></div>
                    </div>
                    <?php if (!empty($order['note'])): ?>
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Ghi chú</div>
                        <div style="background:#fffbeb;padding:12px;border-radius:8px;border:1px solid #fde68a;color:#92400e;font-size:13px;">
                            <?= nl2br(htmlspecialchars($order['note'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-size:12px;color:var(--text-sub);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Thanh toán</div>
                        <div>
                            <?php 
                            echo match($order['payment_method']) {
                                'cod' => 'Thanh toán khi nhận hàng (COD)',
                                'momo' => 'Ví MoMo',
                                'qr' => 'Chuyển khoản QR',
                                default => strtoupper($order['payment_method'])
                            };
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

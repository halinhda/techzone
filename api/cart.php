<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$pdo = getDB();
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';
$productId = (int) ($input['product_id'] ?? 0);
$delta = (int) ($input['delta'] ?? 0);

$sid = cartSessionId();
$user = currentUser();
$userId = $user['id'] ?? null;

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare('SELECT id, stock FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    exit;
}

// Hàm cập nhật tồn kho tạm thời
function updateStock($pdo, $pid, $change)
{
    $pdo->prepare('UPDATE products SET stock = stock + (?) WHERE id = ?')->execute([$change, $pid]);
}

if ($action === 'add') {
    if ((int) $product['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE session_id = ? AND product_id = ?');
    $stmt->execute([$sid, $productId]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        if (($existing + 1) > (int) $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Hết hàng']);
            exit;
        }
        $pdo->prepare('UPDATE cart_items SET quantity = quantity + 1 WHERE session_id = ? AND product_id = ?')->execute([$sid, $productId]);
    } else {
        $pdo->prepare('INSERT INTO cart_items (session_id, user_id, product_id, quantity) VALUES (?, ?, ?, 1)')->execute([$sid, $userId, $productId]);
    }
    updateStock($pdo, $productId, -1); // Trừ 1 khi thêm vào giỏ
}

if ($action === 'update') {
    $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE session_id = ? AND product_id = ?');
    $stmt->execute([$sid, $productId]);
    $currentQty = (int) $stmt->fetchColumn();

    $newQty = $currentQty + $delta;
    if ($newQty > (int) $product['stock']) {
        echo json_encode(['success' => false, 'message' => 'Vượt quá tồn kho']);
        exit;
    }

    if ($newQty <= 0) {
        $pdo->prepare('DELETE FROM cart_items WHERE session_id = ? AND product_id = ?')->execute([$sid, $productId]);
        updateStock($pdo, $productId, $currentQty); // Cộng lại toàn bộ số lượng đã bỏ giỏ
    } else {
        $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE session_id = ? AND product_id = ?')->execute([$newQty, $sid, $productId]);
        updateStock($pdo, $productId, -$delta); // Trừ dựa trên số lượng thay đổi (delta)
    }
}

if ($action === 'remove') {
    $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE session_id = ? AND product_id = ?');
    $stmt->execute([$sid, $productId]);
    $qty = (int) $stmt->fetchColumn();

    $pdo->prepare('DELETE FROM cart_items WHERE session_id = ? AND product_id = ?')->execute([$sid, $productId]);
    updateStock($pdo, $productId, $qty); // Cộng lại số lượng vào tồn kho
}

$countStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE session_id = ?');
$countStmt->execute([$sid]);
echo json_encode(['success' => true, 'cart_count' => (int) $countStmt->fetchColumn()]);
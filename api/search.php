<?php
/**
 * api/search.php – Tìm kiếm gợi ý trực tiếp sản phẩm bằng AJAX
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$q = clean($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = getDB();

try {
    $stmt = $pdo->prepare("
        SELECT id, name, price, brand, image_emoji, image_file 
        FROM products 
        WHERE name LIKE ? OR brand LIKE ? 
        LIMIT 5
    ");
    $stmt->execute(["%$q%", "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($results as $item) {
        $imgSrc = productImageUrl($item['image_file'] ?? '');

        $formatted[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => (float)$item['price'],
            'price_formatted' => formatVND($item['price']),
            'brand' => $item['brand'],
            'image_emoji' => $item['image_emoji'],
            'image_url' => $imgSrc
        ];
    }

    echo json_encode($formatted);
    exit;

} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit;
}

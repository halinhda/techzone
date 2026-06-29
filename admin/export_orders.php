<?php
require_once __DIR__ . '/../includes/config.php';
// Kiểm tra quyền admin...

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=don_hang_'.date('Y-m-d').'.csv');

$output = fopen('php://output', 'w');
// Thêm tiêu đề cột (BOM cho Excel đọc tiếng Việt)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['Mã ĐH', 'Khách hàng', 'SĐT', 'Ngày đặt', 'Tổng tiền', 'Trạng thái']);

$rows = getDB()->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
foreach ($rows as $row) {
    fputcsv($output, [$row['id'], $row['customer_name'], $row['customer_phone'], $row['created_at'], $row['total_amount'], $row['status']]);
}
fclose($output);
exit;
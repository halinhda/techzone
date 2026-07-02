<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=don_hang_'.date('Y-m-d').'.csv');

$output = fopen('php://output', 'w');
// Thêm tiêu đề cột (BOM cho Excel đọc tiếng Việt)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['Mã ĐH', 'Khách hàng', 'SĐT', 'Ngày đặt', 'Tổng tiền', 'Phương thức thanh toán', 'Trạng thái']);

$rows = getDB()->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
foreach ($rows as $row) {
    fputcsv($output, [
        $row['order_code'], 
        $row['customer_name'], 
        $row['customer_phone'], 
        $row['created_at'], 
        $row['total_price'], // Fixed bug: was total_amount
        $row['payment_method'],
        $row['status']
    ]);
}
fclose($output);
exit;
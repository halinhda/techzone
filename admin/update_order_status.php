<?php
// admin/update_order_status.php
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    // Danh sách trạng thái hợp lệ để tránh lỗi dữ liệu rác
    $valid_statuses = ['Chờ xử lý', 'Đang giao', 'Đã hoàn thành', 'Đã hủy'];

    if ($order_id > 0 && in_array($status, $valid_statuses)) {
        try {
            $stmt = getDB()->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            
            // Chuyển hướng về lại trang danh sách sau khi cập nhật thành công
            header("Location: orders.php?msg=success");
            exit();
        } catch (Exception $e) {
            die("Lỗi cập nhật: " . $e->getMessage());
        }
    }
}

// Nếu không phải POST hoặc dữ liệu sai, quay về trang danh sách
header("Location: orders.php");
exit();
?>
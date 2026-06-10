<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /bainhom/index.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: products.php');
exit;
?>
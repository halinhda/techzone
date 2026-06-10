<?php
// admin/product_add.php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('/bainhom/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $brand = trim($_POST['brand'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $category_id = 1; // Mặc định category là 1 (theo ảnh của bạn)
    
    // Xử lý ảnh
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imagePath = 'prod_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/images/' . $imagePath);
    }

    try {
        $pdo = getDB();
        // Insert đầy đủ các cột quan trọng theo DB của bạn
        $sql = "INSERT INTO products (name, price, brand, stock, description, image_file, category_id, stock_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $brand, $stock, $desc, $imagePath, $category_id, $stock]);
        
        redirect('products.php');
    } catch (PDOException $e) {
        die("Lỗi Database: " . $e->getMessage());
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 0; max-width: 600px; margin: auto;">
    <h1>Thêm sản phẩm mới</h1>
    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label>Tên sản phẩm</label>
            <input type="text" name="name" class="form-control" required style="width:100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Thương hiệu (Brand)</label>
            <input type="text" name="brand" class="form-control" style="width:100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Giá (VNĐ)</label>
            <input type="number" name="price" class="form-control" required style="width:100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Số lượng kho (Stock)</label>
            <input type="number" name="stock" class="form-control" required style="width:100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Ảnh sản phẩm</label>
            <input type="file" name="image" class="form-control" accept="image/*" style="width:100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Mô tả</label>
            <textarea name="description" class="form-control" style="width:100%; padding: 8px;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
    </form>
</div>
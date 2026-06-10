<?php
// support.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = "Hỗ Trợ Khách Hàng - " . SITE_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 40px auto; padding: 20px;">
    <h1 style="text-align: center; margin-bottom: 30px;">Trung tâm hỗ trợ</h1>

    <div class="faq-section">
        <h3>Câu hỏi thường gặp (FAQ)</h3>
        
        <div class="faq-item">
            <button class="faq-toggle">Làm thế nào để theo dõi đơn hàng? <i data-lucide="chevron-down"></i></button>
            <div class="faq-answer">Bạn có thể vào mục "Tài khoản của tôi" -> "Đơn hàng" để kiểm tra trạng thái vận chuyển.</div>
        </div>

        <div class="faq-item">
            <button class="faq-toggle">Chính sách bảo hành như thế nào? <i data-lucide="chevron-down"></i></button>
            <div class="faq-answer">Tất cả sản phẩm tại TechZone được bảo hành chính hãng từ 12-24 tháng tùy loại.</div>
        </div>
    </div>

    <hr style="margin: 40px 0;">

    <div class="contact-section">
        <h3>Gửi yêu cầu hỗ trợ</h3>
        <form method="POST" action="support.php" style="margin-top: 20px;">
            <div class="form-group">
                <label>Họ tên:</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nội dung cần hỗ trợ:</label>
                <textarea name="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Gửi yêu cầu</button>
        </form>
    </div>
</div>

<?php
// Xử lý logic gửi form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>
            alert('Cảm ơn bạn, chúng tôi đã nhận được yêu cầu hỗ trợ!');
            window.location.href = '/bainhom/index.php';
          </script>";
    exit; // Dừng việc load phần còn lại của trang sau khi đã thông báo
}

require_once __DIR__ . '/../includes/footer.php'; 
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB(); 
$isAdmin = isAdmin(); 

/* =========================
   USER GỬI SUPPORT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {

    $user_id = $_SESSION['user']['id'] ?? null; 
    $user_name = clean($_POST['name'] ?? '');       
    $subject = clean($_POST['subject'] ?? '');     
    $message = clean($_POST['message'] ?? '');     

    if ($user_name === '' || $subject === '' || $message === '') {
        die("Vui lòng điền đầy đủ thông tin.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (user_id, user_name, subject, message, status)
            VALUES (:user_id, :user_name, :subject, :message, 'open')
        ");

        $stmt->execute([
            ':user_id' => $user_id,
            ':user_name' => $user_name,
            ':subject' => $subject,
            ':message' => $message
        ]);

        echo "<script>
            alert('Gửi yêu cầu thành công!');
            window.location.href = 'support.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        die("Lỗi Database khi chèn dữ liệu: " . $e->getMessage());
    }
}

/* =========================
   ADMIN PHẢN HỒI (REPLY)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin && isset($_POST['reply_id'])) {
    
    $reply = clean($_POST['reply'] ?? ''); 
    $reply_id = $_POST['reply_id'];

    if ($reply !== '') {
        try {
            $stmt = $pdo->prepare("
                UPDATE support_tickets
                SET reply = :reply, status = 'resolved'
                WHERE id = :id
            ");

            $stmt->execute([
                ':reply' => $reply,
                ':id' => $reply_id
            ]);

            echo "<script>alert('Phản hồi thành công!'); window.location.href='support.php';</script>";
            exit;
        } catch (PDOException $e) {
            die("Lỗi Admin DB: " . $e->getMessage());
        }
    }
}

/* =========================
   LẤY DỮ LIỆU ĐỂ HIỂN THỊ
========================= */
$tickets = [];
try {
    if ($isAdmin) {
        $tickets = $pdo->query("SELECT * FROM support_tickets ORDER BY id DESC")->fetchAll();
    } else {
        $user_id = $_SESSION['user']['id'] ?? null;
        $user_name = $_SESSION['user']['fullname'] ?? '';

        if ($user_id !== null) {
            $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = :user_id ORDER BY id DESC");
            $stmt->execute([':user_id' => $user_id]);
            $tickets = $stmt->fetchAll();
        } elseif ($user_name !== '') {
            $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_name = :user_name ORDER BY id DESC");
            $stmt->execute([':user_name' => $user_name]);
            $tickets = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    die("Lỗi lấy dữ liệu ticket: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #4f46e5;
    --primary-hover: #4338ca;
    --primary-glow: rgba(79, 70, 229, 0.08);
    --bg-main: #fafafa;
    --text-dark: #090d16;
    --text-body: #334155;
    --text-muted: #64748b;
    --border: #e2e8f0;
}

/* Đồng bộ font chữ mượt mà */
.container-support { 
    max-width: 950px; 
    margin: 60px auto; 
    padding: 0 20px; 
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--text-body);
    -webkit-font-smoothing: antialiased;
}

/* --- TIÊU ĐỀ ĐỒNG BỘ CĂN GIỮA CHO CẢ USER & ADMIN --- */
.support-header {
    text-align: center;
    margin-bottom: 45px;
}
.support-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 10px 0;
    letter-spacing: -0.8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.support-header p {
    font-size: 15px;
    color: var(--text-muted);
    margin: 0;
    max-width: 550px;
    margin: 0 auto;
    line-height: 1.6;
}

/* Form thiết kế tinh tế dạng Card Premium */
.form-card {
    background: #ffffff;
    padding: 40px;
    border-radius: 24px;
    border: 1px solid var(--border);
    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.04), 0 1px 3px rgba(0,0,0,0.02);
    position: relative;
    overflow: hidden;
}
.form-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4f46e5, #818cf8);
}

/* Bố cục ô nhập liệu song song */
.form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
@media (min-width: 640px) {
    .form-row { grid-template-columns: 1fr 1fr; }
}

.form-group {
    display: flex;
    flex-direction: column;
}
.form-group.full-width {
    margin-bottom: 28px;
}
.form-group label {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Ô nhập liệu */
.form-group input[type="text"], 
.form-group textarea {
    width: 100%;
    padding: 13px 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    box-sizing: border-box;
    font-size: 14.5px;
    font-family: inherit;
    color: var(--text-dark);
    background-color: #f8fafc;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.form-group input[type="text"]:focus, 
.form-group textarea:focus {
    background-color: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px var(--primary-glow);
    outline: none;
}

/* Nút bấm ở trung tâm */
.form-actions {
    display: flex;
    justify-content: center;
}
button.btn-submit {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 14px 40px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    font-family: inherit;
    transition: all 0.15s ease;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    display: flex;
    align-items: center;
    gap: 8px;
}
button.btn-submit:hover {
    background: var(--primary-hover);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.25);
    transform: translateY(-1px);
}
button.btn-submit:active {
    transform: scale(0.97);
}

/* Lịch sử dạng thẻ (Cards) cho Người dùng */
.section-title {
    font-size: 18px;
    font-weight: 700;
    margin: 50px 0 20px 0;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.ticket-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.ticket-item {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.01);
    transition: transform 0.2s, box-shadow 0.2s;
}
.ticket-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 20px -10px rgba(0,0,0,0.05);
}
.ticket-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
    gap: 15px;
}
.ticket-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.ticket-msg {
    color: var(--text-body);
    font-size: 14.5px;
    line-height: 1.6;
    margin: 0 0 16px 0;
    white-space: pre-line;
}

/* Trạng thái Badges tiếng Việt kèm Icon phù hợp */
.badge { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.badge-open { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
.badge-resolved { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }

/* Khung phản hồi Admin */
.admin-reply-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    border-left: 3px solid var(--primary);
}
.admin-reply-card p {
    margin: 0;
    font-size: 14px;
    color: #1e293b;
}

/* Bảng Admin tinh gọn */
.table-responsive { width: 100%; overflow-x: auto; background: #fff; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
th { background: #f8fafc; color: var(--text-muted); padding: 16px 18px; font-weight: 600; border-bottom: 1px solid var(--border); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
td { padding: 18px 18px; border-bottom: 1px solid var(--border); vertical-align: top; }
tr:hover td { background-color: #fafafa; }
.admin-form textarea { padding: 10px; margin-bottom: 8px; background: #fff; border-radius: 8px; width: 100%; border: 1px solid var(--border); font-family: inherit; font-size: 13.5px; }
.admin-form button { background: #10b981; padding: 8px 16px; font-size: 12px; border-radius: 8px; width: auto; box-shadow: none; color:#fff; font-weight:600; cursor:pointer; display: inline-flex; align-items: center; gap: 4px; border: none; }
.admin-form button:hover { background: #059669; }
</style>

<div class="container-support">

<?php if (!$isAdmin): ?>
    <div class="support-header">
        <h1>Trung tâm Hỗ trợ</h1>
        <p>Gửi câu hỏi hoặc báo cáo lỗi, chúng tôi sẽ phản hồi bạn trong thời gian sớm nhất.</p>
    </div>
        
    <div class="form-card">
        <form method="POST" action="support.php">
            <input type="hidden" name="action" value="create_ticket">

            <div class="form-row">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($_SESSION['user']['fullname'] ?? '') ?>" placeholder="Nhập tên của bạn">
                </div>

                <div class="form-group">
                    <label>Tiêu đề yêu cầu</label>
                    <input type="text" name="subject" required placeholder="Vấn đề cần giải quyết...">
                </div>
            </div>

            <div class="form-group full-width">
                <label>Nội dung chi tiết</label>
                <textarea name="message" rows="4" required placeholder="Vui lòng cung cấp chi tiết lỗi hoặc câu hỏi để hệ thống hỗ trợ nhanh nhất..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Gửi yêu cầu hỗ trợ</button>
            </div>
        </form>
    </div>

    <div class="section-title">Yêu cầu đã gửi gần đây</div>
    <div class="ticket-list">
        <?php if (empty($tickets)): ?>
            <div style="text-align:center; color: var(--text-muted); padding: 40px; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
                Chưa có lịch sử yêu cầu hỗ trợ nào từ bạn.
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
            <div class="ticket-item">
                <div class="ticket-top">
                    <h3 class="ticket-title"><?= htmlspecialchars($t['subject'] ?? '') ?></h3>
                    <?php if (($t['status'] ?? 'open') === 'resolved'): ?>
                        <span class="badge badge-resolved">Đã trả lời</span>
                    <?php else: ?>
                        <span class="badge badge-open">Đang chờ xử lý</span>
                    <?php endif; ?>
                </div>
                
                <p class="ticket-msg"><?= htmlspecialchars($t['message'] ?? '') ?></p>
                
                <?php if (!empty($t['reply'])): ?>
                    <div class="admin-reply-card">
                        <p><strong style="color: var(--primary); display: block; margin-bottom: 4px; font-size: 13px;">Ban quản trị phản hồi:</strong>
                        <?= nl2br(htmlspecialchars($t['reply'])) ?></p>
                    </div>
                <?php else: ?>
                    <div style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                        Đội ngũ đang tiếp nhận & xử lý yêu cầu của bạn...
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="support-header">
        <h1>Quản lý hỗ trợ (Admin)</h1>
        <p>Xem, quản lý và xử lý phản hồi từ khách hàng trên toàn hệ thống.</p>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 7%;">Mã số</th>
                    <th style="width: 15%;">Người gửi</th>
                    <th style="width: 18%;">Tiêu đề hỗ trợ</th>
                    <th style="width: 24%;">Nội dung chi tiết</th>
                    <th style="width: 14%;">Trạng thái</th>
                    <th style="width: 22%;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="6" style="text-align:center; color: var(--text-muted); padding: 40px;">Tuyệt vời! Hiện tại không có yêu cầu nào cần xử lý.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><code>#<?= $t['id'] ?></code></td>
                        <td><strong><?= htmlspecialchars($t['user_name'] ?? 'Ẩn danh') ?></strong></td>
                        <td><strong><?= htmlspecialchars($t['subject'] ?? '') ?></strong></td>
                        <td style="white-space: pre-line; color: var(--text-body); font-size: 13.5px;"><?= htmlspecialchars($t['message'] ?? '') ?></td>
                        <td>
                            <?php if (($t['status'] ?? 'open') === 'resolved'): ?>
                                <span class="badge badge-resolved">Đã phản hồi</span>
                            <?php else: ?>
                                <span class="badge badge-open">Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($t['reply'])): ?>
                                <div class="admin-reply-card" style="margin-bottom: 8px; background: #ecfdf5; border-left-color: #10b981; padding: 8px 12px;">
                                    <strong style="font-size:12px; color:#15803d">Cũ:</strong> <span style="font-size:13px; color: #1e293b;"><?= htmlspecialchars($t['reply']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="support.php" class="admin-form">
                                <input type="hidden" name="reply_id" value="<?= $t['id'] ?>">
                                <div class="form-group" style="margin-bottom: 8px;">
                                    <textarea name="reply" rows="2" placeholder="Nhập câu trả lời..." required></textarea>
                                </div>
                                <button type="submit">✍️ Trả lời</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
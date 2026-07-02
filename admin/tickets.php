<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Hỗ trợ khách hàng';
$currentPage = 'tickets';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Truy cập bị từ chối");
}

$pdo = getDB();

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $ticket_id = (int)$_POST['ticket_id'];
    $reply = trim($_POST['reply'] ?? '');
    
    if ($reply !== '') {
        $stmt = $pdo->prepare("UPDATE support_tickets SET reply = ?, status = 'resolved' WHERE id = ?");
        $stmt->execute([$reply, $ticket_id]);
        header("Location: tickets.php?msg=replied");
        exit;
    }
}

// Fetch Tickets
$stmt = $pdo->query("
    SELECT t.*, u.email, u.phone
    FROM support_tickets t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY CASE WHEN t.status = 'open' THEN 0 ELSE 1 END, t.created_at DESC
");
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/admin_layout.php';
?>

<div class="page-header">
    <h1>🎫 Hỗ trợ & Yêu cầu <span class="badge badge-secondary"><?= count($tickets) ?></span></h1>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'replied'): ?>
    <div class="alert alert-success">✅ Đã phản hồi thành công!</div>
<?php endif; ?>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Chủ đề</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-sub);">Không có yêu cầu hỗ trợ nào.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td style="color:var(--text-sub);">#<?= $t['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($t['user_name']) ?></strong>
                                <?php if ($t['email']): ?>
                                    <div style="font-size:12px;color:var(--text-sub);"><?= htmlspecialchars($t['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><strong style="color:var(--text);"><?= htmlspecialchars($t['subject']) ?></strong></td>
                            <td>
                                <div style="max-width:300px;font-size:13px;line-height:1.4;">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($t['message'], 0, 100, "..."))) ?>
                                </div>
                                <?php if ($t['reply']): ?>
                                    <div style="margin-top:8px;padding:8px;background:#f0fdf4;border-left:3px solid #22c55e;font-size:12px;color:#166534;">
                                        <strong>Phản hồi:</strong> <?= nl2br(htmlspecialchars(mb_strimwidth($t['reply'], 0, 100, "..."))) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'open'): ?>
                                    <span class="badge badge-warning">⏳ Chờ xử lý</span>
                                <?php else: ?>
                                    <span class="badge badge-success">✅ Đã phản hồi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="openReplyModal(<?= htmlspecialchars(json_encode($t)) ?>)" class="btn btn-outline btn-sm">
                                    <?= $t['status'] === 'open' ? 'Phản hồi' : 'Xem / Sửa' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Phản hồi -->
<div id="replyModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:#fff;width:100%;max-width:500px;border-radius:16px;padding:30px;box-shadow:0 20px 40px rgba(0,0,0,0.1);">
        <h3 id="modalTitle" style="margin-bottom:20px;font-size:18px;">Phản hồi hỗ trợ</h3>
        
        <div style="background:#f8fafc;padding:16px;border-radius:10px;margin-bottom:20px;border:1px solid #e2e8f0;">
            <div style="font-weight:700;margin-bottom:8px;font-size:14px;" id="modalSubject"></div>
            <div style="font-size:13px;line-height:1.5;color:var(--text-sub);" id="modalMessage"></div>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="ticket_id" id="modalTicketId">
            
            <div class="form-group">
                <label class="form-label">Nội dung phản hồi của Admin *</label>
                <textarea name="reply" id="modalReply" class="form-control" required placeholder="Nhập phản hồi..."></textarea>
            </div>
            
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Gửi phản hồi</button>
                <button type="button" class="btn btn-outline" style="flex:1;justify-content:center;" onclick="closeReplyModal()">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplyModal(ticket) {
    document.getElementById('modalTicketId').value = ticket.id;
    document.getElementById('modalSubject').textContent = 'Chủ đề: ' + ticket.subject;
    document.getElementById('modalMessage').textContent = ticket.message;
    document.getElementById('modalReply').value = ticket.reply || '';
    document.getElementById('replyModal').style.display = 'flex';
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>

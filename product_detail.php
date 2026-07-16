<?php
// product_detail.php – Premium Product Detail Page
require_once __DIR__ . '/includes/config.php';

$pdo = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. Lấy dữ liệu sản phẩm kèm danh mục
$stmt = $pdo->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

$pageTitle = $product ? htmlspecialchars($product['name']) . ' – ' . SITE_NAME : 'Sản phẩm không tồn tại – ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

if (!$product) {
    echo '<div class="pd-container" style="text-align:center;padding:80px 20px;">
      <span style="font-size:64px;display:block;margin-bottom:16px;">🔍</span>
      <h2 style="font-size:22px;font-weight:900;color:#0f172a;margin-bottom:8px;">Sản phẩm không tồn tại</h2>
      <p style="color:#94a3b8;margin-bottom:20px;">Sản phẩm này có thể đã bị xóa hoặc không còn khả dụng.</p>
      <a href="/bainhom/index.php" class="btn btn-dark">← Quay lại cửa hàng</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// 2. Lấy đánh giá
$stmt = $pdo->prepare("SELECT r.*, u.fullname FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

// 3. Lấy 4 sản phẩm liên quan
$relatedStmt = $pdo->prepare("SELECT p.*, c.name AS cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? LIMIT 4");
$relatedStmt->execute([$product['category_id'], $id]);
$relatedProducts = $relatedStmt->fetchAll();

// Image path
$imgSrc = productImageUrl($product['image_file'] ?? '');

$rating = (float) $product['rating'];
$ratingInt = (int) round($rating);
$ratingInt = max(0, min(5, $ratingInt));
$reviewCount = count($reviews);

// Calculate average rating from reviews or use product rating
$avgRating = $rating;
if ($reviewCount > 0) {
    $totalStars = 0;
    foreach ($reviews as $r) $totalStars += (int) $r['rating'];
    $avgRating = $totalStars / $reviewCount;
}

// Lấy thông số kỹ thuật
$specifications = [];
if (!empty($product['specifications'])) {
    $decoded = json_decode($product['specifications'], true);
    if (is_array($decoded)) {
        $specifications = $decoded;
    }
}
?>

<div class="pd-container">

  <!-- BREADCRUMB -->
  <nav class="pd-breadcrumb">
    <a href="/bainhom/index.php"><i data-lucide="home" style="width:14px;height:14px"></i></a>
    <span class="separator">›</span>
    <a href="/bainhom/index.php?cat=<?= urlencode($product['cat_slug']) ?>"><?= clean($product['cat_name']) ?></a>
    <span class="separator">›</span>
    <span class="current"><?= clean($product['name']) ?></span>
  </nav>

  <!-- MAIN PRODUCT SECTION -->
  <div class="pd-main">

    <!-- GALLERY -->
    <div class="pd-gallery">
      <div class="pd-gallery-main">
        <div class="pd-gallery-badge">
          <?php if ($product['featured']): ?>
            <span class="pd-badge pd-badge-hot">🔥 Hot</span>
          <?php endif; ?>
          <?php if ($product['stock'] <= 3 && $product['stock'] > 0): ?>
            <span class="pd-badge pd-badge-sale">⚡ Sắp hết</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($imgSrc)): ?>
          <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= clean($product['name']) ?>">
        <?php else: ?>
          <span style="font-size:96px"><?= clean($product['image_emoji']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- PRODUCT INFO -->
    <div class="pd-info">

      <!-- Brand -->
      <div class="pd-brand">
        <span class="pd-brand-dot"></span>
        <?= clean($product['brand']) ?>
      </div>

      <!-- Title -->
      <h1 class="pd-title"><?= clean($product['name']) ?></h1>

      <!-- Rating -->
      <div class="pd-rating-row">
        <div class="pd-stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="pd-star <?= $i <= $ratingInt ? 'filled' : '' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span class="pd-rating-text"><?= number_format($avgRating, 1) ?> / 5.0</span>
        <span class="pd-review-count">(<?= $reviewCount ?> đánh giá)</span>
      </div>

      <!-- Price -->
      <div class="pd-price-box">
        <span class="pd-price"><?= formatVND($product['price']) ?></span>
        <span class="pd-price-vat">(Đã bao gồm VAT)</span>
      </div>

      <!-- Specs -->
      <div class="pd-specs-grid">
        <div class="pd-spec-item">
          <div class="pd-spec-icon"></div>
          <div>
            <div class="pd-spec-label">Thương hiệu</div>
            <div class="pd-spec-value"><?= clean($product['brand']) ?></div>
          </div>
        </div>
        <div class="pd-spec-item">
          <div class="pd-spec-icon"></div>
          <div>
            <div class="pd-spec-label">Tình trạng</div>
            <div class="pd-spec-value">
              <?= $product['stock'] > 0
                ? '<span style="color:#059669">Còn ' . $product['stock'] . ' sản phẩm</span>'
                : '<span style="color:#ef4444">Hết hàng</span>' ?>
            </div>
          </div>
        </div>
        <div class="pd-spec-item">
          <div class="pd-spec-icon"></div>
          <div>
            <div class="pd-spec-label">Danh mục</div>
            <div class="pd-spec-value"><?= clean($product['cat_name']) ?></div>
          </div>
        </div>
        <div class="pd-spec-item">
          <div class="pd-spec-icon"></div>
          <div>
            <div class="pd-spec-label">Đánh giá</div>
            <div class="pd-spec-value"><?= number_format($avgRating, 1) ?> sao</div>
          </div>
        </div>
      </div>

      <!-- Quantity + Actions -->
      <?php if ($product['stock'] > 0): ?>
        <div class="pd-qty-row">
          <span class="pd-qty-label">Số lượng:</span>
          <div class="pd-qty-selector">
            <button type="button" class="pd-qty-btn" id="qty-minus" aria-label="Giảm">−</button>
            <input type="number" class="pd-qty-input" id="qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
            <button type="button" class="pd-qty-btn" id="qty-plus" aria-label="Tăng">+</button>
          </div>
          <span class="pd-stock-hint in-stock">Còn <?= $product['stock'] ?> sản phẩm</span>
        </div>

        <div class="pd-actions">
          <button type="button" class="pd-btn-cart" id="pd-add-cart"
            data-pid="<?= $product['id'] ?>"
            data-name="<?= clean($product['name']) ?>">
            <i data-lucide="shopping-cart"></i>
            Thêm vào giỏ
          </button>
          <button type="button" class="pd-btn-buy" id="pd-buy-now"
            data-pid="<?= $product['id'] ?>"
            data-name="<?= clean($product['name']) ?>">
            <i data-lucide="zap"></i>
            Mua ngay
          </button>
        </div>
      <?php else: ?>
        <div class="pd-actions">
          <button class="pd-btn-cart" disabled style="opacity:.5;cursor:not-allowed;flex:1;">
            <i data-lucide="x-circle"></i> Sản phẩm tạm hết hàng
          </button>
        </div>
      <?php endif; ?>

      <!-- Trust Badges -->
      <div class="pd-trust">
        <div class="pd-trust-item">
          <span class="pd-trust-icon"></span>
          <span>Hàng chính hãng 100%</span>
        </div>
        <div class="pd-trust-item">
          <span class="pd-trust-icon"></span>
          <span>Miễn phí vận chuyển từ 5 triệu</span>
        </div>
        <div class="pd-trust-item">
          <span class="pd-trust-icon"></span>
          <span>Đổi trả trong 7 ngày</span>
        </div>
      </div>

    </div>
  </div>

  <!-- TABS: Mô tả | Đánh giá | Thông số -->
  <div class="pd-tabs-wrapper">
    <div class="pd-tabs-nav">
      <button class="pd-tab-btn active" data-tab="desc">
        <i data-lucide="file-text"></i> Mô tả sản phẩm
      </button>
      <?php if (!empty($specifications)): ?>
      <button class="pd-tab-btn" data-tab="specs">
        <i data-lucide="list"></i> Thông số kỹ thuật
      </button>
      <?php endif; ?>
      <button class="pd-tab-btn" data-tab="reviews">
        <i data-lucide="message-square"></i> Đánh giá (<?= $reviewCount ?>)
      </button>
    </div>

    <!-- Tab: Mô tả -->
    <div class="pd-tab-content active" id="tab-desc">
      <div class="pd-description">
        <h4>Giới thiệu về <?= clean($product['name']) ?></h4>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <div class="pd-highlight-list">
          <div class="pd-highlight-item">
            <span class="icon"></span>
            Thương hiệu: <?= clean($product['brand']) ?>
          </div>
          <div class="pd-highlight-item">
            <span class="icon"></span>
            Bảo hành chính hãng 12 tháng
          </div>
          <div class="pd-highlight-item">
            <span class="icon"></span>
            Hỗ trợ kỹ thuật 24/7
          </div>
          <div class="pd-highlight-item">
            <span class="icon"></span>
            Trả góp 0% qua thẻ tín dụng
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Thông số kỹ thuật -->
    <?php if (!empty($specifications)): ?>
    <div class="pd-tab-content" id="tab-specs">
      <div class="pd-specs-table-wrapper" style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
          <tbody>
            <?php $i = 0; foreach ($specifications as $key => $val): ?>
            <tr style="background-color: <?= $i % 2 === 0 ? '#f8fafc' : '#ffffff' ?>; border-bottom: 1px solid #f1f5f9;">
              <th style="padding: 16px 20px; width: 35%; color: #475569; font-weight: 600; font-size: 15px; border-right: 1px solid #f1f5f9;"><?= htmlspecialchars($key) ?></th>
              <td style="padding: 16px 20px; color: #0f172a; font-size: 15px; font-weight: 500;"><?= htmlspecialchars($val) ?></td>
            </tr>
            <?php $i++; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tab: Đánh giá -->
    <div class="pd-tab-content" id="tab-reviews">

      <!-- Summary -->
      <div class="pd-reviews-summary">
        <div class="pd-reviews-score">
          <div class="score"><?= number_format($avgRating, 1) ?></div>
          <div class="out-of">trên 5 sao</div>
          <div class="pd-stars" style="justify-content:center;margin-top:6px;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="pd-star <?= $i <= round($avgRating) ? 'filled' : '' ?>" style="font-size:14px;">★</span>
            <?php endfor; ?>
          </div>
        </div>
        <div class="pd-reviews-bars">
          <?php
          // Count reviews per star level
          $starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
          foreach ($reviews as $r) {
              $s = min(5, max(1, (int) $r['rating']));
              $starCounts[$s]++;
          }
          for ($s = 5; $s >= 1; $s--):
              $pct = $reviewCount > 0 ? ($starCounts[$s] / $reviewCount) * 100 : 0;
          ?>
            <div class="pd-bar-row">
              <span class="pd-bar-label"><?= $s ?> sao</span>
              <div class="pd-bar-track">
                <div class="pd-bar-fill" style="width:<?= $pct ?>%"></div>
              </div>
              <span style="font-size:11px;color:#94a3b8;font-weight:700;width:24px;text-align:right;"><?= $starCounts[$s] ?></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Review Form -->
      <?php if (isset($user)): ?>
        <div class="pd-review-form">
          <h4>✍️ Viết đánh giá của bạn</h4>
          <form action="process_review.php" method="POST">
            <input type="hidden" name="product_id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="rating" id="review-rating-value" value="5">

            <div class="pd-star-input" id="star-input-group">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <label class="<?= $i <= 5 ? 'active' : '' ?>" data-star="<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>

            <textarea name="comment" class="pd-review-textarea" placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..." required></textarea>
            <button type="submit" class="pd-review-submit">
              <i data-lucide="send" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
              Gửi đánh giá
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="pd-review-form" style="text-align:center;">
          <p style="color:#475569;font-size:14px;">Bạn cần <a href="/bainhom/controllers/auth.php" style="color:#4f46e5;font-weight:800;text-decoration:underline;">đăng nhập</a> để gửi đánh giá.</p>
        </div>
      <?php endif; ?>

      <!-- Reviews List -->
      <?php if (empty($reviews)): ?>
        <div style="text-align:center;padding:32px;">
          <span style="font-size:40px;display:block;margin-bottom:8px;">💬</span>
          <p style="color:#94a3b8;font-size:14px;">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này!</p>
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $review):
          $initial = mb_substr($review['fullname'], 0, 1, 'UTF-8');
          $reviewRating = (int) $review['rating'];
        ?>
          <div class="pd-review-card">
            <div class="pd-review-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="pd-review-body">
              <div class="pd-review-header">
                <span class="pd-review-author"><?= htmlspecialchars($review['fullname']) ?></span>
                <span class="pd-review-date"><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
              </div>
              <div class="pd-review-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <span class="star <?= $i <= $reviewRating ? '' : 'empty' ?>">★</span>
                <?php endfor; ?>
              </div>
              <p class="pd-review-text"><?= htmlspecialchars($review['comment']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- RELATED PRODUCTS -->
  <?php if ($relatedProducts): ?>
    <div class="pd-related">
      <div class="pd-related-heading">
        <h3>Sản phẩm liên quan</h3>
        <div class="line"></div>
      </div>
      <div class="pd-related-grid">
        <?php foreach ($relatedProducts as $item):
          $riSrc = '';
          $riSrc = productImageUrl($item['image_file'] ?? '');
        ?>
          <article class="rec-card">
            <a href="product_detail.php?id=<?= $item['id'] ?>" class="rec-card-link">
              <div class="rec-card-thumb">
                <?php if (!empty($riSrc)): ?>
                  <img src="<?= htmlspecialchars($riSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= clean($item['name']) ?>">
                <?php else: ?>
                  <span style="font-size:48px"><?= clean($item['image_emoji']) ?></span>
                <?php endif; ?>
                <span class="rec-card-rating">⭐ <?= number_format($item['rating'], 1) ?></span>
              </div>
              <div class="rec-card-body">
                <span class="rec-card-brand"><?= clean($item['brand']) ?></span>
                <h4 class="rec-card-name"><?= clean($item['name']) ?></h4>
              </div>
            </a>
            <div class="rec-card-footer" style="padding: 0 16px 14px;">
              <span class="rec-card-price"><?= formatVND($item['price']) ?></span>
              <div class="rec-card-actions">
                <?php if ($item['stock'] > 0): ?>
                  <button type="button" class="rec-btn-cart js-add-cart" data-pid="<?= $item['id'] ?>" data-name="<?= clean($item['name']) ?>">
                    <i data-lucide="shopping-cart"></i> Mua
                  </button>
                <?php else: ?>
                  <span class="rec-badge" style="background:#f1f5f9;color:#94a3b8;font-size:10px;">Hết hàng</span>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- Product Detail Page Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // ---- TABS ----
  const tabBtns = document.querySelectorAll('.pd-tab-btn');
  const tabContents = document.querySelectorAll('.pd-tab-content');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      const target = btn.dataset.tab;
      document.getElementById('tab-' + target)?.classList.add('active');
    });
  });

  // ---- QUANTITY SELECTOR ----
  const qtyInput = document.getElementById('qty-input');
  const qtyMinus = document.getElementById('qty-minus');
  const qtyPlus = document.getElementById('qty-plus');
  if (qtyInput && qtyMinus && qtyPlus) {
    const maxQty = parseInt(qtyInput.max) || 10;
    qtyMinus.addEventListener('click', () => {
      let val = parseInt(qtyInput.value) || 1;
      if (val > 1) qtyInput.value = val - 1;
    });
    qtyPlus.addEventListener('click', () => {
      let val = parseInt(qtyInput.value) || 1;
      if (val < maxQty) qtyInput.value = val + 1;
    });
    qtyInput.addEventListener('change', () => {
      let val = parseInt(qtyInput.value) || 1;
      val = Math.max(1, Math.min(maxQty, val));
      qtyInput.value = val;
    });
  }

  // ---- STAR INPUT ----
  const starGroup = document.getElementById('star-input-group');
  const ratingValue = document.getElementById('review-rating-value');
  if (starGroup && ratingValue) {
    const labels = starGroup.querySelectorAll('label');
    labels.forEach(label => {
      label.addEventListener('click', () => {
        const star = parseInt(label.dataset.star);
        ratingValue.value = star;
        labels.forEach((l, i) => {
          l.classList.toggle('active', (i + 1) <= star);
        });
      });
      label.addEventListener('mouseenter', () => {
        const star = parseInt(label.dataset.star);
        labels.forEach((l, i) => {
          l.style.color = (i + 1) <= star ? '#f59e0b' : '#e2e8f0';
        });
      });
    });
    starGroup.addEventListener('mouseleave', () => {
      const current = parseInt(ratingValue.value) || 5;
      labels.forEach((l, i) => {
        l.style.color = '';
        l.classList.toggle('active', (i + 1) <= current);
      });
    });
  }

  // ---- ADD TO CART (Product Detail) ----
  const addCartBtn = document.getElementById('pd-add-cart');
  if (addCartBtn) {
    addCartBtn.addEventListener('click', async function() {
      const pid = this.dataset.pid;
      const name = this.dataset.name;
      const qty = parseInt(document.getElementById('qty-input')?.value) || 1;

      this.disabled = true;
      this.innerHTML = '<i data-lucide="loader" class="spin"></i> Đang thêm...';

      try {
        for (let i = 0; i < qty; i++) {
          const res = await fetch('/bainhom/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: pid })
          });
          var data = await res.json();
        }
        if (data.success) {
          if (typeof showToast === 'function') showToast(`Đã thêm "${name}" (x${qty}) vào Giỏ Hàng!`);
          const badge = document.querySelector('.cart-badge');
          if (badge) badge.textContent = data.cart_count;
        } else {
          if (typeof showToast === 'function') showToast(data.message || 'Lỗi!', 'error');
        }
      } catch (e) {
        if (typeof showToast === 'function') showToast('Có lỗi xảy ra!', 'error');
      }

      this.disabled = false;
      this.innerHTML = '<i data-lucide="shopping-cart"></i> Thêm vào giỏ';
      lucide.createIcons();
    });
  }

  // ---- BUY NOW ----
  const buyNowBtn = document.getElementById('pd-buy-now');
  if (buyNowBtn) {
    buyNowBtn.addEventListener('click', async function() {
      const pid = this.dataset.pid;
      const name = this.dataset.name;
      const qty = parseInt(document.getElementById('qty-input')?.value) || 1;

      this.disabled = true;
      this.innerHTML = '<i data-lucide="loader" class="spin"></i> Đang xử lý...';

      try {
        for (let i = 0; i < qty; i++) {
          await fetch('/bainhom/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_id: pid })
          });
        }
        // Redirect to checkout
        window.location.href = '/bainhom/views/cart.php';
      } catch (e) {
        if (typeof showToast === 'function') showToast('Có lỗi xảy ra!', 'error');
        this.disabled = false;
        this.innerHTML = '<i data-lucide="zap"></i> Mua ngay';
        lucide.createIcons();
      }
    });
  }
});
</script>

<style>
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  .spin { animation: spin 1s linear infinite; width: 16px; height: 16px; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
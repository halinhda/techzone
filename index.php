<?php require_once __DIR__ . '/includes/config.php'; ?>

<?php if (!empty($_GET['register']) && $_GET['register'] === 'success'): ?>

  <div id="toast-success" style="
    position: fixed;
    top: 24px;
    right: 24px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    padding: 14px 18px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    z-index: 99999;
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    gap: 10px;
    animation: popIn 0.35s ease;
    min-width: 240px;
  ">
    <span style="
      background: rgba(255,255,255,0.2);
      width: 26px;
      height: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-weight: 900;
    ">✔</span>
    🎉 Đăng ký tài khoản thành công!
  </div>

  <style>
    @keyframes popIn {
      0% {
        transform: translateY(-20px) scale(0.9);
        opacity: 0;
      }

      100% {
        transform: translateY(0) scale(1);
        opacity: 1;
      }
    }
  </style>

  <script>
    setTimeout(() => {
      const el = document.getElementById('toast-success');
      if (el) {
        el.style.transition = "all 0.4s ease";
        el.style.transform = "translateY(-10px)";
        el.style.opacity = "0";
        setTimeout(() => el.remove(), 400);
      }
    }, 2500);
  </script>

<?php endif; ?>


<?php
$pdo = getDB();

// ---- FILTERS ----
$search = clean($_GET['q'] ?? '');
$catSlug = clean($_GET['cat'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = ['1=1'];
$params = [];

if ($search) {
  $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)';
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if ($catSlug) {
  $where[] = 'c.slug = ?';
  $params[] = $catSlug;
}

$whereSQL = implode(' AND ', $where);

// Total count
$countSQL = "SELECT COUNT(*) FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));

// Products
$sql = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE $whereSQL
        ORDER BY p.featured DESC, p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Featured products for carousel
$featured = $pdo->query("SELECT * FROM products WHERE featured = 1 ORDER BY id LIMIT 5")->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$recommended = array_slice($featured, 0, 4);

// Promo data per product id
$promos = [
  1 => ['tag' => '⚡ GIẢM 15% – HOT DEAL LẬP TRÌNH', 'discount' => 0.15, 'bg' => 'background:linear-gradient(135deg,#2563eb,#1e1b4b)'],
  3 => ['tag' => '📱 SIÊU PHẨM TITAN – BÁN CHẠY NHẤT', 'discount' => 0.10, 'bg' => 'background:linear-gradient(135deg,#7c3aed,#1e1b4b)'],
  5 => ['tag' => '🎧 ANC SMART CHỐNG ỒN 99%', 'discount' => 0.08, 'bg' => 'background:linear-gradient(135deg,#059669,#134e4a)'],
];

$pageTitle = $search ? "Tìm kiếm: $search – " . SITE_NAME : SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$search && !$catSlug && $featured): ?>
  <div class="carousel" id="carousel-wrapper" style="margin-bottom:32px; position:relative; overflow:hidden;">

    <?php foreach ($featured as $idx => $p):
      $promo = $promos[$p['id']] ?? ['tag' => '⚡ KHUYẾN MÃI', 'discount' => 0.1, 'bg' => 'background:linear-gradient(135deg,#4f46e5,#1e1b4b)'];
      $deal = round($p['price'] * (1 - $promo['discount']));
      $discPct = round($promo['discount'] * 100);
      ?>
      <div class="carousel-slide carousel-slide-item"
        style="<?= $promo['bg'] ?>; <?= $idx === 0 ? 'display:flex;' : 'display:none;' ?>">
        <div class="carousel-content">
          <span class="carousel-tag"><?= $promo['tag'] ?></span>
          <h2 class="carousel-name" style="margin-top:10px"><?= clean($p['name']) ?></h2>
          <div class="carousel-prices">
            <span class="carousel-deal-price"><?= formatVND($deal) ?></span>
            <span class="carousel-orig-price"><?= formatVND($p['price']) ?></span>
          </div>
        </div>
        <div class="carousel-visual">
          <?php if (!empty($p['image_file'])): ?>
            <img src="assets/images/<?= htmlspecialchars($p['image_file'], ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= clean($p['name']) ?>" class="carousel-image">
          <?php else: ?>
            <span style="font-size:64px"><?= clean($p['image_emoji']) ?></span>
          <?php endif; ?>
          <span class="carousel-discount-badge">-<?= $discPct ?>% GIẢM</span>
        </div>
      </div>
    <?php endforeach; ?>

    <button class="carousel-nav prev" style="position:absolute; top:50%; left:10px; z-index:99; cursor:pointer;"
      onclick="moveCarousel(-1)"><i data-lucide="chevron-left"></i></button>
    <button class="carousel-nav next" style="position:absolute; top:50%; right:10px; z-index:99; cursor:pointer;"
      onclick="moveCarousel(1)"><i data-lucide="chevron-right"></i></button>

  </div>
<?php endif; ?>

<section class="home-panel">
  <div class="panel-heading">
    <div>
      <p class="eyebrow">Phân loại sản phẩm</p>
      <h3>Khám phá theo nhóm thiết bị</h3>
    </div>
  </div>
  <div class="mini-card-grid category-showcase-grid">
    <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
      <a class="mini-card category-card" href="/bainhom/index.php?cat=<?= urlencode($cat['slug']) ?>">
        <span class="mini-card-emoji"><?= clean($cat['icon'] ?: '🛍️') ?></span>
        <strong><?= clean($cat['name']) ?></strong>
        <p>Khám phá các sản phẩm thuộc nhóm <?= clean($cat['name']) ?> phù hợp cho học tập, làm việc và giải trí.</p>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="home-panel">
  <div class="panel-heading">
    <div>
      <p class="eyebrow">Gợi ý cho bạn</p>
      <h3>Top sản phẩm phù hợp hôm nay</h3>
    </div>
    <a href="/bainhom/index.php?q=featured" class="text-link">Xem thêm</a>
  </div>
  <div class="mini-card-grid">
    <?php foreach ($recommended as $item):
      $imgSrc = '';
      if (!empty($item['image_file'])) {
        $imgSrc = ltrim($item['image_file'], '/');
        if (strpos($imgSrc, 'assets/images/') === 0) {
          $imgSrc = substr($imgSrc, strlen('assets/images/'));
        } elseif (strpos($imgSrc, 'images/') === 0) {
          $imgSrc = substr($imgSrc, strlen('images/'));
        }
        $imgSrc = '/bainhom/assets/images/' . $imgSrc;
      }
      ?>
      <article class="mini-card">
        <div class="mini-card-image-wrap">
          <?php if (!empty($imgSrc)): ?>
            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= clean($item['name']) ?>"
              class="mini-card-image">
          <?php else: ?>
            <span class="mini-card-emoji"><?= clean($item['image_emoji']) ?></span>
          <?php endif; ?>
        </div>
        <strong><?= clean($item['name']) ?></strong>
        <p><?= clean($item['description'] ?? '') ?></p>
        <span class="mini-card-price"><?= formatVND($item['price']) ?></span>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="home-panel">
  <div class="panel-heading">
    <div>
      <p class="eyebrow">Combo tiết kiệm</p>
      <h3>Mua theo combo – giá tốt hơn</h3>
    </div>
  </div>
  <div class="combo-grid">
    <article class="combo-card">
      <h4>Combo Học Sinh – Sinh Viên</h4>
      <p>Laptop + chuột + bàn phím, hỗ trợ học tập và làm việc.</p>
      <strong>Tiết kiệm đến 12%</strong>
    </article>
    <article class="combo-card">
      <h4>Combo Gaming</h4>
      <p>Loa + tai nghe + bàn phím cơ, sẵn sàng cho game thủ.</p>
      <strong>Giảm ngay 10%</strong>
    </article>
    <article class="combo-card">
      <h4>Combo Văn Phòng</h4>
      <p>Máy + phụ kiện tối ưu cho nhân viên, công việc liên tục.</p>
      <strong>Trả góp 0% trong 6 tháng</strong>
    </article>
  </div>
</section>

<!-- PAGE HEADER -->
<div class="section-header">
  <div>
    <h2 class="section-title">
      <i data-lucide="shopping-bag"></i>
      <?= $search ? "Kết quả tìm: \"$search\"" : ($catSlug ? 'Danh mục sản phẩm' : 'Tất Cả Sản Phẩm') ?>
    </h2>
    <p class="section-sub">Thiết bị công nghệ phân phối chính hãng, bảo hành điện tử</p>
  </div>
  <span class="results-count">Hiển thị: <?= min($perPage, $totalItems) ?> sản phẩm/trang · <?= $totalItems ?>
    tổng</span>
</div>

<?php if (!empty($promos)): ?>
  <div class="promo-rotator" id="promo-rotator" style="margin:18px 0;">
    <style>
      .promo-rotator {
        display: flex;
        align-items: center;
        gap: 12px
      }

      .promo-slide {
        flex: 1;
        color: #fff;
        padding: 14px;
        border-radius: 10px;
        display: none;
        position: relative
      }

      .promo-slide.active {
        display: block
      }

      .promo-tag {
        font-weight: 800;
        font-size: 14px
      }

      .promo-desc {
        font-size: 13px;
        opacity: 0.95;
        margin-top: 6px
      }

      .promo-nav {
        background: transparent;
        border: 0;
        color: #111;
        font-size: 20px;
        cursor: pointer
      }

      .promo-dots {
        display: flex;
        gap: 6px;
        margin-left: 8px
      }

      .promo-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #cbd5e1;
        cursor: pointer;
        border: 0
      }

      .promo-dot.active {
        background: #0ea5e9
      }
    </style>

    <button type="button" class="promo-nav" onclick="changePromo(-1)">&larr;</button>

    <div style="flex:1;min-width:220px">
      <?php foreach ($promos as $pid => $promo):
        $discPct = round(($promo['discount'] ?? 0) * 100);
        ?>
        <div class="promo-slide <?= $pid === array_key_first($promos) ? 'active' : '' ?>" data-idx="<?= $pid ?>"
          style="<?= $promo['bg'] ?>">
          <div class="promo-tag"><?= $promo['tag'] ?></div>
          <div class="promo-desc">Giảm <?= $discPct ?>% cho sản phẩm liên quan. <a href="?promo=<?= $pid ?>"
              style="color:#fff;text-decoration:underline">Xem ưu đãi</a></div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="button" class="promo-nav" onclick="changePromo(1)">&rarr;</button>
    <div class="promo-dots" id="promo-dots">
      <?php $i = 0;
      foreach ($promos as $pid => $promo):
        $i++; ?>
        <button type="button" class="promo-dot <?= $i === 1 ? 'active' : '' ?>" data-index="<?= $i - 1 ?>"
          onclick="showPromo(<?= $i - 1 ?>)"></button>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- PRODUCT GRID -->
<?php if (empty($products)): ?>
  <div class="empty-state">
    <span class="icon">🔍</span>
    <h4>Không tìm thấy sản phẩm phù hợp</h4>
    <p>Vui lòng thử lại từ khóa khác hoặc chọn danh mục khác.</p>
    <a href="/bainhom/index.php" class="btn btn-dark">Xem tất cả sản phẩm</a>
  </div>
<?php else: ?>
  <div class="product-slider-wrapper">
    <div class="product-grid">
      <?php foreach ($products as $p):
        $oos = $p['stock'] <= 0;
        $imgSrc = '';
        if (!empty($p['image_file'])) {
          $imgSrc = ltrim($p['image_file'], '/');
          if (strpos($imgSrc, 'assets/images/') === 0) {
            $imgSrc = substr($imgSrc, strlen('assets/images/'));
          } elseif (strpos($imgSrc, 'images/') === 0) {
            $imgSrc = substr($imgSrc, strlen('images/'));
          }
          $imgSrc = '/bainhom/assets/images/' . $imgSrc;
        }
        ?>
        <div class="product-card" data-pid="<?= (int) $p['id'] ?>"
          data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>"
          data-brand="<?= htmlspecialchars($p['brand'], ENT_QUOTES, 'UTF-8') ?>" data-price="<?= (float) $p['price'] ?>"
          data-desc="<?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
          data-image="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
          data-rating="<?= number_format($p['rating'], 1) ?>">
          <div class="product-thumb">
            <?php if (!empty($imgSrc)): ?>
              <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= clean($p['name']) ?>"
                class="product-image">
            <?php else: ?>
              <span class="product-emoji"><?= clean($p['image_emoji']) ?></span>
            <?php endif; ?>
            <span class="product-rating">⭐ <?= number_format($p['rating'], 1) ?></span>
            <?php if ($oos): ?>
              <div class="out-of-stock-overlay"><span>Hết Hàng Đợt Này</span></div>
            <?php endif; ?>
          </div>
          <div class="product-body">
            <div class="product-meta">
              <span><?= clean($p['brand']) ?></span>
              <span>KT: <?= $p['stock'] ?> chiếc</span>
            </div>
            <h3 class="product-name"><?= clean($p['name']) ?></h3>
            <p class="product-desc"><?= clean($p['description'] ?? '') ?></p>
            <div class="product-footer">
              <span class="product-price"><?= formatVND($p['price']) ?></span>
              <a href="product_detail.php?id=<?= $p['id'] ?>"
                style="margin-right: 10px; font-size: 13px; color: #666; text-decoration: underline;">
                Xem chi tiết
              </a>
              <?php if (!$oos): ?>
                <button class="btn btn-outline btn-sm js-add-cart" data-pid="<?= $p['id'] ?>"
                  data-name="<?= clean($p['name']) ?>">
                  <i data-lucide="shopping-cart"></i> Mua
                </button>
              <?php else: ?>
                <span class="btn btn-sm" style="background:#f1f5f9;color:#94a3b8;cursor:not-allowed">Hết hàng</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="modal-backdrop" id="product-detail-modal" aria-hidden="true">
    <div class="modal-box product-detail-box">
      <div class="modal-header">
        <div>
          <p class="modal-kicker">Xem hàng</p>
          <h3 class="modal-title" id="modal-product-name">Sản phẩm</h3>
        </div>
        <button type="button" class="modal-close" onclick="closeModal('product-detail-modal')"
          aria-label="Đóng">✕</button>
      </div>
      <div class="product-detail-grid">
        <img id="modal-product-image" class="product-detail-image" src="" alt="Sản phẩm">
        <div class="product-detail-body">
          <p class="product-detail-brand" id="modal-product-brand"></p>
          <p class="product-detail-desc" id="modal-product-desc"></p>
          <div class="product-detail-meta">
            <span class="product-detail-price" id="modal-product-price"></span>
            <span class="product-detail-rating" id="modal-product-rating"></span>
          </div>
          <button type="button" class="btn btn-dark btn-full" id="modal-add-cart-btn">Thêm vào giỏ</button>
        </div>
      </div>
    </div>
  </div>

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">←</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
          class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">→</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
  function toggleSection(id) {
    const panel = document.getElementById(id);
    if (!panel) return;
    const isHidden = panel.hasAttribute('hidden');
    panel.hidden = !isHidden;
  }

  (function () {
    const wrapper = document.getElementById('carousel-wrapper');
    if (!wrapper) return;

    const slides = wrapper.querySelectorAll('.carousel-slide-item');
    let currentIdx = 0;
    let timer;

    // Hàm di chuyển chính
    window.moveCarousel = function (direction) {
      // 1. Chỉ ẩn slide cũ, KHÔNG ĐỤNG ĐẾN CÁC NÚT BẤM
      slides[currentIdx].style.display = 'none';

      // 2. Tính chỉ số mới
      currentIdx = (currentIdx + direction + slides.length) % slides.length;

      // 3. Hiện slide mới
      slides[currentIdx].style.display = 'flex';

      // 4. Reset bộ đếm tự động
      clearInterval(timer);
      startTimer();
    }

    // Tự động lướt
    function startTimer() {
      timer = setInterval(() => {
        // Lặp lại logic moveCarousel(1)
        slides[currentIdx].style.display = 'none';
        currentIdx = (currentIdx + 1) % slides.length;
        slides[currentIdx].style.display = 'flex';
      }, 4500);
    }

    // Khởi động
    startTimer();
  })();
</script>

<script>
  // Promo rotator controls with auto-rotate and pause-on-hover
  let _promoIndex = 0;
  let _promoSlides = [];
  let _promoDots = [];
  let _promoTimer = null;
  const PROMO_INTERVAL_MS = 5000;

  function showPromo(idx) {
    if (!_promoSlides || !_promoSlides.length) return;
    _promoIndex = ((idx % _promoSlides.length) + _promoSlides.length) % _promoSlides.length;
    _promoSlides.forEach((el, i) => el.classList.toggle('active', i === _promoIndex));
    if (_promoDots) Array.from(_promoDots).forEach((d, i) => d.classList.toggle('active', i === _promoIndex));
    // reset auto-rotate when user manually changes
    restartPromoTimer();
  }

  function changePromo(dir) {
    if (!_promoSlides || !_promoSlides.length) return;
    showPromo(_promoIndex + dir);
  }

  function startPromoTimer() {
    stopPromoTimer();
    _promoTimer = setInterval(() => changePromo(1), PROMO_INTERVAL_MS);
  }

  function stopPromoTimer() {
    if (_promoTimer) { clearInterval(_promoTimer); _promoTimer = null; }
  }

  function restartPromoTimer() {
    startPromoTimer();
  }

  // Initialize after DOM ready so elements exist
  document.addEventListener('DOMContentLoaded', () => {
    _promoSlides = Array.from(document.querySelectorAll('.promo-slide'));
    const dotsContainer = document.getElementById('promo-dots');
    _promoDots = dotsContainer ? dotsContainer.children : [];

    // expose to global for inline onclick handlers
    window.showPromo = showPromo;
    window.changePromo = changePromo;

    if (_promoSlides.length) {
      showPromo(0);
      startPromoTimer();
    }

    const rotator = document.getElementById('promo-rotator');
    if (rotator) {
      rotator.addEventListener('mouseenter', stopPromoTimer);
      rotator.addEventListener('mouseleave', startPromoTimer);
    }
    // stop timer on page hide
    window.addEventListener('beforeunload', stopPromoTimer);
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
?>
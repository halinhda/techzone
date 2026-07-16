// ================================================================
// ELECTROTECH STORE – main.js
// Client-side interactions: toasts, cart AJAX, carousel, modals, FAQ
// ================================================================

/* ---- TOAST NOTIFICATIONS ---- */
window.showToast = function (msg, type = 'success') {
  const c = document.getElementById('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
         style="flex-shrink:0">
      ${type === 'error'
      ? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
      : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'}
    </svg>
    <span class="toast-msg">${msg}</span>
    <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
  `;
  c.appendChild(t);
  setTimeout(() => t.classList.add('show'), 10);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 4000);
};

/* ---- ADD TO CART (AJAX) ---- */
document.querySelectorAll('.js-add-cart').forEach(btn => {
  btn.addEventListener('click', async function () {
    const pid = this.dataset.pid;
    const name = this.dataset.name;
    const res = await fetch('/bainhom/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add', product_id: pid })
    });
    const data = await res.json();
    if (data.success) {
      showToast(`Đã thêm "${name}" vào Giỏ Hàng!`);
      const badge = document.querySelector('.cart-badge');
      if (badge) { badge.textContent = data.cart_count; badge.style.display = 'inline-flex'; }
      else {
        const cartLink = document.querySelector('a[href*="views/cart.php"] .nav-link, a[href*="views/cart.php"]');
        if (cartLink) {
          const b = document.createElement('span');
          b.className = 'cart-badge';
          b.textContent = data.cart_count;
          cartLink.appendChild(b);
        }
      }
      // Update bottom nav badge
      const bottomBadge = document.querySelector('.bottom-nav-badge');
      if (bottomBadge) bottomBadge.textContent = data.cart_count;
    } else {
      showToast(data.message || 'Lỗi!', 'error');
    }
  });
});

/* ---- QUANTITY CONTROLS (CART PAGE) ---- */
document.querySelectorAll('.js-qty').forEach(btn => {
  btn.addEventListener('click', async function () {
    const pid = this.dataset.pid;
    const delta = parseInt(this.dataset.delta);
    const res = await fetch('/bainhom/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'update', product_id: pid, delta })
    });
    const data = await res.json();
    if (data.success) location.reload();
    else showToast(data.message || 'Lỗi!', 'error');
  });
});

/* ---- REMOVE FROM CART ---- */
document.querySelectorAll('.js-remove-cart').forEach(btn => {
  btn.addEventListener('click', async function () {
    const pid = this.dataset.pid;
    const res = await fetch('/bainhom/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'remove', product_id: pid })
    });
    const data = await res.json();
    if (data.success) location.reload();
    else showToast(data.message || 'Lỗi!', 'error');
  });
});

/* ---- CAROUSEL ---- */
(function () {
  const wrapper = document.getElementById('carousel-wrapper');
  if (!wrapper) return;

  const slides = wrapper.querySelectorAll('.carousel-slide-item');
  const dots = wrapper.querySelectorAll('.carousel-dot');
  if (!slides.length) return;

  let current = 0;
  let timer;

  function goTo(idx) {
    // Ẩn slide hiện tại
    slides[current].style.display = 'none';
    dots[current]?.classList.remove('active');

    // Tính toán index
    current = (idx + slides.length) % slides.length;

    // Hiện slide mới
    slides[current].style.display = 'flex';
    dots[current]?.classList.add('active');
  }

  // Khởi tạo trạng thái ban đầu
  slides.forEach((s, i) => { s.style.display = i === 0 ? 'flex' : 'none'; });
  dots[0]?.classList.add('active');

  function startTimer() {
    timer = setInterval(() => goTo(current + 1), 4500);
  }

  // Khi click, PHẢI clear timer cũ và tạo timer mới
  wrapper.querySelector('.carousel-nav.prev')?.addEventListener('click', () => {
    clearInterval(timer);
    goTo(current - 1);
    startTimer();
  });

  wrapper.querySelector('.carousel-nav.next')?.addEventListener('click', () => {
    clearInterval(timer);
    goTo(current + 1);
    startTimer();
  });

  dots.forEach((dot, i) => dot.addEventListener('click', () => {
    clearInterval(timer);
    goTo(i);
    startTimer();
  }));

  startTimer();
})();

/* ---- FAQ ACCORDION ---- */
document.querySelectorAll('.faq-toggle').forEach(btn => {
  btn.addEventListener('click', function () {
    const answer = this.nextElementSibling;
    const isOpen = answer.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
    document.querySelectorAll('.faq-toggle').forEach(b => b.classList.remove('open'));

    if (!isOpen) {
      answer.classList.add('open');
      this.classList.add('open');
    }
  });
});

/* ---- PAYMENT METHOD SELECTOR ---- */
document.querySelectorAll('.pay-option').forEach(opt => {
  opt.addEventListener('click', function () {
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
    this.classList.add('selected');
    const radio = this.querySelector('input[type=radio]');
    if (radio) radio.checked = true;
  });
});

/* ---- MODAL HELPERS ---- */
window.openModal = function (id) {
  const modal = document.getElementById(id);
  if (modal) { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); }
};
window.closeModal = function (id) {
  const modal = document.getElementById(id);
  if (modal) { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); }
};

function addQuickCart(pid, name) {
  return fetch('/bainhom/api/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'add', product_id: pid })
  }).then(r => r.json()).then(data => {
    if (data.success) {
      showToast(`Đã thêm "${name}" vào Giỏ Hàng!`);
      const badge = document.querySelector('.cart-badge');
      if (badge) badge.textContent = data.cart_count;
      else {
        const cartLink = document.querySelector('a[href*="views/cart.php"] .nav-link, a[href*="views/cart.php"]');
        if (cartLink) {
          const b = document.createElement('span');
          b.className = 'cart-badge';
          b.textContent = data.cart_count;
          cartLink.appendChild(b);
        }
      }
      closeModal('product-detail-modal');
    } else {
      showToast(data.message || 'Lỗi!', 'error');
    }
  });
}

// Quick view modal for product cards
const productModal = document.getElementById('product-detail-modal');
if (productModal) {
  document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function (e) {
      if (e.target.closest('.js-add-cart')) return;
      const image = this.dataset.image || '';
      const name = this.dataset.name || 'Sản phẩm';
      const brand = this.dataset.brand || '';
      const desc = this.dataset.desc || '';
      const price = Number(this.dataset.price || 0);
      const rating = this.dataset.rating || '0.0';
      const pid = this.dataset.pid || '';

      document.getElementById('modal-product-name').textContent = name;
      document.getElementById('modal-product-brand').textContent = brand;
      document.getElementById('modal-product-desc').textContent = desc;
      document.getElementById('modal-product-price').textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
      document.getElementById('modal-product-rating').textContent = `⭐ ${rating}`;
      const img = document.getElementById('modal-product-image');
      if (img) img.src = image || 'https://via.placeholder.com/320x220?text=No+Image';
      const addBtn = document.getElementById('modal-add-cart-btn');
      if (addBtn) {
        addBtn.dataset.pid = pid;
        addBtn.dataset.name = name;
        addBtn.onclick = () => addQuickCart(pid, name);
      }
      openModal('product-detail-modal');
    });
  });
}

// Close modal on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(m => {
  m.addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('open');
  });
});

/* ---- CONFIRM DELETE ---- */
document.querySelectorAll('.js-confirm-delete').forEach(btn => {
  btn.addEventListener('click', function (e) {
    if (!confirm(this.dataset.confirm || 'Bạn chắc chắn muốn xóa?')) e.preventDefault();
  });
});

/* ---- FLASH SESSION MESSAGES → Toast ---- */
const flashEl = document.getElementById('flash-data');
if (flashEl) {
  const msg = flashEl.dataset.msg;
  const type = flashEl.dataset.type || 'success';
  if (msg) showToast(msg, type);
}

/* ---- ADMIN: inline status update ---- */
window.updateOrderStatus = async function (orderId, status, btn) {
  btn.disabled = true;
  const res = await fetch('/bainhom/admin/api/orders.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_id: orderId, status })
  });
  const data = await res.json();
  if (data.success) { showToast('Cập nhật trạng thái thành công!'); setTimeout(() => location.reload(), 800); }
  else { showToast(data.message || 'Lỗi!', 'error'); btn.disabled = false; }
};

/* ================================================================
   MOBILE UI ENHANCEMENTS
   ================================================================ */

/* ---- HAMBURGER MENU TOGGLE ---- */
(function () {
  const btn = document.getElementById('hamburger-toggle');
  const drawer = document.getElementById('mobile-nav-drawer');
  const overlay = document.getElementById('mobile-nav-overlay');
  const closeBtn = document.getElementById('mobile-nav-close');

  if (!btn || !drawer || !overlay) return;

  function openNav() {
    drawer.classList.add('open');
    overlay.classList.add('open');
    btn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeNav() {
    drawer.classList.remove('open');
    overlay.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', openNav);
  overlay.addEventListener('click', closeNav);
  if (closeBtn) closeBtn.addEventListener('click', closeNav);

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('open')) closeNav();
  });
})();

/* ---- MOBILE SEARCH TOGGLE ---- */
(function () {
  const btn = document.getElementById('mobile-search-toggle');
  const bar = document.getElementById('mobile-search-bar');
  if (!btn || !bar) return;

  btn.addEventListener('click', () => {
    bar.classList.toggle('open');
    if (bar.classList.contains('open')) {
      const input = bar.querySelector('input');
      if (input) input.focus();
    }
  });
})();

/* ---- PASSWORD VISIBILITY TOGGLE ---- */
document.querySelectorAll('.password-toggle').forEach(btn => {
  btn.addEventListener('click', function () {
    const targetId = this.dataset.target;
    const input = document.getElementById(targetId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    // Swap icon
    const icon = this.querySelector('[data-lucide]');
    if (icon) {
      icon.setAttribute('data-lucide', isPassword ? 'eye-off' : 'eye');
      if (window.lucide) lucide.createIcons();
    }
  });
});

/* ---- ADMIN SIDEBAR TOGGLE (Mobile) ---- */
(function () {
  const btn = document.getElementById('admin-hamburger');
  const sidebar = document.querySelector('.admin-sidebar');
  const backdrop = document.getElementById('admin-sidebar-backdrop');

  if (!btn || !sidebar) return;

  function toggle() {
    sidebar.classList.toggle('open');
    if (backdrop) backdrop.classList.toggle('open');
    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
  }

  btn.addEventListener('click', toggle);
  if (backdrop) backdrop.addEventListener('click', toggle);

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) toggle();
  });
})();

/* ================================================================
   SMOOTH PAGE TRANSITIONS
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
  // Apply enter animation class when DOM is ready
  document.body.classList.add('page-enter');
  
  // Cleanup animation classes after it finishes
  setTimeout(() => {
    document.body.classList.remove('page-enter');
  }, 400);
});

document.addEventListener('click', (e) => {
  const link = e.target.closest('a');
  if (!link) return;

  // Validate internal link
  const isInternal = link.host === window.location.host;
  const isHash = link.hash && link.pathname === window.location.pathname;
  const isNewTab = link.target === '_blank';
  const isDownload = link.hasAttribute('download');
  const hasModifier = e.ctrlKey || e.metaKey || e.shiftKey || e.altKey;
  const isJsAction = link.href.startsWith('javascript:');

  if (isInternal && !isHash && !isNewTab && !isDownload && !hasModifier && !isJsAction) {
    e.preventDefault();
    const targetUrl = link.href;

    // Trigger exit animation
    document.body.classList.add('page-leave');

    // Navigate just before the animation fully completes for a snappy feel
    setTimeout(() => {
      window.location.href = targetUrl;
    }, 220); 
  }
});

// Handle Safari/BFCache back/forward button restores
window.addEventListener('pageshow', (e) => {
  if (e.persisted) {
    document.body.classList.remove('page-leave');
    document.body.classList.add('page-enter');
    setTimeout(() => {
      document.body.classList.remove('page-enter');
    }, 400);
  }
});

/* ---- AJAX LIVE SEARCH ---- */
document.addEventListener('DOMContentLoaded', () => {
  const initLiveSearch = (inputId, suggestionsId) => {
    const input = document.getElementById(inputId);
    const suggestions = document.getElementById(suggestionsId);
    if (!input || !suggestions) return;

    let debounceTimer;

    input.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      const query = input.value.trim();

      if (query.length < 2) {
        suggestions.innerHTML = '';
        suggestions.style.display = 'none';
        return;
      }

      debounceTimer = setTimeout(async () => {
        try {
          const res = await fetch(`/bainhom/api/search.php?q=${encodeURIComponent(query)}`);
          const products = await res.json();

          if (products.length === 0) {
            suggestions.innerHTML = `
              <div style="padding: 14px; text-align: center; color: #94a3b8; font-size: 13.5px;">
                Không tìm thấy kết quả nào
              </div>
            `;
          } else {
            suggestions.innerHTML = products.map(p => `
              <a href="/bainhom/product_detail.php?id=${p.id}" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-decoration: none; transition: background 0.15s ease;">
                <div style="width: 38px; height: 38px; background: #f8fafc; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                  ${p.image_url ? `<img src="${p.image_url}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" alt="${p.name}">` : p.image_emoji}
                </div>
                <div style="flex: 1; min-width: 0;">
                  <strong style="font-size: 13.5px; color: #0f172a; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700;">${p.name}</strong>
                  <span style="font-size: 11px; color: #64748b;">${p.brand}</span>
                </div>
                <span style="font-size: 13.5px; font-weight: 800; color: #dc2626; white-space: nowrap;">${p.price_formatted}</span>
              </a>
            `).join('');

            // Hover effect on items
            suggestions.querySelectorAll('a').forEach(item => {
              item.addEventListener('mouseenter', () => item.style.backgroundColor = '#f8fafc');
              item.addEventListener('mouseleave', () => item.style.backgroundColor = 'transparent');
            });
          }
          suggestions.style.display = 'block';
        } catch (error) {
          console.error('Lỗi search:', error);
        }
      }, 300);
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
      }
    });
  };

  initLiveSearch('search-input', 'search-suggestions');
  initLiveSearch('mobile-search-input', 'mobile-search-suggestions');
});
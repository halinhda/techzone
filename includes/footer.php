<?php // includes/footer.php ?>
</main>

<!-- TOAST CONTAINER (JS populated) -->
<div id="toast-container" class="toast-container"></div>

<!-- Mobile Bottom Navigation -->
<?php if (!isset($user) || !$user || $user['role'] !== 'admin'): ?>
<nav class="mobile-bottom-nav" aria-label="Menu di động">
  <div class="mobile-bottom-nav-inner">
    <a href="/bainhom/index.php" class="bottom-nav-item <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && !isset($_GET['page'])) ? 'active' : '' ?>">
      <i data-lucide="home"></i>
      <span>Trang chủ</span>
    </a>
    <a href="/bainhom/views/cart.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'cart.php' ? 'active' : '' ?>">
      <i data-lucide="shopping-cart"></i>
      <span>Giỏ hàng</span>
      <?php if (isset($cartCount) && $cartCount > 0): ?>
        <span class="bottom-nav-badge"><?= $cartCount ?></span>
      <?php endif; ?>
    </a>
    <?php if (isset($user) && $user): ?>
      <a href="/bainhom/views/my_orders.php" class="bottom-nav-item <?= strpos($_SERVER['PHP_SELF'], 'my_orders.php') !== false ? 'active' : '' ?>">
        <i data-lucide="package"></i>
        <span>Đơn hàng</span>
      </a>
    <?php endif; ?>
    <a href="/bainhom/views/support.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : '' ?>">
      <i data-lucide="help-circle"></i>
      <span>Hỗ trợ</span>
    </a>
    <?php if (!isset($user) || !$user): ?>
      <a href="/bainhom/controllers/auth.php" class="bottom-nav-item">
        <i data-lucide="log-in"></i>
        <span>Đăng nhập</span>
      </a>
    <?php endif; ?>
  </div>
</nav>
<?php endif; ?>

<footer id="trade-footer" class="main-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <span class="logo-icon"><i data-lucide="cpu"></i></span>
      <div>
        <h1 class="brand-name">TechZone</h1>
        <p class="brand-slogan">Thiết bị công nghệ chính hãng chất lượng cao</p>
      </div>
    </div>
    <nav class="footer-nav" aria-label="Menu footer">
      <a href="/bainhom/index.php">Cửa hàng</a>
      <a href="/bainhom/views/cart.php">Giỏ Hàng</a>
      <a href="/bainhom/views/my_orders.php">Tra đơn đặt</a>
      <a href="/bainhom/views/support.php">Hỗ trợ</a>
    </nav>
    <span>Hotline: <a href="tel:0909123456">0909 123 456</a></span>
    <p class="footer-copy">© 2026 TechZone. All rights reserved.</p>
  </div>
</footer>

<script>
  function toggleTradePanel() {
    const panel = document.getElementById('trade-panel');
    if (!panel) return;
    panel.hidden = !panel.hidden;
  }
</script>

<script>lucide.createIcons();</script>
<script src="/bainhom/main.js"></script>

<!-- Floating Support Buttons Widget -->
<div class="support-widget">
  <!-- Gợi ý cần hỗ trợ inbox -->
  <div class="support-bubble" id="supportBubble">
    <span class="support-bubble-text">Cần hỗ trợ? Inbox ngay!</span>
    <button class="support-bubble-close" onclick="dismissSupportBubble(event)" aria-label="Đóng gợi ý">&times;</button>
  </div>
  
  <div class="support-buttons-list">
    <!-- Zalo Button -->
    <a href="https://zalo.me/84867069038" target="_blank" rel="noopener noreferrer" class="support-btn support-zalo" title="Chat qua Zalo">
      <svg fill="currentColor" role="img" viewBox="0 0 24 24" width="34" height="34" xmlns="http://www.w3.org/2000/svg">
        <title>Zalo</title>
        <path d="M12.49 10.2722v-.4496h1.3467v6.3218h-.7704a.576.576 0 01-.5763-.5729l-.0006.0005a3.273 3.273 0 01-1.9372.6321c-1.8138 0-3.2844-1.4697-3.2844-3.2823 0-1.8125 1.4706-3.2822 3.2844-3.2822a3.273 3.273 0 011.9372.6321l.0006.0005zM6.9188 7.7896v.205c0 .3823-.051.6944-.2995 1.0605l-.03.0343c-.0542.0615-.1815.206-.2421.2843L2.024 14.8h4.8948v.7682a.5764.5764 0 01-.5767.5761H0v-.3622c0-.4436.1102-.6414.2495-.8476L4.8582 9.23H.1922V7.7896h6.7266zm8.5513 8.3548a.4805.4805 0 01-.4803-.4798v-7.875h1.4416v8.3548H15.47zM20.6934 9.6C22.52 9.6 24 11.0807 24 12.9044c0 1.8252-1.4801 3.306-3.3066 3.306-1.8264 0-3.3066-1.4808-3.3066-3.306 0-1.8237 1.4802-3.3044 3.3066-3.3044zm-10.1412 5.253c1.0675 0 1.9324-.8645 1.9324-1.9312 0-1.065-.865-1.9295-1.9324-1.9295s-1.9324.8644-1.9324 1.9295c0 1.0667.865 1.9312 1.9324 1.9312zm10.1412-.0033c1.0737 0 1.945-.8707 1.945-1.9453 0-1.073-.8713-1.9436-1.945-1.9436-1.0753 0-1.945.8706-1.945 1.9436 0 1.0746.8697 1.9453 1.945 1.9453z"/>
      </svg>
    </a>

    <!-- Messenger Button -->
    <a href="https://www.facebook.com/profile.php?id=61590333485176" target="_blank" rel="noopener noreferrer" class="support-btn support-messenger" title="Chat qua Messenger">
      <svg viewBox="0 0 256 256" width="50" height="50" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
        <defs>
          <radialGradient id="messengerGrad" cx="19.247%" cy="99.465%" r="108.96%" fx="19.247%" fy="99.465%">
            <stop offset="0%" stop-color="#09F"/>
            <stop offset="60.975%" stop-color="#A033FF"/>
            <stop offset="93.482%" stop-color="#FF5280"/>
            <stop offset="100%" stop-color="#FF7061"/>
          </radialGradient>
        </defs>
        <path fill="url(#messengerGrad)" d="M128 0C55.894 0 0 52.818 0 124.16c0 37.317 15.293 69.562 40.2 91.835 2.09 1.871 3.352 4.493 3.438 7.298l.697 22.77c.223 7.262 7.724 11.988 14.37 9.054L84.111 243.9a10.218 10.218 0 0 1 6.837-.501c11.675 3.21 24.1 4.92 37.052 4.92 72.106 0 128-52.818 128-124.16S200.106 0 128 0Z"/>
        <path fill="#FFF" d="m51.137 160.47 37.6-59.653c5.98-9.49 18.788-11.853 27.762-5.123l29.905 22.43a7.68 7.68 0 0 0 9.252-.027l40.388-30.652c5.39-4.091 12.428 2.36 8.82 8.085l-37.6 59.654c-5.981 9.489-18.79 11.852-27.763 5.122l-29.906-22.43a7.68 7.68 0 0 0-9.25.027l-40.39 30.652c-5.39 4.09-12.427-2.36-8.818-8.085Z"/>
      </svg>
    </a>

    <!-- WhatsApp Button -->
    <a href="https://wa.me/qr/VL4ZKXLMLAWOA1" target="_blank" rel="noopener noreferrer" class="support-btn support-whatsapp" title="Chat qua WhatsApp">
      <svg viewBox="0 0 360 362" width="50" height="50" xmlns="http://www.w3.org/2000/svg">
        <circle cx="180" cy="181" r="150" fill="#ffffff" />
        <path fill="#25D366" fill-rule="evenodd" d="M307.546 52.566C273.709 18.684 228.706.017 180.756 0 81.951 0 1.538 80.404 1.504 179.235c-.017 31.594 8.242 62.432 23.928 89.609L0 361.736l95.024-24.925c26.179 14.285 55.659 21.805 85.655 21.814h.077c98.788 0 179.21-80.413 179.244-179.244.017-47.898-18.608-92.926-52.454-126.807v-.008Zm-126.79 275.788h-.06c-26.73-.008-52.952-7.194-75.831-20.765l-5.44-3.231-56.391 14.791 15.05-54.981-3.542-5.638c-14.912-23.721-22.793-51.139-22.776-79.286.035-82.14 66.867-148.973 149.051-148.973 39.793.017 77.198 15.53 105.328 43.695 28.131 28.157 43.61 65.596 43.593 105.398-.035 82.149-66.867 148.982-148.982 148.982v.008Zm81.719-111.577c-4.478-2.243-26.497-13.073-30.606-14.568-4.108-1.496-7.09-2.243-10.073 2.243-2.982 4.487-11.568 14.577-14.181 17.559-2.613 2.991-5.226 3.361-9.704 1.117-4.477-2.243-18.908-6.97-36.02-22.226-13.313-11.878-22.304-26.54-24.916-31.027-2.613-4.486-.275-6.91 1.959-9.136 2.011-2.011 4.478-5.234 6.721-7.847 2.244-2.613 2.983-4.486 4.478-7.469 1.496-2.991.748-5.603-.369-7.847-1.118-2.243-10.073-24.289-13.812-33.253-3.636-8.732-7.331-7.546-10.073-7.692-2.613-.13-5.595-.155-8.586-.155-2.991 0-7.839 1.118-11.947 5.604-4.108 4.486-15.677 15.324-15.677 37.361s16.047 43.344 18.29 46.335c2.243 2.991 31.585 48.225 76.51 67.632 10.684 4.615 19.029 7.374 25.535 9.437 10.727 3.412 20.49 2.931 28.208 1.779 8.604-1.289 26.498-10.838 30.228-21.298 3.73-10.46 3.73-19.433 2.613-21.298-1.117-1.865-4.108-2.991-8.586-5.234l.008-.017Z" clip-rule="evenodd"/>
      </svg>
    </a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (localStorage.getItem('support_bubble_dismissed') === 'true') {
    const bubble = document.getElementById('supportBubble');
    if (bubble) {
      bubble.style.display = 'none';
    }
  }
});

function dismissSupportBubble(event) {
  event.preventDefault();
  event.stopPropagation();
  const bubble = document.getElementById('supportBubble');
  if (bubble) {
    bubble.style.opacity = '0';
    bubble.style.transform = 'translateX(12px) scale(0.9)';
    bubble.style.transition = 'opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
    setTimeout(() => {
      bubble.style.display = 'none';
    }, 350);
  }
  localStorage.setItem('support_bubble_dismissed', 'true');
}
</script>
</body>

</html>
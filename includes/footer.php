<?php // includes/footer.php ?>
</main>

<!-- TOAST CONTAINER (JS populated) -->
<div id="toast-container" class="toast-container"></div>

<footer id="trade-footer" class="main-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <span class="logo-icon"><i data-lucide="cpu"></i></span>
      <div>
        <h1 class="brand-name">TechZone</h1>
        <p class="brand-slogan">Thiết bị công nghệ chính hãng chất lượng cao</p>
      </div>
    </div>
    <nav class="footer-nav">
      <a href="/bainhom/index.php">Cửa hàng</a>
      <a href="/bainhom/views/cart.php">Giỏ Hàng</a>
      <a href="/bainhom/views/my_orders.php">Tra đơn đặt</a>
      <a href="/bainhom/views/support.php">Hỗ trợ</a>
    </nav>
    <span>📞 Hotline: <a href="tel:0909123456">0909 123 456</a></span>
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
</body>

</html>
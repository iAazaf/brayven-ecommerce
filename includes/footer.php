<?php
/** BRAYVEN — global footer. Closes <main> opened by header.php. */
$footerUser = current_user();
?>
</main>

<footer class="site-footer pt-5 pb-4">
  <div class="container">
    <div class="row g-4 g-lg-5 pb-4">
      <div class="col-lg-4">
        <span class="brand-mark brand-mark--sm text-white d-inline-block mb-3">BRAYVEN</span>
        <p class="mb-4" style="max-width:34ch; font-size:0.88rem;">
          Define Your Own Standard. Considered essentials and elevated everyday wear,
          produced in limited runs from honest materials.
        </p>
        <div class="footer-social">
          <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="https://pinterest.com" target="_blank" rel="noopener" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
          <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>

      <div class="col-6 col-md-3 col-lg-2">
        <h2 class="footer-heading">Shop</h2>
        <ul class="list-unstyled footer-list">
          <li><a href="<?= e(url('shop.php')) ?>">All Products</a></li>
          <li><a href="<?= e(url('shop.php?department=men')) ?>">Men</a></li>
          <li><a href="<?= e(url('shop.php?department=women')) ?>">Women</a></li>
          <li><a href="<?= e(url('shop.php?new=1')) ?>">New Arrivals</a></li>
          <li><a href="<?= e(url('shop.php?sale=1')) ?>">Sale</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-3 col-lg-2">
        <h2 class="footer-heading">Support</h2>
        <ul class="list-unstyled footer-list">
          <li><a href="<?= e(url('contact.php')) ?>">Contact Us</a></li>
          <li><a href="<?= e(url('faq.php')) ?>">FAQ</a></li>
          <li><a href="<?= e(url('orders.php')) ?>">Track My Order</a></li>
          <li><a href="<?= e(url('cart.php')) ?>">My Cart</a></li>
          <li><a href="<?= e(url('wishlist.php')) ?>">Wishlist</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-3 col-lg-2">
        <h2 class="footer-heading">Company</h2>
        <ul class="list-unstyled footer-list">
          <li><a href="<?= e(url('about.php')) ?>">Our Story</a></li>
          <li><a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a></li>
          <li><a href="<?= e(url('terms.php')) ?>">Terms &amp; Conditions</a></li>
        </ul>
      </div>

      <div class="col-6 col-md-3 col-lg-2">
        <h2 class="footer-heading">Account</h2>
        <ul class="list-unstyled footer-list">
          <?php if ($footerUser): ?>
            <li><a href="<?= e(url('profile.php')) ?>">My Profile</a></li>
            <li><a href="<?= e(url('orders.php')) ?>">Order History</a></li>
            <?php if ($footerUser['role'] === 'admin'): ?>
              <li><a href="<?= e(url('admin/index.php')) ?>">Admin Panel</a></li>
            <?php endif; ?>
          <?php else: ?>
            <li><a href="<?= e(url('login.php')) ?>">Sign In</a></li>
            <li><a href="<?= e(url('register.php')) ?>">Create Account</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 pt-4">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved. <span class="mx-1">·</span> Demo store — sample products only.</span>
      <span><i class="fa-solid fa-hand-holding-dollar me-2"></i>Cash on Delivery available at checkout</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>

<?php
/**
 * BRAYVEN — Homepage.
 */
require_once __DIR__ . '/includes/init.php';

/* Newsletter subscription */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'newsletter') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('danger', 'Please enter a valid email address.');
    } else {
        try {
            $stmt = db()->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)');
            $stmt->execute([$email]);
            flash_set('success', 'You are on the list. Welcome to BRAYVEN.');
        } catch (PDOException $e) {
            error_log('Newsletter insert failed: ' . $e->getMessage());
            flash_set('danger', 'Something went wrong. Please try again.');
        }
    }
    redirect('index.php#newsletter');
}

$pdo = db();

$newArrivals = $pdo->query(
    'SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.is_new, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_new = 1
     ORDER BY p.created_at DESC LIMIT 6'
)->fetchAll();

$bestSellers = $pdo->query(
    'SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.is_new, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND p.is_featured = 1
     ORDER BY RAND() LIMIT 6'
)->fetchAll();

/* Alt images for hover swap (featured products carry a -2 detail shot). */
$altImages = [];
foreach (array_merge($newArrivals, $bestSellers) as $row) {
    $fs = PROJECT_ROOT . '/assets/images/products/' . $row['slug'] . '-2.jpg';
    $altImages[$row['id']] = is_file($fs) ? $row['slug'] . '-2.jpg' : null;
}

$wishlistIds = current_user() ? wishlist_product_ids((int)current_user()['id']) : [];

$pageTitle = 'Define Your Own Standard.';
require __DIR__ . '/includes/header.php';
?>

<!-- ============ Hero ============ -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7 hero-copy">
        <span class="kicker kicker--light d-block mb-4">Autumn — Winter 2026 · Vol. 04</span>
        <h1 class="display-xl mb-4">Define Your<br>Own Standard.</h1>
        <p class="lead-line mb-5">Considered essentials. Elevated everyday wear.<br>
          Cut with intention, made to outlast seasons — not trends.</p>
        <div class="d-flex flex-column flex-sm-row gap-3">
          <a class="btn btn-ink" href="<?= e(url('shop.php')) ?>" style="background:#F7F6F2;color:#171717;border-color:#F7F6F2;">Shop Collection</a>
          <a class="btn btn-outline-light-bray" href="<?= e(url('shop.php?new=1')) ?>">Explore New Arrivals</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-md-block">
        <div class="hero-collage">
          <img class="hc-a" src="<?= e(url('assets/images/products/editorial-hero-a.jpg')) ?>" alt="BRAYVEN wool overcoat campaign look">
          <img class="hc-b" src="<?= e(url('assets/images/products/editorial-hero-b.jpg')) ?>" alt="BRAYVEN oxford shirt campaign look">
          <img class="hc-c" src="<?= e(url('assets/images/products/editorial-hero-c.jpg')) ?>" alt="BRAYVEN hoodie campaign look">
          <span class="hero-caption">The Standard Edit — Nº 04</span>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-marquee" aria-hidden="true">
    <span>Menswear</span><span>·</span><span>Womenswear</span><span>·</span><span>Accessories</span><span>·</span><span>Limited Runs</span><span>·</span><span>Define Your Own Standard</span><span>·</span><span>Menswear</span><span>·</span><span>Womenswear</span><span>·</span><span>Accessories</span><span>·</span><span>Limited Runs</span><span>·</span>
  </div>
</section>

<!-- ============ New Arrivals ============ -->
<section class="section" id="new-arrivals">
  <div class="container">
    <div class="section-head d-flex flex-wrap align-items-end justify-content-between gap-3">
      <div>
        <span class="kicker">Just Landed</span>
        <h2 class="display-lg mb-0">New Arrivals</h2>
      </div>
      <a class="link-more" href="<?= e(url('shop.php?new=1')) ?>">View all new arrivals</a>
    </div>
    <div class="row g-4 gx-lg-4 gy-5">
      <?php foreach ($newArrivals as $p): ?>
        <div class="col-6 col-md-4">
          <?php $altImage = $altImages[$p['id']] ?? null; include __DIR__ . '/includes/product-card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ Category banners ============ -->
<section class="section pt-0">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-6">
        <a class="cat-banner" href="<?= e(url('shop.php?department=men')) ?>">
          <img src="<?= e(url('assets/images/products/banner-men.jpg')) ?>" alt="Shop menswear — overshirt editorial">
          <span class="cb-label">
            <span class="kicker" style="color:rgba(255,255,255,0.7);">12 Styles</span>
            <span class="display-md d-block">For Men</span>
            <span class="overline d-block mt-2" style="color:var(--bray-accent);">Shop Menswear <i class="fa-solid fa-arrow-right-long ms-1"></i></span>
          </span>
        </a>
      </div>
      <div class="col-md-6">
        <a class="cat-banner" href="<?= e(url('shop.php?department=women')) ?>">
          <img src="<?= e(url('assets/images/products/banner-women.jpg')) ?>" alt="Shop womenswear — slip dress editorial">
          <span class="cb-label">
            <span class="kicker" style="color:rgba(255,255,255,0.7);">Essentials &amp; Layers</span>
            <span class="display-md d-block">For Women</span>
            <span class="overline d-block mt-2" style="color:var(--bray-accent);">Shop Womenswear <i class="fa-solid fa-arrow-right-long ms-1"></i></span>
          </span>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ============ Best Sellers ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="section-head d-flex flex-wrap align-items-end justify-content-between gap-3">
      <div>
        <span class="kicker kicker--light">Most Wanted</span>
        <h2 class="display-lg mb-0">Best Sellers</h2>
      </div>
      <a class="link-more" href="<?= e(url('shop.php?sort=price_desc')) ?>">Shop all best sellers</a>
    </div>
    <div class="row g-4 gx-lg-4 gy-5">
      <?php foreach ($bestSellers as $p): ?>
        <div class="col-6 col-md-4">
          <div class="product-card">
            <?php
              $altImage = $altImages[$p['id']] ?? null;
              $cardBg = '#F7F6F2';
              include __DIR__ . '/includes/product-card.php';
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ Seasonal campaign ============ -->
<section class="section">
  <div class="container">
    <div class="row g-0 align-items-stretch campaign-panel bg-card">
      <div class="col-lg-6">
        <img src="<?= e(url('assets/images/products/campaign.jpg')) ?>" alt="Autumn Winter 2026 campaign — bomber jacket look" style="height:100%;">
      </div>
      <div class="col-lg-6 d-flex align-items-center">
        <div class="p-4 p-lg-5">
          <span class="kicker d-block mb-3">Seasonal Campaign</span>
          <h2 class="display-lg mb-3">The Quiet Layer.<br>AW26 in Motion.</h2>
          <p class="text-muted-bray mb-4" style="max-width:48ch;">
            Wool that holds a line. Denim that earns its fades. Outerwear built on
            workwear logic and finished with restraint. Thirty-four considered pieces —
            nothing more than the season asks for.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-ink" href="<?= e(url('category.php?slug=jackets')) ?>">Shop Outerwear</a>
            <a class="btn btn-outline-ink" href="<?= e(url('about.php')) ?>">Our Philosophy</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Philosophy ============ -->
<section class="section section-tight bg-white" style="border-top:1px solid var(--bray-border); border-bottom:1px solid var(--bray-border);">
  <div class="container">
    <div class="section-head text-center">
      <span class="kicker">House Code</span>
      <h2 class="display-lg mb-0">Three Rules We Keep</h2>
    </div>
    <div class="row g-5 text-center text-md-start">
      <div class="col-md-4">
        <div class="philosophy-num mb-3">01</div>
        <h3 class="h5 mb-2">Considered, not trendy</h3>
        <p class="text-muted-bray mb-0">Every piece is designed to be worn for years. If it only matters this season, it does not carry our name.</p>
      </div>
      <div class="col-md-4">
        <div class="philosophy-num mb-3">02</div>
        <h3 class="h5 mb-2">Honest materials</h3>
        <p class="text-muted-bray mb-0">Long-staple cotton, European flax, full-grain leather. We name the fabric because the fabric does the work.</p>
      </div>
      <div class="col-md-4">
        <div class="philosophy-num mb-3">03</div>
        <h3 class="h5 mb-2">Made in limited runs</h3>
        <p class="text-muted-bray mb-0">Small batches, no dead stock, no landfill discounts. When a size is gone, it returns only if it deserves to.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ Newsletter ============ -->
<section class="section" id="newsletter">
  <div class="container">
    <div class="newsletter-box text-center">
      <span class="kicker d-block mb-3">The Correspondence</span>
      <h2 class="display-md mb-2">First to know, first to wear.</h2>
      <p class="text-muted-bray mb-4" style="max-width:46ch; margin-inline:auto;">
        New drops, restocks and editorial notes — one considered email a month. No noise.
      </p>
      <form method="post" action="<?= e(url('index.php')) ?>#newsletter" class="row justify-content-center g-2" data-validate-email style="max-width:480px; margin-inline:auto;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="newsletter">
        <div class="col-12 col-sm-8">
          <label class="visually-hidden" for="newsletterEmail">Email address</label>
          <input type="email" class="form-control" id="newsletterEmail" name="email" placeholder="your@email.com" required>
        </div>
        <div class="col-12 col-sm-4">
          <button type="submit" class="btn btn-ink w-100">Subscribe</button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- ============ Instagram gallery ============ -->
<section class="section pt-0">
  <div class="container">
    <div class="section-head text-center">
      <span class="kicker">@brayven</span>
      <h2 class="display-lg mb-0">In the Wild</h2>
    </div>
    <div class="row g-2 g-md-3">
      <?php foreach (['essential-crew-tee-1', 'midi-slip-dress-1', 'straight-leg-selvedge-jeans-1', 'classic-pullover-hoodie-1', 'canvas-weekender-bag-1', 'linen-camp-collar-shirt-1'] as $tile): ?>
        <div class="col-4 col-md-2">
          <a class="insta-tile" href="https://instagram.com" target="_blank" rel="noopener">
            <img src="<?= e(url('assets/images/products/insta-' . $tile . '.jpg')) ?>" alt="BRAYVEN community look — <?= e(str_replace('-', ' ', $tile)) ?>" loading="lazy">
            <span class="it-handle"><i class="fa-brands fa-instagram me-2"></i>@brayven</span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

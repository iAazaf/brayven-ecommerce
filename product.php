<?php
/** BRAYVEN — Product detail. */
require_once __DIR__ . '/includes/init.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$stmt = db()->prepare(
    'SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.slug = ? AND p.is_active = 1 LIMIT 1'
);
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state">'
        . '<h1 class="display-md mb-2">This piece is no longer available</h1>'
        . '<p class="text-muted-bray mb-4">It may have sold out or been retired from the collection.</p>'
        . '<a class="btn btn-ink" href="' . e(url('shop.php')) . '">Continue shopping</a></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* Gallery */
$galleryStmt = db()->prepare('SELECT image FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
$galleryStmt->execute([(int)$product['id']]);
$gallery = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);
if (!$gallery) {
    $gallery = [$product['image']];
}

$onSale = $product['sale_price'] !== null && (float)$product['sale_price'] > 0 && (float)$product['sale_price'] < (float)$product['price'];
$effective = $onSale ? (float)$product['sale_price'] : (float)$product['price'];
$stock = (int)$product['stock'];
$sizes = array_values(array_filter(array_map('trim', explode(',', $product['sizes']))));
$colors = array_values(array_filter(array_map('trim', explode(',', $product['colors']))));

/* Related products */
$relStmt = db()->prepare(
    'SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.is_new, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? AND p.is_active = 1
     ORDER BY p.is_featured DESC, RAND() LIMIT 4'
);
$relStmt->execute([(int)$product['category_id'], (int)$product['id']]);
$related = $relStmt->fetchAll();

$wishlistIds = current_user() ? wishlist_product_ids((int)current_user()['id']) : [];

$pageTitle = $product['name'];
$pageDesc  = mb_substr(trim(html_entity_decode(strip_tags($product['description']))), 0, 155);
$activeNav = in_array($product['department'] ?? '', ['men', 'women'], true) ? $product['department'] : 'shop';
require __DIR__ . '/includes/header.php';
$returnUrl = url('product.php?slug=' . rawurlencode($product['slug']));
?>

<section class="page-hero pb-0">
  <div class="container">
    <div class="breadcrumb-bray">
      <a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span>
      <a href="<?= e(url('category.php?slug=' . rawurlencode($product['category_slug']))) ?>"><?= e($product['category_name']) ?></a><span class="sep">/</span>
      <span><?= e($product['name']) ?></span>
    </div>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-4 g-lg-5">

      <!-- Gallery -->
      <div class="col-lg-6">
        <div class="pd-gallery-main">
          <img id="pdMainImage" src="<?= e(product_image_url($gallery[0])) ?>" alt="<?= e($product['name']) ?> — main view">
        </div>
        <?php if (count($gallery) > 1): ?>
          <div class="pd-thumbs">
            <?php foreach ($gallery as $i => $img): ?>
              <button type="button" class="pd-thumb <?= $i === 0 ? 'active' : '' ?>" data-image="<?= e(product_image_url($img)) ?>" aria-label="View image <?= $i + 1 ?>">
                <img src="<?= e(product_image_url($img)) ?>" alt="">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Details -->
      <div class="col-lg-6">
        <div class="ps-lg-4">
          <span class="kicker"><?= e($product['category_name']) ?></span>
          <h1 class="display-md mt-2 mb-3"><?= e($product['name']) ?></h1>

          <p class="pd-price">
            <?= e(money($effective)) ?>
            <?php if ($onSale): ?>
              <span class="compare"><?= e(money($product['price'])) ?></span>
              <span class="sale">Save <?= e(money((float)$product['price'] - $effective)) ?></span>
            <?php endif; ?>
          </p>

          <p class="text-muted-bray" style="max-width:56ch;"><?= nl2br(e($product['description'])) ?></p>

          <?php if ($stock > 0 && $stock <= 5): ?>
            <p class="stock-note low mb-3"><i class="fa-solid fa-circle-exclamation me-1"></i>Only <?= $stock ?> left — limited run</p>
          <?php endif; ?>

          <form id="addToCartForm" method="post" action="<?= e(url('cart.php')) ?>" class="mt-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <input type="hidden" name="return" value="<?= e($returnUrl) ?>">

            <?php if ($sizes): ?>
              <div class="mb-3">
                <p class="filter-title mb-2">Size <span class="text-muted-bray fw-normal" style="text-transform:none;letter-spacing:0;">— select one</span></p>
                <input type="hidden" name="size" id="size" value="">
                <div class="d-flex flex-wrap gap-2" data-picker="size" role="radiogroup" aria-label="Select size">
                  <?php foreach ($sizes as $s): ?>
                    <button type="button" class="size-pill" data-value="<?= e($s) ?>" role="radio" aria-checked="false"><?= e($s) ?></button>
                  <?php endforeach; ?>
                </div>
                <div id="sizeError" class="mini-note text-danger mt-1"></div>
              </div>
            <?php else: ?>
              <input type="hidden" name="size" value="One Size">
            <?php endif; ?>

            <?php if ($colors): ?>
              <div class="mb-3">
                <p class="filter-title mb-2">Colour <span class="text-muted-bray fw-normal" style="text-transform:none;letter-spacing:0;">— select one</span></p>
                <input type="hidden" name="color" id="color" value="">
                <div class="d-flex flex-wrap gap-2 align-items-center" data-picker="color" role="radiogroup" aria-label="Select colour">
                  <?php foreach ($colors as $col): ?>
                    <button type="button" class="btn p-0 border-0 bg-transparent" data-value="<?= e($col) ?>" role="radio" aria-checked="false" title="<?= e($col) ?>" aria-label="<?= e($col) ?>">
                      <span class="color-dot" style="background: <?= e(color_hex($col)) ?>;"></span>
                    </button>
                  <?php endforeach; ?>
                </div>
                <div id="colorError" class="mini-note text-danger mt-1"></div>
              </div>
            <?php else: ?>
              <input type="hidden" name="color" value="Default">
            <?php endif; ?>

            <div class="row g-3 align-items-end mb-4">
              <div class="col-6 col-sm-4">
                <label class="form-label" for="qty">Quantity</label>
                <div class="input-group qty-input" data-qty>
                  <button class="btn btn-outline-secondary" type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                  <input type="number" class="form-control" id="qty" name="qty" value="1" min="1" max="<?= max(1, $stock) ?>" inputmode="numeric">
                  <button class="btn btn-outline-secondary" type="button" data-step="1" aria-label="Increase quantity">+</button>
                </div>
              </div>
              <div class="col-6 col-sm-8">
                <p class="stock-note <?= $stock > 5 ? 'in' : ($stock > 0 ? 'low' : 'out') ?> mb-0">
                  <?php if ($stock > 0): ?><i class="fa-solid fa-circle-check me-1"></i>In stock — ships in 1–2 days
                  <?php else: ?><i class="fa-solid fa-circle-xmark me-1"></i>Sold out<?php endif; ?>
                </p>
              </div>
            </div>

            <?php if ($stock > 0): ?>
              <button type="submit" class="btn btn-ink w-100 mb-2">
                <i class="fa-solid fa-bag-shopping me-2"></i>Add to Cart — <?= e(money($effective)) ?>
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-outline-ink w-100 mb-2" disabled>Sold out</button>
            <?php endif; ?>
          </form>

          <form method="post" action="<?= e(url('wishlist.php')) ?>" class="mb-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
            <button type="submit" class="btn-quiet">
              <i class="fa-<?= in_array((int)$product['id'], $wishlistIds, true) ? 'solid' : 'regular' ?> fa-heart me-1"></i>
              <?= in_array((int)$product['id'], $wishlistIds, true) ? 'Saved to wishlist' : 'Add to wishlist' ?>
            </button>
          </form>

          <div class="accordion accordion-bray" id="pdInfo">
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdShip">Shipping &amp; Returns</button></h2>
              <div id="pdShip" class="accordion-collapse collapse" data-bs-parent="#pdInfo">
                <div class="accordion-body">Free standard shipping on orders over <?= e(money(FREE_SHIPPING_THRESHOLD)) ?>, otherwise <?= e(money(SHIPPING_FLAT)) ?> flat. Cash on Delivery available. 30-day returns on unworn pieces with tags attached.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdCare">Fabric &amp; Care</button></h2>
              <div id="pdCare" class="accordion-collapse collapse" data-bs-parent="#pdInfo">
                <div class="accordion-body">Machine wash cold with like colours, hang to dry, warm iron if needed. Wash denim sparingly — inside out, cold. Leather goods: wipe with a dry cloth and condition seasonally.</div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pdFit">Fit &amp; Sizing</button></h2>
              <div id="pdFit" class="accordion-collapse collapse" data-bs-parent="#pdInfo">
                <div class="accordion-body">True to size with a relaxed, considered drape. Between sizes? Take the smaller for a cleaner line. Model wears size M and is 6'1".</div>
              </div>
            </div>
          </div>

          <ul class="pd-info-list mt-4">
            <li><i class="fa-solid fa-tag"></i>Product code: <?= e($product['sku']) ?></li>
            <li><i class="fa-solid fa-layer-group"></i>Colours: <?= e(implode(', ', $colors) ?: '—') ?></li>
            <li><i class="fa-solid fa-rotate-left"></i>30-day returns, free exchanges</li>
          </ul>
        </div>
      </div>
    </div>

    <?php if ($related): ?>
      <div class="mt-5 pt-4 section-strap">
        <div class="section-head d-flex flex-wrap align-items-end justify-content-between gap-3">
          <div>
            <span class="kicker">Complete the Look</span>
            <h2 class="display-md mb-0">You May Also Like</h2>
          </div>
          <a class="link-more" href="<?= e(url('category.php?slug=' . rawurlencode($product['category_slug']))) ?>">More <?= e($product['category_name']) ?></a>
        </div>
        <div class="row g-4 gx-lg-4 gy-5">
          <?php foreach ($related as $p): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <?php $altImage = null; include __DIR__ . '/includes/product-card.php'; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

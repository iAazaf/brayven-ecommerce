<?php
/**
 * BRAYVEN — reusable product card partial.
 * Expects: $p (product row: slug, name, price, sale_price, image, is_new, stock, category_name)
 * Optional: $altImage (string|null), $wishlistIds (int[] of wishlisted product ids).
 */
$altImage   = $altImage ?? null;
$wishlistIds = $wishlistIds ?? [];
$onSale = $p['sale_price'] !== null && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price'];
$outOfStock = (int)$p['stock'] < 1;
$effective = $onSale ? (float)$p['sale_price'] : (float)$p['price'];
$discount = $onSale ? (int)round(100 - ($effective / (float)$p['price']) * 100) : 0;
$wishlisted = in_array((int)$p['id'], $wishlistIds, true);
?>
<article class="product-card">
  <div class="pc-media">
    <a href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>" aria-label="<?= e($p['name']) ?>">
      <img class="pc-img-main" src="<?= e(product_image_url($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
      <?php if ($altImage): ?>
        <img class="pc-img-alt" src="<?= e(product_image_url($altImage)) ?>" alt="" loading="lazy" aria-hidden="true">
      <?php endif; ?>
    </a>
    <div class="pc-badges">
      <?php if ($outOfStock): ?>
        <span class="pc-badge pc-badge--out">Sold out</span>
      <?php else: ?>
        <?php if ($onSale): ?><span class="pc-badge pc-badge--sale">−<?= $discount ?>%</span><?php endif; ?>
        <?php if ((int)$p['is_new'] === 1): ?><span class="pc-badge">New</span><?php endif; ?>
      <?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('wishlist.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle">
      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
      <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? url('shop.php')) ?>">
      <button class="pc-wishlist <?= $wishlisted ? 'active' : '' ?>" type="submit"
              aria-label="<?= $wishlisted ? 'Remove from wishlist' : 'Add to wishlist' ?>">
        <i class="fa-<?= $wishlisted ? 'solid' : 'regular' ?> fa-heart"></i>
      </button>
    </form>
  </div>
  <div class="pc-body">
    <span class="pc-cat"><?= e($p['category_name'] ?? '') ?></span>
    <h3 class="pc-name"><a href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>"><?= e($p['name']) ?></a></h3>
    <p class="pc-price mb-0">
      <?= e(money($effective)) ?>
      <?php if ($onSale): ?><span class="compare"><?= e(money($p['price'])) ?></span><span class="sale"></span><?php endif; ?>
    </p>
  </div>
</article>

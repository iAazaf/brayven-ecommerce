<?php
/** BRAYVEN — Shopping cart (session-backed, server-priced). */
require_once __DIR__ . '/includes/init.php';

/* ---------- POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $returnTo = (string)($_POST['return'] ?? '');
    $safeReturn = (str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//')) ? $returnTo : url('cart.php');

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $size = trim((string)($_POST['size'] ?? ''));
        $color = trim((string)($_POST['color'] ?? ''));

        $stmt = db()->prepare('SELECT id, name, sizes, colors, stock FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            flash_set('danger', 'That product is no longer available.');
        } elseif (!in_array($size, array_map('trim', explode(',', $product['sizes'])), true)) {
            flash_set('danger', 'Please choose a valid size.');
        } elseif (!in_array($color, array_map('trim', explode(',', $product['colors'])), true)) {
            flash_set('danger', 'Please choose a valid colour.');
        } elseif ($product['stock'] < 1) {
            flash_set('warning', e($product['name']) . ' is sold out.');
        } else {
            $qty = min($qty, (int)$product['stock']);
            cart_add($productId, $qty, $size, $color);
            flash_set('success', $product['name'] . ' added to your bag.');
        }
        redirect($safeReturn);
    }

    if ($action === 'update') {
        $key = (string)($_POST['key'] ?? '');
        $cart = &cart_ref();
        if (isset($cart[$key])) {
            $qty = (int)($_POST['qty'] ?? 1);
            if ($qty < 1) {
                unset($cart[$key]);
                flash_set('success', 'Item removed from your bag.');
            } else {
                $stmt = db()->prepare('SELECT stock FROM products WHERE id = ? AND is_active = 1');
                $stmt->execute([(int)$cart[$key]['product_id']]);
                $stock = (int)($stmt->fetchColumn() ?: 0);
                if ($stock < 1) {
                    unset($cart[$key]);
                    flash_set('warning', 'That item just sold out and was removed.');
                } else {
                    $cart[$key]['qty'] = min(max(1, $qty), $stock);
                    flash_set('success', 'Quantity updated.');
                }
            }
        }
        redirect('cart.php');
    }

    if ($action === 'remove') {
        $key = (string)($_POST['key'] ?? '');
        $cart = &cart_ref();
        if (isset($cart[$key])) {
            unset($cart[$key]);
            flash_set('success', 'Item removed from your bag.');
        }
        redirect('cart.php');
    }

    redirect('cart.php');
}

/* ---------- Render ---------- */
$cart = cart_details();
if ($cart['changed']) {
    flash_set('warning', 'Some items in your bag were adjusted to match current stock.');
}

$pageTitle = 'Shopping Bag';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>Shopping Bag</span></div>
    <h1 class="display-lg mb-0">Your Bag</h1>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <?php if (!$cart['items']): ?>
      <div class="empty-state bg-card">
        <div class="icon"><i class="fa-solid fa-bag-shopping"></i></div>
        <h2 class="display-md mb-2">Your bag is empty</h2>
        <p class="text-muted-bray mb-4">Considered essentials are waiting. Start with the new arrivals.</p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
          <a class="btn btn-ink" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
          <a class="btn btn-outline-ink" href="<?= e(url('shop.php?new=1')) ?>">See New Arrivals</a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4 g-lg-5">
        <div class="col-lg-8">
          <div class="bg-card p-3 p-md-4">
            <?php foreach ($cart['items'] as $item): ?>
              <div class="row g-3 align-items-center py-3 border-bottom">
                <div class="col-auto">
                  <a href="<?= e(url('product.php?slug=' . rawurlencode($item['slug']))) ?>">
                    <img src="<?= e(product_image_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="aspect-45" style="width:88px;">
                  </a>
                </div>
                <div class="col">
                  <p class="cart-line-title mb-1"><a href="<?= e(url('product.php?slug=' . rawurlencode($item['slug']))) ?>"><?= e($item['name']) ?></a></p>
                  <p class="cart-line-meta mb-2">Size <?= e($item['size']) ?> · <?= e($item['color']) ?> · <?= e($item['sku']) ?></p>
                  <div class="d-flex align-items-center gap-3">
                    <form method="post" action="<?= e(url('cart.php')) ?>" class="d-flex align-items-center gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="key" value="<?= e($item['key']) ?>">
                      <div class="input-group input-group-sm qty-input" data-qty>
                        <button class="btn btn-outline-secondary" type="button" data-step="-1" aria-label="Decrease quantity">−</button>
                        <input type="number" class="form-control" name="qty" id="qty-<?= e(md5($item['key'])) ?>" value="<?= (int)$item['qty'] ?>" min="1" max="<?= (int)$item['stock'] ?>" aria-label="Quantity">
                        <button class="btn btn-outline-secondary" type="button" data-step="1" aria-label="Increase quantity">+</button>
                      </div>
                      <button type="submit" class="btn-quiet">Update</button>
                    </form>
                    <form method="post" action="<?= e(url('cart.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="remove">
                      <input type="hidden" name="key" value="<?= e($item['key']) ?>">
                      <button type="submit" class="btn-quiet" style="color:#A55B3C;">Remove</button>
                    </form>
                  </div>
                </div>
                <div class="col-auto text-end">
                  <p class="fw-medium mb-0"><?= e(money($item['line_total'])) ?></p>
                  <?php if ($item['qty'] >= $item['stock']): ?><p class="mini-note mb-0">Max available</p><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3">
              <a class="btn btn-outline-ink btn-sm-bray" href="<?= e(url('shop.php')) ?>"><i class="fa-solid fa-arrow-left-long me-2"></i>Continue Shopping</a>
              <span class="mini-note"><i class="fa-solid fa-lock me-1"></i>Prices &amp; stock re-verified at checkout</span>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="summary-card">
            <h2 class="h5 mb-3">Order Summary</h2>
            <div class="summary-row"><span>Subtotal (<?= count($cart['items']) ?> item<?= count($cart['items']) === 1 ? '' : 's' ?>)</span><span><?= e(money($cart['subtotal'])) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : 'Free' ?></span></div>
            <?php if ($cart['shipping'] > 0): ?>
              <p class="free-ship-note mb-2"><i class="fa-solid fa-truck-fast me-1"></i>Add <?= e(money(FREE_SHIPPING_THRESHOLD - $cart['subtotal'])) ?> more for free shipping.</p>
            <?php endif; ?>
            <div class="summary-row total"><span>Total</span><span><?= e(money($cart['total'])) ?></span></div>
            <a class="btn btn-ink w-100 mt-3" href="<?= e(url('checkout.php')) ?>">Proceed to Checkout</a>
            <p class="mini-note text-center mt-3 mb-0"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Cash on Delivery available</p>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

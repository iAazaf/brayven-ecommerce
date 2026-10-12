<?php
/** BRAYVEN — Wishlist (DB-backed for signed-in customers). */
require_once __DIR__ . '/includes/init.php';

/* Toggle action can come from product cards / detail pages. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $returnTo = (string)($_POST['return'] ?? '');
    $user = current_user();
    if (!$user) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? url('wishlist.php');
        flash_set('warning', 'Sign in to save pieces to your wishlist.');
        redirect('login.php');
    }
    $stmt = db()->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$productId]);
    if ($stmt->fetch()) {
        $added = wishlist_toggle((int)$user['id'], $productId);
        flash_set($added ? 'success' : 'success', $added ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    }
    if (str_starts_with($returnTo, '/') && !str_starts_with($returnTo, '//')) {
        redirect($returnTo);
    }
    redirect('wishlist.php');
}

$user = current_user();
if (!$user) {
    $pageTitle = 'Wishlist';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="auth-wrap text-center">'
        . '<div class="empty-state bg-card"><div class="icon"><i class="fa-regular fa-heart"></i></div>'
        . '<h1 class="display-md mb-2">Save what you love</h1>'
        . '<p class="text-muted-bray mb-4">Sign in to build a wishlist that follows you across devices.</p>'
        . '<a class="btn btn-ink" href="' . e(url('login.php')) . '">Sign In</a> '
        . '<a class="btn btn-outline-ink" href="' . e(url('register.php')) . '">Create Account</a>'
        . '</div></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = db()->prepare(
    'SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.is_new, c.name AS category_name
     FROM wishlist_items wi
     JOIN wishlists w ON w.id = wi.wishlist_id
     JOIN products p ON p.id = wi.product_id
     JOIN categories c ON c.id = p.category_id
     WHERE w.user_id = ?
     ORDER BY wi.created_at DESC'
);
$stmt->execute([$user['id']]);
$items = $stmt->fetchAll();
$wishlistIds = array_map(fn($r) => (int)$r['id'], $items);

$pageTitle = 'Wishlist';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><a href="<?= e(url('profile.php')) ?>">My Account</a><span class="sep">/</span><span>Wishlist</span></div>
    <span class="kicker d-block mb-2">Saved Pieces</span>
    <h1 class="display-lg mb-0">Wishlist</h1>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <?php if (!$items): ?>
      <div class="empty-state bg-card">
        <div class="icon"><i class="fa-regular fa-heart"></i></div>
        <h2 class="display-md mb-2">Nothing saved yet</h2>
        <p class="text-muted-bray mb-4">Tap the heart on any piece to keep it here for later.</p>
        <a class="btn btn-ink" href="<?= e(url('shop.php')) ?>">Browse the Collection</a>
      </div>
    <?php else: ?>
      <p class="mini-note mb-4"><?= count($items) ?> saved piece<?= count($items) === 1 ? '' : 's' ?></p>
      <div class="row g-4 gx-lg-4 gy-5">
        <?php foreach ($items as $p): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <?php $altImage = null; include __DIR__ . '/includes/product-card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

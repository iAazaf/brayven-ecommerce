<?php
/**
 * BRAYVEN — global header.
 * Expects (optional): $pageTitle (string), $pageDesc (string), $bodyClass (string).
 * Must be included AFTER includes/init.php.
 */
$pageTitle = $pageTitle ?? 'Define Your Own Standard.';
$pageDesc  = $pageDesc  ?? 'BRAYVEN — considered essentials and elevated everyday wear. Premium menswear, womenswear and accessories.';
$bodyClass = $bodyClass ?? '';
$user = current_user();
$cartCount = cart_count();
$activeNav = $activeNav ?? '';

/* Auto-detect the active nav item when the page doesn't set one explicitly,
   so shop-context pages highlight the right item (Men/Women/New/Sale/Shop). */
if ($activeNav === '') {
    $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (in_array($script, ['shop.php', 'category.php', 'product.php', 'search.php'], true)) {
        $dept = strtolower(trim((string)($_GET['department'] ?? '')));
        if ($dept === 'men')          $activeNav = 'men';
        elseif ($dept === 'women')    $activeNav = 'women';
        elseif (isset($_GET['new']))  $activeNav = 'new';
        elseif (isset($_GET['sale'])) $activeNav = 'sale';
        else                          $activeNav = 'shop';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($pageDesc) ?>">
  <title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Marcellus&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="visually-hidden-focusable" href="#mainContent">Skip to main content</a>

<div class="announcement-bar">
  Free shipping on orders over <?= e(money(FREE_SHIPPING_THRESHOLD)) ?> &nbsp;<span class="accent">·</span>&nbsp; Autumn — Winter 2026 now live
</div>

<header class="site-header">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between">

      <button class="icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Open menu" aria-controls="mobileNav">
        <i class="fa-solid fa-bars"></i>
      </button>

      <a class="brand-mark" href="<?= e(url('index.php')) ?>">BRAYVEN</a>

      <nav class="nav-main d-none d-lg-flex align-items-center" aria-label="Main navigation">
        <a class="nav-link <?= $activeNav === 'shop' ? 'active' : '' ?>" href="<?= e(url('shop.php')) ?>">Shop</a>
        <a class="nav-link <?= $activeNav === 'men' ? 'active' : '' ?>" href="<?= e(url('shop.php?department=men')) ?>">Men</a>
        <a class="nav-link <?= $activeNav === 'women' ? 'active' : '' ?>" href="<?= e(url('shop.php?department=women')) ?>">Women</a>
        <a class="nav-link <?= $activeNav === 'new' ? 'active' : '' ?>" href="<?= e(url('shop.php?new=1')) ?>">New Arrivals</a>
        <a class="nav-link nav-sale <?= $activeNav === 'sale' ? 'active' : '' ?>" href="<?= e(url('shop.php?sale=1')) ?>">Sale</a>
      </nav>

      <div class="header-icons">
        <a class="icon-btn" href="<?= e(url('search.php')) ?>" aria-label="Search">
          <i class="fa-solid fa-magnifying-glass"></i>
        </a>
        <a class="icon-btn" href="<?= e(url($user ? 'profile.php' : 'login.php')) ?>" aria-label="<?= $user ? 'My account' : 'Sign in' ?>">
          <i class="fa-regular fa-user"></i>
        </a>
        <a class="icon-btn d-none d-sm-inline-flex" href="<?= e(url('wishlist.php')) ?>" aria-label="Wishlist">
          <i class="fa-regular fa-heart"></i>
        </a>
        <a class="icon-btn" href="<?= e(url('cart.php')) ?>" aria-label="Cart, <?= $cartCount ?> items">
          <i class="fa-solid fa-bag-shopping"></i>
          <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</header>

<!-- Mobile navigation -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
  <div class="offcanvas-header border-bottom">
    <span class="brand-mark brand-mark--sm" id="mobileNavLabel">BRAYVEN</span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <nav aria-label="Mobile navigation">
      <a class="nav-link <?= $activeNav === 'shop' ? 'active' : '' ?>" href="<?= e(url('shop.php')) ?>">Shop All</a>
      <a class="nav-link <?= $activeNav === 'men' ? 'active' : '' ?>" href="<?= e(url('shop.php?department=men')) ?>">Men</a>
      <a class="nav-link <?= $activeNav === 'women' ? 'active' : '' ?>" href="<?= e(url('shop.php?department=women')) ?>">Women</a>
      <a class="nav-link <?= $activeNav === 'new' ? 'active' : '' ?>" href="<?= e(url('shop.php?new=1')) ?>">New Arrivals</a>
      <a class="nav-link nav-sale <?= $activeNav === 'sale' ? 'active' : '' ?>" href="<?= e(url('shop.php?sale=1')) ?>">Sale</a>
      <a class="nav-link" href="<?= e(url('wishlist.php')) ?>">Wishlist</a>
      <a class="nav-link" href="<?= e(url($user ? 'profile.php' : 'login.php')) ?>"><?= $user ? 'My Account' : 'Sign In' ?></a>
    </nav>
  </div>
</div>

<div class="flash-zone" aria-live="polite">
  <?php foreach (flash_take() as $flash): ?>
    <div class="flash-item <?= e($flash['type']) ?> mb-2"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>
</div>

<main id="mainContent">

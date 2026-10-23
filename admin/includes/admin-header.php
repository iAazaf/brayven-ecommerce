<?php
/**
 * BRAYVEN — admin header (separate layout).
 * Every admin page must call require_admin() BEFORE including this file.
 */
$pageTitle = $pageTitle ?? 'Dashboard';
$adminActive = $adminActive ?? '';
$adminUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle) ?> — BRAYVEN Admin</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Marcellus&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="adm-backdrop" onclick="document.body.classList.remove('adm-open')"></div>

<aside class="adm-sidebar">
  <span class="adm-brand">BRAYVEN</span>
  <span class="adm-brand-sub">Admin Console</span>

  <nav class="adm-nav" aria-label="Admin navigation">
    <p class="nav-label">Overview</p>
    <a href="<?= e(url('admin/index.php')) ?>" class="<?= $adminActive === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>

    <p class="nav-label">Catalogue</p>
    <a href="<?= e(url('admin/products.php')) ?>" class="<?= $adminActive === 'products' ? 'active' : '' ?>"><i class="fa-solid fa-shirt"></i>Products</a>
    <a href="<?= e(url('admin/categories.php')) ?>" class="<?= $adminActive === 'categories' ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i>Categories</a>

    <p class="nav-label">Sales</p>
    <a href="<?= e(url('admin/orders.php')) ?>" class="<?= $adminActive === 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-box-archive"></i>Orders</a>

    <p class="nav-label">People</p>
    <a href="<?= e(url('admin/customers.php')) ?>" class="<?= $adminActive === 'customers' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i>Customers</a>

    <p class="nav-label">Store</p>
    <a href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i>View Storefront</a>
  </nav>

  <div class="adm-sidebar-footer">
    Signed in as<br><strong><?= e($adminUser['name'] ?? 'Admin') ?></strong>
    <div class="mt-2"><a href="<?= e(url('logout.php')) ?>"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Sign out</a></div>
  </div>
</aside>

<div class="adm-main">
  <div class="adm-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="adm-toggle" type="button" onclick="document.body.classList.add('adm-open')" aria-label="Open admin menu"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($pageTitle) ?></h1>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="chip chip-active d-none d-md-inline-block"><?= e(date('D, M j')) ?></span>
      <a class="btn-adm-outline text-decoration-none" href="<?= e(url('admin/products.php?action=new')) ?>"><i class="fa-solid fa-plus me-1"></i>New Product</a>
    </div>
  </div>

  <div class="adm-content">
    <div class="flash-zone" aria-live="polite">
      <?php foreach (flash_take() as $flash): ?>
        <div class="flash-item <?= e($flash['type']) ?> mb-2"><?= e($flash['message']) ?></div>
      <?php endforeach; ?>
    </div>

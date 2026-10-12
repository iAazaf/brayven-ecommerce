<?php
/** BRAYVEN — Category listing. */
require_once __DIR__ . '/includes/init.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['slug'] ?? '')));
if ($slug === '') {
    redirect('shop.php');
}

$stmt = db()->prepare('SELECT id, name, slug, description FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$slug]);
$category = $stmt->fetch();
if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state">'
        . '<h1 class="display-md mb-2">Category not found</h1>'
        . '<p class="text-muted-bray mb-4">The collection you are looking for does not exist or is no longer available.</p>'
        . '<a class="btn btn-ink" href="' . e(url('shop.php')) . '">Browse all products</a></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $category['name'];
$pageDesc  = $category['description'] ?? ('Shop ' . $category['name'] . ' at BRAYVEN.');

$listingPreset  = ['category_slug' => $category['slug']];
$listingBaseUrl = url('category.php?slug=' . rawurlencode($category['slug']));
$listingHeading = [
    'kicker' => 'Category',
    'title'  => $category['name'],
    'lead'   => $category['description'] ?? null,
];

require __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/product-listing.php';
require __DIR__ . '/includes/footer.php';

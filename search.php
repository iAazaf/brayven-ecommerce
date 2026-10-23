<?php
/** BRAYVEN — Search results. */
require_once __DIR__ . '/includes/init.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) > 80) {
    $q = mb_substr($q, 0, 80);
}

$pageTitle = $q !== '' ? 'Search: ' . $q : 'Search';
$pageDesc  = 'Search the BRAYVEN collection.';

$listingPreset  = $q !== '' ? ['q' => $q] : [];
$listingBaseUrl = url('search.php');
$listingHeading = [
    'kicker' => 'Search',
    'title'  => $q !== '' ? '“' . $q . '”' : 'Search the collection',
    'lead'   => $q !== ''
        ? 'Results across names, descriptions and product codes.'
        : 'Find pieces by name, description or product code below.',
];

require __DIR__ . '/includes/header.php';

if ($q === '') {
    echo '<section class="page-hero pb-0"><div class="container">';
    echo '<form method="get" action="' . e(url('search.php')) . '" class="row g-2" style="max-width:560px;">'
        . '<div class="col"><label class="visually-hidden" for="q">Search products</label>'
        . '<input type="search" class="form-control form-control-lg" id="q" name="q" placeholder="Try “hoodie”, “linen”, “BRV-JNS-001”…" autofocus></div>'
        . '<div class="col-auto"><button class="btn btn-ink" type="submit">Search</button></div></form>';
    echo '</div></section>';
}

include __DIR__ . '/includes/product-listing.php';
require __DIR__ . '/includes/footer.php';

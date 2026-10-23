<?php
/** BRAYVEN — Shop: all products with filters. */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Shop All';
$pageDesc  = 'Shop the full BRAYVEN collection — considered essentials for men and women.';

$listingPreset = [];
$listingBaseUrl = url('shop.php');
$listingHeading = [
    'kicker' => 'The Collection',
    'title'  => 'Shop All',
    'lead'   => 'Every piece in the current season — tops, layers, tailoring, denim, outerwear and accessories.',
];

require __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/product-listing.php';
require __DIR__ . '/includes/footer.php';

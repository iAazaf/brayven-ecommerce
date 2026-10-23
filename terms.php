<?php
/** BRAYVEN — Terms and conditions. */
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Terms & Conditions';
require __DIR__ . '/includes/header.php';

$sections = [
    ['The Store and Sample Content', 'This is a demonstration e-commerce application. All products, prices and availability are sample content for evaluation purposes only and do not constitute an offer to sell real goods. No real payment is processed — orders are Cash on Delivery only.'],
    ['Accounts', 'You are responsible for the accuracy of the details you provide and for keeping your password confidential. Accounts are personal and non-transferable. We may suspend accounts used for fraud, abuse or attempted technical interference with the store.'],
    ['Orders and Pricing', 'An order is a request to buy; the contract is formed when we confirm dispatch. Prices are shown in the site currency and include applicable display rules. Stock is re-verified at checkout — if an item has sold out, the affected line is cancelled and the remainder of your order proceeds. We may cancel an order in cases of obvious pricing errors or unfulfillable stock.'],
    ['Cash on Delivery', 'Orders are payable in cash to the courier at the moment of delivery, for the exact total shown at checkout. Repeated refusal of COD deliveries may result in account suspension.'],
    ['Shipping', 'Delivery estimates are good-faith guides, not guarantees. Risk of loss passes to you on delivery to the address provided. Ensure your address and phone number are correct — we cannot be liable for failed deliveries caused by incorrect details.'],
    ['Returns', 'Unworn items with tags attached may be returned within 30 days of delivery for exchange or refund, as detailed on the FAQ page. Sale items follow the same policy.'],
    ['Acceptable Use', 'You agree not to misuse the store: no scraping at disruptive volume, no attempts to access other users\' data, no injection or automation attacks, and no interference with site security controls.'], 
    ['Intellectual Property', 'The BRAYVEN name, tagline, product artwork and site content are the property of the store owner and may not be reproduced without permission.'],
    ['Limitation of Liability', 'To the extent permitted by law, the store is provided "as is" and our liability for any claim is limited to the value of the affected order. Nothing in these terms limits rights that cannot be excluded under applicable consumer law.'],
];
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>Terms &amp; Conditions</span></div>
    <span class="kicker d-block mb-3">Legal</span>
    <h1 class="display-lg mb-2">Terms &amp; Conditions</h1>
    <p class="text-muted-bray mb-0">Last updated: <?= e(date('F Y')) ?>. The short version: shop honestly, we ship honestly.</p>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row">
      <div class="col-lg-8">
        <?php foreach ($sections as $si => [$title, $body]): ?>
          <div class="mb-4">
            <h2 class="h4 mb-3"><?= ($si + 1) . '. ' . e($title) ?></h2>
            <p class="text-muted-bray mb-0"><?= e($body) ?></p>
          </div>
        <?php endforeach; ?>
        <p class="mini-note">These terms are sample content for a demo store and are not legal advice.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

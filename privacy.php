<?php
/** BRAYVEN — Privacy policy. */
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Privacy Policy';
require __DIR__ . '/includes/header.php';

$sections = [
    ['Information We Collect', [
        'Account details you give us: name, email address, phone number and password (stored only as a modern one-way hash).',
        'Order details: delivery address, contact details, items purchased and order notes — needed to fulfil your order.',
        'Wishlist and newsletter data: saved products and your email address if you subscribe.',
        'Contact messages: whatever you choose to send us through the contact form.',
    ]],
    ['How We Use Your Information', [
        'To process orders, arrange delivery and handle returns and exchanges.',
        'To provide customer support and respond to your enquiries.',
        'To send order confirmations and shipping updates. Marketing emails are only sent if you subscribe, and every email includes an unsubscribe option.',
        'To maintain site security, prevent fraud and meet legal obligations.',
    ]],
    ['What We Never Do', [
        'We never store payment card data — this store processes Cash on Delivery orders only.',
        'We never sell or rent your personal information to third parties.',
        'We never share your details beyond what delivery and communication require.',
    ]],
    ['Cookies & Sessions', [
        'We use a single session cookie to keep you signed in, remember your shopping bag and protect forms against cross-site request forgery. It contains no personal data beyond an anonymous session identifier and expires when you close your browser or sign out.',
    ]],
    ['Data Retention & Your Rights', [
        'Order records are kept as long as required by accounting rules; contact messages are retained for 12 months; newsletter subscriptions are kept until you unsubscribe.',
        'You may request a copy, correction or deletion of your personal data at any time through the contact page. Deleting an account removes your profile; anonymised order records are retained for accounting compliance.',
    ]],
];
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>Privacy Policy</span></div>
    <span class="kicker d-block mb-3">Legal</span>
    <h1 class="display-lg mb-2">Privacy Policy</h1>
    <p class="text-muted-bray mb-0">Last updated: <?= e(date('F Y')) ?>. Plain-language summary: we collect the minimum needed to run the store and never sell your data.</p>
  </div>
</section>

<section class="pb-5">
  <div class="container legal-body">
    <div class="row">
      <div class="col-lg-8">
        <?php foreach ($sections as $si => [$title, $points]): ?>
          <div class="mb-4">
            <h2 class="h4 mb-3"><?= ($si + 1) . '. ' . e($title) ?></h2>
            <ul class="text-muted-bray">
              <?php foreach ($points as $point): ?>
                <li class="mb-2"><?= e($point) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
        <p class="mini-note">Questions about this policy? Use the <a class="text-decoration-underline" href="<?= e(url('contact.php')) ?>">contact page</a>.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/** BRAYVEN — Frequently asked questions. */
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'FAQ';
$pageDesc  = 'Shipping, returns, sizing and payment questions answered.';
require __DIR__ . '/includes/header.php';

$faqGroups = [
    'Orders & Shipping' => [
        ['How long does shipping take?', 'Orders are prepared within 1–2 business days. Standard delivery takes 3–5 business days; you will receive tracking by email as soon as your parcel leaves the studio.'],
        ['What does shipping cost?', 'Flat rate of ' . money(SHIPPING_FLAT) . ', free on all orders over ' . money(FREE_SHIPPING_THRESHOLD) . '.'],
        ['Can I change my order after placing it?', 'If your order has not shipped yet, contact us within 12 hours and we will do our best to update it. Include your order number (starts with BRV-).'],
        ['Where is my order?', 'Check the tracking link in your shipping email first. If tracking has not updated for 48 hours, message us through the contact page.'],
    ],
    'Returns & Exchanges' => [
        ['What is your return policy?', '30 days from delivery, on unworn pieces with tags attached. Returns are free for exchanges; refunds are issued to the original payment method within 5 business days of inspection.'],
        ['How do I start a return?', 'Message us with your order number and the item(s) you are returning. We will email a prepaid label and simple instructions.'],
        ['Can I exchange for a different size?', 'Yes — exchanges are always free. Request an exchange through the contact page and we will reserve your new size while your return ships.'],
    ],
    'Sizing & Fit' => [
        ['How does BRAYVEN sizing run?', 'True to size with a considered, relaxed drape. Each product page lists the fit; if you are between sizes, take the smaller for a cleaner line.'],
        ['Do you offer both menswear and womenswear?', 'Yes. The Men and Women collections share our unisex essentials, and each department has dedicated silhouettes cut for its fit.'],
    ],
    'Payment' => [
        ['What payment methods do you accept?', 'Cash on Delivery. Our courier collects the exact amount when your order arrives. Please have the total ready — couriers carry limited change.'],
        ['Do you store my card details?', 'No. We do not process card payments and never store card data.'],
    ],
];
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>FAQ</span></div>
    <span class="kicker d-block mb-3">Support</span>
    <h1 class="display-lg mb-2">Frequently Asked Questions</h1>
    <p class="text-muted-bray mb-0" style="max-width:52ch;">Everything about shipping, returns, sizing and payment — in plain language.</p>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-5">
      <aside class="col-lg-3">
        <nav class="sticky-top pt-lg-4" style="top:6.5rem;" aria-label="FAQ sections">
          <p class="filter-title">Jump to</p>
          <ul class="filter-list">
            <?php foreach (array_keys($faqGroups) as $i => $group): ?>
              <li><a href="#faq-<?= $i ?>"><?= e($group) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= e(url('contact.php')) ?>">Still stuck? Contact us</a></li>
          </ul>
        </nav>
      </aside>
      <div class="col-lg-9">
        <?php foreach ($faqGroups as $gi => $group): ?>
          <div class="mb-5" id="faq-<?= $gi ?>">
            <h2 class="display-md mb-3"><?= e($gi) ?></h2>
            <div class="accordion accordion-bray">
              <?php foreach ($group as $qi => $qa): ?>
                <div class="accordion-item">
                  <h3 class="accordion-header">
                    <button class="accordion-button <?= $gi !== 0 || $qi !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq-<?= $gi ?>-<?= $qi ?>" aria-expanded="<?= $gi === 0 && $qi === 0 ? 'true' : 'false' ?>">
                      <?= e($qa[0]) ?>
                    </button>
                  </h3>
                  <div id="faq-<?= $gi ?>-<?= $qi ?>" class="accordion-collapse collapse <?= $gi === 0 && $qi === 0 ? 'show' : '' ?>" <?= $gi === 0 && $qi === 0 ? '' : 'data-bs-parent="#faqWrap' . $gi . '"' ?>>
                    <div class="accordion-body"><?= e($qa[1]) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <div class="newsletter-box">
          <h2 class="display-md mb-2">Didn't find your answer?</h2>
          <p class="text-muted-bray mb-3">Our team replies within one business day.</p>
          <a class="btn btn-ink" href="<?= e(url('contact.php')) ?>">Contact Support</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

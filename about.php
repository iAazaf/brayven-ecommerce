<?php
/** BRAYVEN — Brand story. */
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Our Story';
$pageDesc  = 'The BRAYVEN philosophy: considered essentials, honest materials, limited runs.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>Our Story</span></div>
    <span class="kicker d-block mb-3">Est. 2021 — Independent</span>
    <h1 class="display-xl mb-4" style="max-width:14ch;">We make less, so it means more.</h1>
    <p class="lead text-muted-bray mb-0" style="max-width:58ch;">
      BRAYVEN began with a simple frustration: wardrobes full of clothes and nothing
      that felt right. So we set out to build the opposite of fast fashion — a small,
      deliberate collection you can actually live in.
    </p>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-0 campaign-panel bg-card">
      <div class="col-lg-7"><img src="<?= e(url('assets/images/products/story-01.jpg')) ?>" alt="BRAYVEN atelier — sweatshirt study" style="height:100%;"></div>
      <div class="col-lg-5 d-flex align-items-center">
        <div class="p-4 p-lg-5">
          <span class="kicker d-block mb-3">Chapter One</span>
          <h2 class="display-md mb-3">The Standard Edit</h2>
          <p class="text-muted-bray mb-0">
            Every season starts the same way: a blank page and one question — what does
            a modern wardrobe actually need? Not what can we sell, but what will be worn
            two hundred times. The pieces that survive that question become the collection.
          </p>
        </div>
      </div>
    </div>

    <div class="row g-4 my-5">
      <div class="col-md-4"><div class="bg-card p-4 h-100"><img class="aspect-45 mb-3" src="<?= e(url('assets/images/products/story-02.jpg')) ?>" alt="Trouser fitting study"><span class="kicker">Design</span><h3 class="h5 mt-2 mb-2">Drawn, not decorated</h3><p class="text-muted-bray mb-0 small">Our studio sketches in proportions: shoulder lines, rise, drape. A garment earns detail only when the fit is already right.</p></div></div>
      <div class="col-md-4"><div class="bg-card p-4 h-100"><img class="aspect-45 mb-3" src="<?= e(url('assets/images/products/story-03.jpg')) ?>" alt="Material study — scarf"><span class="kicker">Materials</span><h3 class="h5 mt-2 mb-2">Named and traceable</h3><p class="text-muted-bray mb-0 small">Long-staple cotton, European flax, full-grain vegetable-tanned leather. We publish the fabric because the fabric is the product.</p></div></div>
      <div class="col-md-4"><div class="bg-card p-4 h-100"><img class="aspect-45 mb-3" src="<?= e(url('assets/images/products/editorial-hero-a.jpg')) ?>" alt="Overcoat silhouette study"><span class="kicker">Production</span><h3 class="h5 mt-2 mb-2">Small runs, no waste</h3><p class="text-muted-bray mb-0 small">We produce to demand in limited quantities. Sold out means sold out — and that is the point.</p></div></div>
    </div>

    <div class="row justify-content-center text-center py-5 section-dark mx-0 px-0">
      <div class="col-lg-8 py-5">
        <span class="kicker kicker--light d-block mb-4">Our Promise</span>
        <p class="display-lg mb-4" style="font-size:clamp(1.5rem,3vw,2.2rem);">“Buy fewer, choose well. Define your own standard — then hold everything else to it.”</p>
        <span class="kicker kicker--light">The BRAYVEN House Code</span>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

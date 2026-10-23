<?php
/**
 * BRAYVEN — reusable catalogue engine (filters + grid + pagination).
 *
 * The caller sets BEFORE including:
 *   $listingPreset  array  e.g. ['department' => 'men', 'category_slug' => 'hoodies', 'new' => true, 'sale' => true, 'q' => '...']
 *   $listingHeading array  optional ['kicker' => ..., 'title' => ..., 'lead' => ...] rendered above the grid
 *   $listingBaseUrl string URL that "clear filters" returns to (defaults to shop.php)
 *
 * Reads from $_GET: q, size, color, min_price, max_price, sort, page, department, new, sale
 * (department/new/sale/q only honoured when not already fixed by the preset).
 */

$listingPreset  = $listingPreset ?? [];
$listingBaseUrl = $listingBaseUrl ?? url('shop.php');

/* ---------- 1. Normalise incoming parameters ---------- */
$presetDepartment = $listingPreset['department'] ?? null; // 'men' | 'women' | null
$presetCategory   = $listingPreset['category_slug'] ?? null;
$presetNew        = !empty($listingPreset['new']);
$presetSale       = !empty($listingPreset['sale']);
$presetQ          = $listingPreset['q'] ?? null;

$q        = $presetQ !== null ? (string)$presetQ : trim((string)($_GET['q'] ?? ''));
$size     = preg_replace('/[^A-Za-z0-9 \-]/', '', (string)($_GET['size'] ?? ''));
$color    = preg_replace('/[^A-Za-z0-9 \-]/', '', (string)($_GET['color'] ?? ''));
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? max(0, (float)$_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? max(0, (float)$_GET['max_price']) : null;
$sort     = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc'], true) ? $_GET['sort'] : 'newest';
$showNew  = $presetNew || (!$presetQ && !empty($_GET['new']));
$showSale = $presetSale || (!$presetQ && !empty($_GET['sale']));
$department = $presetDepartment;
if ($department === null && in_array($_GET['department'] ?? '', ['men', 'women'], true)) {
    $department = $_GET['department'];
}
$page = max(1, (int)($_GET['page'] ?? 1));

/* ---------- 2. Build the WHERE clause ---------- */
$where  = ['p.is_active = 1', 'c.is_active = 1'];
$params = [];

if ($presetCategory !== null) {
    $where[] = 'c.slug = ?';
    $params[] = $presetCategory;
}
if ($department === 'men') {
    $where[] = "p.department IN ('men','unisex')";
} elseif ($department === 'women') {
    $where[] = "p.department IN ('women','unisex')";
}
if ($showNew)  { $where[] = 'p.is_new = 1'; }
if ($showSale) { $where[] = 'p.sale_price IS NOT NULL AND p.sale_price > 0 AND p.sale_price < p.price'; }
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    array_push($params, $like, $like, $like);
}
if ($size !== '') {
    $where[] = 'FIND_IN_SET(?, p.sizes)';
    $params[] = $size;
}
if ($color !== '') {
    $where[] = 'FIND_IN_SET(?, p.colors)';
    $params[] = $color;
}
if ($minPrice !== null) { $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) >= ?'; $params[] = $minPrice; }
if ($maxPrice !== null) { $where[] = 'COALESCE(NULLIF(p.sale_price,0), p.price) <= ?'; $params[] = $maxPrice; }

$whereSql = implode(' AND ', $where);
$orderBy = match ($sort) {
    'price_asc'  => 'COALESCE(NULLIF(p.sale_price,0), p.price) ASC, p.name ASC',
    'price_desc' => 'COALESCE(NULLIF(p.sale_price,0), p.price) DESC, p.name ASC',
    default      => 'p.created_at DESC, p.id DESC',
};

$pdo = db();

/* ---------- 3. Count + page of products ---------- */
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE $whereSql"
);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalProducts / PRODUCTS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.image, p.stock, p.is_new,
            p.colors, p.department, c.name AS category_name
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY $orderBy
     LIMIT " . PRODUCTS_PER_PAGE . " OFFSET $offset"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

/* ---------- 4. Facet data for the filter sidebar ---------- */
$facetRows = $pdo->query(
    "SELECT p.sizes, p.colors, c.slug AS cat_slug, c.name AS cat_name, COUNT(p.id) AS n
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND c.is_active = 1
     GROUP BY p.id"
)->fetchAll();

$sizeCounts = [];
$colorCounts = [];
$categoryCounts = [];
foreach ($facetRows as $row) {
    $categoryCounts[$row['cat_slug']] = ($categoryCounts[$row['cat_slug']] ?? 0) + 1;
    foreach (array_filter(array_map('trim', explode(',', $row['sizes']))) as $s) {
        $sizeCounts[$s] = ($sizeCounts[$s] ?? 0) + 1;
    }
    foreach (array_filter(array_map('trim', explode(',', $row['colors']))) as $col) {
        $colorCounts[$col] = ($colorCounts[$col] ?? 0) + 1;
    }
}
$sizeOrder = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One Size'];
uksort($sizeCounts, function ($a, $b) use ($sizeOrder) {
    $ia = array_search(strtoupper($a), $sizeOrder, true);
    $ib = array_search(strtoupper($b), $sizeOrder, true);
    if ($ia === false && $ib === false) { return (float)$a <=> (float)$b; }
    if ($ia === false) return 1;
    if ($ib === false) return -1;
    return $ia <=> $ib;
});
ksort($colorCounts, SORT_NATURAL | SORT_FLAG_CASE);

$wishlistIds = current_user() ? wishlist_product_ids((int)current_user()['id']) : [];

/* ---------- 5. URL helpers (preserve active filters) ---------- */
$currentParams = array_filter([
    'q'          => $q,
    'department' => $department,
    'new'        => $showNew ? '1' : '',
    'sale'       => $showSale ? '1' : '',
    'size'       => $size,
    'color'      => $color,
    'min_price'  => $minPrice,
    'max_price'  => $maxPrice,
    'sort'       => $sort !== 'newest' ? $sort : '',
], fn($v) => $v !== '' && $v !== null);

function listing_url(array $overrides = []): string
{
    global $listingBaseUrl, $currentParams;
    $params = array_merge($currentParams, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $listingBaseUrl . ($params ? '?' . http_build_query($params) : '');
}

$hasActiveFilters = $q !== '' || $size !== '' || $color !== '' || $minPrice !== null
    || $maxPrice !== null || ($presetCategory === null && $department !== null) || $showNew || $showSale;

$heading = $listingHeading ?? null;
?>

<?php if ($heading): ?>
  <section class="page-hero">
    <div class="container">
      <div class="breadcrumb-bray mb-3">
        <a href="<?= e(url('index.php')) ?>">Home</a>
        <span class="sep">/</span>
        <span><?= e($heading['title']) ?></span>
      </div>
      <span class="kicker"><?= e($heading['kicker'] ?? 'Collection') ?></span>
      <h1 class="display-lg mb-2"><?= e($heading['title']) ?></h1>
      <?php if (!empty($heading['lead'])): ?>
        <p class="text-muted-bray mb-0" style="max-width:56ch;"><?= e($heading['lead']) ?></p>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<section class="pb-5">
  <div class="container">
    <div class="row g-4 g-lg-5">

      <!-- Filters -->
      <aside class="col-lg-3" aria-label="Product filters">
        <div class="d-none d-lg-block">
          <?php if ($hasActiveFilters): ?>
            <a class="btn-quiet mb-4 d-inline-block" href="<?= e($listingBaseUrl) ?>"><i class="fa-solid fa-xmark me-1"></i> Clear all filters</a>
          <?php endif; ?>

          <div class="filter-block">
            <h2 class="filter-title">Category</h2>
            <ul class="filter-list">
              <li><a href="<?= e(listing_url(['department' => null, 'q' => $q !== '' ? $q : null])) ?>" class="<?= $presetCategory === null ? 'active' : '' ?>">All categories</a></li>
              <?php foreach ($categoryCounts as $slug => $count): ?>
                <li>
                  <a href="<?= e(url('category.php?slug=' . rawurlencode($slug))) ?>" class="<?= ($currentParams['category'] ?? $presetCategory) === $slug ? 'active' : '' ?>">
                    <?= e(ucwords(str_replace('-', ' ', $slug))) ?> <span class="filter-count">(<?= (int)$count ?>)</span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <?php if ($presetDepartment === null && $presetCategory === null): ?>
            <div class="filter-block">
              <h2 class="filter-title">Department</h2>
              <ul class="filter-list">
                <li><a href="<?= e(listing_url(['department' => null])) ?>" class="<?= $department === null ? 'active' : '' ?>">Everyone</a></li>
                <li><a href="<?= e(listing_url(['department' => 'men'])) ?>" class="<?= $department === 'men' ? 'active' : '' ?>">Men</a></li>
                <li><a href="<?= e(listing_url(['department' => 'women'])) ?>" class="<?= $department === 'women' ? 'active' : '' ?>">Women</a></li>
              </ul>
            </div>
          <?php endif; ?>

          <div class="filter-block">
            <h2 class="filter-title">Price</h2>
            <form method="get" action="<?= e($listingBaseUrl) ?>">
              <?php foreach (['q', 'department', 'new', 'sale', 'size', 'color', 'sort'] as $hiddenKey): ?>
                <?php if (($currentParams[$hiddenKey] ?? '') !== ''): ?>
                  <input type="hidden" name="<?= e($hiddenKey) ?>" value="<?= e((string)$currentParams[$hiddenKey]) ?>">
                <?php endif; ?>
              <?php endforeach; ?>
              <div class="row g-2 align-items-center">
                <div class="col-5">
                  <label class="visually-hidden" for="minPrice">Minimum price</label>
                  <input type="number" class="form-control form-control-sm" id="minPrice" name="min_price"
                         min="0" step="1" placeholder="Min" value="<?= $minPrice !== null ? e((string)$minPrice) : '' ?>">
                </div>
                <div class="col-auto text-muted-bray">–</div>
                <div class="col-5">
                  <label class="visually-hidden" for="maxPrice">Maximum price</label>
                  <input type="number" class="form-control form-control-sm" id="maxPrice" name="max_price"
                         min="0" step="1" placeholder="Max" value="<?= $maxPrice !== null ? e((string)$maxPrice) : '' ?>">
                </div>
                <div class="col-12 mt-2">
                  <button type="submit" class="btn btn-outline-ink btn-sm-bray w-100">Apply price</button>
                </div>
              </div>
            </form>
          </div>

          <div class="filter-block">
            <h2 class="filter-title">Size</h2>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($sizeCounts as $s => $count): ?>
                <a class="size-pill <?= strcasecmp($s, $size) === 0 ? 'active' : '' ?>"
                   href="<?= e(listing_url(['size' => strcasecmp($s, $size) === 0 ? null : $s])) ?>"
                   aria-pressed="<?= strcasecmp($s, $size) === 0 ? 'true' : 'false' ?>"><?= e($s) ?></a>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="filter-block">
            <h2 class="filter-title">Colour</h2>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($colorCounts as $col => $count): ?>
                <a href="<?= e(listing_url(['color' => strcasecmp($col, $color) === 0 ? null : $col])) ?>"
                   class="<?= strcasecmp($col, $color) === 0 ? 'active' : '' ?>"
                   title="<?= e($col) ?> (<?= (int)$count ?>)" aria-label="<?= e($col) ?>, <?= (int)$count ?> products">
                  <span class="color-dot <?= strcasecmp($col, $color) === 0 ? 'active' : '' ?>" style="background: <?= e(color_hex($col)) ?>;"></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Mobile: collapsed filters -->
        <div class="d-lg-none">
          <button class="btn btn-outline-ink btn-sm-bray w-100" type="button" data-bs-toggle="collapse" data-bs-target="#mobileFilters" aria-expanded="false" aria-controls="mobileFilters">
            <i class="fa-solid fa-sliders me-2"></i>Filters &amp; refine
          </button>
          <div class="collapse mt-3" id="mobileFilters">
            <div class="bg-card p-3">
              <form method="get" action="<?= e($listingBaseUrl) ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <?php if ($department): ?><input type="hidden" name="department" value="<?= e($department) ?>"><?php endif; ?>
                <?php if ($showNew): ?><input type="hidden" name="new" value="1"><?php endif; ?>
                <?php if ($showSale): ?><input type="hidden" name="sale" value="1"><?php endif; ?>
                <div class="mb-3">
                  <label class="form-label" for="mSize">Size</label>
                  <select class="form-select form-select-sm" id="mSize" name="size">
                    <option value="">All sizes</option>
                    <?php foreach ($sizeCounts as $s => $count): ?>
                      <option value="<?= e($s) ?>" <?= strcasecmp($s, $size) === 0 ? 'selected' : '' ?>><?= e($s) ?> (<?= (int)$count ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="mColor">Colour</label>
                  <select class="form-select form-select-sm" id="mColor" name="color">
                    <option value="">All colours</option>
                    <?php foreach ($colorCounts as $col => $count): ?>
                      <option value="<?= e($col) ?>" <?= strcasecmp($col, $color) === 0 ? 'selected' : '' ?>><?= e(ucwords($col)) ?> (<?= (int)$count ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label" for="mMin">Min price</label>
                    <input type="number" class="form-control form-control-sm" id="mMin" name="min_price" min="0" placeholder="0" value="<?= $minPrice !== null ? e((string)$minPrice) : '' ?>">
                  </div>
                  <div class="col-6">
                    <label class="form-label" for="mMax">Max price</label>
                    <input type="number" class="form-control form-control-sm" id="mMax" name="max_price" min="0" placeholder="Any" value="<?= $maxPrice !== null ? e((string)$maxPrice) : '' ?>">
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-ink btn-sm-bray flex-fill">Apply</button>
                  <a class="btn btn-outline-ink btn-sm-bray flex-fill" href="<?= e($listingBaseUrl) ?>">Clear</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </aside>

      <!-- Results -->
      <div class="col-lg-9">
        <div class="toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
          <p class="mb-0 mini-note"><?= (int)$totalProducts ?> product<?= $totalProducts === 1 ? '' : 's' ?><?= $q !== '' ? ' for “' . e($q) . '”' : '' ?></p>
          <form method="get" action="<?= e($listingBaseUrl) ?>" class="d-flex align-items-center gap-2">
            <?php foreach (['q', 'department', 'new', 'sale', 'size', 'color', 'min_price', 'max_price'] as $hiddenKey): ?>
              <?php if (($currentParams[$hiddenKey] ?? '') !== ''): ?>
                <input type="hidden" name="<?= e($hiddenKey) ?>" value="<?= e((string)$currentParams[$hiddenKey]) ?>">
              <?php endif; ?>
            <?php endforeach; ?>
            <label class="form-label mb-0" for="sortSelect">Sort</label>
            <select class="form-select form-select-sm sort-select" id="sortSelect" name="sort" onchange="this.form.submit()">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            </select>
            <noscript><button type="submit" class="btn btn-outline-ink btn-sm-bray">Go</button></noscript>
          </form>
        </div>

        <?php if (!$products): ?>
          <div class="empty-state">
            <div class="icon"><i class="fa-regular fa-face-meh"></i></div>
            <h2 class="display-md mb-2">Nothing matches those filters</h2>
            <p class="text-muted-bray mb-4">Try widening the price range or clearing a filter or two.</p>
            <a class="btn btn-ink" href="<?= e($listingBaseUrl) ?>">Clear filters</a>
          </div>
        <?php else: ?>
          <div class="row g-4 gx-lg-4 gy-5">
            <?php foreach ($products as $p): ?>
              <div class="col-6 col-md-4">
                <?php include __DIR__ . '/product-card.php'; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <nav class="mt-5" aria-label="Product pages">
              <ul class="pagination pagination-bray justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= e(listing_url(['page' => $page - 1])) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(listing_url(['page' => $i === 1 ? null : $i])) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= e(listing_url(['page' => $page + 1])) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

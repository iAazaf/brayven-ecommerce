<?php
/** BRAYVEN — Admin: product management (list, activate/deactivate, delete). */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['product_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        flash_set('danger', 'Product not found.');
        redirect('admin/products.php');
    }

    if ($action === 'toggle_active') {
        $pdo->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash_set('success', 'Product visibility updated.');
    } elseif ($action === 'toggle_featured') {
        $pdo->prepare('UPDATE products SET is_featured = 1 - is_featured WHERE id = ?')->execute([$id]);
        flash_set('success', 'Featured status updated.');
    } elseif ($action === 'delete') {
        // Orders keep their snapshots (order_items.product_id → SET NULL).
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        flash_set('success', 'Product deleted permanently.');
    }
    redirect('admin/products.php');
}

/* ---------- Listing + filters ---------- */
$filter = (string)($_GET['filter'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];
if ($filter === 'low') { $where[] = 'p.stock <= 5'; }
if ($filter === 'inactive') { $where[] = 'p.is_active = 0'; }
if ($filter === 'out') { $where[] = 'p.stock = 0'; }
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    array_push($params, $like, $like);
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE $whereSql
     ORDER BY p.created_at DESC
     LIMIT 200"
);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
$adminActive = 'products';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="adm-card mb-3">
  <div class="adm-card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/products.php')) ?>">
      <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or SKU…" style="min-width:220px;" aria-label="Search products">
      <button class="btn btn-adm-outline" type="submit">Search</button>
    </form>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-adm-outline text-decoration-none <?= $filter === '' ? 'active' : '' ?>" href="<?= e(url('admin/products.php')) ?>">All</a>
      <a class="btn btn-adm-outline text-decoration-none <?= $filter === 'low' ? 'active' : '' ?>" href="<?= e(url('admin/products.php?filter=low')) ?>">Low stock</a>
      <a class="btn btn-adm-outline text-decoration-none <?= $filter === 'out' ? 'active' : '' ?>" href="<?= e(url('admin/products.php?filter=out')) ?>">Sold out</a>
      <a class="btn btn-adm-outline text-decoration-none <?= $filter === 'inactive' ? 'active' : '' ?>" href="<?= e(url('admin/products.php?filter=inactive')) ?>">Inactive</a>
      <a class="btn btn-adm text-decoration-none" href="<?= e(url('admin/product-edit.php')) ?>"><i class="fa-solid fa-plus me-1"></i>Add Product</a>
    </div>
  </div>
</div>

<div class="adm-card">
  <div class="adm-card-head"><h2><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></h2></div>
  <div class="table-responsive">
    <table class="table adm-table mb-0">
      <thead>
        <tr>
          <th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?= e(product_image_url($p['image'])) ?>" alt="">
                <div>
                  <a class="fw-medium" href="<?= e(url('admin/product-edit.php?id=' . (int)$p['id'])) ?>"><?= e($p['name']) ?></a>
                  <span class="d-block text-muted-bray" style="font-size:0.72rem;"><?= e($p['sku']) ?><?= (int)$p['is_featured'] === 1 ? ' · <span class="chip chip-featured">Featured</span>' : '' ?><?= (int)$p['is_new'] === 1 ? ' · New' : '' ?></span>
                </div>
              </div>
            </td>
            <td><?= e($p['category_name']) ?></td>
            <td><?= $p['sale_price'] !== null ? '<del class="text-muted-bray">' . e(money($p['price'])) . '</del> ' . e(money($p['sale_price'])) : e(money($p['price'])) ?></td>
            <td>
              <?php if ((int)$p['stock'] === 0): ?><span class="chip chip-disabled">Sold out</span>
              <?php elseif ((int)$p['stock'] <= 5): ?><span class="chip chip-low"><?= (int)$p['stock'] ?> left</span>
              <?php else: ?><?= (int)$p['stock'] ?><?php endif; ?>
            </td>
            <td><span class="chip <?= (int)$p['is_active'] === 1 ? 'chip-active' : 'chip-disabled' ?>"><?= (int)$p['is_active'] === 1 ? 'Active' : 'Hidden' ?></span></td>
            <td class="text-end text-nowrap">
              <a class="btn-adm-quiet" href="<?= e(url('product.php?slug=' . rawurlencode($p['slug']))) ?>" target="_blank">View</a>
              <a class="btn-adm-quiet" href="<?= e(url('admin/product-edit.php?id=' . (int)$p['id'])) ?>">Edit</a>
              <form class="d-inline" method="post" action="<?= e(url('admin/products.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <button class="btn-adm-quiet" type="submit"><?= (int)$p['is_active'] === 1 ? 'Hide' : 'Show' ?></button>
              </form>
              <form class="d-inline" method="post" action="<?= e(url('admin/products.php')) ?>" data-confirm="Permanently delete “<?= e($p['name']) ?>”? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <button class="btn-adm-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

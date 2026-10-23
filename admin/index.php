<?php
/** BRAYVEN — Admin dashboard: store statistics, recent orders, low stock. */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();
$stats = [
    'products'  => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn(),
    'orders'    => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'customers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'sales'     => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn(),
    'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'lowstock'  => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock > 0 AND stock <= 5')->fetchColumn(),
    'unread'    => (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
];

$recentOrders = $pdo->query(
    'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o ORDER BY o.created_at DESC LIMIT 6'
)->fetchAll();

$lowStock = $pdo->query(
    'SELECT id, name, sku, stock, image FROM products WHERE is_active = 1 AND stock <= 5 ORDER BY stock ASC, name ASC LIMIT 8'
)->fetchAll();

$topCategories = $pdo->query(
    'SELECT c.name, COUNT(oi.id) AS sold
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN categories c ON c.id = p.category_id
     GROUP BY c.id ORDER BY sold DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Dashboard';
$adminActive = 'dashboard';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-icon"><i class="fa-solid fa-shirt"></i></div><div class="st-value"><?= $stats['products'] ?></div><div class="st-label">Active Products</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-icon"><i class="fa-solid fa-box-archive"></i></div><div class="st-value"><?= $stats['orders'] ?></div><div class="st-label">Total Orders</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-icon"><i class="fa-solid fa-users"></i></div><div class="st-value"><?= $stats['customers'] ?></div><div class="st-label">Customers</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div><div class="st-value"><?= e(money($stats['sales'])) ?></div><div class="st-label">Total Sales (valid orders)</div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-value"><?= $stats['pending'] ?></div><div class="st-label">Orders Pending</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-value"><?= $stats['lowstock'] ?></div><div class="st-label">Low-Stock SKUs</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-value"><?= $stats['unread'] ?></div><div class="st-label">Unread Messages</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-tile"><div class="st-value"><?= e(money($stats['sales'] > 0 && $stats['orders'] > 0 ? $stats['sales'] / max(1, $stats['orders']) : 0)) ?></div><div class="st-label">Avg. Order Value</div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="adm-card h-100">
      <div class="adm-card-head"><h2>Recent Orders</h2><a href="<?= e(url('admin/orders.php')) ?>" class="btn-adm-quiet">View all</a></div>
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (!$recentOrders): ?>
              <tr><td colspan="5" class="adm-empty">No orders yet — they will appear here.</td></tr>
            <?php endif; ?>
            <?php foreach ($recentOrders as $o): ?>
              <tr>
                <td><a class="fw-medium" href="<?= e(url('admin/order-view.php?id=' . (int)$o['id'])) ?>"><?= e($o['order_number']) ?></a></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= (int)$o['item_count'] ?></td>
                <td><?= e(money($o['total'])) ?></td>
                <td><span class="status-chip status-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Low Stock</h2><a href="<?= e(url('admin/products.php?filter=low')) ?>" class="btn-adm-quiet">Manage</a></div>
      <div class="adm-card-body p-0">
        <?php if (!$lowStock): ?>
          <p class="adm-empty mb-0">All products are comfortably stocked.</p>
        <?php else: ?>
          <ul class="list-unstyled m-0">
            <?php foreach ($lowStock as $p): ?>
              <li class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                <img src="<?= e(product_image_url($p['image'])) ?>" alt="" width="34" height="42" style="object-fit:cover;background:#ECE9E3;">
                <div class="flex-grow-1 small">
                  <a class="fw-medium" href="<?= e(url('admin/product-edit.php?id=' . (int)$p['id'])) ?>"><?= e($p['name']) ?></a>
                  <span class="d-block text-muted-bray" style="font-size:0.72rem;"><?= e($p['sku']) ?></span>
                </div>
                <span class="chip <?= $p['stock'] == 0 ? 'chip-disabled' : 'chip-low' ?>"><?= $p['stock'] == 0 ? 'Sold out' : (int)$p['stock'] . ' left' ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="adm-card">
      <div class="adm-card-head"><h2>Top Categories (units sold)</h2></div>
      <div class="adm-card-body p-0">
        <?php if (!$topCategories): ?>
          <p class="adm-empty mb-0">Sales data appears after the first order.</p>
        <?php else: ?>
          <ul class="list-unstyled m-0">
            <?php foreach ($topCategories as $c): ?>
              <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom small">
                <span><?= e($c['name']) ?></span><strong><?= (int)$c['sold'] ?></strong>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

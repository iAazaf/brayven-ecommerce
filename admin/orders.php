<?php
/** BRAYVEN — Admin: order management (filter + inline status updates). */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true) ? $_POST['status'] : null;
    $paymentStatus = in_array($_POST['payment_status'] ?? '', ['pending', 'paid', 'failed'], true) ? $_POST['payment_status'] : null;

    $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    if (!$stmt->fetch()) {
        flash_set('danger', 'Order not found.');
        redirect('admin/orders.php');
    }
    if ($status !== null) {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
        flash_set('success', 'Order status updated to ' . $status . '.');
    }
    if ($paymentStatus !== null) {
        $pdo->prepare('UPDATE orders SET payment_status = ? WHERE id = ?')->execute([$paymentStatus, $orderId]);
        flash_set('success', 'Payment status updated to ' . $paymentStatus . '.');
    }
    redirect('admin/orders.php');
}

/* ---------- Filters ---------- */
$statusFilter = in_array($_GET['status'] ?? '', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'], true) ? $_GET['status'] : '';
$q = trim((string)($_GET['q'] ?? ''));

$where = ['1=1'];
$params = [];
if ($statusFilter !== '') { $where[] = 'o.status = ?'; $params[] = $statusFilter; }
if ($q !== '') {
    $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    array_push($params, $like, $like, $like);
}

$stmt = $pdo->prepare(
    'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count,
            (SELECT COALESCE(SUM(quantity),0) FROM order_items oi2 WHERE oi2.order_id = o.id) AS units
     FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 200'
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$counts = [];
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
    $counts[$row['status']] = (int)$row['n'];
}

$pageTitle = 'Orders';
$adminActive = 'orders';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="adm-card mb-3">
  <div class="adm-card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/orders.php')) ?>">
      <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Order number, name or email…" style="min-width:240px;" aria-label="Search orders">
      <button class="btn btn-adm-outline" type="submit">Search</button>
    </form>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-adm-outline text-decoration-none <?= $statusFilter === '' ? 'active' : '' ?>" href="<?= e(url('admin/orders.php')) ?>">All</a>
      <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $st): ?>
        <a class="btn btn-adm-outline text-decoration-none <?= $statusFilter === $st ? 'active' : '' ?>" href="<?= e(url('admin/orders.php?status=' . $st)) ?>">
          <?= ucfirst($st) ?><?= isset($counts[$st]) ? ' (' . $counts[$st] . ')' : '' ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="adm-card">
  <div class="adm-card-head"><h2><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></h2></div>
  <div class="table-responsive">
    <table class="table adm-table mb-0">
      <thead>
        <tr><th>Order</th><th>Customer</th><th>Date</th><th>Units</th><th>Total</th><th>Payment</th><th>Fulfilment</th></tr>
      </thead>
      <tbody>
        <?php if (!$orders): ?>
          <tr><td colspan="7" class="adm-empty">No orders match this view.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><a class="fw-medium" href="<?= e(url('admin/order-view.php?id=' . (int)$o['id'])) ?>"><?= e($o['order_number']) ?></a></td>
            <td>
              <?= e($o['customer_name']) ?>
              <span class="d-block text-muted-bray" style="font-size:0.72rem;"><?= e($o['email']) ?><?= $o['user_id'] === null ? ' · Guest' : '' ?></span>
            </td>
            <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
            <td><?= (int)$o['units'] ?></td>
            <td class="fw-medium"><?= e(money($o['total'])) ?></td>
            <td>
              <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="status" value="<?= e($o['status']) ?>">
                <select class="form-select form-select-sm" name="payment_status" aria-label="Payment status">
                  <?php foreach (['pending', 'paid', 'failed'] as $ps): ?>
                    <option value="<?= $ps ?>" <?= $o['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-adm-quiet" type="submit">Save</button>
              </form>
            </td>
            <td>
              <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <input type="hidden" name="payment_status" value="<?= e($o['payment_status']) ?>">
                <select class="form-select form-select-sm" name="status" aria-label="Fulfilment status">
                  <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $o['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-adm-quiet" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

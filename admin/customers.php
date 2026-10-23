<?php
/** BRAYVEN — Admin: customer management. */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$pdo = db();

/* Self-protection: an admin cannot disable or demote their own account. */
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $userId = (int)($_POST['user_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id, role, status, name FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target) {
        flash_set('danger', 'Customer not found (admin accounts are managed outside this page).');
        redirect('admin/customers.php');
    }

    if ($action === 'toggle_status') {
        $newStatus = $target['status'] === 'active' ? 'disabled' : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
        flash_set('success', $target['name'] . ' is now ' . $newStatus . '.');
    }
    redirect('admin/customers.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$where = "u.role = 'customer'";
$params = [];
if ($q !== '') {
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
    array_push($params, $like, $like);
}

$stmt = $pdo->prepare(
    "SELECT u.*,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
            (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status <> 'cancelled') AS lifetime_spend,
            (SELECT MAX(o.created_at) FROM orders o WHERE o.user_id = u.id) AS last_order
     FROM users u
     WHERE $where
     ORDER BY u.created_at DESC
     LIMIT 200"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$guestOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE user_id IS NULL')->fetchColumn();

$pageTitle = 'Customers';
$adminActive = 'customers';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="adm-card mb-3">
  <div class="adm-card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <form class="d-flex gap-2" method="get" action="<?= e(url('admin/customers.php')) ?>">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email…" style="min-width:220px;" aria-label="Search customers">
      <button class="btn btn-adm-outline" type="submit">Search</button>
    </form>
    <span class="mini-note mb-0"><?= count($customers) ?> registered customers · <?= $guestOrders ?> guest order<?= $guestOrders === 1 ? '' : 's' ?></span>
  </div>
</div>

<div class="adm-card">
  <div class="table-responsive">
    <table class="table adm-table mb-0">
      <thead><tr><th>Customer</th><th>Joined</th><th>Orders</th><th>Lifetime Spend</th><th>Last Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (!$customers): ?>
          <tr><td colspan="7" class="adm-empty">No customers found.</td></tr>
        <?php endif; ?>
        <?php foreach ($customers as $c): ?>
          <tr>
            <td>
              <span class="fw-medium"><?= e($c['name']) ?></span>
              <span class="d-block text-muted-bray" style="font-size:0.72rem;"><?= e($c['email']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?></span>
            </td>
            <td><?= e(date('M j, Y', strtotime($c['created_at']))) ?></td>
            <td><?= (int)$c['order_count'] ?></td>
            <td><?= e(money($c['lifetime_spend'])) ?></td>
            <td><?= $c['last_order'] ? e(date('M j, Y', strtotime($c['last_order']))) : '—' ?></td>
            <td><span class="chip <?= $c['status'] === 'active' ? 'chip-active' : 'chip-disabled' ?>"><?= e($c['status']) ?></span></td>
            <td class="text-end text-nowrap">
              <a class="btn-adm-quiet" href="<?= e(url('admin/orders.php?q=' . rawurlencode($c['email']))) ?>">Orders</a>
              <form class="d-inline" method="post" action="<?= e(url('admin/customers.php')) ?>"
                    data-confirm="<?= $c['status'] === 'active' ? 'Disable' : 'Re-enable' ?> account “<?= e($c['name']) ?>”?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= (int)$c['id'] ?>">
                <button class="btn-adm-quiet" type="submit"><?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

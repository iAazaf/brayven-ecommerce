<?php
/** BRAYVEN — Customer order history (own orders only). */
require_once __DIR__ . '/includes/init.php';
$user = require_login();

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$itemsStmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ?');

$pageTitle = 'Order History';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><a href="<?= e(url('profile.php')) ?>">My Account</a><span class="sep">/</span><span>Orders</span></div>
    <span class="kicker d-block mb-2">My Account</span>
    <h1 class="display-lg mb-0">Order History</h1>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <?php if (!$orders): ?>
      <div class="empty-state bg-card">
        <div class="icon"><i class="fa-solid fa-box-open"></i></div>
        <h2 class="display-md mb-2">No orders yet</h2>
        <p class="text-muted-bray mb-4">When you place an order it will appear here with full tracking details.</p>
        <a class="btn btn-ink" href="<?= e(url('shop.php')) ?>">Start Shopping</a>
      </div>
    <?php else: ?>
      <div class="bg-card p-3 p-md-4">
        <div class="table-responsive">
          <table class="table table-bray align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">Order</th>
                <th scope="col">Date</th>
                <th scope="col">Items</th>
                <th scope="col">Total</th>
                <th scope="col">Payment</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): $itemsStmt->execute([(int)$order['id']]); ?>
                <tr>
                  <td class="fw-medium"><?= e($order['order_number']) ?></td>
                  <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                  <td><?= (int)$itemsStmt->fetchColumn() ?></td>
                  <td class="fw-medium"><?= e(money($order['total'])) ?></td>
                  <td><span class="status-chip status-<?= e($order['payment_status']) ?>"><?= e($order['payment_status']) ?></span></td>
                  <td><span class="status-chip status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                  <td class="text-end">
                    <a class="btn-quiet" href="<?= e(url('order-success.php?order=' . rawurlencode($order['order_number']))) ?>">View <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

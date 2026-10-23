<?php
/** BRAYVEN — Order confirmation / receipt. */
require_once __DIR__ . '/includes/init.php';

$orderNumber = preg_replace('/[^A-Za-z0-9\-]/', '', (string)($_GET['order'] ?? ''));
$stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

/*
 * Access rule: guests may view a receipt only right after checkout (it stays in
 * their session); signed-in customers may view their own orders.
 */
$mayView = false;
if ($order) {
    if (($_SESSION['last_order_number'] ?? '') === $order['order_number']) {
        $mayView = true;
    } else {
        $user = current_user();
        if ($user && $order['user_id'] !== null && (int)$order['user_id'] === (int)$user['id']) {
            $mayView = true;
        }
    }
}

if (!$order || !$mayView) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="empty-state">'
        . '<h1 class="display-md mb-2">Order not found</h1>'
        . '<p class="text-muted-bray mb-4">We could not find that order, or it does not belong to your account.</p>'
        . '<a class="btn btn-ink" href="' . e(url('orders.php')) . '">View my orders</a></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

unset($_SESSION['last_order_number']); // one-time view for guests

$itemStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemStmt->execute([(int)$order['id']]);
$items = $itemStmt->fetchAll();

$pageTitle = 'Order Confirmed';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container text-center">
    <span class="kicker d-block mb-3 text-accent">Thank you</span>
    <h1 class="display-lg mb-3">Your order is confirmed.</h1>
    <p class="text-muted-bray mb-0">Order <strong class="text-dark"><?= e($order['order_number']) ?></strong> placed <?= e(date('F j, Y', strtotime($order['created_at']))) ?> · A confirmation email is on its way to <?= e($order['email']) ?>.</p>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-7">
        <div class="bg-card p-4">
          <h2 class="h6 overline mb-3">Items (<?= count($items) ?>)</h2>
          <?php foreach ($items as $item): ?>
            <div class="d-flex gap-3 py-2 border-bottom">
              <div class="flex-grow-1 small">
                <p class="mb-0 fw-medium"><?= e($item['product_name']) ?></p>
                <p class="mini-note mb-0">Size <?= e($item['size']) ?> · <?= e($item['color']) ?> · <?= e($item['sku']) ?> × <?= (int)$item['quantity'] ?></p>
              </div>
              <span class="small fw-medium"><?= e(money($item['line_total'])) ?></span>
            </div>
          <?php endforeach; ?>
          <div class="mt-3">
            <div class="summary-row"><span>Subtotal</span><span><?= e(money($order['subtotal'])) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span><?= (float)$order['shipping_fee'] > 0 ? e(money($order['shipping_fee'])) : 'Free' ?></span></div>
            <div class="summary-row total"><span>Total (<?= e(strtoupper($order['payment_method'])) ?>)</span><span><?= e(money($order['total'])) ?></span></div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="summary-card">
          <h2 class="h6 overline mb-3">Delivery &amp; Payment</h2>
          <p class="small mb-1"><strong><?= e($order['customer_name']) ?></strong></p>
          <p class="small text-muted-bray mb-3"><?= e($order['address']) ?><br><?= e($order['city']) ?>, <?= e($order['postal_code']) ?><br><?= e($order['phone']) ?></p>
          <?php if ($order['notes']): ?><p class="small text-muted-bray mb-3"><strong>Note:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
          <div class="summary-row"><span>Payment</span><span>Cash on Delivery</span></div>
          <div class="summary-row"><span>Status</span><span class="status-chip status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span></div>
          <p class="mini-note mt-3">Have the exact amount ready for the courier. You will also receive SMS updates on <?= e($order['phone']) ?>.</p>
          <div class="d-grid gap-2 mt-3">
            <a class="btn btn-ink" href="<?= e(url('shop.php')) ?>">Continue Shopping</a>
            <a class="btn btn-outline-ink" href="<?= e(url(current_user() ? 'orders.php' : 'index.php')) ?>"><?= current_user() ? 'View Order History' : 'Back to Home' ?></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/** BRAYVEN — Admin: single order detail. */
require_once dirname(__DIR__) . '/includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT o.*, u.name AS account_name, u.email AS account_email
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     WHERE o.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash_set('danger', 'Order not found.');
    redirect('admin/orders.php');
}

$itemsStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Order ' . $order['order_number'];
$adminActive = 'orders';
require __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <p class="mini-note mb-1"><a href="<?= e(url('admin/orders.php')) ?>" class="text-decoration-underline">Orders</a> / <?= e($order['order_number']) ?></p>
    <h2 class="mb-0" style="font-family:var(--font-display);"><?= e($order['order_number']) ?> <span class="status-chip status-<?= e($order['status']) ?> ms-2"><?= e($order['status']) ?></span></h2>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-adm-outline text-decoration-none" href="<?= e(url('order-success.php?order=' . rawurlencode($order['order_number']))) ?>" target="_blank">Customer receipt</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Items</h2></div>
      <div class="table-responsive">
        <table class="table adm-table mb-0">
          <thead><tr><th>Product</th><th>Variant</th><th>Unit price</th><th>Qty</th><th class="text-end">Line total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <?= e($item['product_name']) ?>
                  <span class="d-block text-muted-bray" style="font-size:0.72rem;"><?= e($item['sku']) ?><?= $item['product_id'] === null ? ' · product removed' : '' ?></span>
                </td>
                <td>Size <?= e($item['size']) ?> · <?= e($item['color']) ?></td>
                <td><?= e(money($item['unit_price'])) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td class="text-end fw-medium"><?= e(money($item['line_total'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="4" class="text-end text-muted-bray">Subtotal</td><td class="text-end"><?= e(money($order['subtotal'])) ?></td></tr>
            <tr><td colspan="4" class="text-end text-muted-bray">Shipping</td><td class="text-end"><?= e(money($order['shipping_fee'])) ?></td></tr>
            <tr><td colspan="4" class="text-end fw-semibold">Total (COD)</td><td class="text-end fw-semibold"><?= e(money($order['total'])) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="adm-card">
      <div class="adm-card-head"><h2>Update Status</h2></div>
      <div class="adm-card-body">
        <form method="post" action="<?= e(url('admin/orders.php')) ?>" class="row g-3 align-items-end">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
          <div class="col-md-5">
            <label class="form-label-adm" for="ovStatus">Fulfilment</label>
            <select class="form-select" id="ovStatus" name="status">
              <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label-adm" for="ovPay">Payment (Cash on Delivery)</label>
            <select class="form-select" id="ovPay" name="payment_status">
              <?php foreach (['pending', 'paid', 'failed'] as $ps): ?>
                <option value="<?= $ps ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2"><button class="btn btn-adm w-100" type="submit">Update</button></div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="adm-card mb-3">
      <div class="adm-card-head"><h2>Customer</h2></div>
      <div class="adm-card-body small">
        <p class="mb-1 fw-medium"><?= e($order['customer_name']) ?></p>
        <p class="text-muted-bray mb-1"><?= e($order['email']) ?></p>
        <p class="text-muted-bray mb-3"><?= e($order['phone']) ?></p>
        <p class="overline text-muted-bray mb-1">Ship to</p>
        <p class="mb-3"><?= e($order['address']) ?><br><?= e($order['city']) ?>, <?= e($order['postal_code']) ?></p>
        <?php if ($order['notes']): ?>
          <p class="overline text-muted-bray mb-1">Order notes</p>
          <p class="mb-0"><?= e($order['notes']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="adm-card">
      <div class="adm-card-head"><h2>Meta</h2></div>
      <div class="adm-card-body small">
        <dl class="row mb-0">
          <dt class="col-5 text-muted-bray fw-normal">Placed</dt><dd class="col-7"><?= e(date('M j, Y H:i', strtotime($order['created_at']))) ?> UTC</dd>
          <dt class="col-5 text-muted-bray fw-normal">Updated</dt><dd class="col-7"><?= e(date('M j, Y H:i', strtotime($order['updated_at']))) ?> UTC</dd>
          <dt class="col-5 text-muted-bray fw-normal">Account</dt><dd class="col-7"><?= $order['user_id'] === null ? 'Guest checkout' : '#' . (int)$order['user_id'] . ' — ' . e($order['account_name'] ?? '') ?></dd>
          <dt class="col-5 text-muted-bray fw-normal">Method</dt><dd class="col-7"><?= strtoupper(e($order['payment_method'])) ?></dd>
        </dl>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

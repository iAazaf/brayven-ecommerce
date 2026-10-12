<?php
/**
 * BRAYVEN — Checkout (Cash on Delivery).
 * Order creation runs inside a transaction with row locks; stock and prices
 * are re-validated server-side. Cart is cleared only after a successful commit.
 */
require_once __DIR__ . '/includes/init.php';

$cart = cart_details();
if (!$cart['items']) {
    flash_set('warning', 'Your bag is empty — add something before checking out.');
    redirect('cart.php');
}

$user = current_user();
$errors = [];
$old = [
    'name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'phone' => $user['phone'] ?? '',
    'address' => '', 'city' => '', 'postal_code' => '', 'notes' => '',
];

/* ---------- Handle placement ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // One-time form token: a second submission with the same token is rejected.
    $formToken = (string)($_POST['form_token'] ?? '');
    $expected = $_SESSION['checkout_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, $formToken)) {
        flash_set('warning', 'This order has already been submitted. Please review your bag.');
        redirect('checkout.php');
    }
    unset($_SESSION['checkout_token']);

    foreach (array_keys($old) as $field) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) { $errors['name'] = 'Please enter your full name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'A valid email is required for your receipt.'; }
    if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $old['phone'])) { $errors['phone'] = 'Please enter a reachable phone number.'; }
    if (mb_strlen($old['address']) < 5) { $errors['address'] = 'Please enter your street address.'; }
    if (mb_strlen($old['city']) < 2) { $errors['city'] = 'City is required.'; }
    if (!preg_match('/^[A-Za-z0-9 \-]{3,12}$/', $old['postal_code'])) { $errors['postal_code'] = 'Enter a valid postal/ZIP code.'; }
    if (mb_strlen($old['notes']) > 500) { $errors['notes'] = 'Notes are limited to 500 characters.'; }

    // Cart was re-priced on the line above; make sure nothing was dropped since the page loaded.
    if (!$errors && $cart['changed']) {
        flash_set('warning', 'Stock changed while you were checking out — please review your bag.');
        redirect('cart.php');
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            /* Lock and re-check every line inside the transaction. */
            $orderLines = [];
            foreach ($cart['items'] as $item) {
                $stmt = $pdo->prepare('SELECT name, sku, price, sale_price, stock FROM products WHERE id = ? AND is_active = 1 FOR UPDATE');
                $stmt->execute([$item['product_id']]);
                $row = $stmt->fetch();
                if (!$row || (int)$row['stock'] < $item['qty']) {
                    throw new RuntimeException('insufficient_stock:' . ($row['name'] ?? $item['name']));
                }
                $unit = $row['sale_price'] !== null && (float)$row['sale_price'] > 0 ? (float)$row['sale_price'] : (float)$row['price'];
                $orderLines[] = [
                    'product_id' => $item['product_id'],
                    'name'       => $row['name'],
                    'sku'        => $row['sku'],
                    'size'       => $item['size'],
                    'color'      => $item['color'],
                    'unit'       => $unit,
                    'qty'        => $item['qty'],
                    'line_total' => $unit * $item['qty'],
                ];
            }

            $subtotal = array_sum(array_column($orderLines, 'line_total'));
            $shipping = $subtotal < FREE_SHIPPING_THRESHOLD ? SHIPPING_FLAT : 0.0;
            $total = $subtotal + $shipping;

            $orderNumber = generate_order_number();
            $stmt = $pdo->prepare(
                'INSERT INTO orders (order_number, user_id, customer_name, email, phone, address, city, postal_code, notes, subtotal, shipping_fee, total, payment_method, payment_status, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "cod", "pending", "pending")'
            );
            $stmt->execute([
                $orderNumber,
                $user['id'] ?? null,
                $old['name'], $old['email'], $old['phone'],
                $old['address'], $old['city'], $old['postal_code'],
                $old['notes'] !== '' ? $old['notes'] : null,
                $subtotal, $shipping, $total,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, product_name, sku, size, color, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($orderLines as $line) {
                $itemStmt->execute([
                    $orderId, $line['product_id'], $line['name'], $line['sku'],
                    $line['size'], $line['color'], $line['unit'], $line['qty'], $line['line_total'],
                ]);
                $stockStmt->execute([$line['qty'], $line['product_id']]);
            }

            $pdo->commit();

            /* Success only after commit: clear cart + remember the order for the receipt. */
            $_SESSION['cart'] = [];
            $_SESSION['last_order_number'] = $orderNumber;
            redirect('order-success.php?order=' . rawurlencode($orderNumber));

        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if (str_starts_with($e->getMessage(), 'insufficient_stock:')) {
                flash_set('danger', 'Sorry — "' . substr($e->getMessage(), 20) . '" no longer has enough stock. Your bag was updated.');
                redirect('cart.php');
            }
            error_log('Order failed: ' . $e->getMessage());
            flash_set('danger', 'We could not place your order. Please try again.');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Order DB error: ' . $e->getMessage());
            flash_set('danger', 'We could not place your order right now. Please try again.');
        }
    }
}

/* Fresh token for the form. */
$checkoutToken = bin2hex(random_bytes(16));
$_SESSION['checkout_token'] = $checkoutToken;

$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><a href="<?= e(url('cart.php')) ?>">Bag</a><span class="sep">/</span><span>Checkout</span></div>
    <h1 class="display-lg mb-0">Checkout</h1>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <form method="post" action="<?= e(url('checkout.php')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_token" value="<?= e($checkoutToken) ?>">

      <div class="row g-4 g-lg-5">
        <div class="col-lg-7">
          <div class="bg-card p-4">
            <h2 class="h5 mb-1">Delivery Details</h2>
            <p class="mini-note mb-4">All fields except order notes are required.</p>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="coName">Full name</label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="coName" name="name" value="<?= e($old['name']) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="coEmail">Email</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="coEmail" name="email" value="<?= e($old['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="coPhone">Phone number</label>
                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="coPhone" name="phone" value="<?= e($old['phone']) ?>" required>
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="coCity">City</label>
                <input class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" id="coCity" name="city" value="<?= e($old['city']) ?>" required>
                <?php if (isset($errors['city'])): ?><div class="invalid-feedback"><?= e($errors['city']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="coAddress">Street address</label>
                <input class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="coAddress" name="address" value="<?= e($old['address']) ?>" required>
                <?php if (isset($errors['address'])): ?><div class="invalid-feedback"><?= e($errors['address']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="coPostal">Postal code</label>
                <input class="form-control <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" id="coPostal" name="postal_code" value="<?= e($old['postal_code']) ?>" required>
                <?php if (isset($errors['postal_code'])): ?><div class="invalid-feedback"><?= e($errors['postal_code']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="coNotes">Order notes <span style="text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea class="form-control" id="coNotes" name="notes" rows="3" maxlength="500" placeholder="Delivery instructions, gift note…"><?= e($old['notes']) ?></textarea>
              </div>
            </div>

            <h2 class="h5 mt-4 pt-2 mb-3">Payment</h2>
            <div class="form-check bg-white border p-3 d-flex gap-3 align-items-start" style="border-color: var(--bray-border) !important;">
              <input class="form-check-input mt-1" type="radio" name="payment" id="payCod" value="cod" checked>
              <label class="form-check-label" for="payCod">
                <strong>Cash on Delivery</strong>
                <span class="d-block mini-note">Pay in cash when your order arrives. Our courier collects the exact amount shown below.</span>
              </label>
            </div>
            <p class="mini-note mt-2 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Card payments are not offered — this demo store processes Cash on Delivery orders only.</p>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="summary-card">
            <h2 class="h5 mb-3">Your Order</h2>
            <?php foreach ($cart['items'] as $item): ?>
              <div class="d-flex gap-3 py-2 border-bottom">
                <img src="<?= e(product_image_url($item['image'])) ?>" alt="" width="56" height="70" style="object-fit:cover; background:#ECE9E3;">
                <div class="flex-grow-1 small">
                  <p class="mb-0 fw-medium"><?= e($item['name']) ?></p>
                  <p class="mini-note mb-0">Size <?= e($item['size']) ?> · <?= e($item['color']) ?> × <?= (int)$item['qty'] ?></p>
                </div>
                <span class="small fw-medium"><?= e(money($item['line_total'])) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="mt-3">
              <div class="summary-row"><span>Subtotal</span><span><?= e(money($cart['subtotal'])) ?></span></div>
              <div class="summary-row"><span>Shipping</span><span><?= $cart['shipping'] > 0 ? e(money($cart['shipping'])) : 'Free' ?></span></div>
              <div class="summary-row total"><span>Total (COD)</span><span><?= e(money($cart['total'])) ?></span></div>
            </div>
            <button type="submit" class="btn btn-ink w-100 mt-3">
              <i class="fa-solid fa-check me-2"></i>Place Order — <?= e(money($cart['total'])) ?>
            </button>
            <p class="mini-note mt-3 mb-0 text-center">By placing this order you agree to our <a href="<?= e(url('terms.php')) ?>" class="text-decoration-underline">Terms</a>.</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

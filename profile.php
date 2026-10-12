<?php
/** BRAYVEN — Customer profile: details + quick account overview. */
require_once __DIR__ . '/includes/init.php';
$user = require_login();

$errors = [];
$old = ['name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    verify_csrf();
    $old['name']  = trim((string)($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['phone'] = trim((string)($_POST['phone'] ?? ''));
    $newPassword  = (string)($_POST['new_password'] ?? '');

    if (mb_strlen($old['name']) < 2) { $errors['name'] = 'Please enter your full name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $old['phone'])) { $errors['phone'] = 'Enter a valid phone number.'; }
    if ($newPassword !== '' && (strlen($newPassword) < 8 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword))) {
        $errors['new_password'] = 'New password must be 8+ characters with letters and numbers.';
    }

    if (!$errors) {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$old['email'], $user['id']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Another account already uses this email.';
        } else {
            if ($newPassword !== '') {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$old['name'], $old['email'], $old['phone'] !== '' ? $old['phone'] : null, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$old['name'], $old['email'], $old['phone'] !== '' ? $old['phone'] : null, $user['id']]);
            }
            flash_set('success', 'Profile updated.');
            redirect('profile.php');
        }
    }
}

$statsStmt = db()->prepare('SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS spend FROM orders WHERE user_id = ?');
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch();

$wishlistCount = count(wishlist_product_ids((int)$user['id']));

$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="kicker d-block mb-2">My Account</span>
        <h1 class="display-lg mb-0">Hello, <?= e(explode(' ', trim($user['name']))[0]) ?></h1>
      </div>
      <a class="btn-quiet" href="<?= e(url('logout.php')) ?>"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Sign out</a>
    </div>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-6 col-md-4"><div class="summary-card h-100"><p class="overline text-muted-bray mb-1">Orders</p><p class="display-md mb-0"><?= (int)$stats['orders'] ?></p><a class="mini-note text-decoration-underline" href="<?= e(url('orders.php')) ?>">View history</a></div></div>
      <div class="col-6 col-md-4"><div class="summary-card h-100"><p class="overline text-muted-bray mb-1">Lifetime Spend</p><p class="display-md mb-0"><?= e(money($stats['spend'])) ?></p><a class="mini-note text-decoration-underline" href="<?= e(url('wishlist.php')) ?>"><?= (int)$wishlistCount ?> wishlist items</a></div></div>
      <div class="col-6 col-md-4"><div class="summary-card h-100"><p class="overline text-muted-bray mb-1">Member Since</p><p class="display-md mb-0"><?= e(date('M Y', strtotime($user['created_at'] ?? 'now'))) ?></p><span class="mini-note">Standard member</span></div></div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="bg-card p-4">
          <h2 class="h5 mb-4">Account Details</h2>
          <form method="post" action="<?= e(url('profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="pfName">Full name</label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="pfName" name="name" value="<?= e($old['name']) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pfEmail">Email</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="pfEmail" name="email" value="<?= e($old['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pfPhone">Phone</label>
                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="pfPhone" name="phone" value="<?= e($old['phone']) ?>">
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="pfPass">New password <span style="text-transform:none;letter-spacing:0;">(leave blank to keep current)</span></label>
                <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="pfPass" name="new_password" autocomplete="new-password">
                <?php if (isset($errors['new_password'])): ?><div class="invalid-feedback"><?= e($errors['new_password']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-ink">Save Changes</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="summary-card">
          <h2 class="h5 mb-3">Quick Links</h2>
          <ul class="list-unstyled footer-list">
            <li class="mb-2"><a class="text-decoration-underline" href="<?= e(url('orders.php')) ?>"><i class="fa-solid fa-box me-2"></i>Order History</a></li>
            <li class="mb-2"><a class="text-decoration-underline" href="<?= e(url('wishlist.php')) ?>"><i class="fa-regular fa-heart me-2"></i>Wishlist</a></li>
            <li class="mb-2"><a class="text-decoration-underline" href="<?= e(url('shop.php')) ?>"><i class="fa-solid fa-shirt me-2"></i>Shop the Collection</a></li>
            <?php if ($user['role'] === 'admin'): ?>
              <li class="mb-2"><a class="text-decoration-underline" href="<?= e(url('admin/index.php')) ?>"><i class="fa-solid fa-gauge me-2"></i>Admin Panel</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

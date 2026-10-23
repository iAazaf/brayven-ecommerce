<?php
/**
 * BRAYVEN — one-time first-admin setup.
 *
 * Security rules:
 *   1. Refuses to run if any admin account already exists (setup is locked).
 *   2. Refuses to run from a non-localhost request.
 * Delete this file after creating your admin (recommended in README).
 */
require_once __DIR__ . '/includes/init.php';

$locked = false;
try {
    $locked = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
} catch (PDOException $e) {
    error_log('Setup check failed: ' . $e->getMessage());
    exit('Database unavailable. Import database.sql first (see README.md).');
}

$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? 'cli', ['127.0.0.1', '::1', 'cli'], true);
if (!$isLocal) {
    http_response_code(403);
    exit('Admin setup may only be run from localhost.');
}

if ($locked) {
    http_response_code(403);
    $pageTitle = 'Setup Locked';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><div class="auth-wrap text-center">'
        . '<div class="empty-state bg-card"><div class="icon"><i class="fa-solid fa-lock"></i></div>'
        . '<h1 class="display-md mb-2">Setup is locked</h1>'
        . '<p class="text-muted-bray mb-0">An administrator account already exists. For security this setup page can only be used once.</p>'
        . '</div></div></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']  = trim((string)($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['name']) < 2) { $errors['name'] = 'Enter the admin name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
    if (strlen($password) < 10) { $errors['password'] = 'Admin password must be at least 10 characters.'; }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) { $errors['password'] = 'Include letters and numbers.'; }
    if ($password !== $confirm) { $errors['password_confirm'] = 'Passwords do not match.'; }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'That email is already registered.';
        } else {
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, "admin", "active")');
            $stmt->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            $_SESSION['user_role'] = 'admin';
            flash_set('success', 'Admin account created. Welcome, ' . $old['name'] . '. Delete setup-admin.php now.');
            redirect('admin/index.php');
        }
    }
}

$pageTitle = 'Create Admin Account';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <div class="text-center mb-4">
        <span class="kicker d-block mb-2" style="color:#A55B3C;">One-Time Setup</span>
        <h1 class="display-md mb-2">Create the First Admin</h1>
        <p class="text-muted-bray mb-0">This page locks itself forever once an admin exists. Delete <code>setup-admin.php</code> afterwards.</p>
      </div>
      <div class="auth-card">
        <form method="post" action="<?= e(url('setup-admin.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="saName">Admin name</label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="saName" name="name" value="<?= e($old['name']) ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="saEmail">Admin email</label>
            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="saEmail" name="email" value="<?= e($old['email']) ?>" required>
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="saPass">Admin password</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="saPass" name="password" minlength="10" required>
            <div class="mini-note mt-1">Minimum 10 characters with letters and numbers. Stored hashed.</div>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <div class="mb-4">
            <label class="form-label" for="saPass2">Confirm password</label>
            <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="saPass2" name="password_confirm" required>
            <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn btn-ink w-100">Create Admin &amp; Sign In</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

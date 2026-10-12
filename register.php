<?php
/** BRAYVEN — Customer registration. */
require_once __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('profile.php');
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old['name']  = trim((string)($_POST['name'] ?? ''));
    $old['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $old['phone'] = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100) { $errors['name'] = 'Please enter your full name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email address.'; }
    if ($old['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $old['phone'])) { $errors['phone'] = 'Enter a valid phone number or leave it blank.'; }
    if (strlen($password) < 8) { $errors['password'] = 'Password must be at least 8 characters.'; }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) { $errors['password'] = 'Password must include letters and numbers.'; }
    if ($password !== $confirm) { $errors['password_confirm'] = 'Passwords do not match.'; }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists. Try signing in.';
        } else {
            $stmt = db()->prepare('INSERT INTO users (name, email, password_hash, phone, role, status) VALUES (?, ?, ?, ?, "customer", "active")');
            $stmt->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['phone'] !== '' ? $old['phone'] : null]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            $_SESSION['user_role'] = 'customer';
            flash_set('success', 'Welcome to BRAYVEN, ' . $old['name'] . '.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <div class="text-center mb-4">
        <span class="kicker d-block mb-2">Join the Standard</span>
        <h1 class="display-md mb-2">Create Account</h1>
        <p class="text-muted-bray mb-0">Track orders, save pieces, check out faster.</p>
      </div>
      <div class="auth-card">
        <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="regName">Full name</label>
            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="regName" name="name" value="<?= e($old['name']) ?>" required>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="regEmail">Email address</label>
            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="regEmail" name="email" value="<?= e($old['email']) ?>" required>
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="regPhone">Phone <span style="text-transform:none;letter-spacing:0;">(optional)</span></label>
            <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="regPhone" name="phone" value="<?= e($old['phone']) ?>">
            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="regPass">Password</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="regPass" name="password" minlength="8" required>
            <div class="mini-note mt-1">Minimum 8 characters, letters and numbers.</div>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <div class="mb-4">
            <label class="form-label" for="regPass2">Confirm password</label>
            <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="regPass2" name="password_confirm" required>
            <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn btn-ink w-100">Create Account</button>
        </form>
      </div>
      <p class="text-center mini-note mt-4">Already have an account? <a class="text-decoration-underline text-dark" href="<?= e(url('login.php')) ?>">Sign in</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

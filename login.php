<?php
/** BRAYVEN — Customer login. */
require_once __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('profile.php');
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $oldEmail = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($oldEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $stmt = db()->prepare('SELECT id, name, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$oldEmail]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            // Same generic message for unknown email and wrong password.
            $errors['password'] = 'Email or password is incorrect.';
        } elseif ($account['status'] !== 'active') {
            $errors['email'] = 'This account has been disabled. Contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$account['id'];
            $_SESSION['user_role'] = $account['role'];
            $intended = $_SESSION['intended_url'] ?? '';
            unset($_SESSION['intended_url']);
            flash_set('success', 'Welcome back, ' . $account['name'] . '.');
            // Only follow internal redirects.
            if ($intended !== '' && str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
                redirect($intended);
            }
            redirect($account['role'] === 'admin' ? 'admin/index.php' : 'profile.php');
        }
    }
}

$pageTitle = 'Sign In';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <div class="auth-wrap">
      <div class="text-center mb-4">
        <span class="kicker d-block mb-2">Welcome Back</span>
        <h1 class="display-md mb-2">Sign In</h1>
        <p class="text-muted-bray mb-0">Your standard, remembered.</p>
      </div>
      <div class="auth-card">
        <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="loginEmail">Email address</label>
            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="loginEmail" name="email" value="<?= e($oldEmail) ?>" required autofocus>
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="mb-4">
            <label class="form-label" for="loginPass">Password</label>
            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="loginPass" name="password" required>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn btn-ink w-100">Sign In</button>
        </form>
        <p class="mini-note text-center mt-4 mb-0">Demo customer: <code>demo@brayven.test</code> / <code>Brayven@123</code></p>
      </div>
      <p class="text-center mini-note mt-4">New to BRAYVEN? <a class="text-decoration-underline text-dark" href="<?= e(url('register.php')) ?>">Create an account</a></p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

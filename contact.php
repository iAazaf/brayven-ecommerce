<?php
/** BRAYVEN — Contact page with DB-backed message form. */
require_once __DIR__ . '/includes/init.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact') {
    verify_csrf();
    foreach (array_keys($old) as $field) {
        $old[$field] = trim((string)($_POST[$field] ?? ''));
    }
    if (mb_strlen($old['name']) < 2) { $errors['name'] = 'Please enter your name.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Enter a valid email so we can reply.'; }
    if (mb_strlen($old['subject']) < 3) { $errors['subject'] = 'Give your message a short subject.'; }
    if (mb_strlen($old['message']) < 10) { $errors['message'] = 'Tell us a little more (at least 10 characters).'; }
    if (mb_strlen($old['message']) > 5000) { $errors['message'] = 'Message is too long (5000 characters max).'; }

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
            $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
            flash_set('success', 'Message received. Our team replies within one business day.');
            redirect('contact.php');
        } catch (PDOException $e) {
            error_log('Contact insert failed: ' . $e->getMessage());
            flash_set('danger', 'Could not send your message right now. Please try again.');
        }
    }
}

$pageTitle = 'Contact';
$pageDesc  = 'Reach the BRAYVEN team — sizing help, orders, wholesale and press.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-bray mb-3"><a href="<?= e(url('index.php')) ?>">Home</a><span class="sep">/</span><span>Contact</span></div>
    <span class="kicker d-block mb-3">Customer Care</span>
    <h1 class="display-lg mb-2">We reply within one business day.</h1>
    <p class="text-muted-bray mb-0" style="max-width:52ch;">Sizing advice, order changes, returns — a real person reads every message.</p>
  </div>
</section>

<section class="pb-5">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-7">
        <div class="bg-card p-4">
          <h2 class="h5 mb-4">Send a Message</h2>
          <form method="post" action="<?= e(url('contact.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="contact">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="ctName">Name</label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="ctName" name="name" value="<?= e($old['name']) ?>" required>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="ctEmail">Email</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="ctEmail" name="email" value="<?= e($old['email']) ?>" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="ctSubject">Subject</label>
                <input class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="ctSubject" name="subject" value="<?= e($old['subject']) ?>" required>
                <?php if (isset($errors['subject'])): ?><div class="invalid-feedback"><?= e($errors['subject']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="ctMessage">Message</label>
                <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="ctMessage" name="message" rows="6" required><?= e($old['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?><div class="invalid-feedback"><?= e($errors['message']) ?></div><?php endif; ?>
              </div>
              <div class="col-12"><button type="submit" class="btn btn-ink">Send Message</button></div>
            </div>
          </form>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="summary-card h-100">
          <h2 class="h5 mb-4">Other Ways to Reach Us</h2>
          <ul class="list-unstyled mb-4">
            <li class="d-flex gap-3 py-3 border-bottom"><i class="fa-regular fa-envelope mt-1 text-accent"></i><div><strong class="small d-block">Email</strong><span class="mini-note">care@brayven.example — Mon–Fri, 9am–6pm</span></div></li>
            <li class="d-flex gap-3 py-3 border-bottom"><i class="fa-solid fa-phone mt-1 text-accent"></i><div><strong class="small d-block">Phone</strong><span class="mini-note">+1 (555) 010-2026 — Mon–Sat</span></div></li>
            <li class="d-flex gap-3 py-3 border-bottom"><i class="fa-solid fa-location-dot mt-1 text-accent"></i><div><strong class="small d-block">Studio</strong><span class="mini-note">14 Meridian Lane, Suite 3<br>Open by appointment</span></div></li>
          </ul>
          <p class="mini-note mb-0">For order questions, include your order number (starts with <code>BRV-</code>) — it helps us find you faster.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

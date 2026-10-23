<?php
/**
 * BRAYVEN — shared bootstrap. Every page starts with:
 *   require_once __DIR__ . '/includes/init.php';
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/* ---------- Secure session handling ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
    ]);
    session_name('BRAYVENSESS');
    session_start();
}

/* ---------- Output escaping ---------- */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    if (preg_match('~^https?://~', $path)) {
        header('Location: ' . $path);
        exit;
    }
    /* Root-absolute paths that already include the base URL must not be prefixed twice. */
    if (BASE_URL !== '' && (str_starts_with($path, BASE_URL . '/') || $path === BASE_URL)) {
        header('Location: ' . $path);
        exit;
    }
    header('Location: ' . url($path));
    exit;
}

/* ---------- Flash messages ---------- */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ---------- CSRF protection ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back and try again.');
    }
}

/* ---------- Authentication ---------- */
function current_user(): ?array
{
    static $cached = false;
    static $user = null;
    if ($cached === false) {
        $cached = true;
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = ? AND status = "active" LIMIT 1');
            $stmt->execute([(int)$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if ($user === null) {
                unset($_SESSION['user_id'], $_SESSION['user_role']);
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        flash_set('warning', 'Please sign in to continue.');
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if ($user === null || $user['role'] !== 'admin') {
        http_response_code(403);
        flash_set('danger', 'Administrator access required.');
        redirect('login.php');
    }
    return $user;
}

/* ---------- Formatting helpers ---------- */
function money(float|int|string|null $amount): string
{
    return CURRENCY . number_format((float)$amount, 2);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function color_hex(string $color): string
{
    $map = [
        'black' => '#171717', 'charcoal' => '#3B3B3B', 'white' => '#FAFAF8', 'ivory' => '#F2EEE6',
        'sand' => '#D8C9AE', 'stone' => '#B8B0A4', 'taupe' => '#A79C8D', 'tan' => '#C4A176',
        'camel' => '#B78B5B', 'rust' => '#A55B3C', 'terracotta' => '#B85C43', 'olive' => '#6B6B4A',
        'navy' => '#2B3548', 'indigo' => '#32405F', 'slate blue' => '#5C6B85', 'light blue' => '#9FB3C8',
        'grey' => '#8A8A8A', 'gray' => '#8A8A8A', 'cream' => '#EFE9DC', 'ecru' => '#E3DAC9',
        'brown' => '#6F5136', 'burgundy' => '#642A2E', 'forest green' => '#3C4A38',
    ];
    return $map[strtolower(trim($color))] ?? '#C9C4BB';
}

/* ---------- Cart (session-backed; prices & stock always re-read from DB) ---------- */
function &cart_ref(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function cart_key(int $productId, string $size, string $color): string
{
    return $productId . '|' . $size . '|' . $color;
}

function cart_add(int $productId, int $qty, string $size, string $color): void
{
    $cart = &cart_ref();
    $key = cart_key($productId, $size, $color);
    $cart[$key] = [
        'product_id' => $productId,
        'size'       => $size,
        'color'      => $color,
        'qty'        => max(1, $qty),
    ];
}

function cart_count(): int
{
    $count = 0;
    foreach (cart_ref() as $line) {
        $count += (int)$line['qty'];
    }
    return $count;
}

/**
 * Re-prices the cart from the database and clamps quantities to available stock.
 * Lines whose product is gone/inactive are dropped.
 */
function cart_details(): array
{
    $cart = &cart_ref();
    if (!$cart) {
        return ['items' => [], 'subtotal' => 0.0, 'shipping' => 0.0, 'total' => 0.0, 'changed' => false];
    }
    $ids = array_values(array_unique(array_map(fn($l) => (int)$l['product_id'], $cart)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, name, slug, sku, price, sale_price, stock, image FROM products WHERE is_active = 1 AND id IN ($placeholders)");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[(int)$row['id']] = $row;
    }

    $items = [];
    $subtotal = 0.0;
    $changed = false;
    foreach ($cart as $key => $line) {
        $pid = (int)$line['product_id'];
        if (!isset($products[$pid])) {
            unset($cart[$key]);
            $changed = true;
            continue;
        }
        $p = $products[$pid];
        $qty = max(1, (int)$line['qty']);
        if ($p['stock'] < 1) {
            unset($cart[$key]);
            $changed = true;
            continue;
        }
        if ($qty > (int)$p['stock']) {
            $qty = (int)$p['stock'];
            $changed = true;
            $cart[$key]['qty'] = $qty;
        }
        $unit = $p['sale_price'] !== null && (float)$p['sale_price'] > 0 ? (float)$p['sale_price'] : (float)$p['price'];
        $lineTotal = $unit * $qty;
        $subtotal += $lineTotal;
        $items[] = [
            'key'         => $key,
            'product_id'  => $pid,
            'name'        => $p['name'],
            'slug'        => $p['slug'],
            'sku'         => $p['sku'],
            'image'       => $p['image'],
            'size'        => (string)$line['size'],
            'color'       => (string)$line['color'],
            'qty'         => $qty,
            'stock'       => (int)$p['stock'],
            'unit_price'  => $unit,
            'line_total'  => $lineTotal,
        ];
    }
    $shipping = ($subtotal > 0 && $subtotal < FREE_SHIPPING_THRESHOLD) ? SHIPPING_FLAT : 0.0;
    return ['items' => $items, 'subtotal' => $subtotal, 'shipping' => $shipping, 'total' => $subtotal + $shipping, 'changed' => $changed];
}

/* ---------- Wishlist (DB-backed, logged-in customers) ---------- */
function wishlist_product_ids(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT wi.product_id FROM wishlist_items wi
         JOIN wishlists w ON w.id = wi.wishlist_id
         WHERE w.user_id = ?'
    );
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function wishlist_toggle(int $userId, int $productId): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $wishlistId = $stmt->fetchColumn();
    if (!$wishlistId) {
        $stmt = $pdo->prepare('INSERT INTO wishlists (user_id) VALUES (?)');
        $stmt->execute([$userId]);
        $wishlistId = (int)$pdo->lastInsertId();
    }
    $stmt = $pdo->prepare('SELECT id FROM wishlist_items WHERE wishlist_id = ? AND product_id = ?');
    $stmt->execute([$wishlistId, $productId]);
    $itemId = $stmt->fetchColumn();
    if ($itemId) {
        $pdo->prepare('DELETE FROM wishlist_items WHERE id = ?')->execute([$itemId]);
        return false; // removed
    }
    $pdo->prepare('INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)')->execute([$wishlistId, $productId]);
    return true; // added
}

/* ---------- Secure image upload ---------- */
function save_uploaded_image(string $field, string $prefix): ?string
{
    if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash_set('danger', 'Image upload failed (code ' . (int)$file['error'] . ').');
        return null;
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        flash_set('danger', 'Image is too large. Maximum size is 3 MB.');
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        flash_set('danger', 'Only JPG, PNG, or WebP images are allowed.');
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = $prefix . '-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        flash_set('danger', 'Could not save the uploaded image.');
        return null;
    }
    return $name;
}

/* ---------- Orders ---------- */
function generate_order_number(): string
{
    return 'BRV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

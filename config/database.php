<?php
/**
 * BRAYVEN — Database & site configuration.
 * Single source of truth for credentials and global constants.
 * Included by includes/functions.php; do not include directly on pages.
 */

declare(strict_types=1);

/* ---------- Database credentials (XAMPP defaults) ---------- */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'brayven');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default is empty; change for production
define('DB_CHARSET', 'utf8mb4');

/* ---------- Store settings ---------- */
define('SITE_NAME', 'BRAYVEN');
define('SITE_TAGLINE', 'Define Your Own Standard.');
define('CURRENCY', '$');
define('SHIPPING_FLAT', 6.95);
define('FREE_SHIPPING_THRESHOLD', 120.00);
define('PRODUCTS_PER_PAGE', 9);
define('UPLOAD_MAX_BYTES', 3 * 1024 * 1024); // 3 MB

/* ---------- Paths ---------- */
define('PROJECT_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', PROJECT_ROOT . '/uploads/products');

/*
 * Base URL auto-detection: works both when the project folder is the web root
 * (php -S / vhost) and when it lives in a subfolder such as /htdocs/brayven.
 */
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'])), '/') : '';
$projectPath = str_replace('\\', '/', PROJECT_ROOT);
$baseUrl = '';
if ($docRoot !== '' && stripos($projectPath, $docRoot) === 0) {
    $baseUrl = rtrim(substr($projectPath, strlen($docRoot)), '/');
}
define('BASE_URL', $baseUrl);

/** Build an absolute URL from a project-relative path. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Path to an uploaded/seed product image, with a graceful fallback. */
function product_image_url(?string $image): string
{
    if ($image !== null && $image !== '') {
        $fs = PROJECT_ROOT . '/uploads/products/' . $image;
        if (is_file($fs)) {
            return url('uploads/products/' . rawurlencode($image));
        }
        $fs = PROJECT_ROOT . '/assets/images/products/' . $image;
        if (is_file($fs)) {
            return url('assets/images/products/' . rawurlencode($image));
        }
    }
    return url('assets/images/products/placeholder.svg');
}

date_default_timezone_set('UTC');

/* ---------- PDO connection (lazy singleton) ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            // Never leak credentials or driver details to the browser.
            error_log('DB connection failed: ' . $e->getMessage());
            exit('<!doctype html><html><head><title>Service unavailable</title></head>'
                . '<body style="font-family:sans-serif;padding:3rem;text-align:center">'
                . '<h1>We&rsquo;ll be right back.</h1><p>The store is temporarily unavailable. Please try again shortly.</p></body></html>');
        }
    }
    return $pdo;
}

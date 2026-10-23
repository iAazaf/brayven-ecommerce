<?php
/** BRAYVEN — Logout. */
require_once __DIR__ . '/includes/init.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
session_regenerate_id(true);
flash_set('success', 'You have been signed out. See you soon.');
redirect('index.php');

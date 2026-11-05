<?php
require_once __DIR__ . '/includes/bootstrap.php';

$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();

session_start();
flash('success', 'You have been logged out.');
redirect('login.php');

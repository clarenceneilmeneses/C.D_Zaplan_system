<?php
/* Streams a booking's uploaded valid ID to an admin. The uploads folder itself is blocked from direct access. */
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$path = q('SELECT valid_id_path FROM bookings WHERE id = ?', [(int)($_GET['booking'] ?? 0)])->fetchColumn();
$file = $path ? __DIR__ . '/uploads/ids/' . basename($path) : null;
if (!$file || !is_file($file)) {
  http_response_code(404);
  exit('No ID on file for this booking.');
}

$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="valid-id-BK-' . (int)$_GET['booking'] . '.' . pathinfo($file, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
readfile($file);

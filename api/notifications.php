<?php
/*
 * Bell dropdown data for the logged-in user.
 *   GET                         → { unread, items: [...] }
 *   POST action=read_all        → marks everything read
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = current_user();
if (!$me) json_out(['ok' => false, 'error' => 'Please log in again.'], 401);

if (is_post()) {
  csrf_check();
  if (($_POST['action'] ?? '') === 'read_all') {
    q('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$me['id']]);
  }
}

json_out(['ok' => true, 'unread' => unread_count((int)$me['id']), 'items' => recent_notifications((int)$me['id'])]);

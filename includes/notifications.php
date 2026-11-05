<?php
/*
 * In-app notifications (the bell in the portal top bar). Loaded by bootstrap.php.
 *
 *   Admins get:  new booking requests, new inquiries, client cancellations, new client sign-ups
 *   Clients get: booking confirmed / dispatched / completed / cancelled   (if "Booking updates" is on)
 *                invoice issued, due soon, overdue, payment received      (if "Invoice reminders" is on)
 *
 * Email and SMS are not sent yet (that needs SMTP / an SMS gateway). The switches on Account Settings
 * control these in-app notifications.
 */

const NOTIFY_ICONS = [
  'booking'  => 'lucide:calendar-days',
  'inquiry'  => 'lucide:message-square-text',
  'invoice'  => 'lucide:receipt',
  'payment'  => 'lucide:circle-check',
  'dispatch' => 'lucide:truck',
  'cancel'   => 'lucide:circle-x',
  'account'  => 'lucide:user-plus',
];

/** $refKey makes a notification one-off: a second notify() with the same key for the same user is ignored. */
function notify(int $userId, string $kind, string $title, string $body = '', string $link = '', ?string $refKey = null): void {
  q('INSERT IGNORE INTO notifications (user_id, kind, title, body, link, ref_key) VALUES (?, ?, ?, ?, ?, ?)',
    [$userId, $kind, mb_substr($title, 0, 150), mb_substr($body, 0, 255) ?: null, $link ?: null, $refKey]);
}

function notify_admins(string $kind, string $title, string $body = '', string $link = ''): void {
  foreach (q('SELECT id FROM users WHERE role = "admin" AND status = "Active"')->fetchAll(PDO::FETCH_COLUMN) as $id) {
    notify((int)$id, $kind, $title, $body, $link);
  }
}

/** Sends to a client only if they left the matching switch on (notify_booking or notify_invoice) */
function notify_client(int $userId, string $pref, string $kind, string $title, string $body = '', string $link = '', ?string $refKey = null): void {
  if (!in_array($pref, ['notify_booking', 'notify_invoice'], true)) return;
  if ((int)q("SELECT $pref FROM users WHERE id = ?", [$userId])->fetchColumn() !== 1) return;
  notify($userId, $kind, $title, $body, $link, $refKey);
}

/** Creates "due soon" / "overdue" reminders for a client's unpaid invoices (once per invoice). Runs on each portal page load. */
function invoice_reminders(array $user): void {
  if ($user['role'] !== 'client' || !(int)$user['notify_invoice']) return;
  $rows = q('SELECT i.invoice_no, i.due_on, i.amount FROM invoices i JOIN bookings b ON b.id = i.booking_id
             WHERE b.user_id = ? AND i.status = "Pending" AND i.due_on <= ?', [$user['id'], date('Y-m-d', strtotime('+3 days'))])->fetchAll();
  foreach ($rows as $r) {
    $link = 'invoice.php?no=' . urlencode($r['invoice_no']);
    if ($r['due_on'] < date('Y-m-d')) {
      notify((int)$user['id'], 'invoice', "{$r['invoice_no']} is overdue", money($r['amount']) . ' was due ' . fdate($r['due_on']) . '. Please settle it on site or call us.', $link, 'overdue:' . $r['invoice_no']);
    } else {
      notify((int)$user['id'], 'invoice', "{$r['invoice_no']} is due " . fdate($r['due_on'], 'M j'), money($r['amount']) . ' is due soon. Payment is cash on delivery / on site.', $link, 'due:' . $r['invoice_no']);
    }
  }
}

function unread_count(int $userId): int {
  return (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId])->fetchColumn();
}

/** Latest notifications in the shape the bell dropdown (assets/js/script.js) renders */
function recent_notifications(int $userId, int $limit = 8): array {
  $rows = q('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit, [$userId])->fetchAll();
  return array_map('notification_view', $rows);
}

function notification_view(array $n): array {
  return [
    'id' => (int)$n['id'], 'kind' => $n['kind'], 'icon' => NOTIFY_ICONS[$n['kind']] ?? 'lucide:bell',
    'title' => $n['title'], 'body' => (string)$n['body'], 'ago' => time_ago($n['created_at']),
    'unread' => $n['read_at'] === null, 'href' => 'notifications.php?go=' . (int)$n['id'],
  ];
}

function time_ago(string $datetime): string {
  $s = time() - strtotime($datetime);
  if ($s < 60)     return 'Just now';
  if ($s < 3600)   return floor($s / 60) . ' min ago';
  if ($s < 86400)  return floor($s / 3600) . ' hr' . (floor($s / 3600) == 1 ? '' : 's') . ' ago';
  if ($s < 604800) return floor($s / 86400) . ' day' . (floor($s / 86400) == 1 ? '' : 's') . ' ago';
  return fdate($datetime, 'M j, Y');
}

/** One notification row (dropdown + notifications page). Same markup as renderNotif() in script.js. */
function notification_item(array $n): string {
  return '<a href="' . e($n['href']) . '" class="notif-item' . ($n['unread'] ? ' is-unread' : '') . '">'
    . '<span class="notif-item__icon notif-item__icon--' . e($n['kind']) . '"><iconify-icon icon="' . e($n['icon']) . '" width="16"></iconify-icon></span>'
    . '<span class="notif-item__text"><span class="notif-item__title">' . e($n['title']) . '</span>'
    . ($n['body'] !== '' ? '<span class="notif-item__body">' . e($n['body']) . '</span>' : '')
    . '<span class="notif-item__ago">' . e($n['ago']) . '</span></span></a>';
}

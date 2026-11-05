<?php
/*
 * Loaded at the top of every page.
 * Starts the session and provides: db(), auth helpers, CSRF protection, flash messages,
 * formatting helpers (e, money, pill, ...) and the shared pricing formula.
 */
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

/* ---------------- Database ---------------- */
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    try {
      $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
      );
    } catch (PDOException $ex) {
      http_response_code(500);
      exit('<p style="font-family:sans-serif;padding:2rem">Cannot connect to the database. '
         . 'Start MySQL in XAMPP and import <b>database/cdzaplan_db.sql</b> (see README.md).</p>');
    }
  }
  return $pdo;
}

/** Run a prepared query: q('SELECT * FROM users WHERE id = ?', [$id]) */
function q(string $sql, array $params = []): PDOStatement {
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st;
}

function setting(string $key, $default = null) {
  static $all = null;
  if ($all === null) $all = q('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
  return $all[$key] ?? $default;
}

/* ---------------- Auth ---------------- */
function current_user(): ?array {
  static $user = false;
  if ($user === false) {
    $user = null;
    if (!empty($_SESSION['user_id'])) {
      $user = q('SELECT * FROM users WHERE id = ? AND status = "Active"', [$_SESSION['user_id']])->fetch() ?: null;
      if (!$user) unset($_SESSION['user_id']);   // account deleted or suspended → log out
    }
  }
  return $user;
}

function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

/** Put at the top of a page: require_role('admin') or require_role('client'). */
function require_role(string $role): array {
  $u = current_user();
  if (!$u) {
    redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
  }
  if ($u['role'] !== $role) {
    redirect($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php');
  }
  return $u;
}

function login_user(array $user, bool $remember = false): void {
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$user['id'];
  if ($remember) {   // keep the session cookie for 30 days instead of until the browser closes
    $p = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
      'expires' => time() + 60 * 60 * 24 * 30, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 30));
  }
}

/* ---------------- CSRF ---------------- */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void {
  $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!hash_equals(csrf_token(), (string)$sent)) {
    http_response_code(419);
    exit('Your session expired. Go back, refresh the page and try again.');
  }
}

/* ---------------- Requests / responses ---------------- */
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function redirect(string $to): void {
  header('Location: ' . $to);
  exit;
}

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

/** Only allow redirects back to a page of this site (prevents ?next=https://evil.example) */
function safe_next(?string $next, string $fallback): string {
  if (!$next) return $fallback;
  $page = basename((string)parse_url($next, PHP_URL_PATH));
  if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $page)) return $fallback;
  $query = parse_url($next, PHP_URL_QUERY);
  return $page . ($query ? '?' . $query : '');
}

/* ---------------- Flash messages (shown once after a redirect) ---------------- */
function flash(string $type, string $message): void { $_SESSION['flash'][] = [$type, $message]; }
function render_flash(): string {
  $html = '';
  foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
    $icon = $type === 'error' ? 'lucide:circle-alert' : 'lucide:circle-check';
    $html .= '<div class="alert alert--' . e($type) . '"><iconify-icon icon="' . $icon . '" width="16"></iconify-icon><span>' . e($msg) . '</span></div>';
  }
  unset($_SESSION['flash']);
  return $html;
}

/** Keep typed form values after a failed submit: old('email') */
function old(string $key, string $default = ''): string { return e($_POST[$key] ?? $default); }

/* ---------------- Formatting ---------------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/** JSON that is safe to print inside a <script> tag (a "</script>" in the data can't end the tag) */
function js($v): string { return json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); }

function initials(string $name): string {
  $out = '';
  foreach (preg_split('/\s+/', trim($name)) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
  return mb_substr($out, 0, 2);
}

function money($n, int $decimals = 2): string { return '₱' . number_format((float)$n, $decimals); }

/** ₱1.25M / ₱165K style for stat cards */
function money_short($n): string {
  $n = (float)$n;
  if ($n >= 1_000_000) return '₱' . rtrim(rtrim(number_format($n / 1_000_000, 2), '0'), '.') . 'M';
  if ($n >= 1_000)     return '₱' . rtrim(rtrim(number_format($n / 1_000, 1), '0'), '.') . 'K';
  return money($n, 0);
}

function fdate(?string $d, string $fmt = 'M d, Y'): string { return $d ? date($fmt, strtotime($d)) : '—'; }
function date_range(string $a, string $b): string { return fdate($a, 'M d') . ' - ' . fdate($b, 'M d, Y'); }
function booking_ref($id): string { return 'BK-' . $id; }

/** Pending invoices past their due date are shown as Overdue */
function invoice_status(array $inv): string {
  return ($inv['status'] === 'Pending' && $inv['due_on'] < date('Y-m-d')) ? 'Overdue' : $inv['status'];
}

/* Status pill: green / amber / blue / rose / slate (styles in assets/css/style.css) */
function pill(string $status): string {
  $map = [
    'Active'         => ['green', 'Active'],
    'Paid'           => ['green', 'Paid'],
    'Confirmed'      => ['green', 'Confirmed'],
    'Available'      => ['green', 'Standby'],
    'Due'            => ['amber', 'Deposit Due'],
    'Pending'        => ['amber', 'Pending'],
    'Pending Review' => ['amber', 'Pending Review'],
    'Deployed'       => ['blue',  'Deployed'],
    'New'            => ['green', 'New'],
    'Replied'        => ['blue',  'Replied'],
    'Archived'       => ['slate', 'Archived'],
    'Overdue'        => ['rose',  'Overdue'],
    'Maintenance'    => ['rose',  'Maintenance'],
    'Suspended'      => ['rose',  'Suspended'],
    'Cancelled'      => ['rose',  'Cancelled'],
    'Inactive'       => ['slate', 'Inactive'],
    'Completed'      => ['slate', 'Completed'],
  ];
  [$tone, $label] = $map[$status] ?? ['slate', $status];
  return '<span class="pill pill--' . $tone . '"><span class="pill__dot">●</span>' . e($label) . '</span>';
}

/* ---------------- Pricing (the same formula runs in assets/js/estimator.js) ---------------- */
function equipment_types(): array {
  static $types = null;
  if ($types === null) $types = q('SELECT * FROM equipment_types ORDER BY id')->fetchAll();
  return $types;
}
function equipment_type(int $id): ?array {
  foreach (equipment_types() as $t) if ((int)$t['id'] === $id) return $t;
  return null;
}

/**
 * rental       = daily rate × days
 * mobilization = 2 trips (delivery + pull-out) × MAX(minimum trip fee, km × per-km rate)
 */
function estimate_cost(array $type, float $km, int $days): array {
  $trip   = max((float)$type['min_trip_fee'], $km * (float)$type['per_km_rate']);
  $rental = (float)$type['daily_rate'] * $days;
  $mobil  = round($trip * 2, 2);
  return ['rental' => $rental, 'mobilization' => $mobil, 'total' => $rental + $mobil];
}

/** Straight-line distance in km between two coordinates */
function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float {
  $r = 6371;
  $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
  $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
  return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * The browser sends the road distance it got from the routing service. Never trust it blindly:
 * a road can't be shorter than the straight line, and is rarely more than 3× longer.
 * If the number is outside that range, fall back to straight line × 1.3.
 */
function trusted_distance(float $lat, float $lng, $claimedKm): float {
  $line = haversine_km((float)setting('base_lat'), (float)setting('base_lng'), $lat, $lng);
  $km = is_numeric($claimedKm) ? (float)$claimedKm : -1;
  if ($km < $line * 0.98 || $km > $line * 3 + 2) $km = $line * 1.3;
  return round($km, 2);
}

/** Data handed to assets/js/estimator.js */
function estimator_config(): array {
  return [
    'base'  => ['lat' => (float)setting('base_lat'), 'lng' => (float)setting('base_lng'), 'label' => setting('base_label')],
    'maxKm' => (float)setting('max_service_km', 150),
    'types' => array_map(fn($t) => [
      'id' => (int)$t['id'], 'name' => $t['name'], 'daily' => (float)$t['daily_rate'],
      'perKm' => (float)$t['per_km_rate'], 'minTrip' => (float)$t['min_trip_fee'],
    ], equipment_types()),
  ];
}

/* ---------------- Valid ID uploads ---------------- */
/** Saves an uploaded ID into uploads/ids/ and returns the stored file name, or throws with a friendly message. */
function store_valid_id(array $file): string {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Please attach a valid ID.');
  if ($file['size'] > 5 * 1024 * 1024) throw new RuntimeException('The ID file must be 5 MB or smaller.');
  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
  if (!isset($allowed[$mime])) throw new RuntimeException('The ID must be a JPG, PNG, WEBP or PDF file.');
  $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../uploads/ids/' . $name)) {
    throw new RuntimeException('Could not save the ID file. Check that uploads/ids is writable.');
  }
  return $name;
}

/* ---------------- CSV export ---------------- */
function csv_download(string $filename, array $header, array $rows): void {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF");   // BOM so Excel shows ₱ correctly
  fputcsv($out, $header);
  foreach ($rows as $r) fputcsv($out, array_values($r));
  exit;
}

/** Writes a fleet status change and logs it for the availability report */
function set_equipment_status(int $equipmentId, string $to, string $note): void {
  $from = q('SELECT status FROM equipment WHERE id = ?', [$equipmentId])->fetchColumn();
  if ($from === false || $from === $to) return;
  q('UPDATE equipment SET status = ? WHERE id = ?', [$to, $equipmentId]);
  q('INSERT INTO equipment_logs (equipment_id, from_status, to_status, note) VALUES (?, ?, ?, ?)', [$equipmentId, $from, $to, $note]);
}

/* ---------------- Lists (pagination, date filter, search) + notifications ---------------- */
require_once __DIR__ . '/lists.php';
require_once __DIR__ . '/notifications.php';
handle_date_filter_request();   // ?range=… from the top-bar date picker → saved in the session

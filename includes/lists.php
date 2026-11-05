<?php
/*
 * Shared list helpers (loaded by bootstrap.php):
 *   - paginate() / pagination()   server-side paging for every table
 *   - date_filter()               the global date range picked in the portal top bar (kept in the session)
 *   - where() / like_any()        small WHERE-clause builders for search + date filters
 *   - global_search()             the top-bar search (api/search.php and search.php)
 */

/* ---------------- URLs ---------------- */
/** The current page's URL with some query parameters changed. null or '' removes a parameter. */
function url_with(array $changes = []): string {
  $params = array_filter(array_merge($_GET, $changes), fn($v) => $v !== null && $v !== '');
  return basename($_SERVER['SCRIPT_NAME']) . ($params ? '?' . http_build_query($params) : '');
}

/** Hidden inputs that keep the current query parameters when a GET form is submitted */
function keep_params(array $except = []): string {
  $html = '';
  foreach ($_GET as $k => $v) {
    if (in_array($k, $except, true) || is_array($v) || $v === '') continue;
    $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
  }
  return $html;
}

/* ---------------- WHERE builders ---------------- */
/** Joins [sql, params] pairs into "WHERE a AND b". Empty parts are skipped. */
function where(array ...$conds): array {
  $sql = []; $params = [];
  foreach ($conds as [$s, $p]) {
    if ($s === '') continue;
    $sql[] = "($s)";
    array_push($params, ...$p);
  }
  return [$sql ? 'WHERE ' . implode(' AND ', $sql) : '', $params];
}

/** "col1 LIKE %q% OR col2 LIKE %q% ..." — empty when there is no search text */
function like_any(array $cols, ?string $q): array {
  $q = trim((string)$q);
  if ($q === '') return ['', []];
  $term = '%' . addcslashes($q, '%_\\') . '%';
  return [implode(' OR ', array_map(fn($c) => "$c LIKE ?", $cols)), array_fill(0, count($cols), $term)];
}

/** The ?q= search text of the current page */
function search_term(): string { return mb_substr(trim((string)($_GET['q'] ?? '')), 0, 100); }

/* ---------------- Pagination ---------------- */
/**
 * Runs $sql for one page of results. The page number comes from ?page= (or ?$param= when a page
 * has several paged tables). Returns rows plus what pagination() needs.
 */
function paginate(string $sql, array $params = [], int $perPage = 10, string $param = 'page'): array {
  $total  = (int)q("SELECT COUNT(*) FROM ($sql) AS counted", $params)->fetchColumn();
  $pages  = max(1, (int)ceil($total / $perPage));
  $page   = min($pages, max(1, (int)($_GET[$param] ?? 1)));
  $offset = ($page - 1) * $perPage;
  $rows   = q("$sql LIMIT $perPage OFFSET $offset", $params)->fetchAll();
  return ['rows' => $rows, 'page' => $page, 'pages' => $pages, 'total' => $total, 'param' => $param,
          'first' => $total ? $offset + 1 : 0, 'last' => $offset + count($rows)];
}

/** "Showing 11–20 of 43 bookings" + page links. $anchor keeps the scroll position on pages with several tables. */
function pagination(array $p, string $noun = 'results', string $anchor = ''): string {
  $info = $p['total'] ? "Showing {$p['first']}–{$p['last']} of {$p['total']} $noun" : "No $noun";
  $html = '<div class="pager"><span class="pager__info">' . e($info) . '</span>';
  if ($p['pages'] > 1) {
    $link = function (int $n, string $label, string $aria) use ($p, $anchor): string {
      if ($n < 1 || $n > $p['pages']) return '<span class="pager__btn is-disabled" aria-hidden="true">' . $label . '</span>';
      $cur = $n === $p['page'];
      return '<a href="' . e(url_with([$p['param'] => $n > 1 ? $n : null]) . ($anchor ? "#$anchor" : '')) . '" class="pager__btn' . ($cur ? ' is-current' : '') . '"'
           . ' aria-label="' . e($aria) . '"' . ($cur ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    };
    // Window of pages: 1 … 4 5 [6] 7 8 … 20
    $show = array_unique(array_filter([1, $p['page'] - 1, $p['page'], $p['page'] + 1, $p['pages']], fn($n) => $n >= 1 && $n <= $p['pages']));
    sort($show);
    $html .= '<nav class="pager__nav" aria-label="Pagination">';
    $html .= $link($p['page'] - 1, '<iconify-icon icon="lucide:chevron-left" width="16"></iconify-icon>', 'Previous page');
    $prev = 0;
    foreach ($show as $n) {
      if ($n - $prev > 1) $html .= '<span class="pager__gap">…</span>';
      $html .= $link($n, (string)$n, "Page $n");
      $prev = $n;
    }
    $html .= $link($p['page'] + 1, '<iconify-icon icon="lucide:chevron-right" width="16"></iconify-icon>', 'Next page');
    $html .= '</nav>';
  }
  return $html . '</div>';
}

/** Search box for a list's toolbar. Submits ?q= to the same page and keeps the other filters. */
function list_search(string $placeholder): string {
  $q = search_term();
  $reset = array_merge(['q'], array_filter(array_keys($_GET), fn($k) => str_ends_with((string)$k, 'page')));   // a new search starts at page 1
  return '<form method="get" class="search search--grow" role="search">' . keep_params($reset)
    . '<iconify-icon icon="lucide:search" width="16" class="search__icon"></iconify-icon>'
    . '<input name="q" type="search" value="' . e($q) . '" placeholder="' . e($placeholder) . '" class="search__input" aria-label="' . e($placeholder) . '">'
    . ($q !== '' ? '<a href="' . e(url_with(['q' => null, 'page' => null])) . '" class="search__clear" aria-label="Clear search"><iconify-icon icon="lucide:x" width="14"></iconify-icon></a>' : '')
    . '</form>';
}

/* ---------------- Global date filter ---------------- */
function date_presets(): array {
  return [
    'all'        => 'All time',
    'today'      => 'Today',
    '7d'         => 'Last 7 days',
    '30d'        => 'Last 30 days',
    'month'      => 'This month',
    'last_month' => 'Last month',
    'year'       => 'This year',
  ];
}

/**
 * The date range chosen in the top bar: ['key', 'from', 'to', 'label'] with from/to as Y-m-d (null for "All time").
 * Presets are recalculated on every request, so "This month" keeps meaning this month.
 */
function date_filter(): array {
  $saved = $_SESSION['date_range'] ?? ['key' => 'all'];
  $key = $saved['key'] ?? 'all';
  $today = date('Y-m-d');
  [$from, $to] = match ($key) {
    'today'      => [$today, $today],
    '7d'         => [date('Y-m-d', strtotime('-6 days')), $today],
    '30d'        => [date('Y-m-d', strtotime('-29 days')), $today],
    'month'      => [date('Y-m-01'), date('Y-m-t')],
    'last_month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'year'       => [date('Y-01-01'), date('Y-12-31')],
    'custom'     => [$saved['from'] ?? null, $saved['to'] ?? null],
    default      => [null, null],
  };
  if (!$from || !$to) $key = 'all';
  $label = $key === 'custom'
    ? (substr($from, 0, 4) === substr($to, 0, 4) ? fdate($from, 'M j') : fdate($from, 'M j, Y')) . ' – ' . fdate($to, 'M j, Y')
    : date_presets()[$key];
  return ['key' => $key, 'from' => $key === 'all' ? null : $from, 'to' => $key === 'all' ? null : $to, 'label' => $label];
}

/** Stores ?range=…(&from=&to=) in the session, then reloads the page without those parameters */
function handle_date_filter_request(): void {
  if (!isset($_GET['range']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
  $key = (string)$_GET['range'];
  $valid = fn($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
  if ($key === 'custom' && $valid($_GET['from'] ?? null) && $valid($_GET['to'] ?? null)) {
    [$from, $to] = [$_GET['from'], $_GET['to']];
    if ($from > $to) [$from, $to] = [$to, $from];
    $_SESSION['date_range'] = ['key' => 'custom', 'from' => $from, 'to' => $to];
  } elseif (isset(date_presets()[$key])) {
    $_SESSION['date_range'] = ['key' => $key];
  }
  redirect(url_with(['range' => null, 'from' => null, 'to' => null, 'page' => null]));
}

/** Rows whose date column falls inside the range (works for DATE and DATETIME columns) */
function date_where(string $col): array {
  $f = date_filter();
  if (!$f['from']) return ['', []];
  return ["$col >= ? AND $col < ?", [$f['from'], date('Y-m-d', strtotime($f['to'] . ' +1 day'))]];
}

/** Rentals that overlap the range at all (start before it ends, end after it starts) */
function date_overlap(string $startCol, string $endCol): array {
  $f = date_filter();
  if (!$f['from']) return ['', []];
  return ["$startCol <= ? AND $endCol >= ?", [$f['to'], $f['from']]];
}

/* ---------------- Global search (top bar) ---------------- */
/**
 * Searches everything the user may see. Returns groups:
 *   [['label', 'icon', 'more' => link to the full list, 'items' => [['title', 'meta', 'link', 'status'], ...]], ...]
 */
function global_search(array $me, string $q, int $limit = 5): array {
  $q = trim($q);
  if (mb_strlen($q) < 2) return [];
  $groups = [];
  $add = function (string $label, string $icon, string $more, array $items) use (&$groups) {
    if ($items) $groups[] = ['label' => $label, 'icon' => $icon, 'more' => $more, 'items' => $items];
  };
  $lim = (int)$limit;
  $qs = urlencode($q);

  if ($me['role'] === 'admin') {
    [$w, $p] = where(like_any(['CONCAT("BK-", b.id)', 'u.full_name', 'b.contact_name', 'b.contact_phone', 'b.site_address', 't.name'], $q));
    $add('Bookings', 'lucide:calendar-days', "booking.php?q=$qs", array_map(fn($r) => [
      'title' => booking_ref($r['id']) . ' · ' . $r['full_name'],
      'meta' => $r['equipment'] . ' · ' . date_range($r['start_date'], $r['end_date']),
      'link' => 'booking.php?open=' . $r['id'], 'status' => $r['status'],
    ], q("SELECT b.id, b.status, b.start_date, b.end_date, u.full_name, t.name AS equipment FROM bookings b
          JOIN users u ON u.id = b.user_id JOIN equipment_types t ON t.id = b.type_id $w ORDER BY b.created_at DESC LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(like_any(['full_name', 'email', 'phone', 'item', 'inquiry_type', 'message'], $q));
    $add('Inquiries', 'lucide:message-square-text', "inquiries.php?q=$qs", array_map(fn($r) => [
      'title' => $r['full_name'] . ' · ' . $r['item'], 'meta' => $r['inquiry_type'] . ' · ' . fdate($r['created_at']),
      'link' => 'inquiries.php?open=' . $r['id'], 'status' => $r['status'],
    ], q("SELECT id, full_name, item, inquiry_type, status, created_at FROM inquiries $w ORDER BY created_at DESC LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(like_any(['full_name', 'email', 'phone', 'company'], $q));
    $add('Users', 'lucide:users', "user-management.php?q=$qs", array_map(fn($r) => [
      'title' => $r['full_name'], 'meta' => $r['email'] . ' · ' . ucfirst($r['role']),
      'link' => 'user-management.php?q=' . urlencode($r['email']), 'status' => $r['status'],
    ], q("SELECT full_name, email, role, status FROM users $w ORDER BY full_name LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(like_any(['e.asset_code', 'e.plate_no', 'e.model', 'e.capacity', 't.name'], $q));
    $add('Fleet', 'lucide:truck', "fleet.php?q=$qs", array_map(fn($r) => [
      'title' => $r['asset_code'] . ' · ' . $r['type_name'], 'meta' => $r['model'] . ($r['plate_no'] ? ' · ' . $r['plate_no'] : ''),
      'link' => 'fleet.php?q=' . urlencode($r['asset_code']), 'status' => $r['status'],
    ], q("SELECT e.asset_code, e.plate_no, e.model, e.status, t.name AS type_name FROM equipment e
          JOIN equipment_types t ON t.id = e.type_id $w ORDER BY e.asset_code LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(like_any(['full_name', 'position'], $q));
    $add('Employees', 'lucide:wallet', "payroll.php?q=$qs#employees", array_map(fn($r) => [
      'title' => $r['full_name'], 'meta' => $r['position'] . ' · ' . money($r['hourly_rate']) . '/hr',
      'link' => 'payroll.php?q=' . urlencode($r['full_name']) . '#employees', 'status' => $r['status'],
    ], q("SELECT full_name, position, hourly_rate, status FROM employees $w ORDER BY full_name LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(like_any(['i.invoice_no', 'CONCAT("BK-", i.booking_id)', 'u.full_name'], $q));
    $add('Invoices', 'lucide:receipt', '', array_map(fn($r) => [
      'title' => $r['invoice_no'] . ' · ' . $r['full_name'], 'meta' => booking_ref($r['booking_id']) . ' · ' . money($r['amount']),
      'link' => 'invoice.php?no=' . urlencode($r['invoice_no']), 'status' => invoice_status($r),
    ], q("SELECT i.*, u.full_name FROM invoices i JOIN bookings b ON b.id = i.booking_id JOIN users u ON u.id = b.user_id
          $w ORDER BY i.issued_on DESC LIMIT $lim", $p)->fetchAll()));
  } else {
    [$w, $p] = where(['b.user_id = ?', [$me['id']]], like_any(['CONCAT("BK-", b.id)', 'b.site_address', 't.name'], $q));
    $add('My bookings', 'lucide:truck', "dashboard.php?q=$qs", array_map(fn($r) => [
      'title' => booking_ref($r['id']) . ' · ' . $r['equipment'], 'meta' => $r['site_address'] . ' · ' . date_range($r['start_date'], $r['end_date']),
      'link' => 'dashboard.php?q=' . urlencode(booking_ref($r['id'])), 'status' => $r['status'],
    ], q("SELECT b.id, b.status, b.site_address, b.start_date, b.end_date, t.name AS equipment FROM bookings b
          JOIN equipment_types t ON t.id = b.type_id $w ORDER BY b.start_date DESC LIMIT $lim", $p)->fetchAll()));

    [$w, $p] = where(['b.user_id = ?', [$me['id']]], like_any(['i.invoice_no', 'CONCAT("BK-", i.booking_id)', 't.name'], $q));
    $add('Invoices & receipts', 'lucide:file-text', "invoices.php?q=$qs", array_map(fn($r) => [
      'title' => $r['invoice_no'] . ' · ' . $r['equipment'], 'meta' => booking_ref($r['booking_id']) . ' · ' . money($r['amount']),
      'link' => 'invoice.php?no=' . urlencode($r['invoice_no']), 'status' => invoice_status($r),
    ], q("SELECT i.*, t.name AS equipment FROM invoices i JOIN bookings b ON b.id = i.booking_id JOIN equipment_types t ON t.id = b.type_id
          $w ORDER BY i.issued_on DESC LIMIT $lim", $p)->fetchAll()));
  }
  return $groups;
}

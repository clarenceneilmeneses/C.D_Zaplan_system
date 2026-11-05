<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

if (is_post()) {
  csrf_check();
  $status = $_POST['status'] ?? '';
  if (in_array($status, ['New', 'Replied', 'Archived'], true)) {
    q('UPDATE inquiries SET status = ? WHERE id = ?', [$status, (int)($_POST['id'] ?? 0)]);
    flash('success', 'Inquiry marked as ' . $status . '.');
  }
  redirect(url_with(['open' => null]));   // back to the same filters and page
}

$statuses = ['New', 'Replied', 'Archived'];
$status = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$search = search_term();
$dayAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

$from = 'FROM inquiries i LEFT JOIN equipment_types t ON t.id = i.type_id';
$searchCond = like_any(['i.full_name', 'i.email', 'i.phone', 'i.item', 'i.inquiry_type', 'i.message'], $search);
$rangeCond = date_where('i.created_at');
[$w, $p] = where($searchCond, $rangeCond, $status ? ['i.status = ?', [$status]] : ['', []]);
$listSql = "SELECT i.*, t.name AS equipment $from $w ORDER BY i.created_at DESC, i.id DESC";

/** Row → the shape the detail drawer and CSV use */
function inquiry_view(array $i, string $dayAgo): array {
  return [
    'id'       => (int)$i['id'],
    'status'   => $i['status'],
    'date'     => fdate($i['created_at'], 'M d, Y · g:i A'),
    'name'     => $i['full_name'],
    'email'    => $i['email'],
    'phone'    => $i['phone'],
    'item'     => $i['item'],
    'type'     => $i['inquiry_type'],
    'followup' => (int)($i['status'] === 'New' && $i['created_at'] < $dayAgo),   // still unanswered after 24 hours
    'message'  => $i['message'],
    'estimate' => $i['estimate_amount'] !== null ? [
      'equipment' => $i['equipment'], 'days' => (int)$i['rental_days'], 'km' => (float)$i['distance_km'],
      'total' => money($i['estimate_amount']),
      'map' => 'https://www.openstreetmap.org/directions?route=' . setting('base_lat') . ',' . setting('base_lng') . ';' . $i['site_lat'] . ',' . $i['site_lng'],
    ] : null,
  ];
}

// CSV of everything matching the current filters (all pages)
if (($_GET['export'] ?? '') === 'csv') {
  $rows = array_map(function ($r) use ($dayAgo) {
    $d = inquiry_view($r, $dayAgo);
    return [$d['status'], $d['date'], $d['name'], $d['email'], $d['phone'], $d['item'], $d['type'], $d['estimate']['total'] ?? '', $d['message']];
  }, q($listSql, $p)->fetchAll());
  csv_download('inquiries.csv', ['Status', 'Date', 'Name', 'Email', 'Phone', 'Inquired About', 'Category', 'Estimate', 'Message'], $rows);
}

$list = paginate($listSql, $p);
$inquiries = array_map(fn($r) => inquiry_view($r, $dayAgo), $list['rows']);

// Stat cards follow the date range (not the search box)
[$sw, $sp] = where($rangeCond);
$stat = q("SELECT COUNT(*) AS total, SUM(status = 'New') AS new_cnt, SUM(status = 'New' AND created_at < ?) AS followup
           FROM inquiries i $sw", array_merge([$dayAgo], $sp))->fetch();
$rangeLabel = date_filter()['label'];
$stats = [
  ['Total Inquiries',   (int)$stat['total'],    $rangeLabel,                 'lucide:inbox'],
  ['New / Unread',      (int)$stat['new_cnt'],  'Waiting for a first reply', 'lucide:mail'],
  ['Pending Follow-up', (int)$stat['followup'], 'No reply after 24 hours',   'lucide:clock'],
];

// ?open=ID (from a notification or search result) opens that inquiry even if it's on another page
$open = null;
if ($openId = (int)($_GET['open'] ?? 0)) {
  $row = q("SELECT i.*, t.name AS equipment $from WHERE i.id = ?", [$openId])->fetch();
  $open = $row ? inquiry_view($row, $dayAgo) : null;
}

$pageTitle = 'Inquiry Management | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'inquiries';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Inquiry Management</h1>
<p class="page-sub">Messages sent from the website inquiry form and the map estimator.</p>

<div class="stat-grid">
  <?php foreach ($stats as [$label,$val,$sub,$icon]): ?>
  <div class="stat-card">
    <div class="stat-card__head"><span><?= $label ?></span><iconify-icon icon="<?= $icon ?>" width="16" class="stat-card__icon"></iconify-icon></div>
    <div class="stat-card__value"><?= $val ?></div>
    <div class="stat-card__sub"><?= e($sub) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<section class="card card--spaced">
  <!-- Toolbar -->
  <div class="toolbar">
    <?= list_search('Search name, email, item or message…') ?>
    <select onchange="location.href = this.value" class="select" aria-label="Status">
      <option value="<?= e(url_with(['status' => null, 'page' => null])) ?>">Status: All</option>
      <?php foreach ($statuses as $s): ?>
      <option value="<?= e(url_with(['status' => $s, 'page' => null])) ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <a href="<?= e(url_with(['export' => 'csv', 'page' => null])) ?>" class="btn btn--outline">
      <iconify-icon icon="lucide:download" width="16"></iconify-icon> Export CSV
    </a>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table class="table table--min-860 table--hover">
      <thead>
        <tr><th>Status</th><th>Date Submitted</th><th>Customer</th><th>Inquired About</th><th>Category</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($inquiries as $i): ?>
        <tr>
          <td><?= pill($i['status']) ?></td>
          <td class="td-muted td-nowrap"><?= e($i['date']) ?></td>
          <td><div class="person">
            <span class="avatar"><?= e(initials($i['name'])) ?></span>
            <div><div class="cell-title"><?= e($i['name']) ?></div><div class="cell-meta"><?= e($i['email']) ?></div></div></div></td>
          <td><?= e($i['item']) ?><?php if ($i['estimate']): ?><div class="cell-meta"><iconify-icon icon="lucide:map-pin" width="11"></iconify-icon> Estimate <?= e($i['estimate']['total']) ?></div><?php endif; ?></td>
          <td class="td-muted"><?= e($i['type']) ?></td>
          <td><button type="button" data-inquiry="<?= e(json_encode($i)) ?>" onclick="openDrawer(JSON.parse(this.dataset.inquiry))" class="btn btn--line btn--sm">View Details</button></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$inquiries): ?>
        <tr><td colspan="6" class="empty"><?= ($search !== '' || $status || date_filter()['from']) ? 'No inquiries match your filters.' : 'No inquiries yet.' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($list, 'inquiries') ?>
</section>

<!-- Detail drawer -->
<div id="inquiryDrawer" class="overlay overlay-right" role="dialog" aria-modal="true" aria-labelledby="drawerTitle"
     onclick="if (event.target === this) closeDrawer()">
  <aside class="sheet drawer">
    <div class="drawer__head">
      <div>
        <h2 id="drawerTitle" class="drawer__title">Inquiry Details</h2>
        <div class="drawer__meta"><span id="dStatus"></span><span id="dDate" class="drawer__date"></span></div>
      </div>
      <button onclick="closeDrawer()" aria-label="Close" class="close-btn"><iconify-icon icon="lucide:x" width="22"></iconify-icon></button>
    </div>

    <div class="drawer__body">
      <div>
        <div class="eyebrow eyebrow--muted drawer__label">Customer</div>
        <div class="drawer__customer">
          <span id="dInitials" class="avatar avatar--navy avatar--lg"></span>
          <div class="drawer__name" id="dName"></div>
        </div>
        <dl class="detail-list detail-list--spaced">
          <div class="detail"><dt>Email</dt><dd id="dEmail"></dd></div>
          <div class="detail"><dt>Phone</dt><dd id="dPhone"></dd></div>
        </dl>
      </div>

      <div>
        <div class="eyebrow eyebrow--muted drawer__label">Inquiry</div>
        <dl class="detail-list">
          <div class="detail"><dt>About</dt><dd><a id="dItem" href="fleet.php" class="detail__link"></a></dd></div>
          <div class="detail"><dt>Type</dt><dd id="dType"></dd></div>
        </dl>
      </div>

      <div id="dEstimateBox" class="hidden">
        <div class="eyebrow eyebrow--muted drawer__label">Website estimate</div>
        <dl class="detail-list">
          <div class="detail"><dt>Unit</dt><dd id="dEstUnit"></dd></div>
          <div class="detail"><dt>Distance</dt><dd><a id="dEstMap" target="_blank" rel="noopener" class="detail__link"></a></dd></div>
          <div class="detail"><dt>Total</dt><dd id="dEstTotal" class="strong"></dd></div>
        </dl>
      </div>

      <div>
        <div class="eyebrow eyebrow--muted drawer__label">Message</div>
        <p id="dMessage" class="drawer__message"></p>
      </div>
    </div>

    <form method="post" class="drawer__foot">
      <?= csrf_field() ?><input type="hidden" name="id" id="dId">
      <a id="dReply" href="#" class="btn btn--amber"><iconify-icon icon="lucide:reply" width="16"></iconify-icon> Reply via Email</a>
      <button name="status" value="Replied" class="btn btn--outline">Mark as Replied</button>
      <button name="status" value="Archived" class="btn btn--ghost">Archive</button>
      <button name="status" value="New" id="dReopen" class="btn btn--ghost">Mark as New</button>
    </form>
  </aside>
</div>

<script>
// Status badge (same markup as pill() in includes/bootstrap.php)
const TONE = { New: 'green', Replied: 'blue', Archived: 'slate' };
const badge = s => `<span class="pill pill--${TONE[s]}"><span class="pill__dot">●</span>${s}</span>`;

function openDrawer(d) {
  const $ = id => document.getElementById(id);
  $('dId').value = d.id;
  $('dStatus').innerHTML = badge(d.status);
  $('dDate').textContent = d.date;
  $('dInitials').textContent = d.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  $('dName').textContent = d.name;
  $('dEmail').textContent = d.email;
  $('dPhone').textContent = d.phone || '—';
  $('dItem').textContent = d.item;
  $('dType').textContent = d.type;
  $('dMessage').textContent = d.message;
  $('dReopen').classList.toggle('hidden', d.status === 'New');
  $('dEstimateBox').classList.toggle('hidden', !d.estimate);
  if (d.estimate) {
    $('dEstUnit').textContent = `${d.estimate.equipment} · ${d.estimate.days} day(s)`;
    $('dEstMap').textContent = `${d.estimate.km.toFixed(1)} km · open route map`;
    $('dEstMap').href = d.estimate.map;
    $('dEstTotal').textContent = d.estimate.total;
  }
  $('dReply').href = 'mailto:' + d.email + '?subject=' + encodeURIComponent('Re: ' + d.item + ' (' + d.type + ')');
  $('inquiryDrawer').classList.add('is-open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('inquiryDrawer').classList.remove('is-open');
  document.body.style.overflow = '';
}
<?php if ($open): ?>document.addEventListener('DOMContentLoaded', () => openDrawer(<?= js($open) ?>));<?php endif; ?>
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>

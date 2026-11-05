<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

// Weeks to choose from: only those inside the top-bar date range (a week linked directly with ?period= is always allowed)
[$w, $p] = where(date_overlap('period_start', 'period_end'));
$periods = q("SELECT DISTINCT period_start, period_end FROM payroll_entries $w ORDER BY period_start DESC", $p)->fetchAll(PDO::FETCH_NUM);
$selected = $_GET['period'] ?? ($periods[0][0] ?? null);
$payrollPeriod = null;
foreach ($periods as $p) if ($p[0] === $selected) $payrollPeriod = $p;
if (!$payrollPeriod && $selected) {
  $payrollPeriod = q('SELECT period_start, period_end FROM payroll_entries WHERE period_start = ? LIMIT 1', [$selected])->fetch(PDO::FETCH_NUM) ?: null;
  if ($payrollPeriod) array_unshift($periods, $payrollPeriod);
}

function payroll_rows(?array $period): array {
  return $period ? q('SELECT p.*, e.full_name, e.position FROM payroll_entries p JOIN employees e ON e.id = p.employee_id
                      WHERE p.period_start = ? ORDER BY e.full_name', [$period[0]])->fetchAll() : [];
}

// CSV export (also used by the "Export Payroll" button on the overview page)
if (($_GET['export'] ?? '') === 'csv') {
  $rows = array_map(fn($r) => [$r['full_name'], $r['position'], $r['period_start'], $r['period_end'], $r['hours'], $r['rate'], $r['total'], $r['status'], $r['paid_on']],
                    payroll_rows($payrollPeriod));
  csv_download('payroll-' . ($payrollPeriod[0] ?? 'empty') . '.csv',
               ['Employee', 'Position', 'Period Start', 'Period End', 'Hours', 'Rate', 'Total', 'Status', 'Paid On'], $rows);
}

if (is_post()) {
  csrf_check();
  $back = url_with();   // same week, search and page
  switch ($_POST['action'] ?? '') {
    case 'add_entry':
      $emp   = q('SELECT * FROM employees WHERE id = ? AND status = "Active"', [(int)($_POST['employee_id'] ?? 0)])->fetch();
      $start = $_POST['period_start'] ?? '';
      $hours = $_POST['hours'] ?? '';
      if (!$emp || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !is_numeric($hours) || $hours < 0 || $hours > 168) {
        flash('error', 'Choose an employee, a week start date and 0–168 hours.');
        redirect($back);
      }
      $end = date('Y-m-d', strtotime($start . ' +6 days'));
      try {
        q('INSERT INTO payroll_entries (employee_id, period_start, period_end, hours, rate, total) VALUES (?, ?, ?, ?, ?, ?)',
          [$emp['id'], $start, $end, $hours, $emp['hourly_rate'], round($hours * $emp['hourly_rate'], 2)]);
        flash('success', "Added {$emp['full_name']}'s hours for the week of " . fdate($start) . '.');
      } catch (PDOException $ex) {
        flash('error', "{$emp['full_name']} already has an entry for that week.");
      }
      redirect('payroll.php?period=' . urlencode($start));

    case 'mark_paid':
      q('UPDATE payroll_entries SET status = "Paid", paid_on = CURDATE() WHERE id = ?', [(int)$_POST['entry_id']]);
      flash('success', 'Marked as paid.');
      break;

    case 'pay_all':
      $n = q('UPDATE payroll_entries SET status = "Paid", paid_on = CURDATE() WHERE period_start = ? AND status = "Pending"', [$selected])->rowCount();
      flash('success', "$n entr" . ($n === 1 ? 'y' : 'ies') . ' marked as paid.');
      break;

    case 'delete_entry':
      q('DELETE FROM payroll_entries WHERE id = ?', [(int)$_POST['entry_id']]);
      flash('success', 'Payroll entry deleted.');
      break;

    case 'save_employee':
      $name = trim($_POST['full_name'] ?? ''); $pos = trim($_POST['position'] ?? ''); $rate = $_POST['hourly_rate'] ?? '';
      if ($name === '' || $pos === '' || !is_numeric($rate) || $rate < 0) { flash('error', 'Name, position and hourly rate are required.'); break; }
      if ($eid = (int)($_POST['id'] ?? 0)) {
        q('UPDATE employees SET full_name = ?, position = ?, hourly_rate = ?, status = ? WHERE id = ?',
          [$name, $pos, $rate, ($_POST['status'] ?? '') === 'Inactive' ? 'Inactive' : 'Active', $eid]);
        flash('success', "$name updated. New rates apply to new payroll entries only.");
      } else {
        q('INSERT INTO employees (full_name, position, hourly_rate) VALUES (?, ?, ?)', [$name, $pos, $rate]);
        flash('success', "$name added.");
      }
      break;
  }
  redirect($back);
}

$payrollRows = payroll_rows($payrollPeriod);
$payrollActions = true;
// Employees card: search box + 10 per page. The "Log Hours" form still lists every active employee.
[$w, $p] = where(like_any(['full_name', 'position'], search_term()));
$empPager = paginate("SELECT * FROM employees $w ORDER BY status, full_name", $p, 10, 'emp_page');
$employees = $empPager['rows'];
$activeEmployees = q('SELECT * FROM employees WHERE status = "Active" ORDER BY full_name')->fetchAll();
$nextMonday = date('Y-m-d', strtotime('monday this week'));

$pageTitle = 'Payroll | C.D. Zaplan Trucking';
$portal = 'admin'; $active = 'payroll';
$dateFilter = true;
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Payroll</h1>
    <p class="page-sub">Weekly compensation for heavy equipment operators and drivers.</p>
  </div>
  <div class="row-actions page-head__actions">
    <?php if ($periods): ?>
    <select class="select" onchange="location.href = 'payroll.php?period=' + this.value" aria-label="Payroll week">
      <?php foreach ($periods as [$s, $e]): ?>
      <option value="<?= e($s) ?>" <?= $s === $selected ? 'selected' : '' ?>>Week of <?= fdate($s, 'M d') ?>–<?= fdate($e, 'M d, Y') ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <button onclick="openModal('entryModal')" class="btn btn--amber"><iconify-icon icon="lucide:plus" width="16"></iconify-icon> Log Hours</button>
  </div>
</div>

<div class="stack stack--spaced">
  <?php require __DIR__ . '/includes/payroll-card.php'; ?>
  <?php if (array_filter($payrollRows, fn($r) => $r['status'] === 'Pending')): ?>
  <form method="post" class="form-actions form-actions--tight" onsubmit="return confirm('Mark every pending entry this week as paid?')">
    <?= csrf_field() ?><button name="action" value="pay_all" class="btn btn--outline">Mark whole week as paid</button>
  </form>
  <?php endif; ?>

  <section class="card" id="employees">
    <div class="card__head">
      <div><h2 class="card__title">Employees</h2><p class="card__sub">Drivers and operators on payroll</p></div>
      <button onclick="openEmployeeForm()" class="btn btn--outline btn--shrink"><iconify-icon icon="lucide:user-plus" width="16"></iconify-icon> Add Employee</button>
    </div>
    <div class="toolbar toolbar--flush"><?= list_search('Search name or position…') ?></div>
    <div class="table-wrap">
      <table class="table table--min-560">
        <thead><tr><th>Name</th><th>Position</th><th>Hourly Rate</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($employees as $emp): ?>
          <tr>
            <td><div class="person"><span class="avatar"><?= e(initials($emp['full_name'])) ?></span><span class="cell-title"><?= e($emp['full_name']) ?></span></div></td>
            <td><?= e($emp['position']) ?></td>
            <td><?= money($emp['hourly_rate']) ?>/hr</td>
            <td><?= pill($emp['status']) ?></td>
            <td><button type="button" class="link-underline" data-emp="<?= e(json_encode($emp)) ?>" onclick="openEmployeeForm(JSON.parse(this.dataset.emp))">Edit</button></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$employees): ?><tr><td colspan="5" class="empty">No employees match your search.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= pagination($empPager, 'employees', 'employees') ?>
  </section>
</div>

<!-- Log hours -->
<div id="entryModal" class="modal hidden" onclick="if (event.target === this) closeModal('entryModal')">
  <div class="dialog dialog--padded">
    <div class="dialog__head">
      <h2 class="dialog__title">Log Weekly Hours</h2>
      <button onclick="closeModal('entryModal')" aria-label="Close" class="close-btn close-btn--plain"><iconify-icon icon="lucide:x" width="20"></iconify-icon></button>
    </div>
    <form method="post" class="form form--modal">
      <?= csrf_field() ?><input type="hidden" name="action" value="add_entry">
      <div><label class="label">Employee</label>
        <select name="employee_id" class="field" required>
          <?php foreach ($activeEmployees as $emp): ?>
          <option value="<?= (int)$emp['id'] ?>"><?= e($emp['full_name']) ?> · <?= e($emp['position']) ?> · <?= money($emp['hourly_rate']) ?>/hr</option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-grid form-grid--two">
        <div><label class="label">Week starting</label><input name="period_start" type="date" value="<?= $nextMonday ?>" required class="field"></div>
        <div><label class="label">Hours worked</label><input name="hours" type="number" step="0.25" min="0" max="168" required class="field"></div>
      </div>
      <p class="form-hint">Total pay = hours × the employee's current hourly rate. The week runs 7 days from the start date.</p>
      <button class="btn btn--amber btn--block">Add to Payroll</button>
    </form>
  </div>
</div>

<!-- Add / edit employee -->
<div id="employeeModal" class="modal hidden" onclick="if (event.target === this) closeModal('employeeModal')">
  <div class="dialog dialog--padded">
    <div class="dialog__head">
      <h2 class="dialog__title" id="empTitle">Add Employee</h2>
      <button onclick="closeModal('employeeModal')" aria-label="Close" class="close-btn close-btn--plain"><iconify-icon icon="lucide:x" width="20"></iconify-icon></button>
    </div>
    <form method="post" id="empForm" class="form form--modal">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_employee"><input type="hidden" name="id">
      <div><label class="label">Full Name</label><input name="full_name" required maxlength="120" class="field"></div>
      <div class="form-grid form-grid--two">
        <div><label class="label">Position</label><input name="position" required maxlength="60" list="positions" class="field">
          <datalist id="positions"><option>Driver</option><option>Operator</option><option>Helper</option><option>Mechanic</option></datalist></div>
        <div><label class="label">Hourly Rate (₱)</label><input name="hourly_rate" type="number" step="0.01" min="0" required class="field"></div>
      </div>
      <div><label class="label">Status</label><select name="status" class="field"><option>Active</option><option>Inactive</option></select></div>
      <button class="btn btn--amber btn--block">Save Employee</button>
    </form>
  </div>
</div>
<script>
function openEmployeeForm(emp) {
  const f = document.getElementById('empForm');
  f.reset();
  f.elements['id'].value = emp ? emp.id : '';
  if (emp) ['full_name', 'position', 'hourly_rate', 'status'].forEach(k => { f.elements[k].value = emp[k]; });
  document.getElementById('empTitle').textContent = emp ? 'Edit ' + emp.full_name : 'Add Employee';
  openModal('employeeModal');
}
</script>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>

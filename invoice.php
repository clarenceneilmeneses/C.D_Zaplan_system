<?php
/* Printable invoice / receipt. Clients can open their own; admins can open any. "Download" = browser Print → Save as PDF. */
require_once __DIR__ . '/includes/bootstrap.php';
$me = current_user() ?: redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));

$inv = q('SELECT i.*, b.*, i.status AS invoice_status, i.id AS invoice_id, t.name AS equipment, t.daily_rate,
                 u.full_name AS client_name, u.email AS client_email, u.company, u.billing_address
          FROM invoices i
          JOIN bookings b ON b.id = i.booking_id
          JOIN equipment_types t ON t.id = b.type_id
          JOIN users u ON u.id = b.user_id
          WHERE i.invoice_no = ?', [$_GET['no'] ?? ''])->fetch();

if (!$inv || ($me['role'] !== 'admin' && (int)$inv['user_id'] !== (int)$me['id'])) {
  http_response_code(404);
  exit('Invoice not found.');
}
$status = invoice_status(['status' => $inv['invoice_status'], 'due_on' => $inv['due_on']]);
$isReceipt = $status === 'Paid';
$days = (int)round((strtotime($inv['end_date']) - strtotime($inv['start_date'])) / 86400) + 1;
$back = $me['role'] === 'admin' ? 'booking.php' : 'invoices.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= ($isReceipt ? 'Receipt ' : 'Invoice ') . e($inv['invoice_no']) ?> | C.D. Zaplan Trucking</title>
  <script src="https://code.iconify.design/iconify-icon/2.1.0/iconify-icon.min.js"></script>
  <link rel="icon" href="assets/img/logo.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="invoice-page">
  <div class="invoice-toolbar no-print">
    <a href="<?= $back ?>" class="back-link">← Back</a>
    <button onclick="window.print()" class="btn btn--amber btn--sm"><iconify-icon icon="lucide:printer" width="14"></iconify-icon> Print / Save as PDF</button>
  </div>

  <article class="invoice">
    <header class="invoice__head">
      <div class="brand">
        <img src="assets/img/logo.png" alt="" class="brand__logo">
        <div>
          <div class="brand__name">C.D. Zaplan Trucking</div>
          <div class="invoice__muted">Ventinilla, Sta. Barbara, Pangasinan · 09176757888 · cdzaplantrucking@gmail.com</div>
        </div>
      </div>
      <div class="invoice__title">
        <div class="display"><?= $isReceipt ? 'Official Receipt' : 'Invoice' ?></div>
        <div class="invoice__no"><?= e($inv['invoice_no']) ?></div>
        <?= pill($status) ?>
      </div>
    </header>

    <section class="invoice__meta">
      <div>
        <div class="eyebrow eyebrow--muted">Billed to</div>
        <div class="strong"><?= e($inv['client_name']) ?></div>
        <?php if ($inv['company']): ?><div><?= e($inv['company']) ?></div><?php endif; ?>
        <div class="invoice__muted"><?= e($inv['billing_address'] ?: $inv['client_email']) ?></div>
        <div class="invoice__muted"><?= e($inv['contact_phone']) ?></div>
      </div>
      <dl class="invoice__dates">
        <div><dt>Booking</dt><dd><?= booking_ref($inv['booking_id']) ?></dd></div>
        <div><dt>Issued</dt><dd><?= fdate($inv['issued_on']) ?></dd></div>
        <div><dt>Due</dt><dd><?= fdate($inv['due_on']) ?></dd></div>
        <?php if ($isReceipt): ?><div><dt>Paid</dt><dd><?= fdate($inv['paid_on']) ?></dd></div><?php endif; ?>
      </dl>
    </section>

    <table class="table invoice__table">
      <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>
        <tr>
          <td><div class="cell-title"><?= e($inv['equipment']) ?> rental</div>
              <div class="cell-meta"><?= date_range($inv['start_date'], $inv['end_date']) ?> · <?= e($inv['site_address']) ?></div></td>
          <td><?= $days ?> day<?= $days === 1 ? '' : 's' ?></td>
          <td><?= money($inv['rental_amount'] / max(1, $days)) ?></td>
          <td class="td-strong"><?= money($inv['rental_amount']) ?></td>
        </tr>
        <tr>
          <td><div class="cell-title">Delivery &amp; pull-out (mobilization)</div>
              <div class="cell-meta"><?= $inv['distance_km'] !== null ? e($inv['distance_km']) . ' km from yard · ' : '' ?>2 trips</div></td>
          <td>2</td>
          <td><?= money($inv['mobilization_fee'] / 2) ?></td>
          <td class="td-strong"><?= money($inv['mobilization_fee']) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="invoice__total">
      <span>Total <?= $isReceipt ? 'paid' : 'due' ?></span>
      <strong><?= money($inv['amount']) ?></strong>
    </div>
    <p class="invoice__note">Payment is accepted in cash on delivery / on site. Thank you for choosing C.D. Zaplan Trucking.</p>
  </article>
  <?php if (isset($_GET['print'])): ?><script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script><?php endif; ?>
</body>
</html>

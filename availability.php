<?php
/* Our Fleet: minimalist availability view (UI only, no database or calculations).
   Units come from $fleetUnits + $fleetAvailability (includes/mock-data.php). Swap those two arrays for database rows later and this page needs no changes.
   status: 'available' | 'booked' | 'unavailable'     next: date the unit is free again (shown on the badge). */
$pageTitle = 'Our Fleet | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'fleet';
require_once __DIR__ . '/includes/portal-header.php';

// One photo per category (swap the files in assets/img). Optional per-unit photo: 'TRK-02' => 'assets/img/trk-02.jpg'
$images = ['trucks' => 'assets/img/fleet-dump-truck.webp', 'excavation' => 'assets/img/fleet-backhoe.jpg'];
$unitImages = [];

$units = [];
foreach ($fleetUnits as $f) {
  $isTruck = str_contains($f['type'], 'Truck');
  $cat     = $isTruck ? 'trucks' : 'excavation';
  $title   = $isTruck ? '10-Wheeler Dump Truck' : 'Backhoe Loader';

  $av     = $fleetAvailability[$f['id']] ?? ['status' => 'available'];
  $status = $av['status'] ?? 'available';
  $next   = $av['next'] ?? '';
  $ts     = $next !== '' ? strtotime($next) : false;
  $date   = $ts ? date('M j', $ts) : $next;
  $tone   = $status === 'available' ? 'ok' : ($status === 'booked' ? 'wait' : 'off');
  $label  = $status === 'available' ? 'Available Now'
          : ($status === 'booked' ? 'Booked' : 'Unavailable') . ($date !== '' ? ' • Next Available: ' . $date : '');

  // Inquiry shown when the "Inquire Availability" button is clicked (booked / unavailable units)
  $askItem = $title . ' · ' . $f['model'] . ' (' . $f['id'] . ')';

  // Spec chips: Capacity, Engine, Type. "Diesel" is a PLACEHOLDER fact: confirm before launch.
  $chips = [
    ['Capacity', $f['capacity']],
    ['Engine',   'Diesel'],
    ['Type',     $isTruck ? 'Dump Truck' : 'Excavator'],
  ];

  $units[] = [
    'id' => $f['id'], 'category' => $cat, 'title' => $title, 'model' => $f['model'],
    'status' => $status, 'tone' => $tone, 'label' => $label, 'chips' => $chips, 'askItem' => $askItem,
    'image' => $unitImages[$f['id']] ?? $images[$cat],
    'book'  => $isTruck ? '10-Wheeler Dump Truck' : 'Backhoe',   // must match an option in the booking form
    'search' => strtolower($title . ' ' . $f['model'] . ' ' . $f['id'] . ' ' . $status),
  ];
}
?>
<section class="flt" id="fltRoot" aria-labelledby="fltTitle">

  <header class="flt-head">
    <span class="flt-head__bar" aria-hidden="true"></span>
    <h1 class="flt-title" id="fltTitle">Our Fleet</h1>
    <p class="flt-tagline">Well-maintained trucks and excavators, ready when your project is.</p>
  </header>

  <div class="flt-bar">
    <div class="flt-filters" role="group" aria-label="Filter equipment">
      <button type="button" class="flt-filter is-active" data-flt-filter="all" aria-pressed="true">All</button>
      <button type="button" class="flt-filter" data-flt-filter="excavation" aria-pressed="false">Excavators</button>
      <button type="button" class="flt-filter" data-flt-filter="trucks" aria-pressed="false">Dump Trucks</button>
    </div>
    <label class="flt-search">
      <iconify-icon icon="lucide:search" width="16"></iconify-icon>
      <input id="fltSearch" type="search" placeholder="Search fleet" autocomplete="off" aria-label="Search fleet">
    </label>
  </div>

  <div class="flt-grid" id="fltGrid">
    <?php foreach ($units as $u): ?>
    <article class="flt-card" data-category="<?= $u['category'] ?>" data-search="<?= htmlspecialchars($u['search'], ENT_QUOTES) ?>">
      <div class="flt-card__media">
        <img src="<?= htmlspecialchars($u['image']) ?>" alt="<?= htmlspecialchars($u['title']) ?>" loading="lazy">
        <span class="flt-pill flt-pill--<?= $u['tone'] ?>"><?= htmlspecialchars($u['label']) ?></span>
      </div>
      <div class="flt-card__body">
        <h3 class="flt-card__title"><?= htmlspecialchars($u['title']) ?></h3>
        <p class="flt-card__model"><?= htmlspecialchars($u['model']) ?> · <?= htmlspecialchars($u['id']) ?></p>
        <div class="flt-chips">
          <?php foreach ($u['chips'] as [$k, $v]): ?><span class="flt-chip"><small><?= $k ?></small><?= htmlspecialchars($v) ?></span><?php endforeach; ?>
        </div>
        <?php if ($u['status'] === 'available'): ?>
        <button type="button" class="flt-btn flt-btn--yellow" data-flt-inquire data-book="<?= htmlspecialchars($u['book'], ENT_QUOTES) ?>">Book Now</button>
        <?php else: ?>
        <!-- Booked / unavailable: an actual inquiry (opens the "Send an Inquiry" form, not the booking form) -->
        <button type="button" class="flt-btn flt-btn--outline" data-flt-ask data-item="<?= htmlspecialchars($u['askItem'], ENT_QUOTES) ?>">Inquire Availability</button>
        <?php endif; ?>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <p class="flt-empty" id="fltEmpty" hidden>No equipment found. <button type="button" class="flt-reset" id="fltReset">Show all</button></p>

</section>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>

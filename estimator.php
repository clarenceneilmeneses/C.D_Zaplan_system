<?php
$pageTitle = 'Booking Fee Estimator | C.D. Zaplan Trucking';
$portal = 'client'; $active = 'estimator';

// Leaflet (free, no API key) is loaded only on this page
$extraHead    = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">';
$extraScripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>'
              . '<script src="assets/js/estimator.js"></script>';
require_once __DIR__ . '/includes/portal-header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Booking Fee Estimator</h1>
    <p class="page-sub">Pin your job site, choose equipment, and see an estimated cost before you book.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/estimator-widget.php'; ?>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>

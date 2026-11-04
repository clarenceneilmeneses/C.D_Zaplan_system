<?php
/* ---------------------------------------------------------------------
   Booking Fee Estimator: UI component (Leaflet + OpenStreetMap, no API key).
   This widget does NOT calculate fees. It collects inputs and displays whatever
   your backend returns. See assets/js/estimator.js for the request/response contract.

   >>> Depot coordinates are approximate: set the exact lat/lng of the yard. <<<
   'maxTons' only drives the form validation message (set it from your fleet data).
   'endpoint' : URL that accepts the JSON request and returns the JSON result (null = don't call anything;
                listen for the 'estimator:request' event instead).
   --------------------------------------------------------------------- */
$estimatorConfig = [
  'depot' => ['lat' => 15.9960, 'lng' => 120.4000, 'label' => 'C.D. Zaplan Trucking Depot', 'address' => 'Ventinilla, Sta. Barbara, Pangasinan'],
  'equipment' => [
    'dump'    => ['label' => '10-Wheeler Dump Truck', 'maxTons' => 20],
    'backhoe' => ['label' => 'Backhoe',               'maxTons' => 5],
  ],
  'endpoint' => null,   // e.g. 'api/estimate.php'
];
?>
<script>window.ESTIMATOR = <?= json_encode($estimatorConfig, JSON_UNESCAPED_UNICODE) ?>;</script>

<div class="estimator">

  <!-- MAP -->
  <section class="card card--hover est-map-card">
    <div class="card__head card__head--line">
      <div>
        <h2 class="card__title">Job Site Location</h2>
        <p class="card__sub">Click the map or search an address to drop your destination pin.</p>
      </div>
    </div>

    <form id="estSearchForm" class="est-search" autocomplete="off">
      <div class="toolbar__search">
        <iconify-icon icon="lucide:search" width="16" class="search-icon"></iconify-icon>
        <input id="estSearch" type="text" placeholder="Search a town, barangay, or address..." class="search-input">
      </div>
      <button type="submit" class="btn btn--outline btn--md">Search</button>
    </form>
    <p id="estSearchMsg" class="est-msg" role="status"></p>

    <div id="estMap" class="est-map" aria-label="Map for choosing the job site"></div>

    <div class="est-legend">
      <span class="est-legend__item"><span class="est-dot est-dot--depot"></span> Depot</span>
      <span class="est-legend__item"><span class="est-dot est-dot--dest"></span> Job site</span>
      <span class="est-distance"><iconify-icon icon="lucide:route" width="14"></iconify-icon> <span id="estDistance">Distance: —</span></span>
    </div>
  </section>

  <div class="est-side">

    <!-- INPUTS -->
    <section class="card card--hover">
      <div class="card__head card__head--line">
        <div><h2 class="card__title">Estimate Details</h2><p class="card__sub">Pick equipment, load, and rental length.</p></div>
      </div>
      <div class="card__body">
        <div class="form form--tight">
          <div>
            <label class="label" for="estEquipment">Equipment</label>
            <select id="estEquipment" class="field">
              <?php foreach ($estimatorConfig['equipment'] as $key => $eq): ?>
              <option value="<?= $key ?>"><?= e($eq['label']) ?></option>
              <?php endforeach; ?>
            </select>
            <p id="estRateNote" class="hint"></p>
          </div>

          <div>
            <label class="label" for="estWeight">Estimated Load Weight</label>
            <div class="est-inline">
              <input id="estWeight" type="number" min="0" step="any" inputmode="decimal" placeholder="0" class="field">
              <select id="estWeightUnit" class="field est-unit" aria-label="Weight unit"><option value="tons">tons</option><option value="kg">kg</option></select>
            </div>
            <p id="estWeightErr" class="field-error hidden"></p>
          </div>

          <div>
            <label class="label" for="estDays">Duration (Number of Days)</label>
            <input id="estDays" type="number" min="1" max="90" step="1" value="1" inputmode="numeric" class="field">
            <p id="estDaysErr" class="field-error hidden"></p>
          </div>

          <div>
            <label class="label" for="estDestination">Destination</label>
            <input id="estDestination" type="text" readonly placeholder="Select a point on the map" class="field field--readonly">
          </div>
        </div>
      </div>
    </section>

    <!-- BREAKDOWN -->
    <section class="card card--hover">
      <div class="card__head card__head--line">
        <div><h2 class="card__title">Cost Breakdown</h2><p class="card__sub">Shows the estimate once your details are submitted.</p></div>
      </div>
      <div id="estLines" class="card__body est-lines" aria-live="polite">
        <div class="est-line">
          <div><div class="est-line__label">Base Equipment Rental</div><div id="lnBaseDetail" class="est-line__detail">—</div></div>
          <div id="lnBase" class="est-line__value est-line__value--empty">—</div>
        </div>
        <div class="est-line">
          <div><div class="est-line__label">Distance-Based Hauling Fee</div><div id="lnHaulDetail" class="est-line__detail">—</div></div>
          <div id="lnHaul" class="est-line__value est-line__value--empty">—</div>
        </div>
        <div class="est-line">
          <div><div class="est-line__label">Weight-Based Surcharge</div><div id="lnWeightDetail" class="est-line__detail">—</div></div>
          <div id="lnWeight" class="est-line__value est-line__value--empty">—</div>
        </div>

        <div class="est-total">
          <div class="est-total__label">Estimated Total Cost</div>
          <div id="lnTotal" class="est-total__value">—</div>
          <p class="est-total__note">Estimate only. Final rate is confirmed by dispatch. A deposit is required before booking is confirmed.</p>
        </div>

        <button id="estProceed" type="button" class="btn btn--amber btn--block" disabled>
          <iconify-icon icon="lucide:arrow-right" width="16"></iconify-icon> Proceed with This Estimate
        </button>
        <p id="estHint" class="hint est-hint"></p>
      </div>
    </section>

  </div>
</div>

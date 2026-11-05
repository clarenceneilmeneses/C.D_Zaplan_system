<?php
$pageTitle = 'C.D. Zaplan Trucking | Site Clearing & Heavy Equipment Rentals';
$useMap = true;
require_once __DIR__ . '/includes/header.php';

$testimonials = [
  ['name' => 'Sarah Jenkins',   'meta' => 'Site grading · Laguna (sample)'],
  ['name' => 'Michael Chen',    'meta' => 'Excavation · Cavite (sample)'],
  ['name' => 'Elena Rodriguez', 'meta' => 'Site clearing · Rizal (sample)'],
  ['name' => 'Marco Santos',    'meta' => 'Excavation · Pangasinan (sample)'],
];
$services = [
  ['01', 'Heavy-Duty Truck Fleet', 'Dump trucks & material haulers ready for deployment. Our fleet is meticulously maintained to ensure zero downtime on your site.'],
  ['02', 'Precision Excavation',   'Professional site grading, trenching, & clearing. We handle complex terrain with precision and care for your future build.'],
  ['03', 'Experienced Crew',       'Certified operators ensuring safe, zero-delay execution. Our team is your partner from the first dig to the final grade.'],
];
$types = equipment_types();
?>

<!-- Hero -->
<section class="hero hero-bg">
  <div class="hero__inner">
    <h1 class="display hero__title">
      <span class="accent">Site</span> Clearing.<br>
      <span class="accent">Build</span> Right.
    </h1>
    <p class="hero__text">
      Heavy-duty site clearing and foundation excavation tailored for commercial property developments. Operated by licensed professionals to deliver build-ready sites on schedule and on budget.
    </p>
    <div class="hero__actions">
      <button type="button" onclick="openInquiry('Site Clearing & Heavy Equipment Services')" class="btn--hero">Inquire</button>
      <a href="#estimate" class="btn--hero btn--hero-ghost">Get an Estimate</a>
    </div>
  </div>
</section>

<!-- Stat cards on navy band -->
<section class="stats-band">
  <div class="stats-band__grid">
    <?php foreach ([['100%','Certified Operators'],['Commercial','Grade Equipment'],['On-Time','Site Preparation'],['Safety','First Execution']] as [$big,$label]): ?>
    <div class="stat-tile">
      <div class="display stat-tile__big"><?= $big ?></div>
      <div class="stat-tile__label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- About -->
<section id="about" class="section section--white">
  <div class="container">
    <div class="display section__kicker">About C.D. Zaplan Trucking</div>
    <h2 class="section__title">
      Dependable Truck Rentals &amp; Professional Site Excavation
    </h2>
    <p class="section__lead">
      We deliver heavy-duty truck rentals and site clearing services built on safety, equipment reliability, and on-time project delivery.
    </p>

    <div id="services" class="service-grid">
      <?php foreach ($services as [$num, $title, $text]): ?>
      <div class="service-card">
        <div class="display service-card__num"><?= $num ?></div>
        <h3 class="service-card__title"><?= $title ?></h3>
        <p class="service-card__text"><?= $text ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Distance-based estimate -->
<section id="estimate" class="section section--muted">
  <div class="container">
    <div class="display section__kicker">Instant Estimate</div>
    <h2 class="section__title">How much to bring our equipment to your site?</h2>
    <p class="section__lead">
      Pin your project site on the map. We measure the road distance from our yard in <?= e(setting('base_label')) ?>
      and add delivery and pull-out to the rental cost.
    </p>

    <div class="estimate">
      <div class="estimate__map-wrap">
        <form class="estimate__search" onsubmit="event.preventDefault(); landingPicker.search(this.q.value)">
          <div class="estimate__search-field">
            <iconify-icon icon="lucide:search" width="16" class="search__icon"></iconify-icon>
            <input id="estSearch" name="q" type="search" placeholder="Search town, barangay or landmark…" class="search__input" aria-label="Search location">
          </div>
          <button type="submit" class="btn btn--amber btn--sm">Find</button>
        </form>
        <div id="estimateMap" class="estimate__map" role="application" aria-label="Map. Click to choose your project site."></div>
        <p class="estimate__hint"><iconify-icon icon="lucide:mouse-pointer-click" width="14"></iconify-icon> Click the map or drag the pin to set your project site.</p>
      </div>

      <div class="estimate__panel">
        <div class="form">
          <div>
            <label class="label" for="estType">Equipment</label>
            <select id="estType" class="field">
              <?php foreach ($types as $t): ?>
              <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?> · <?= money($t['daily_rate'], 0) ?>/day</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="label" for="estDays">Rental days</label>
            <input id="estDays" type="number" min="1" max="365" value="5" class="field">
          </div>
        </div>

        <div class="estimate__site">
          <div class="eyebrow eyebrow--muted">Project site</div>
          <div id="estSite" class="estimate__site-name">No site selected yet</div>
        </div>

        <dl class="estimate__lines">
          <div><dt>Road distance from our yard</dt><dd id="estKm">—</dd></div>
          <div><dt id="estRentalLabel">Rental</dt><dd id="estRental">—</dd></div>
          <div><dt id="estMobLabel">Delivery &amp; pull-out</dt><dd id="estMob">—</dd></div>
        </dl>
        <div class="estimate__total">
          <span>Estimated total</span>
          <strong id="estTotal">—</strong>
        </div>
        <p id="estWarn" class="estimate__warn hidden"></p>
        <p class="estimate__fine">Estimate only. Final price is confirmed by our team after a site check. Payment is cash on delivery / on site.</p>

        <div class="estimate__actions">
          <button id="estQuote" type="button" class="btn btn--amber" disabled onclick="sendEstimateInquiry()">
            <iconify-icon icon="lucide:send" width="16"></iconify-icon> Request this quote
          </button>
          <button id="estBook" type="button" class="btn btn--outline" disabled onclick="bookFromEstimate()">Book now</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Projects & reviews -->
<section id="projects" class="section section--white">
  <div class="container">
    <h2 class="display section__heading">Projects &amp; Reviews · Sample Testimonials</h2>

    <!-- To add a review, add one more entry to $testimonials at the top of this file -->
    <div class="reviews">
      <div id="reviewTrack" class="reviews__track">
        <?php foreach ($testimonials as $t): ?>
        <div class="review-card">
          <img src="assets/img/project.jpg" alt="Excavator and bulldozer on a cleared site" class="review-card__img">
          <div class="review-card__stars">
            <?php for ($i = 0; $i < 5; $i++): ?><iconify-icon icon="lucide:star" width="14"></iconify-icon><?php endfor; ?>
          </div>
          <p class="review-card__quote">Fast excavation and top-tier truck fleet service!</p>
          <div class="review-card__author">
            <span class="review-card__avatar"></span>
            <div>
              <div class="review-card__name"><?= e($t['name']) ?></div>
              <div class="review-card__meta"><?= e($t['meta']) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Arrows: centered row under the cards, floating beside the cards (with a gap) on wide screens -->
      <div class="reviews__nav">
        <button id="reviewPrev" onclick="scrollReviews(-1)" aria-label="Previous reviews" class="reviews__btn reviews__btn--prev">
          <iconify-icon icon="lucide:chevron-left" width="20"></iconify-icon>
        </button>
        <button id="reviewNext" onclick="scrollReviews(1)" aria-label="Next reviews" class="reviews__btn reviews__btn--next">
          <iconify-icon icon="lucide:chevron-right" width="20"></iconify-icon>
        </button>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/inquiry-modal.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>window.ESTIMATOR = <?= js(estimator_config()) ?>;</script>
<script src="assets/js/estimator.js"></script>
<script>
  // Landing-page estimator: map + price panel
  const est = { site: null };
  const landingPicker = new SitePicker(document.getElementById('estimateMap'), ESTIMATOR, site => { est.site = site; renderEstimate(); });
  // Suggestions while typing; picking one pins it on the map
  new PlaceAutocomplete(document.getElementById('estSearch'), place => landingPicker.setSite(place.lat, place.lng, place.label),
                        { near: [ESTIMATOR.base.lat, ESTIMATOR.base.lng], container: document.querySelector('.estimate__search') });
  document.getElementById('estType').addEventListener('change', renderEstimate);
  document.getElementById('estDays').addEventListener('input', renderEstimate);

  function currentType() { return ESTIMATOR.types.find(t => t.id === +document.getElementById('estType').value); }
  function currentDays() { return Math.max(1, Math.min(365, parseInt(document.getElementById('estDays').value, 10) || 1)); }

  function renderEstimate() {
    const s = est.site, type = currentType(), days = currentDays();
    const $ = id => document.getElementById(id);
    $('estQuote').disabled = $('estBook').disabled = !s || s.pending;
    if (!s) return;
    $('estSite').textContent = s.address || `${s.lat.toFixed(5)}, ${s.lng.toFixed(5)}`;
    if (s.pending) { $('estKm').textContent = 'Measuring…'; return; }

    const c = estimateCost(type, s.km, days);
    $('estKm').textContent = s.km.toFixed(1) + ' km' + (s.approx ? ' (approx.)' : '');
    $('estRentalLabel').textContent = `Rental (${days} day${days > 1 ? 's' : ''} × ${peso(type.daily, 0)})`;
    $('estRental').textContent = peso(c.rental);
    $('estMobLabel').textContent = `Delivery & pull-out (2 trips × ${peso(c.trip)})`;
    $('estMob').textContent = peso(c.mobilization);
    $('estTotal').textContent = peso(c.total);

    const warn = $('estWarn');
    warn.classList.toggle('hidden', s.km <= ESTIMATOR.maxKm);
    warn.textContent = `This site is beyond our usual ${ESTIMATOR.maxKm} km service area. Send a quote request and we'll confirm if we can deploy.`;
  }

  function sendEstimateInquiry() {
    const s = est.site, type = currentType(), days = currentDays(), c = estimateCost(type, s.km, days);
    openInquiry(`${type.name} · ${days} day${days > 1 ? 's' : ''} · ${s.km.toFixed(1)} km`, {
      type: 'Quote & Pricing Request',
      estimate: { type_id: type.id, days, lat: s.lat, lng: s.lng, km: s.km, total: c.total },
      message: `Hi! I'd like a quote for a ${type.name} for ${days} day(s).\nProject site: ${s.address || (s.lat.toFixed(5) + ', ' + s.lng.toFixed(5))}\nWebsite estimate: ${peso(c.total)}`,
    });
  }

  function bookFromEstimate() {
    const s = est.site;
    const p = new URLSearchParams({ book: 1, type: currentType().id, days: currentDays(), lat: s.lat.toFixed(6), lng: s.lng.toFixed(6), km: s.km.toFixed(2), addr: s.address || '' });
    window.location.href = 'dashboard.php?' + p.toString();
  }
</script>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>

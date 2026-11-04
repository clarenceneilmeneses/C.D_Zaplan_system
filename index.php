<?php
$pageTitle = 'C.D. Zaplan Trucking | Reliable Hauling & Heavy Equipment Rental';
// $isLoggedIn = true;   // uncomment to preview the "Register + Logout" header
require_once __DIR__ . '/includes/header.php';

/* ---- Editable content ------------------------------------------------ */

// Stats strip. PLACEHOLDER numbers: replace with the real ones. The "+" is kept after the count-up.
$stats = [
  ['12+',  'Active Fleet'],
  ['150+', 'Completed Projects'],
  ['100+', 'Satisfied Clients'],
];

// About section. PLACEHOLDER story: replace with the company's real history (founding year, milestones).
$about = [
  'eyebrow' => 'About Us',
  'title'   => 'A Pangasinan hauling crew built on reliability and safety.',
  'paras'   => [
    'C.D. Zaplan Trucking is a local hauling and heavy equipment rental company based in Ventinilla, Sta. Barbara, Pangasinan. We started with one promise: show up when we say we will, with equipment that is ready to work.',
    'Today we support contractors, developers, and property owners with 10-wheeler dump trucks and site excavation, run by certified operators and maintained to keep your schedule intact.',
  ],
  'image'   => 'assets/img/project.jpg',
  'alt'     => 'Excavator and bulldozer working on a cleared construction site',
  'badge'   => ['Certified operators', 'Safety-first execution on every site'],
  'values'  => [
    ['lucide:clock-4',     'Reliability',     'On-time dispatch and equipment that is ready the day you book.'],
    ['lucide:shield-check','Safety',          'Certified operators and safe, disciplined work on every site.'],
    ['lucide:handshake',   'Professionalism', 'Clear communication, transparent terms, and respect for your deadline.'],
  ],
];

// Exactly two services. Specs show on the card so clients don't have to ask.
// Add assets/img/service-trucks.jpg to replace the truck placeholder.
$services = [
  [
    'tag'   => 'Service 01',
    'title' => 'Site Excavation',
    'desc'  => 'Build-ready sites delivered on schedule. We clear, shape, and dig so your foundation crew can start on day one.',
    'image' => 'assets/img/service-excavation.jpg',
    'alt'   => 'Excavator digging on an open construction site',
    'inquire' => 'Site Excavation',
    'specs' => [
      ['lucide:weight',     'Load capacity', '1.0 cu.m bucket'],
      ['lucide:settings-2', 'Engine / type', 'Backhoe loader · Caterpillar 420, JCB 3CX'],
      ['lucide:shovel',     'Primary use',   'Clearing, grading, trenching & foundation digging'],
    ],
  ],
  [
    'tag'   => 'Service 02',
    'title' => 'Truck Rentals',
    'desc'  => 'Dependable 10-wheeler dump trucks for the loads that keep your project moving, from the yard to your site.',
    'image' => file_exists(__DIR__ . '/assets/img/service-trucks.jpg') ? 'assets/img/service-trucks.jpg' : null,
    'alt'   => '10-wheeler dump truck',
    'inquire' => 'Truck Rentals',
    'specs' => [
      ['lucide:weight',     'Load capacity', '20 tons / 15 cu.m'],
      ['lucide:settings-2', 'Engine / type', '10-wheeler dump truck · Isuzu Giga, Hino 700 Series'],
      ['lucide:route',      'Primary use',   'Hauling soil, gravel, sand & debris'],
    ],
  ],
];

// Sample reviews. 'photo' is optional: set a path (e.g. 'assets/img/clients/sarah.jpg') to show a real picture,
// otherwise an initials avatar is shown. Add more entries and the carousel picks them up.
$testimonials = [
  ['name' => 'Sarah Jenkins',   'photo' => null, 'meta' => 'Site grading · Laguna (sample)',      'text' => 'The dump trucks arrived exactly on schedule every single morning. Zero delays on our grading work, which kept our whole timeline intact.'],
  ['name' => 'Michael Chen',    'photo' => null, 'meta' => 'Excavation · Cavite (sample)',        'text' => 'The backhoe and operator showed up on time and finished ahead of plan. A dependable crew that does exactly what they promise.'],
  ['name' => 'Elena Rodriguez', 'photo' => null, 'meta' => 'Site clearing · Rizal (sample)',      'text' => 'Clear communication, fair pricing, and equipment in great shape. Our site was cleared on the day we were told. We will rent again.'],
  ['name' => 'Marco Santos',    'photo' => null, 'meta' => 'Excavation · Pangasinan (sample)',    'text' => 'Punctual, professional, and safe. They treated our deadline like it was their own, and the work was done right the first time.'],
];

// Project photo shown on each review card. Give a testimonial its own photo with an 'image' key
// (e.g. 'image' => 'assets/img/clients/sarah-site.jpg'); otherwise these sample photos rotate. PLACEHOLDER: replace with real project photos.
$reviewImages = ['assets/img/project.jpg', 'assets/img/service-excavation.jpg', 'assets/img/hero-poster.jpg'];
?>

<div id="scrollProgress" class="scroll-progress"></div>

<!-- Hero: looping video + dark tint -->
<section class="hero" id="top">
  <video class="hero__video" autoplay muted loop playsinline preload="metadata" poster="assets/img/hero-poster.jpg" aria-hidden="true">
    <source src="assets/video/hero.mp4" type="video/mp4">
  </video>
  <div class="hero__overlay"></div>

  <div class="container hero__inner">
    <div class="eyebrow eyebrow--amber hero__eyebrow">Construction &amp; Heavy Equipment Rental</div>
    <h1>
      <span class="line"><span class="line__in">Site <span class="accent">Clearing</span> &amp;</span></span>
      <span class="line"><span class="line__in">Heavy Truck <span class="accent"> Rentals</span></span>
    </h1>
    <p>From our yard in Sta. Barbara, Pangasinan, we deliver dependable dump trucks and expert excavation for construction sites that run on tight schedules. Certified operators. Maintained equipment. Built for the job.</p>
    <div class="hero__actions">
      <a href="#services" class="hero__cta hero__cta--ghost">Browse Fleet <iconify-icon icon="lucide:arrow-down" width="16"></iconify-icon></a>
      <button type="button" onclick="openInquiry('Site Excavation & Truck Rentals')" class="hero__cta">Inquire Now</button>
    </div>
  </div>
</section>

<div class="hazard" aria-hidden="true"></div>

<!-- Stats strip (count-up when scrolled into view) -->
<section class="stats">
  <div class="container stats__grid">
    <?php foreach ($stats as $n => [$value, $label]): ?>
    <div class="stat" data-reveal style="--d:<?= $n * 0.1 ?>s">
      <div class="stat__big" data-count><?= $value ?></div>
      <div class="stat__label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- About -->
<section id="about" class="section">
  <div class="container about">
    <div class="about__media" data-reveal>
      <img src="<?= e($about['image']) ?>" alt="<?= e($about['alt']) ?>" loading="lazy">
      <div class="about__badge">
        <iconify-icon icon="lucide:hard-hat" width="28"></iconify-icon>
        <div><strong><?= e($about['badge'][0]) ?></strong><span><?= e($about['badge'][1]) ?></span></div>
      </div>
    </div>

    <div class="about__body" data-reveal style="--d:.12s">
      <div class="section-eyebrow"><?= e($about['eyebrow']) ?></div>
      <h2 class="section-heading"><?= e($about['title']) ?></h2>
      <?php foreach ($about['paras'] as $p): ?><p class="about__text"><?= e($p) ?></p><?php endforeach; ?>

      <div class="about__values">
        <?php foreach ($about['values'] as [$icon, $name, $text]): ?>
        <div class="value">
          <span class="value__icon"><iconify-icon icon="<?= e($icon) ?>" width="22"></iconify-icon></span>
          <div><h3><?= e($name) ?></h3><p><?= e($text) ?></p></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Core services -->
<section id="services" class="section section--gray">
  <div class="container">
    <div data-reveal>
      <div class="section-eyebrow">What We Do</div>
      <h2 class="section-heading">Two services. One dependable crew.</h2>
      <p class="section-lead">Whether you need ground prepared or materials moved, we bring the machines and the people to keep your project on schedule.</p>
    </div>

    <div class="service-grid">
      <?php foreach ($services as $n => $svc): ?>
      <article class="service-card" data-reveal style="--d:<?= $n * 0.12 ?>s">
        <div class="service-card__media">
          <?php if ($svc['image']): ?>
            <img src="<?= $svc['image'] ?>" alt="<?= htmlspecialchars($svc['alt']) ?>" loading="lazy">
          <?php else: ?>
            <div class="service-card__ph"><iconify-icon icon="lucide:truck" width="56"></iconify-icon><span>Truck photo coming soon</span></div>
          <?php endif; ?>
          <span class="service-card__tag"><?= $svc['tag'] ?></span>
        </div>
        <div class="service-card__body">
          <h3><?= e($svc['title']) ?></h3>
          <p class="service-card__desc"><?= e($svc['desc']) ?></p>
          <dl class="spec-grid">
            <?php foreach ($svc['specs'] as [$icon, $label, $value]): ?>
            <div class="spec">
              <span class="spec__icon"><iconify-icon icon="<?= e($icon) ?>" width="16"></iconify-icon></span>
              <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            </div>
            <?php endforeach; ?>
          </dl>
          <div class="service-card__foot">
            <span class="service-card__note">Deposit required to confirm booking</span>
            <button type="button" onclick="openInquiry('<?= e($svc['inquire']) ?>')" class="btn btn--amber btn--md">Inquire Now</button>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Reviews: infinite marquee (data comes from $testimonials above; sections.js duplicates the cards for a seamless loop) -->
<section id="reviews" class="cdz-reviews" aria-labelledby="cdzReviewsTitle">
  <div class="cdz-container">
    <header class="cdz-head">
      <span class="cdz-eyebrow">Client Reviews</span>
      <h2 id="cdzReviewsTitle" class="cdz-title">What Our Clients Say</h2>
      <p class="cdz-lead">What commercial developers say about our hauling and site clearing.</p>
    </header>
  </div>

  <div class="cdz-marquee-wrap" id="cdzMarqueeWrap">
  <div class="cdz-marquee" id="cdzMarquee" aria-label="Customer reviews, scrolling. Hover or focus to pause.">
    <ul class="cdz-marquee__track" id="cdzMarqueeTrack">
      <?php foreach ($testimonials as $k => $t): $img = $t['image'] ?? $reviewImages[$k % count($reviewImages)]; ?>
      <li class="cdz-review">
        <div class="cdz-review__media"><img src="<?= e($img) ?>" alt="Project photo: <?= e(strip_tags($t['meta'])) ?>" loading="lazy"></div>
        <div class="cdz-review__body">
          <iconify-icon icon="mdi:format-quote-close" width="36" class="cdz-review__mark"></iconify-icon>
          <div class="cdz-stars" role="img" aria-label="5 out of 5 stars">
            <?php for ($i = 0; $i < 5; $i++): ?><iconify-icon icon="mdi:star" width="18"></iconify-icon><?php endfor; ?>
          </div>
          <p class="cdz-review__tag"><?= $t['meta'] ?></p>
          <blockquote class="cdz-review__text">“<?= $t['text'] ?>”</blockquote>
          <button type="button" class="cdz-review__more">Read full review <iconify-icon icon="lucide:arrow-right" width="14"></iconify-icon></button>
          <div class="cdz-review__author">
            <span class="cdz-avatar"><?php if (!empty($t['photo'])): ?><img src="<?= e($t['photo']) ?>" alt="<?= e($t['name']) ?>" loading="lazy"><?php else: ?><?= initials($t['name']) ?><?php endif; ?></span>
            <span><strong><?= $t['name'] ?></strong><small>Verified client</small></span>
          </div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  </div>
</section>

<!-- Full-review pop-up (filled by sections.js from the card that was clicked) -->
<div class="cdz-modal" id="cdzReviewModal" role="dialog" aria-modal="true" aria-labelledby="cdzRmName" aria-hidden="true">
  <div class="cdz-modal__dialog cdz-rm" tabindex="-1">
    <button type="button" class="cdz-modal__close cdz-rm__close" data-modal-close aria-label="Close review">
      <iconify-icon icon="lucide:x" width="22"></iconify-icon>
    </button>
    <img class="cdz-rm__img" id="cdzRmImg" src="" alt="">
    <div class="cdz-modal__body cdz-rm__body">
      <div class="cdz-stars" role="img" aria-label="5 out of 5 stars">
        <iconify-icon icon="mdi:star" width="20"></iconify-icon><iconify-icon icon="mdi:star" width="20"></iconify-icon><iconify-icon icon="mdi:star" width="20"></iconify-icon><iconify-icon icon="mdi:star" width="20"></iconify-icon><iconify-icon icon="mdi:star" width="20"></iconify-icon>
      </div>
      <p class="cdz-review__tag" id="cdzRmTag"></p>
      <blockquote class="cdz-rm__text" id="cdzRmText"></blockquote>
      <div class="cdz-review__author cdz-rm__author">
        <span class="cdz-avatar cdz-avatar--lg" id="cdzRmAvatar"></span>
        <span><strong id="cdzRmName"></strong><small>Verified client</small></span>
      </div>
    </div>
    <div class="cdz-modal__foot">
      <button type="button" class="cdz-linkbtn cdz-rm__close-text" data-modal-close>Close</button>
      <button type="button" class="cdz-btn" data-review-quote>Request a Quote</button>
    </div>
  </div>
</div>

<!-- Founder's message (directly above the footer). PLACEHOLDER name/quote/portrait: replace with the real ones. -->
<section id="founder" class="cdz-founder" aria-labelledby="cdzFounderTitle">
  <div class="cdz-container cdz-founder__grid">

    <!-- Left: quote card -->
    <article class="cdz-quote">
      <span class="cdz-badge">Leadership</span>
      <iconify-icon icon="mdi:format-quote-open" width="56" class="cdz-quote__mark" aria-hidden="true"></iconify-icon>
      <h2 id="cdzFounderTitle" class="cdz-visually-hidden">Message from our Founder</h2>
      <blockquote class="cdz-quote__text">
        <p>Every project we take on starts with trust. We believe that strong relationships are built one dependable delivery at a time, and that excellence is not a goal we reach once, but a promise we keep every day.</p>
        <p>To our clients and our crew: thank you for building this company with us. We will keep showing up, on time and ready to work.</p>
      </blockquote>

      <footer class="cdz-quote__author">
        <span class="cdz-avatar cdz-avatar--lg" aria-hidden="true">CZ</span>
        <div>
          <strong class="cdz-quote__name">Carlito Zaplan</strong>
          <span class="cdz-quote__role">Founder &amp; Chief Executive Officer</span>
        </div>
        <a href="#" class="cdz-social" aria-label="Founder on Facebook" target="_blank" rel="noopener">
          <iconify-icon icon="mdi:facebook" width="20"></iconify-icon>
        </a>
      </footer>
    </article>

    <!-- Right: portrait card -->
    <figure class="cdz-portrait">
      <!-- To use a real photo: add assets/img/founder.jpg and replace the placeholder <div> with <img src="assets/img/founder.jpg" alt="Carlito Zaplan, Founder and CEO" loading="lazy"> -->
      <div class="cdz-portrait__ph" role="img" aria-label="Founder portrait placeholder">
        <iconify-icon icon="mdi:account" width="140"></iconify-icon>
      </div>
      <figcaption class="cdz-portrait__badge">Founder &amp; CEO</figcaption>
    </figure>

  </div>
</section>


<?php require __DIR__ . '/includes/inquiry-modal.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php require_once __DIR__ . '/includes/scripts.php'; ?>

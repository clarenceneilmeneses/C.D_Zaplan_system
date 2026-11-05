<?php
/* Full results for the portal top-bar search (pressing Enter in it) */
require_once __DIR__ . '/includes/bootstrap.php';
$me = current_user() ?: redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));

$q = search_term();
$groups = global_search($me, $q, 10);
$count = array_sum(array_map(fn($g) => count($g['items']), $groups));

$pageTitle = 'Search | C.D. Zaplan Trucking';
$portal = $me['role'] === 'admin' ? 'admin' : 'client';
$active = 'search';
require_once __DIR__ . '/includes/portal-header.php';
?>
<h1 class="page-title">Search</h1>
<p class="page-sub page-sub--spaced">
  <?php if (mb_strlen($q) < 2): ?>Type at least 2 characters in the search bar above.
  <?php else: ?><?= $count ?> result<?= $count === 1 ? '' : 's' ?> for “<?= e($q) ?>”<?php endif; ?>
</p>

<div class="stack">
  <?php foreach ($groups as $g): ?>
  <section class="card">
    <div class="card__head">
      <h2 class="card__title search-group__title"><iconify-icon icon="<?= e($g['icon']) ?>" width="18"></iconify-icon> <?= e($g['label']) ?></h2>
      <?php if ($g['more']): ?><a href="<?= e($g['more']) ?>" class="link-underline">Open in list</a><?php endif; ?>
    </div>
    <div class="result-list">
      <?php foreach ($g['items'] as $it): ?>
      <a href="<?= e($it['link']) ?>" class="result">
        <span class="result__text"><span class="result__title"><?= e($it['title']) ?></span><span class="result__meta"><?= e($it['meta']) ?></span></span>
        <?= pill($it['status']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
  <?php if (mb_strlen($q) >= 2 && !$groups): ?>
  <section class="card"><div class="empty">Nothing matches “<?= e($q) ?>”. Try a name, booking number (BK-1083), invoice number, asset ID or email.</div></section>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/portal-footer.php'; ?>

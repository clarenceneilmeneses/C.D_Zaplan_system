<?php
/* Live results for the portal top-bar search: GET ?q=text → { groups: [...] } (see global_search() in includes/lists.php) */
require_once __DIR__ . '/../includes/bootstrap.php';

$me = current_user();
if (!$me) json_out(['ok' => false, 'error' => 'Please log in again.'], 401);

$groups = global_search($me, (string)($_GET['q'] ?? ''), 4);
foreach ($groups as &$g) {
  foreach ($g['items'] as &$item) $item['pill'] = pill($item['status']);
}
json_out(['ok' => true, 'groups' => $groups]);

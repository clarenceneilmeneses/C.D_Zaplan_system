<?php
/* Small helper functions used on every page (loaded by header.php). */

function e($v) { return htmlspecialchars((string)$v); }

function initials($name) {
  $out = '';
  foreach (explode(' ', $name) as $w) { $out .= strtoupper($w[0] ?? ''); }
  return substr($out, 0, 2);
}

/* Status pill: pill--green / amber / blue / rose / slate (styles live in style.css) */
function pill($status) {
  $map = [
    'Active' => ['green', 'Active'], 'Paid' => ['green', 'Paid'], 'Confirmed' => ['green', 'Confirmed'],
    'Available' => ['green', 'Standby'], 'New' => ['green', 'New'],
    'Pending' => ['amber', 'Pending'], 'Pending Review' => ['amber', 'Pending Review'], 'Due' => ['amber', 'Deposit Due'],
    'Deployed' => ['blue', 'Deployed'], 'Replied' => ['blue', 'Replied'],
    'Maintenance' => ['rose', 'Maintenance'], 'Overdue' => ['rose', 'Overdue'],
    'Completed' => ['slate', 'Completed'], 'Archived' => ['slate', 'Archived'],
  ];
  [$color, $label] = $map[$status] ?? ['slate', $status];
  return '<span class="pill pill--'.$color.'"><span class="pill__dot">●</span>'.e($label).'</span>';
}

/* Availability badge for a service card.
   $a = ['status' => 'available']                         -> green  "Available Now"
        ['status' => 'booked',      'next' => 'Nov 11, 2026'] -> amber  "Booked · Next available: Nov 11, 2026"
        ['status' => 'unavailable', 'next' => 'Nov 30, 2026'] -> red    "Unavailable" (+ return date if given)
   Feed this from your database / API; the markup and styles don't change. */
function availability_badge($a) {
  $next = !empty($a['next']) ? e($a['next']) : '';
  switch ($a['status'] ?? 'available') {
    case 'booked':      $tone = 'amber'; $text = 'Booked' . ($next ? ' · Next available: ' . $next : ''); break;
    case 'unavailable': $tone = 'rose';  $text = 'Unavailable' . ($next ? ' · Next available: ' . $next : '');        break;
    default:            $tone = 'green'; $text = 'Available Now';
  }
  return '<span class="avail avail--' . $tone . '" role="status"><span class="avail__dot"></span>' . $text . '</span>';
}

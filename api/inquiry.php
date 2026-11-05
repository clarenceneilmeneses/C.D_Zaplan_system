<?php
/* Receives the landing-page inquiry form (includes/inquiry-modal.php) and saves it. Returns JSON. */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) json_out(['ok' => false, 'error' => 'POST only.'], 405);
csrf_check();

// Spam trap filled → pretend it worked
if (!empty($_POST['website'])) json_out(['ok' => true]);

$name    = trim($_POST['full_name'] ?? '');
$email   = strtolower(trim($_POST['email'] ?? ''));
$phone   = trim($_POST['phone'] ?? '');
$item    = trim($_POST['item'] ?? '') ?: 'General Inquiry';
$type    = trim($_POST['inquiry_type'] ?? '') ?: 'General Inquiry';
$message = trim($_POST['message'] ?? '');

$allowedTypes = ['General Inquiry', 'Quote & Pricing Request', 'Availability Check', 'Technical Specifications'];
if ($name === '' || mb_strlen($name) > 120)        json_out(['ok' => false, 'error' => 'Please enter your full name.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    json_out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
if ($message === '' || mb_strlen($message) > 3000) json_out(['ok' => false, 'error' => 'Please write a message (up to 3000 characters).'], 422);
if (!in_array($type, $allowedTypes, true))         $type = 'General Inquiry';

// Optional estimate from the map. The price is recalculated here — never trust the browser's number.
$typeId = $days = $lat = $lng = $km = $estimate = null;
$eqType = equipment_type((int)($_POST['type_id'] ?? 0));
if ($eqType && is_numeric($_POST['lat'] ?? null) && is_numeric($_POST['lng'] ?? null)) {
  $lat  = (float)$_POST['lat'];
  $lng  = (float)$_POST['lng'];
  if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
    $typeId   = (int)$eqType['id'];
    $days     = max(1, min(365, (int)($_POST['days'] ?? 1)));
    $km       = trusted_distance($lat, $lng, $_POST['km'] ?? null);
    $estimate = estimate_cost($eqType, $km, $days)['total'];
  } else {
    $lat = $lng = null;
  }
}

q('INSERT INTO inquiries (user_id, full_name, email, phone, item, inquiry_type, message, type_id, rental_days, site_lat, site_lng, distance_km, estimate_amount)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
  [current_user()['id'] ?? null, $name, $email, $phone ?: null, mb_substr($item, 0, 150), $type, $message,
   $typeId, $days, $lat, $lng, $km, $estimate]);
$inquiryId = (int)db()->lastInsertId();

notify_admins('inquiry', "New inquiry from $name",
  $type . ' · ' . mb_substr($item, 0, 80) . ($estimate !== null ? ' · estimate ' . money($estimate) : ''), "inquiries.php?open=$inquiryId");

json_out(['ok' => true, 'estimate' => $estimate]);

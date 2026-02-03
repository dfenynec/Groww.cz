<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/_db.php';

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['error' => 'method_not_allowed'], 405);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) $input = $_POST; // fallback

$slug     = clean_slug((string)($input['property'] ?? ''));
$checkin  = (string)($input['checkin'] ?? '');
$checkout = (string)($input['checkout'] ?? '');
$guests   = isset($input['guests']) ? (int)$input['guests'] : null;

$name  = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$msg   = trim((string)($input['message'] ?? ''));

if ($slug === '') json_out(['error' => 'missing_property'], 400);
if (!is_valid_iso_date($checkin) || !is_valid_iso_date($checkout)) json_out(['error' => 'invalid_dates'], 400);

$nights = nights_between($checkin, $checkout);
if ($nights <= 0) json_out(['error' => 'checkout_must_be_after_checkin'], 400);

// enforce min nights from property JSON
$minNights = get_min_nights_for_property($slug);
if ($nights < $minNights) {
  json_out([
    'error' => 'min_nights',
    'minNights' => $minNights,
    'nights' => $nights
  ], 400);
}

// basic guests validation (optional)
if ($guests !== null && ($guests < 1 || $guests > 50)) {
  json_out(['error' => 'invalid_guests'], 400);
}

// conflict check against DB confirmed
if (db_has_conflict($slug, $checkin, $checkout)) {
  json_out(['error' => 'dates_unavailable'], 409);
}

$pdo = db();

$stmt = $pdo->prepare("
  INSERT INTO reservations
    (property_slug, checkin, checkout, guests, guest_name, guest_email, guest_phone, message, source, status, created_at)
  VALUES
    (:slug, :checkin, :checkout, :guests, :name, :email, :phone, :message, 'enquiry', 'enquiry', :created_at)
");

$createdAt = gmdate('c');

$stmt->execute([
  ':slug' => $slug,
  ':checkin' => $checkin,
  ':checkout' => $checkout,
  ':guests' => $guests,
  ':name' => $name !== '' ? $name : null,
  ':email' => $email !== '' ? $email : null,
  ':phone' => $phone !== '' ? $phone : null,
  ':message' => $msg !== '' ? $msg : null,
  ':created_at' => $createdAt,
]);

$id = (int)$pdo->lastInsertId();

json_out([
  'ok' => true,
  'id' => $id,
  'status' => 'enquiry',
  'created_at' => $createdAt,
]);
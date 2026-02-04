<?php
declare(strict_types=1);

// ✅ 1) CACHE RAW BODY dřív než include (php://input je stream)
$RAW_BODY = file_get_contents('php://input') ?: '';

require_once __DIR__ . '/../_shared/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function read_input(): array {
  global $RAW_BODY;

  $ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');

  // 1) JSON podle headeru
  if (stripos($ct, 'application/json') !== false) {
    $raw = $RAW_BODY;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
  }

  // 2) POST – zkus JSON i bez headeru
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $rawTrim = trim($RAW_BODY);
    if ($rawTrim !== '' && ($rawTrim[0] === '{' || $rawTrim[0] === '[')) {
      $data = json_decode($rawTrim, true);
      if (is_array($data)) return $data;
    }
  }

  // 3) Fallback na form POST
  return $_POST ?? [];
}

try {
  $in = read_input();

  $slug = clean_slug((string)($in['property'] ?? $in['slug'] ?? ''));
  if ($slug === '') {
    // ✅ debug info, ať hned vidíš co server fakt dostal
    respond(400, [
      'ok' => false,
      'error' => 'missing property',
      'debug' => [
        'content_type' => ($_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '')),
        'method' => ($_SERVER['REQUEST_METHOD'] ?? ''),
        'raw_len' => strlen((string)$GLOBALS['RAW_BODY']),
        'keys' => array_keys(is_array($in) ? $in : []),
      ]
    ]);
  }

  $checkin  = (string)($in['checkin'] ?? '');
  $checkout = (string)($in['checkout'] ?? '');
  if (!is_valid_iso_date($checkin) || !is_valid_iso_date($checkout)) {
    respond(400, ['ok'=>false, 'error'=>'invalid dates']);
  }

  $nights = nights_between($checkin, $checkout);
  if ($nights <= 0) respond(400, ['ok'=>false, 'error'=>'checkout must be after checkin']);

  $minReq = get_min_nights_for_property($slug);
  if ($nights < $minReq) {
    respond(400, [
      'ok'=>false,
      'error'=>"minimum stay is {$minReq} nights",
      'minNights'=>$minReq,
      'nights'=>$nights
    ]);
  }

  $guestsRaw = $in['guests'] ?? null;
  if ($guestsRaw === null || $guestsRaw === '') {
    respond(400, ['ok'=>false, 'error'=>'missing guests']);
  }
  $guests = (int)$guestsRaw;
  if ($guests <= 0 || $guests > 50) respond(400, ['ok'=>false, 'error'=>'invalid guests']);

  if (db_has_conflict($slug, $checkin, $checkout)) {
    respond(409, ['ok'=>false, 'error'=>'dates not available']);
  }

  $guest_name  = trim((string)($in['name'] ?? $in['guest_name'] ?? ''));
  $guest_email = trim((string)($in['email'] ?? $in['guest_email'] ?? ''));
  $guest_phone = trim((string)($in['phone'] ?? $in['guest_phone'] ?? ''));
  $message     = trim((string)($in['message'] ?? ''));

  if ($guest_email !== '' && !filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['ok'=>false, 'error'=>'invalid email']);
  }

  $pdo = db();
  $stmt = $pdo->prepare("
    INSERT INTO reservations (
      property_slug, checkin, checkout, guests,
      guest_name, guest_email, guest_phone, message,
      source, status, created_at
    ) VALUES (
      :slug, :cin, :cout, :guests,
      :name, :email, :phone, :msg,
      'enquiry', 'enquiry', :created
    )
  ");

  $created = gmdate('c');
  $stmt->execute([
    ':slug'   => $slug,
    ':cin'    => $checkin,
    ':cout'   => $checkout,
    ':guests' => $guests,
    ':name'   => $guest_name,
    ':email'  => $guest_email,
    ':phone'  => $guest_phone,
    ':msg'    => $message,
    ':created'=> $created,
  ]);

  $id = (int)$pdo->lastInsertId();

  respond(200, [
    'ok' => true,
    'id' => $id,
    'status' => 'enquiry',
    'property' => $slug,
    'checkin' => $checkin,
    'checkout' => $checkout,
    'nights' => $nights
  ]);

} catch (Throwable $e) {
  respond(500, ['ok'=>false, 'error'=>'server error', 'message'=>$e->getMessage()]);
}
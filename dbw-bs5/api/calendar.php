<?php
declare(strict_types=1);

require_once __DIR__ . '/../_shared/db.php';

header('Cache-Control: no-store');

function bad(int $code, string $msg): void {
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  exit;
}

function clean_slug(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s ?: '';
}

function ics_escape(string $s): string {
  // ICS escaping: backslash, comma, semicolon, newline
  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace(",", "\\,", $s);
  $s = str_replace(";", "\\;", $s);
  $s = str_replace("\r\n", "\n", $s);
  $s = str_replace("\r", "\n", $s);
  $s = str_replace("\n", "\\n", $s);
  return $s;
}

function ymd_to_ics_date(string $ymd): string {
  // "2026-03-02" -> "20260302"
  return str_replace("-", "", $ymd);
}

function now_utc_ics(): string {
  return gmdate('Ymd\THis\Z');
}

// --- Input ---
$slug  = clean_slug((string)($_GET['property'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));

if ($slug === '') bad(400, 'missing property');
if ($token === '') bad(401, 'missing token');

// --- Tokens config (per-property) ---
$base = realpath(__DIR__ . '/..'); // /dbw-bs5/api -> /dbw-bs5
if ($base === false) bad(500, 'base path error');

$tokPath = $base . '/private/ical-export-tokens.json';
if (!file_exists($tokPath)) bad(500, 'token config missing');
if (!is_readable($tokPath)) bad(500, 'token config not readable');

$rawTok = file_get_contents($tokPath);
$tokCfg = json_decode($rawTok ?: '', true);
if (!is_array($tokCfg)) bad(500, 'token config invalid json');

$expected = (string)($tokCfg[$slug] ?? '');
if ($expected === '') bad(404, 'unknown property');
if (!hash_equals($expected, $token)) bad(403, 'invalid token');

// --- Fetch confirmed reservations ---
try {
  $pdo = db();

  // jen confirmed (to je to, co má blokovat)
  $stmt = $pdo->prepare("
    SELECT id, checkin, checkout
    FROM reservations
    WHERE property_slug = :slug
      AND status = 'confirmed'
      AND checkin IS NOT NULL AND checkout IS NOT NULL
    ORDER BY checkin ASC
  ");
  $stmt->execute([':slug' => $slug]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  bad(500, 'db error');
}

// --- Build ICS ---
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="' . $slug . '.ics"');

$prodid = '-//DBW Direct Booking//iCal Export//EN';
$calName = 'DBW - ' . $slug;

$out = [];
$out[] = 'BEGIN:VCALENDAR';
$out[] = 'VERSION:2.0';
$out[] = 'PRODID:' . $prodid;
$out[] = 'CALSCALE:GREGORIAN';
$out[] = 'METHOD:PUBLISH';
$out[] = 'X-WR-CALNAME:' . ics_escape($calName);

$dtstamp = now_utc_ics();

foreach ($rows as $r) {
  $id = (int)($r['id'] ?? 0);
  $checkin  = (string)($r['checkin'] ?? '');
  $checkout = (string)($r['checkout'] ?? '');

  // sanity
  if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $checkin)) continue;
  if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $checkout)) continue;

  $uid = $slug . '-' . $id . '@dbw';

  $out[] = 'BEGIN:VEVENT';
  $out[] = 'UID:' . ics_escape($uid);
  $out[] = 'DTSTAMP:' . $dtstamp;
  $out[] = 'SUMMARY:' . ics_escape('Booked');
  // all-day, end-exclusive (DTEND = checkout)
  $out[] = 'DTSTART;VALUE=DATE:' . ymd_to_ics_date($checkin);
  $out[] = 'DTEND;VALUE=DATE:' . ymd_to_ics_date($checkout);
  $out[] = 'END:VEVENT';
}

$out[] = 'END:VCALENDAR';

// fold lines (75 octets) – optional; většinou to platformy sežerou i bez, ale přidáme basic folding
$ics = implode("\r\n", $out) . "\r\n";
echo $ics;
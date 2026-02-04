<?php
declare(strict_types=1);

/**
 * DBW iCal export (per-property, confirmed only)
 * URL:
 *   /dbw-bs5/api/calendar.php?property=SLUG&token=TOKEN
 *
 * Tokens file:
 *   /dbw-bs5/private/ical-export-tokens.json
 *   {
 *     "nissi-golden-sands-a15": "LONG_RANDOM_TOKEN",
 *     "another-slug": "ANOTHER_TOKEN"
 *   }
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// --- Buffer so we can replace output on fatal ---
ob_start();

/** Plain text response (useful for debugging 500) */
function respondText(int $code, string $text): void {
  // clear any buffered output
  if (ob_get_level()) { @ob_clean(); }
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  header('Cache-Control: no-store');
  echo $text;
  exit;
}

/** Safe JSON (only if you want) */
function respondJson(int $code, array $payload): void {
  if (ob_get_level()) { @ob_clean(); }
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/** Fatal error -> readable text */
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    $msg = "FATAL 500 in calendar.php\n"
         . $e['message'] . "\n"
         . "File: " . $e['file'] . "\n"
         . "Line: " . $e['line'] . "\n";
    // log too
    error_log($msg);
    if (ob_get_level()) { @ob_clean(); }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo $msg;
  }
});

function clean_slug(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s ?: '';
}

function is_valid_iso_date(string $s): bool {
  return (bool)preg_match('~^\d{4}-\d{2}-\d{2}$~', $s);
}

function ics_escape(string $s): string {
  // RFC5545: backslash, comma, semicolon, newline
  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace([",", ";"], ["\\,", "\\;"], $s);
  $s = str_replace(["\r\n", "\n", "\r"], "\\n", $s);
  return $s;
}

/** Fold long lines at 75 octets-ish (simple) */
function ics_fold(string $line): string {
  $out = '';
  $len = strlen($line);
  $pos = 0;
  while ($pos < $len) {
    $chunk = substr($line, $pos, 73); // keep some margin
    $pos += 73;
    if ($out === '') $out = $chunk;
    else $out .= "\r\n " . $chunk;
  }
  return $out;
}

try {
  $slug  = clean_slug((string)($_GET['property'] ?? ''));
  $token = trim((string)($_GET['token'] ?? ''));

  if ($slug === '') respondText(400, "missing property\n");
  if ($token === '') respondText(401, "missing token\n");

  // base = /dbw-bs5
  $base = realpath(__DIR__ . '/..');
  if ($base === false) respondText(500, "base realpath failed\n");

  // token file
  $tokenPath = $base . '/private/ical-export-tokens.json';
  if (!file_exists($tokenPath)) respondText(500, "tokens file missing: {$tokenPath}\n");
  if (!is_readable($tokenPath)) respondText(500, "tokens file not readable: {$tokenPath}\n");

  $tokRaw = file_get_contents($tokenPath);
  $tokCfg = json_decode($tokRaw ?: '', true);
  if (!is_array($tokCfg)) respondText(500, "tokens file invalid json: " . json_last_error_msg() . "\n");

  $expected = (string)($tokCfg[$slug] ?? '');
  if ($expected === '' || !hash_equals($expected, $token)) {
    respondText(403, "invalid token\n");
  }

  // DB: read confirmed reservations
  $dbPath = $base . '/storage/app.sqlite';
  if (!file_exists($dbPath)) respondText(500, "db not found: {$dbPath}\n");

  $pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // Ensure table exists
  $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='reservations'")->fetchColumn();
  if (!$exists) respondText(500, "table reservations missing\n");

  $stmt = $pdo->prepare("
    SELECT id, checkin, checkout, guest_name, status, created_at
    FROM reservations
    WHERE property_slug = :slug
      AND status = 'confirmed'
      AND checkin IS NOT NULL AND checkout IS NOT NULL
    ORDER BY checkin ASC
  ");
  $stmt->execute([':slug' => $slug]);
  $rows = $stmt->fetchAll();

  // --- Output ICS ---
  if (ob_get_level()) { @ob_clean(); }
  header('Content-Type: text/calendar; charset=utf-8');
  header('Content-Disposition: inline; filename="dbw-' . $slug . '.ics"');
  header('Cache-Control: no-store');

  $now = gmdate('Ymd\THis\Z');

  echo "BEGIN:VCALENDAR\r\n";
  echo "VERSION:2.0\r\n";
  echo "PRODID:-//DBW//Direct Booking Website//EN\r\n";
  echo "CALSCALE:GREGORIAN\r\n";
  echo "METHOD:PUBLISH\r\n";
  echo ics_fold("X-WR-CALNAME:" . ics_escape("DBW • " . $slug)) . "\r\n";

  foreach ($rows as $r) {
    $checkin  = (string)$r['checkin'];
    $checkout = (string)$r['checkout'];

    if (!is_valid_iso_date($checkin) || !is_valid_iso_date($checkout)) continue;

    // DTSTART/DTEND as DATE (all-day), DTEND is checkout day (end-exclusive)
    $dtstart = str_replace('-', '', $checkin);
    $dtend   = str_replace('-', '', $checkout);

    $uid = "dbw-{$slug}-" . (int)$r['id'] . "@groww.cz";
    $summary = "Booked";

    echo "BEGIN:VEVENT\r\n";
    echo ics_fold("UID:" . $uid) . "\r\n";
    echo "DTSTAMP:" . $now . "\r\n";
    echo "DTSTART;VALUE=DATE:" . $dtstart . "\r\n";
    echo "DTEND;VALUE=DATE:" . $dtend . "\r\n";
    echo ics_fold("SUMMARY:" . ics_escape($summary)) . "\r\n";
    echo "STATUS:CONFIRMED\r\n";
    echo "END:VEVENT\r\n";
  }

  echo "END:VCALENDAR\r\n";
  exit;

} catch (Throwable $e) {
  error_log("calendar.php exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
  respondText(500, "EXCEPTION 500 in calendar.php\n" . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n");
}
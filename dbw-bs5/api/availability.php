<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

/** Vrátí JSON a exit */
function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

/** Když se stane fatal error, vrať JSON (hoster jinak ukáže jen 500 HTML) */
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'error' => 'fatal',
      'message' => $e['message'],
      'file' => $e['file'],
      'line' => $e['line'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }
});

function clean_slug(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s ?: '';
}

/**
 * Robustní HTTP GET:
 * - zkus file_get_contents (allow_url_fopen)
 * - fallback cURL
 * Vrací: [string|null $body, array $meta]
 */
function http_get(string $url, int $timeoutSec = 12): array {
  $meta = [
    'url' => $url,
    'method' => null,
    'ok' => false,
    'http_code' => null,
    'error' => null,
  ];

  // 1) file_get_contents
  if (filter_var($url, FILTER_VALIDATE_URL) && (bool)ini_get('allow_url_fopen')) {
    $meta['method'] = 'fopen';
    $ctx = stream_context_create([
      'http' => [
        'method'  => 'GET',
        'timeout' => $timeoutSec,
        'header'  => "User-Agent: DBW-Availability/1.0\r\n",
      ],
      'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
      ],
    ]);

    $body = @file_get_contents($url, false, $ctx);

    $code = null;
    if (isset($http_response_header) && is_array($http_response_header)) {
      foreach ($http_response_header as $h) {
        if (preg_match('~^HTTP/\S+\s+(\d{3})~i', $h, $m)) {
          $code = (int)$m[1];
          break;
        }
      }
    }
    $meta['http_code'] = $code;

    if ($body !== false && $body !== null && $body !== '') {
      $meta['ok'] = ($code === null) ? true : ($code >= 200 && $code < 300);
      return [$body, $meta];
    }
    $meta['error'] = 'file_get_contents failed (or empty response)';
  }

  // 2) cURL fallback
  if (function_exists('curl_init')) {
    $meta['method'] = ($meta['method'] === null) ? 'curl' : ($meta['method'] . '+curl');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS      => 5,
      CURLOPT_CONNECTTIMEOUT => $timeoutSec,
      CURLOPT_TIMEOUT        => $timeoutSec,
      CURLOPT_USERAGENT      => 'DBW-Availability/1.0',
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $meta['http_code'] = $code ?: null;

    if ($body !== false && $body !== null && $body !== '') {
      $meta['ok'] = ($code >= 200 && $code < 300);
      return [$body, $meta];
    }

    $meta['error'] = $err ?: 'curl failed (or empty response)';
    return [null, $meta];
  }

  $meta['error'] = 'No fetch method available (allow_url_fopen off and curl missing)';
  return [null, $meta];
}

function unfold_ical(string $ics): array {
  $ics = str_replace("\r\n", "\n", $ics);
  $lines = explode("\n", $ics);
  $out = [];

  foreach ($lines as $line) {
    if ($line === '') continue;
    if (!empty($out) && isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t")) {
      $out[count($out) - 1] .= substr($line, 1);
    } else {
      $out[] = $line;
    }
  }
  return $out;
}

function parse_ical_events(string $ics): array {
  $lines = unfold_ical($ics);
  $events = [];
  $in = false;
  $cur = [];

  foreach ($lines as $line) {
    $t = trim($line);
    if ($t === 'BEGIN:VEVENT') { $in = true; $cur = []; continue; }
    if ($t === 'END:VEVENT') {
      if (!empty($cur['DTSTART']) && !empty($cur['DTEND'])) $events[] = $cur;
      $in = false; $cur = [];
      continue;
    }
    if (!$in) continue;

    $pos = strpos($line, ':');
    if ($pos === false) continue;

    $k = substr($line, 0, $pos);
    $v = substr($line, $pos + 1);

    $semi = strpos($k, ';'); // DTSTART;VALUE=DATE -> DTSTART
    if ($semi !== false) $k = substr($k, 0, $semi);

    $k = strtoupper(trim($k));
    $cur[$k] = trim($v);
  }

  return $events;
}

function ical_date_to_iso(string $dt): ?string {
  $dt = trim($dt);
  if ($dt === '') return null;

  if (preg_match('~^\d{8}$~', $dt)) {
    return substr($dt, 0, 4) . '-' . substr($dt, 4, 2) . '-' . substr($dt, 6, 2);
  }
  if (preg_match('~^(\d{8})T~', $dt, $m)) {
    $d = $m[1];
    return substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2);
  }
  return null;
}

/**
 * END-EXCLUSIVE range: [from, to)
 */
function add_range_exclusive(array &$ranges, string $fromISO, string $toISO): void {
  $from = DateTime::createFromFormat('Y-m-d', $fromISO, new DateTimeZone('UTC'));
  $to   = DateTime::createFromFormat('Y-m-d', $toISO,   new DateTimeZone('UTC'));
  if (!$from || !$to) return;
  if ($to <= $from) return;

  $ranges[] = [
    'from' => $from->format('Y-m-d'),
    'to'   => $to->format('Y-m-d'),
  ];
}

/** merge end-exclusive ranges */
function merge_ranges_exclusive(array $ranges): array {
  if (!$ranges) return [];
  usort($ranges, fn($a, $b) => strcmp($a['from'], $b['from']));

  $out = [];
  $cur = $ranges[0];

  foreach (array_slice($ranges, 1) as $r) {
    // merge if overlap OR touch: next.from <= cur.to
    if ($r['from'] <= $cur['to']) {
      if ($r['to'] > $cur['to']) $cur['to'] = $r['to'];
    } else {
      $out[] = $cur;
      $cur = $r;
    }
  }
  $out[] = $cur;
  return $out;
}

/** načte CONFIRMED rezervace z SQLite */
function fetch_db_ranges_exclusive(string $dbPath, string $slug, array &$warnings): array {
  if (!file_exists($dbPath)) {
    $warnings[] = "DB not found: {$dbPath}";
    return [];
  }

  try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='reservations'")->fetchColumn();
    if (!$exists) {
      $warnings[] = "DB table 'reservations' missing";
      return [];
    }

    $stmt = $pdo->prepare("
      SELECT checkin, checkout
      FROM reservations
      WHERE property_slug = :slug
        AND status = 'confirmed'
        AND checkin IS NOT NULL AND checkout IS NOT NULL
    ");
    $stmt->execute([':slug' => $slug]);

    $ranges = [];
    foreach ($stmt->fetchAll() as $row) {
      add_range_exclusive($ranges, (string)$row['checkin'], (string)$row['checkout']);
    }
    return $ranges;

  } catch (Throwable $e) {
    $warnings[] = "DB error: " . $e->getMessage();
    return [];
  }
}

/** načte iCal URL z property JSON: ical.airbnb + ical.booking */
function load_ical_urls_from_property(string $base, string $slug, array &$warnings): array {
  $propPath = $base . '/data/properties/' . $slug . '.json';
  if (!file_exists($propPath)) {
    $warnings[] = "Property JSON not found: {$propPath}";
    return [];
  }
  if (!is_readable($propPath)) {
    $warnings[] = "Property JSON not readable: {$propPath}";
    return [];
  }

  $raw = file_get_contents($propPath);
  $prop = json_decode($raw ?: '', true);
  if (!is_array($prop)) {
    $warnings[] = "Property JSON invalid: {$propPath} (" . json_last_error_msg() . ")";
    return [];
  }

  $urls = [];
  $air = $prop['ical']['airbnb'] ?? '';
  $boo = $prop['ical']['booking'] ?? '';

  if (is_string($air) && trim($air) !== '') $urls[] = trim($air);
  if (is_string($boo) && trim($boo) !== '') $urls[] = trim($boo);

  if (!$urls) $warnings[] = "No iCal URLs set in property JSON (ical.airbnb / ical.booking)";

  return $urls;
}

// ---------------- MAIN ----------------
try {
  $slug = clean_slug((string)($_GET['property'] ?? ''));
  if ($slug === '') respond(400, ['error' => 'missing property']);

  // /dbw-bs5/api -> /dbw-bs5
  $base = realpath(__DIR__ . '/..');
  if ($base === false) respond(500, ['error' => 'base realpath failed', 'dir' => __DIR__]);

  $warnings = [];
  $ranges = [];

  // 1) iCal urls from property JSON
  $urls = load_ical_urls_from_property($base, $slug, $warnings);

  // 2) iCal ranges
  foreach ($urls as $u) {
    [$ics, $meta] = http_get($u);

    if (!$meta['ok'] || !$ics) {
      $warnings[] = "iCal fetch failed: {$u} (method={$meta['method']}, code=" . ($meta['http_code'] ?? 'null') . ", err=" . ($meta['error'] ?? 'unknown') . ")";
      continue;
    }

    $events = parse_ical_events($ics);
    foreach ($events as $ev) {
      $startISO = ical_date_to_iso((string)($ev['DTSTART'] ?? ''));
      $endISO   = ical_date_to_iso((string)($ev['DTEND'] ?? ''));
      if ($startISO && $endISO) add_range_exclusive($ranges, $startISO, $endISO);
    }
  }

  // 3) DB confirmed ranges
  $dbPath = $base . '/storage/app.sqlite';
  $rangesDb = fetch_db_ranges_exclusive($dbPath, $slug, $warnings);
  $ranges = array_merge($ranges, $rangesDb);

  // 4) merge
  $ranges = merge_ranges_exclusive($ranges);

  respond(200, [
    'property' => $slug,
    'booked' => $ranges,
    'warnings' => $warnings,
  ]);

} catch (Throwable $e) {
  respond(500, [
    'error' => 'exception',
    'message' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine(),
  ]);
}
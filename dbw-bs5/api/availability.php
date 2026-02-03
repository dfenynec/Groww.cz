<?php
header('Content-Type: application/json; charset=utf-8');

function clean_slug($s){
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s;
}

function http_get($url){
  $ctx = stream_context_create([
    'http' => [
      'method' => 'GET',
      'timeout' => 12,
      'header' => "User-Agent: DBW-Availability/1.0\r\n"
    ],
    'ssl' => [
      'verify_peer' => true,
      'verify_peer_name' => true
    ]
  ]);
  return @file_get_contents($url, false, $ctx);
}

function unfold_ical($ics){
  $ics = str_replace("\r\n", "\n", $ics);
  $lines = explode("\n", $ics);
  $out = [];
  foreach ($lines as $line) {
    if ($line === '') continue;
    if (!empty($out) && (isset($line[0]) && ($line[0] === ' ' || $line[0] === "\t"))) {
      $out[count($out)-1] .= substr($line, 1);
    } else {
      $out[] = $line;
    }
  }
  return $out;
}

function parse_ical_events($ics){
  $lines = unfold_ical($ics);
  $events = [];
  $in = false;
  $cur = [];

  foreach ($lines as $line) {
    if (trim($line) === 'BEGIN:VEVENT') { $in = true; $cur = []; continue; }
    if (trim($line) === 'END:VEVENT') {
      if (!empty($cur['DTSTART']) && !empty($cur['DTEND'])) $events[] = $cur;
      $in = false; $cur = [];
      continue;
    }
    if (!$in) continue;

    $pos = strpos($line, ':');
    if ($pos === false) continue;
    $k = substr($line, 0, $pos);
    $v = substr($line, $pos + 1);

    $semi = strpos($k, ';');
    if ($semi !== false) $k = substr($k, 0, $semi);

    $k = strtoupper(trim($k));
    $cur[$k] = trim($v);
  }
  return $events;
}

function ical_date_to_iso($dt){
  $dt = trim($dt);
  if (preg_match('~^\d{8}$~', $dt)) {
    return substr($dt,0,4).'-'.substr($dt,4,2).'-'.substr($dt,6,2);
  }
  if (preg_match('~^(\d{8})T~', $dt, $m)) {
    $d = $m[1];
    return substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2);
  }
  return null;
}

/**
 * END-EXCLUSIVE range:
 * from = checkin
 * to   = checkout  (DTEND)
 */
function add_range_exclusive(&$ranges, $fromISO, $toISO){
  // basic validation
  $from = DateTime::createFromFormat('Y-m-d', $fromISO, new DateTimeZone('UTC'));
  $to   = DateTime::createFromFormat('Y-m-d', $toISO,   new DateTimeZone('UTC'));
  if (!$from || !$to) return;

  // end-exclusive: pokud to <= from => nic (0 nocí)
  if ($to <= $from) return;

  $ranges[] = [
    'from' => $from->format('Y-m-d'),
    'to'   => $to->format('Y-m-d'),
  ];
}

/**
 * Merge END-EXCLUSIVE ranges:
 * merge if overlap OR touch (cur.to >= next.from)
 */
function merge_ranges_exclusive($ranges){
  if (!$ranges) return [];
  usort($ranges, fn($a,$b) => strcmp($a['from'], $b['from']));

  $out = [];
  $cur = $ranges[0];

  foreach (array_slice($ranges, 1) as $r) {
    // if next starts before or exactly at current end -> merge
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

function fetch_db_ranges_exclusive($dbPath, $slug){
  if (!file_exists($dbPath)) return [];

  $pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // očekáváme tabulku reservations:
  // property_slug TEXT, checkin TEXT (YYYY-MM-DD), checkout TEXT (YYYY-MM-DD), status TEXT
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
    add_range_exclusive($ranges, $row['checkin'], $row['checkout']);
  }
  return $ranges;
}

// -------- main --------
$slug = clean_slug($_GET['property'] ?? '');
if (!$slug) { http_response_code(400); echo json_encode(['error'=>'missing property']); exit; }

// base path
$base = realpath(__DIR__ . '/..'); // /.../dbw-bs5
$configPath = $base . '/private/ical-config.json';

if (!file_exists($configPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'config missing', 'configPath' => $configPath]);
  exit;
}
if (!is_readable($configPath)) {
  http_response_code(500);
  echo json_encode(['error' => 'config not readable', 'configPath' => $configPath]);
  exit;
}

$raw = file_get_contents($configPath);
if ($raw === false) {
  http_response_code(500);
  echo json_encode(['error' => 'config read failed', 'configPath' => $configPath]);
  exit;
}
$cfg = $raw ? json_decode($raw, true) : null;

// iCal urls (optional)
$urls = [];
if (is_array($cfg) && !empty($cfg[$slug])) {
  if (!empty($cfg[$slug]['airbnb']))  $urls[] = $cfg[$slug]['airbnb'];
  if (!empty($cfg[$slug]['booking'])) $urls[] = $cfg[$slug]['booking'];
}

// 1) ranges from iCal
$ranges = [];

foreach ($urls as $u) {
  $ics = http_get($u);
  if (!$ics) continue;

  $events = parse_ical_events($ics);
  foreach ($events as $ev) {
    $startISO = ical_date_to_iso($ev['DTSTART'] ?? '');
    $endISO   = ical_date_to_iso($ev['DTEND'] ?? '');
    if ($startISO && $endISO) add_range_exclusive($ranges, $startISO, $endISO);
  }
}

// 2) ranges from DB reservations
$dbPath = $base . '/storage/app.sqlite';
$rangesDb = fetch_db_ranges_exclusive($dbPath, $slug);
$ranges = array_merge($ranges, $rangesDb);

// 3) merge
$ranges = merge_ranges_exclusive($ranges);

header('Cache-Control: no-store');

echo json_encode([
  'property' => $slug,
  'booked' => $ranges
], JSON_UNESCAPED_SLASHES);
<?php
header('Content-Type: application/json; charset=utf-8');

function clean_slug($s){
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s;
}

function http_get($url){
  // jednoduché stažení přes file_get_contents s timeoutem
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
  // iCal "line folding": řádky začínající mezerou/tab jsou pokračování předchozího
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

    // Key;PARAMS:VALUE
    $pos = strpos($line, ':');
    if ($pos === false) continue;
    $k = substr($line, 0, $pos);
    $v = substr($line, $pos + 1);

    // strip params (DTSTART;VALUE=DATE -> DTSTART)
    $semi = strpos($k, ';');
    if ($semi !== false) $k = substr($k, 0, $semi);

    $k = strtoupper(trim($k));
    $cur[$k] = trim($v);
  }
  return $events;
}

function ical_date_to_iso($dt){
  // DTSTART může být:
  // - 20260202 (DATE)
  // - 20260202T120000Z (DATE-TIME)
  // - 20260202T120000 (DATE-TIME local)
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

function add_range(&$ranges, $fromISO, $toISO){
  // iCal DTEND je obvykle "checkout" (exclusive),
  // takže obsazené noci jsou [DTSTART, DTEND)
  // My vrátíme disable range inclusive -> to = DTEND - 1 den
  $from = DateTime::createFromFormat('Y-m-d', $fromISO, new DateTimeZone('UTC'));
  $to = DateTime::createFromFormat('Y-m-d', $toISO, new DateTimeZone('UTC'));
  if (!$from || !$to) return;

  // to = to - 1 day
  $to->modify('-1 day');

  // pokud by to vyšlo obráceně (jednodenní), tak nic
  if ($to < $from) return;

  $ranges[] = [
    'from' => $from->format('Y-m-d'),
    'to'   => $to->format('Y-m-d')
  ];
}

function merge_ranges($ranges){
  if (!$ranges) return [];
  usort($ranges, fn($a,$b) => strcmp($a['from'], $b['from']));

  $out = [];
  $cur = $ranges[0];

  foreach (array_slice($ranges,1) as $r) {
    // pokud se překrývají nebo navazují (cur.to +1 >= r.from), sloučit
    $curTo = DateTime::createFromFormat('Y-m-d', $cur['to'], new DateTimeZone('UTC'));
    $rFrom = DateTime::createFromFormat('Y-m-d', $r['from'], new DateTimeZone('UTC'));
    $curToPlus = clone $curTo; $curToPlus->modify('+1 day');

    if ($rFrom <= $curToPlus) {
      // extend cur.to pokud je potřeba
      if ($r['to'] > $cur['to']) $cur['to'] = $r['to'];
    } else {
      $out[] = $cur;
      $cur = $r;
    }
  }
  $out[] = $cur;
  return $out;
}

// -------- main --------
$slug = clean_slug($_GET['property'] ?? '');
if (!$slug) { http_response_code(400); echo json_encode(['error'=>'missing property']); exit; }

// config file (private)
$base = rtrim(dirname(__DIR__), "."); // /dbw-bs5
$configPath = $base . '/private/ical-config.json';

$raw = @file_get_contents($configPath);
if (!$raw) { http_response_code(500); echo json_encode(['error'=>'config missing']); exit; }

$cfg = json_decode($raw, true);
if (!is_array($cfg) || empty($cfg[$slug])) {
  http_response_code(400);
  echo json_encode(['error'=>'unknown property']);
  exit;
}

$urls = [];
if (!empty($cfg[$slug]['airbnb']))  $urls[] = $cfg[$slug]['airbnb'];
if (!empty($cfg[$slug]['booking'])) $urls[] = $cfg[$slug]['booking'];

if (!$urls) { echo json_encode(['booked'=>[]]); exit; }

$ranges = [];

foreach ($urls as $u) {
  $ics = http_get($u);
  if (!$ics) continue;

  $events = parse_ical_events($ics);
  foreach ($events as $ev) {
    $startISO = ical_date_to_iso($ev['DTSTART'] ?? '');
    $endISO   = ical_date_to_iso($ev['DTEND'] ?? '');
    if ($startISO && $endISO) add_range($ranges, $startISO, $endISO);
  }
}

$ranges = merge_ranges($ranges);

// cache header (klidně uprav)
header('Cache-Control: no-store');

echo json_encode([
  'property' => $slug,
  'booked' => $ranges
], JSON_UNESCAPED_SLASHES);
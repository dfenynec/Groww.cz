<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ---- CONFIG ----
// slug -> "airbnb_ical|booking_ical"  (může být i jen jeden)
$ICAL = [
 'nissi-golden-sands-a15' => 'https://www.airbnb.cz/calendar/ical/1582050281655598894.ics?t=0fcfb18f876d4df899eaeb5b9ea257c6',
];

// Pokud chceš, aby byl checkout den volný (typické chování):
// iCal DTEND je "end-exclusive", takže takhle to sedí.
// Pokud bys chtěl konzervativní blokaci, nastav false.
$ALLOW_CHECKOUT_DAY = true;

// ---- INPUT ----
$slug = isset($_GET['property']) ? $_GET['property'] : '';
if (!$slug) { http_response_code(400); echo json_encode(['error'=>'Missing property']); exit; }
if (!isset($ICAL[$slug])) { http_response_code(404); echo json_encode(['error'=>'Unknown property']); exit; }

$urls = array_filter(array_map('trim', explode('|', $ICAL[$slug])));

$booked = [];
$debug = [
  'slug' => $slug,
  'sources' => count($urls),
  'fetched' => 0
];

foreach ($urls as $u) {
  $ics = fetch_ics($u);
  if ($ics === null) continue;
  $debug['fetched']++;
  $ranges = parse_ics_to_ranges($ics, $ALLOW_CHECKOUT_DAY);
  $booked = array_merge($booked, $ranges);
}

$booked = merge_ranges($booked);

header('Cache-Control: public, max-age=300');
echo json_encode([
  'booked' => $booked,
  'updatedAt' => gmdate('c'),
  'debug' => $debug
]);

// -------- helpers --------

function fetch_ics($url) {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 12,
      CURLOPT_CONNECTTIMEOUT => 6,
      CURLOPT_USERAGENT => 'DBW-Calendar-Proxy/1.0'
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($data !== false && $code >= 200 && $code < 300) return $data;
    return null;
  } else {
    $ctx = stream_context_create([
      'http' => ['timeout' => 12, 'header' => "User-Agent: DBW-Calendar-Proxy/1.0\r\n"]
    ]);
    $data = @file_get_contents($url, false, $ctx);
    return $data === false ? null : $data;
  }
}

function parse_ics_to_ranges($ics, $allowCheckoutDay) {
  $ics = str_replace("\r\n", "\n", $ics);
  $raw = explode("\n", $ics);

  // Unfold lines
  $lines = [];
  foreach ($raw as $l) {
    if ($l === '') continue;
    if (strlen($l) > 0 && $l[0] === ' ' && count($lines)) {
      $lines[count($lines)-1] .= substr($l, 1);
    } else {
      $lines[] = trim($l);
    }
  }

  $in = false; $start=null; $end=null;
  $out = [];

  foreach ($lines as $line) {
    if ($line === 'BEGIN:VEVENT') { $in = true; $start=null; $end=null; continue; }
    if ($line === 'END:VEVENT') {
      if ($in && $start && $end) {
        // DTEND v iCal je u all-day eventů typicky end-exclusive.
        // Pokud allowCheckoutDay=true, necháváme to jak je (to už je správně).
        // Pokud false, uděláme end inclusive tím, že end posuneme o +1 den.
        if (!$allowCheckoutDay) {
          $end = add_days($end, 1);
        }
        $out[] = ['from'=>$start, 'to'=>$end];
      }
      $in = false; $start=null; $end=null; continue;
    }
    if (!$in) continue;

    if (strpos($line, 'DTSTART') === 0) $start = to_iso_date(extract_val($line));
    if (strpos($line, 'DTEND') === 0) $end = to_iso_date(extract_val($line));
  }

  return $out;
}

function extract_val($line) {
  $pos = strpos($line, ':');
  return $pos !== false ? trim(substr($line, $pos+1)) : '';
}

function to_iso_date($v) {
  if (!$v) return null;
  if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $v, $m)) {
    return $m[1].'-'.$m[2].'-'.$m[3];
  }
  return null;
}

function add_days($iso, $days) {
  $dt = DateTime::createFromFormat('Y-m-d', $iso, new DateTimeZone('UTC'));
  if (!$dt) return $iso;
  $dt->modify(($days >= 0 ? '+' : '') . $days . ' day');
  return $dt->format('Y-m-d');
}

function merge_ranges($ranges) {
  usort($ranges, function($a,$b){ return strcmp($a['from'], $b['from']); });
  $out = [];
  foreach ($ranges as $r) {
    if (empty($r['from']) || empty($r['to'])) continue;
    if (!$out) { $out[] = $r; continue; }
    $lastIndex = count($out)-1;
    if ($r['from'] <= $out[$lastIndex]['to']) {
      if ($r['to'] > $out[$lastIndex]['to']) $out[$lastIndex]['to'] = $r['to'];
    } else {
      $out[] = $r;
    }
  }
  return $out;
}
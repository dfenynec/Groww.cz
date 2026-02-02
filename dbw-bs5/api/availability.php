<?php
// /api/availability.php?property=<slug>
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$slug = isset($_GET['property']) ? $_GET['property'] : '';
if (!$slug) { http_response_code(400); echo json_encode(['error'=>'Missing property']); exit; }

/**
 * MAPA: slug -> iCal URL(s)
 * Pro 1–20 nemovitostí tohle stačí.
 * Pro škálu to později přepneš do DB/adminu.
 *
 * Pozn.: Můžeš dát více URL oddělené | (Airbnb|Booking)
 */
$ICAL = ['nissi-golden-sands-a15' => 'https://www.airbnb.cz/calendar/ical/1582050281655598894.ics?t=0fcfb18f876d4df899eaeb5b9ea257c6'];

if (!isset($ICAL[$slug])) { http_response_code(404); echo json_encode(['error'=>'Unknown property']); exit; }

$urls = array_filter(array_map('trim', explode('|', $ICAL[$slug])));

$booked = [];
foreach ($urls as $u) {
  $ics = fetch_ics($u);
  if ($ics === null) continue;
  $ranges = parse_ics_to_ranges($ics);
  $booked = array_merge($booked, $ranges);
}

$booked = merge_ranges($booked);

// Cache hint (na hostingu se může lišit; ale prohlížeč to respektuje)
header('Cache-Control: public, max-age=300');

echo json_encode(['booked'=>$booked, 'updatedAt'=>gmdate('c')]);

// -------- helpers --------

function fetch_ics($url) {
  // Prefer cURL, fallback file_get_contents
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

function parse_ics_to_ranges($ics) {
  $ics = str_replace("\r\n", "\n", $ics);
  $raw = explode("\n", $ics);

  // Unfold lines (iCal folding)
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
      if ($in && $start && $end) $out[] = ['from'=>$start, 'to'=>$end];
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
  // Handles YYYYMMDD and YYYYMMDDTHHMMSSZ
  if (!$v) return null;
  if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $v, $m)) {
    return $m[1].'-'.$m[2].'-'.$m[3];
  }
  return null;
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
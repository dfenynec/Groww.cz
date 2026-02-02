<?php
header('Content-Type: application/json; charset=utf-8');

$url = isset($_GET['url']) ? $_GET['url'] : '';
if (!$url) { echo json_encode(['error'=>'missing url']); exit; }

$out = ['url'=>$url];

if (function_exists('curl_init')) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_USERAGENT => 'DBW-Proxy-Test/1.0'
  ]);
  $data = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);

  $out['curl'] = [
    'ok' => $data !== false,
    'http' => $code,
    'error' => $err,
    'len' => $data !== false ? strlen($data) : 0,
    'head' => $data !== false ? substr($data, 0, 120) : ''
  ];
} else {
  $out['curl'] = ['ok'=>false,'error'=>'curl not available'];
}

$ctx = stream_context_create(['http' => ['timeout' => 12, 'header' => "User-Agent: DBW-Proxy-Test/1.0\r\n"]]);
$data2 = @file_get_contents($url, false, $ctx);
$out['fopen'] = [
  'ok' => $data2 !== false,
  'len' => $data2 !== false ? strlen($data2) : 0,
  'head' => $data2 !== false ? substr($data2, 0, 120) : ''
];

echo json_encode($out);
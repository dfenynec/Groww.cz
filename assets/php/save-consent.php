<?php
// save-consent.php

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get and decode JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate consent value
if (!isset($data['consent']) || !in_array($data['consent'], ['accepted', 'rejected', 'all', 'necessary'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid consent value']);
    exit;
}


// Prepare consent record
$record = [
    'timestamp' => $data['timestamp'] ?? date('c'),
    'consent'   => $data['consent'],
    'ip'        => $_SERVER['REMOTE_ADDR'],
    'userAgent' => $data['userAgent'] ?? '',
    'pageUrl'   => $data['pageUrl'] ?? '',
];


// File to store logs (make sure this is not web-accessible)
$file = __DIR__ . '/consent-log.json';

// Read existing log
$log = [];
if (file_exists($file)) {
    $log = json_decode(file_get_contents($file), true);
    if (!is_array($log)) $log = [];
}

// Append new record
$log[] = $record;

// Save back to file
file_put_contents($file, json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Respond OK
http_response_code(200);
echo json_encode(['status' => 'ok']);
?>

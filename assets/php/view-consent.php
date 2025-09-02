<?php
$file = __DIR__ . '/consent-log.json';

if (!file_exists($file)) {
    echo "No consent data found.";
    exit;
}

$data = json_decode(file_get_contents($file), true);

if (!$data) {
    echo "Consent log is empty or corrupted.";
    exit;
}

echo "<h1>Consent Log</h1>";
echo "<table border='1' cellpadding='5'><tr><th>Timestamp</th><th>Consent</th><th>IP</th><th>User Agent</th></tr>";
foreach ($data as $entry) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($entry['timestamp']) . "</td>";
    echo "<td>" . htmlspecialchars($entry['consent']) . "</td>";
    echo "<td>" . htmlspecialchars($entry['ip']) . "</td>";
    echo "<td>" . htmlspecialchars($entry['userAgent']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>

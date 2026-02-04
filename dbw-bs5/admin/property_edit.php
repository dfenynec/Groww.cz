<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

$base = realpath(__DIR__ . '/..');
$slug = preg_replace('~[^a-z0-9\-]~', '', strtolower((string)($_GET['slug'] ?? '')));
if ($slug === '') { echo "Missing slug"; exit; }

$path = $base . '/data/properties/' . $slug . '.json';
if (!file_exists($path)) { echo "Not found: " . htmlspecialchars($path); exit; }

$err = null;
$ok  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = (string)($_POST['json'] ?? '');

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    $err = "Invalid JSON: " . json_last_error_msg();
  } else {
    // základní “must have”
    if (empty($decoded['slug'])) $decoded['slug'] = $slug;

    // backup
    $backupDir = $base . '/storage/property-backups';
    if (!is_dir($backupDir)) @mkdir($backupDir, 0775, true);

    $stamp = gmdate('Ymd-His');
    $backupPath = $backupDir . '/' . $slug . '-' . $stamp . '.json';
    @copy($path, $backupPath);

    // pretty save
    $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($pretty === false) {
      $err = "Failed to encode JSON.";
    } else {
      $res = file_put_contents($path, $pretty);
      if ($res === false) $err = "Failed to write file.";
      else $ok = "Saved. Backup: " . basename($backupPath);
    }
  }
}

$rawCurrent = file_get_contents($path) ?: '';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit property <?= htmlspecialchars($slug) ?></title></head>
<body style="font-family:system-ui;max-width:1100px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Edit property: <?= htmlspecialchars($slug) ?></h2>
    <div><a href="properties.php">Back</a> | <a href="dashboard.php">Dashboard</a></div>
  </div>

  <?php if ($err): ?>
    <div style="background:#fee;border:1px solid #f99;padding:10px;border-radius:8px;margin:12px 0;">
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div style="background:#efe;border:1px solid #9f9;padding:10px;border-radius:8px;margin:12px 0;">
      <?= htmlspecialchars($ok) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <textarea name="json" style="width:100%;min-height:70vh;font-family:ui-monospace, SFMono-Regular, Menlo, monospace;font-size:13px;padding:12px;border-radius:10px;border:1px solid #ddd;"><?= htmlspecialchars($rawCurrent) ?></textarea>
    <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
      <button style="padding:10px 14px">Save JSON</button>
      <a href="../property.html?p=<?= urlencode($slug) ?>" target="_blank" style="padding:10px 14px;border:1px solid #ddd;border-radius:10px;text-decoration:none">Open property page</a>
    </div>
    <p style="color:#666;font-size:13px;margin-top:10px">
      Tip: Uložením se vytvoří backup do <code>/storage/property-backups/</code>
    </p>
  </form>
</body>
</html>
<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

$base = realpath(__DIR__ . '/..');
$dir = $base . '/data/properties';
$files = glob($dir . '/*.json') ?: [];

function slug_from_path(string $p): string {
  return basename($p, '.json');
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Properties</title></head>
<body style="font-family:system-ui;max-width:1000px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Properties</h2>
    <div><a href="dashboard.php">Dashboard</a> | <a href="logout.php">Logout</a></div>
  </div>

  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="text-align:left;border-bottom:1px solid #ddd">
        <th>Slug</th><th>File</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($files as $f): $slug = slug_from_path($f); ?>
      <tr style="border-bottom:1px solid #f0f0f0">
        <td><b><?= htmlspecialchars($slug) ?></b></td>
        <td><small><?= htmlspecialchars(str_replace($base,'',$f)) ?></small></td>
        <td><a href="property_edit.php?slug=<?= urlencode($slug) ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

require_once __DIR__ . '/../_shared/db.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pdo = db();

$filter = (string)($_GET['status'] ?? 'confirmed');
$allowed = ['confirmed','cancelled','rejected','enquiry','all'];
if (!in_array($filter, $allowed, true)) $filter = 'confirmed';

$where = "";
$params = [];
if ($filter !== 'all') {
  $where = "WHERE status = :s";
  $params[':s'] = $filter;
}

$stmt = $pdo->prepare("
  SELECT *
  FROM reservations
  $where
  ORDER BY datetime(created_at) DESC, id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Reservations</title></head>
<body style="font-family:system-ui;max-width:1100px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Reservations</h2>
    <div><a href="dashboard.php">Dashboard</a> | <a href="bookings.php">Enquiries</a> | <a href="properties.php">Properties</a> | <a href="logout.php">Logout</a></div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0">
    <?php foreach (['confirmed','enquiry','cancelled','rejected','all'] as $s): ?>
      <a href="reservations.php?status=<?= urlencode($s) ?>"
         style="padding:8px 12px;border:1px solid #ddd;border-radius:999px;text-decoration:none;<?= $filter===$s?'background:#111;color:#fff;border-color:#111':'' ?>">
        <?= h($s) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="text-align:left;border-bottom:1px solid #ddd">
        <th>ID</th><th>Status</th><th>Property</th><th>Dates</th><th>Guests</th><th>Guest</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" style="padding:14px;color:#666;">No reservations.</td></tr>
      <?php endif; ?>

      <?php foreach ($rows as $r): ?>
      <tr style="border-bottom:1px solid #f0f0f0">
        <td style="padding:10px;"><b>#<?= (int)$r['id'] ?></b></td>
        <td style="padding:10px;"><?= h($r['status']) ?></td>
        <td style="padding:10px;"><?= h($r['property_slug']) ?></td>
        <td style="padding:10px;"><?= h($r['checkin']) ?> → <?= h($r['checkout']) ?></td>
        <td style="padding:10px;"><?= h($r['guests'] ?? '') ?></td>
        <td style="padding:10px;">
          <div><?= h($r['guest_name'] ?? '') ?></div>
          <small style="color:#666"><?= h($r['guest_email'] ?? '') ?></small>
        </td>
        <td style="padding:10px;white-space:nowrap">
          <a href="booking_edit.php?id=<?= (int)$r['id'] ?>">Open</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
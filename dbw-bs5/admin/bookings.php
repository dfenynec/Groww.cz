<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

require_once __DIR__ . '/../_shared/db.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pdo = db();

// quick actions (confirm/reject) přes GET (pro MVP ok; později přepneme na POST + CSRF)
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id > 0 && in_array($action, ['confirm','reject'], true)) {
  $newStatus = $action === 'confirm' ? 'confirmed' : 'rejected';
  $stmt = $pdo->prepare("UPDATE reservations SET status = :s WHERE id = :id AND status = 'enquiry'");
  $stmt->execute([':s' => $newStatus, ':id' => $id]);
  header('Location: bookings.php?ok=1');
  exit;
}

$rows = $pdo->query("
  SELECT *
  FROM reservations
  WHERE status = 'enquiry'
  ORDER BY datetime(created_at) DESC, id DESC
")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Enquiries</title>
</head>
<body style="font-family:system-ui;max-width:1100px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Enquiries</h2>
    <div><a href="dashboard.php">Dashboard</a> | <a href="properties.php">Properties</a> | <a href="reservations.php">Reservations</a> | <a href="logout.php">Logout</a></div>
  </div>

  <?php if (!empty($_GET['ok'])): ?>
    <div style="background:#efe;border:1px solid #9f9;padding:10px;border-radius:8px;margin:12px 0;">Saved.</div>
  <?php endif; ?>

  <table style="width:100%;border-collapse:collapse">
    <thead>
      <tr style="text-align:left;border-bottom:1px solid #ddd">
        <th>ID</th>
        <th>Property</th>
        <th>Dates</th>
        <th>Guests</th>
        <th>Guest</th>
        <th>Created</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" style="padding:14px;color:#666;">No enquiries.</td></tr>
      <?php endif; ?>

      <?php foreach ($rows as $r): ?>
        <tr style="border-bottom:1px solid #f0f0f0">
          <td style="padding:10px;"><b>#<?= (int)$r['id'] ?></b></td>
          <td style="padding:10px;"><?= h($r['property_slug']) ?></td>
          <td style="padding:10px;">
            <?= h($r['checkin']) ?> → <?= h($r['checkout']) ?>
          </td>
          <td style="padding:10px;"><?= h($r['guests'] ?? '') ?></td>
          <td style="padding:10px;">
            <div><?= h($r['guest_name'] ?? '') ?></div>
            <small style="color:#666"><?= h($r['guest_email'] ?? '') ?></small>
          </td>
          <td style="padding:10px;"><small><?= h($r['created_at']) ?></small></td>
          <td style="padding:10px;white-space:nowrap">
            <a href="booking_edit.php?id=<?= (int)$r['id'] ?>">Open</a>
            &nbsp;|&nbsp;
            <a href="bookings.php?action=confirm&id=<?= (int)$r['id'] ?>" onclick="return confirm('Confirm this enquiry?')">Confirm</a>
            &nbsp;|&nbsp;
            <a href="bookings.php?action=reject&id=<?= (int)$r['id'] ?>" onclick="return confirm('Reject this enquiry?')">Reject</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p style="color:#666;font-size:13px;margin-top:12px;">
    Confirmed enquiries become <b>confirmed reservations</b> and will immediately block dates in <code>availability.php</code>.
  </p>
</body>
</html>
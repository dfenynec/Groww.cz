<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();
require_once __DIR__ . '/../_shared/db.php';

$pdo = db();

$counts = [
  'enquiry' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='enquiry'")->fetchColumn(),
  'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='confirmed'")->fetchColumn(),
  'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status='cancelled'")->fetchColumn(),
];
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body style="font-family:system-ui;max-width:980px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Dashboard</h2>
    <div>
      <?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?> |
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <div style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0">
    <div style="padding:14px;border:1px solid #ddd;border-radius:10px;min-width:200px">
      <div style="color:#666">Enquiries</div>
      <div style="font-size:28px;font-weight:700"><?= $counts['enquiry'] ?></div>
      <a href="bookings.php?status=enquiry">Open</a>
    </div>
    <div style="padding:14px;border:1px solid #ddd;border-radius:10px;min-width:200px">
      <div style="color:#666">Confirmed</div>
      <div style="font-size:28px;font-weight:700"><?= $counts['confirmed'] ?></div>
      <a href="reservations.php?status=confirmed">Open</a>
    </div>
    <div style="padding:14px;border:1px solid #ddd;border-radius:10px;min-width:200px">
      <div style="color:#666">Cancelled</div>
      <div style="font-size:28px;font-weight:700"><?= $counts['cancelled'] ?></div>
      <a href="reservations.php?status=cancelled">Open</a>
    </div>
  </div>

  <hr>
  <p>
    <a href="bookings.php">Booking enquiries</a> •
    <a href="reservations.php">Reservations</a> •
    <a href="properties.php">Properties</a>
  </p>
</body>
</html>
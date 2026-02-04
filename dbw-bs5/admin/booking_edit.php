<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

require_once __DIR__ . '/../_shared/db.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo "Missing id"; exit; }

$err = null;
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $status = (string)($_POST['status'] ?? '');
  $allowed = ['enquiry','confirmed','rejected','cancelled'];
  if (!in_array($status, $allowed, true)) $status = 'enquiry';

  $checkin = trim((string)($_POST['checkin'] ?? ''));
  $checkout = trim((string)($_POST['checkout'] ?? ''));
  $guests = (int)($_POST['guests'] ?? 0);

  $name = trim((string)($_POST['guest_name'] ?? ''));
  $email = trim((string)($_POST['guest_email'] ?? ''));
  $phone = trim((string)($_POST['guest_phone'] ?? ''));
  $message = trim((string)($_POST['message'] ?? ''));

  // základní validace dat (použijeme helpery ze shared db.php)
  if (!is_valid_iso_date($checkin) || !is_valid_iso_date($checkout)) {
    $err = "Invalid dates (YYYY-MM-DD).";
  } else if (nights_between($checkin, $checkout) <= 0) {
    $err = "Checkout must be after checkin.";
  } else if ($guests <= 0 || $guests > 50) {
    $err = "Invalid guests.";
  } else if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Invalid email.";
  } else {
    // pokud potvrzuješ, zkontroluj konflikt s CONFIRMED
    $row = $pdo->prepare("SELECT property_slug FROM reservations WHERE id=:id");
    $row->execute([':id'=>$id]);
    $prop = (string)($row->fetchColumn() ?: '');

    if ($status === 'confirmed' && $prop !== '' && db_has_conflict($prop, $checkin, $checkout)) {
      $err = "Dates conflict with another CONFIRMED reservation.";
    } else {
      $stmt = $pdo->prepare("
        UPDATE reservations
        SET checkin=:cin, checkout=:cout, guests=:g,
            guest_name=:n, guest_email=:e, guest_phone=:p, message=:m,
            status=:s
        WHERE id=:id
      ");
      $stmt->execute([
        ':cin'=>$checkin, ':cout'=>$checkout, ':g'=>$guests,
        ':n'=>$name, ':e'=>$email, ':p'=>$phone, ':m'=>$message,
        ':s'=>$status,
        ':id'=>$id,
      ]);
      $ok = "Saved.";
    }
  }
}

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
$stmt->execute([':id'=>$id]);
$r = $stmt->fetch();
if (!$r) { echo "Not found"; exit; }

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Booking #<?= (int)$r['id'] ?></title>
</head>
<body style="font-family:system-ui;max-width:1000px;margin:30px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Booking #<?= (int)$r['id'] ?></h2>
    <div><a href="bookings.php">Back</a> | <a href="reservations.php">Reservations</a> | <a href="dashboard.php">Dashboard</a></div>
  </div>

  <?php if ($err): ?>
    <div style="background:#fee;border:1px solid #f99;padding:10px;border-radius:8px;margin:12px 0;"><?= h($err) ?></div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div style="background:#efe;border:1px solid #9f9;padding:10px;border-radius:8px;margin:12px 0;"><?= h($ok) ?></div>
  <?php endif; ?>

  <div style="margin:12px 0;color:#666">
    <div><b>Property:</b> <?= h($r['property_slug']) ?></div>
    <div><b>Source:</b> <?= h($r['source']) ?> &nbsp; <b>Status:</b> <?= h($r['status']) ?></div>
    <div><b>Created:</b> <?= h($r['created_at']) ?></div>
    <div style="margin-top:6px;">
      <a href="../property.html?p=<?= urlencode((string)$r['property_slug']) ?>" target="_blank">Open property page</a>
    </div>
  </div>

  <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div>
      <label>Check-in</label><br>
      <input name="checkin" value="<?= h($r['checkin']) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>
    <div>
      <label>Check-out</label><br>
      <input name="checkout" value="<?= h($r['checkout']) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>

    <div>
      <label>Guests</label><br>
      <input name="guests" type="number" min="1" max="50" value="<?= h($r['guests'] ?? 1) ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>
    <div>
      <label>Status</label><br>
      <select name="status" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
        <?php foreach (['enquiry','confirmed','rejected','cancelled'] as $s): ?>
          <option value="<?= h($s) ?>" <?= ($r['status']===$s?'selected':'') ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>Guest name</label><br>
      <input name="guest_name" value="<?= h($r['guest_name'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>
    <div>
      <label>Guest email</label><br>
      <input name="guest_email" value="<?= h($r['guest_email'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>

    <div>
      <label>Guest phone</label><br>
      <input name="guest_phone" value="<?= h($r['guest_phone'] ?? '') ?>" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:8px">
    </div>
    <div></div>

    <div style="grid-column:1 / -1">
      <label>Message</label><br>
      <textarea name="message" style="width:100%;min-height:120px;padding:8px;border:1px solid #ddd;border-radius:8px"><?= h($r['message'] ?? '') ?></textarea>
    </div>

    <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
      <button style="padding:10px 14px">Save</button>
      <a href="bookings.php?action=confirm&id=<?= (int)$r['id'] ?>" style="padding:10px 14px;border:1px solid #ddd;border-radius:10px;text-decoration:none" onclick="return confirm('Confirm this booking?')">Confirm</a>
      <a href="bookings.php?action=reject&id=<?= (int)$r['id'] ?>" style="padding:10px 14px;border:1px solid #ddd;border-radius:10px;text-decoration:none" onclick="return confirm('Reject this booking?')">Reject</a>
    </div>
  </form>

  <p style="color:#666;font-size:13px;margin-top:12px">
    Confirmed bookings block dates in availability immediately.
  </p>
</body>
</html>
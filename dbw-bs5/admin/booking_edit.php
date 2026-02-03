<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM bookings WHERE id=?");
$stmt->execute([$id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$b) { http_response_code(404); echo "Not found"; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['delete'])) {
    $stmt = db()->prepare("DELETE FROM bookings WHERE id=?");
    $stmt->execute([$id]);
    header("Location: bookings.php");
    exit;
  }

  $status = $_POST['status'] ?? $b['status'];
  $checkin = $_POST['checkin'] ?? $b['checkin'];
  $checkout = $_POST['checkout'] ?? $b['checkout'];
  $guests = $_POST['guests'] !== "" ? (int)$_POST['guests'] : null;

  $stmt = db()->prepare("UPDATE bookings SET status=?, checkin=?, checkout=?, guests=?, updated_at=datetime('now') WHERE id=?");
  $stmt->execute([$status, $checkin, $checkout, $guests, $id]);

  header("Location: booking_edit.php?id=" . $id);
  exit;
}

include __DIR__ . "/_layout_top.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="m-0">Booking #<?=$b['id']?></h3>
    <div class="text-muted small">Property: <code><?=$b['property_slug']?></code></div>
  </div>
  <a class="btn btn-outline-dark" href="bookings.php">Back</a>
</div>

<div class="card p-3">
  <form method="post" class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach(["ENQUIRY","HOLD","CONFIRMED","CANCELLED","BLOCKED"] as $s): ?>
          <option value="<?=$s?>" <?=$b['status']===$s?'selected':''?>><?=$s?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Check-in</label>
      <input class="form-control" name="checkin" value="<?=htmlspecialchars($b['checkin'])?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Check-out</label>
      <input class="form-control" name="checkout" value="<?=htmlspecialchars($b['checkout'])?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Guests</label>
      <input class="form-control" name="guests" value="<?=htmlspecialchars((string)($b['guests'] ?? ""))?>">
    </div>

    <div class="col-12">
      <label class="form-label">Message</label>
      <textarea class="form-control" rows="4" readonly><?=htmlspecialchars($b['message'] ?? "")?></textarea>
    </div>

    <div class="col-12 d-flex justify-content-between">
      <button class="btn btn-dark">Save</button>
      <button class="btn btn-outline-danger" name="delete" value="1" onclick="return confirm('Delete booking?')">Delete</button>
    </div>
  </form>
</div>
<?php include __DIR__ . "/_layout_bottom.php"; ?>
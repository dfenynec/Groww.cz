<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

$props = (int)db()->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$enq   = (int)db()->query("SELECT COUNT(*) FROM bookings WHERE status='ENQUIRY'")->fetchColumn();
$conf  = (int)db()->query("SELECT COUNT(*) FROM bookings WHERE status='CONFIRMED'")->fetchColumn();

include __DIR__ . "/_layout_top.php";
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">Properties</div>
      <div class="fs-2 fw-bold"><?=$props?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">New enquiries</div>
      <div class="fs-2 fw-bold"><?=$enq?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <div class="text-muted">Confirmed</div>
      <div class="fs-2 fw-bold"><?=$conf?></div>
    </div>
  </div>
</div>
<?php include __DIR__ . "/_layout_bottom.php"; ?>
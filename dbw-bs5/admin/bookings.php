<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

$q = trim($_GET['q'] ?? "");
$params = [];
$sql = "SELECT id,property_slug,checkin,checkout,guests,name,email,status,created_at FROM bookings";

if ($q) {
  $sql .= " WHERE property_slug LIKE :q OR email LIKE :q OR name LIKE :q OR status LIKE :q";
  $params[':q'] = "%$q%";
}
$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/_layout_top.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="m-0">Bookings</h3>
  <form class="d-flex gap-2" method="get">
    <input class="form-control" name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search...">
    <button class="btn btn-outline-dark">Search</button>
  </form>
</div>

<div class="card p-3">
  <table class="table align-middle mb-0">
    <thead><tr>
      <th>ID</th><th>Property</th><th>Dates</th><th>Guest</th><th>Status</th><th>Created</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><code>#<?=$r['id']?></code></td>
        <td><code><?=$r['property_slug']?></code></td>
        <td><?=$r['checkin']?> → <?=$r['checkout']?></td>
        <td>
          <div class="fw-semibold"><?=htmlspecialchars($r['name'] ?? "—")?></div>
          <div class="text-muted small"><?=htmlspecialchars($r['email'] ?? "")?></div>
        </td>
        <td><span class="badge bg-dark"><?=$r['status']?></span></td>
        <td class="text-muted small"><?=$r['created_at']?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-dark" href="booking_edit.php?id=<?=$r['id']?>">Open</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . "/_layout_bottom.php"; ?>
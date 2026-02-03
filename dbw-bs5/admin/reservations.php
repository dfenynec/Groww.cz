<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_admin();

require_once __DIR__ . '/../api/_db.php';

$pdo = db();

$slug = clean_slug((string)($_GET['property'] ?? ''));
$filter = (string)($_GET['filter'] ?? 'all'); // all|enquiry|confirmed|cancelled|rejected
$limit = 200;

$where = [];
$params = [];

if ($slug !== '') {
  $where[] = "property_slug = :slug";
  $params[':slug'] = $slug;
}
if (in_array($filter, ['enquiry','confirmed','cancelled','rejected'], true)) {
  $where[] = "status = :status";
  $params[':status'] = $filter;
}

$sql = "SELECT * FROM reservations";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$csrf = csrf_token();

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES); }

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reservations Admin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:system-ui;margin:0;background:#f7f7fb;color:#111827}
    .wrap{max-width:1100px;margin:28px auto;padding:0 16px}
    .card{background:#fff;border-radius:14px;padding:16px;box-shadow:0 6px 24px rgba(0,0,0,.06)}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px 8px;border-bottom:1px solid rgba(0,0,0,.08);vertical-align:top}
    th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
    .pill{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;background:#eef2ff}
    .pill.confirmed{background:#ecfdf5}
    .pill.enquiry{background:#eff6ff}
    .pill.cancelled{background:#fef3c7}
    .pill.rejected{background:#fee2e2}
    .actions form{display:inline}
    button{border:0;border-radius:10px;padding:8px 10px;margin-right:6px;cursor:pointer}
    .btn-ok{background:#111827;color:#fff}
    .btn-warn{background:#f59e0b;color:#111827}
    .btn-bad{background:#ef4444;color:#fff}
    .btn-ghost{background:#e5e7eb;color:#111827}
    .top{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:12px}
    .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    input,select{padding:10px;border-radius:10px;border:1px solid rgba(0,0,0,.14)}
    .hint{color:#6b7280;font-size:13px}
  </style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <h2 style="margin:0 0 4px">Reservations</h2>
      <div class="hint">Approve enquiries → confirmed reservations → availability.php začne blokovat</div>
    </div>

    <form class="filters" method="get">
      <input name="property" placeholder="property slug (optional)" value="<?=h($slug)?>">
      <select name="filter">
        <option value="all" <?= $filter==='all'?'selected':'' ?>>All</option>
        <option value="enquiry" <?= $filter==='enquiry'?'selected':'' ?>>Enquiry</option>
        <option value="confirmed" <?= $filter==='confirmed'?'selected':'' ?>>Confirmed</option>
        <option value="cancelled" <?= $filter==='cancelled'?'selected':'' ?>>Cancelled</option>
        <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
      </select>
      <button class="btn-ghost" type="submit">Filter</button>
    </form>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Property</th>
          <th>Dates</th>
          <th>Nights</th>
          <th>Guests</th>
          <th>Guest</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): 
        $n = nights_between($r['checkin'], $r['checkout']);
        $status = (string)$r['status'];
      ?>
        <tr>
          <td><?=h($r['id'])?></td>
          <td><?=h($r['property_slug'])?></td>
          <td>
            <div><strong><?=h($r['checkin'])?></strong> → <strong><?=h($r['checkout'])?></strong></div>
            <?php if (!empty($r['message'])): ?>
              <div class="hint" style="margin-top:6px"><?=h($r['message'])?></div>
            <?php endif; ?>
          </td>
          <td><?=h($n)?></td>
          <td><?=h($r['guests'] ?? '')?></td>
          <td>
            <div><?=h($r['guest_name'] ?? '')?></div>
            <div class="hint"><?=h($r['guest_email'] ?? '')?></div>
            <div class="hint"><?=h($r['guest_phone'] ?? '')?></div>
          </td>
          <td><span class="pill <?=h($status)?>"><?=h($status)?></span></td>
          <td class="hint"><?=h($r['created_at'])?></td>
          <td class="actions">
            <?php if ($status === 'enquiry'): ?>
              <form method="post" action="../api/reservation-update.php">
                <input type="hidden" name="csrf" value="<?=h($csrf)?>">
                <input type="hidden" name="id" value="<?=h($r['id'])?>">
                <input type="hidden" name="action" value="approve">
                <button class="btn-ok" type="submit">Approve</button>
              </form>
              <form method="post" action="../api/reservation-update.php">
                <input type="hidden" name="csrf" value="<?=h($csrf)?>">
                <input type="hidden" name="id" value="<?=h($r['id'])?>">
                <input type="hidden" name="action" value="reject">
                <button class="btn-bad" type="submit">Reject</button>
              </form>
            <?php elseif ($status === 'confirmed'): ?>
              <form method="post" action="../api/reservation-update.php">
                <input type="hidden" name="csrf" value="<?=h($csrf)?>">
                <input type="hidden" name="id" value="<?=h($r['id'])?>">
                <input type="hidden" name="action" value="cancel">
                <button class="btn-warn" type="submit">Cancel</button>
              </form>
            <?php endif; ?>

            <form method="post" action="../api/reservation-update.php" onsubmit="return confirm('Delete reservation #<?=h($r['id'])?>?');">
              <input type="hidden" name="csrf" value="<?=h($csrf)?>">
              <input type="hidden" name="id" value="<?=h($r['id'])?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn-ghost" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$rows): ?>
        <tr><td colspan="9" class="hint">No reservations found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="hint" style="margin-top:10px">
    Tip: Ověř si blokování tak, že vytvoříš enquiry → Approve → otevřeš property page a zkontroluješ, že se to promítlo přes availability.php.
  </div>
</div>
</body>
</html>
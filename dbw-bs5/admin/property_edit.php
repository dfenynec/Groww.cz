<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

$slug = $_GET['slug'] ?? "";
$stmt = db()->prepare("SELECT * FROM properties WHERE slug=?");
$stmt->execute([$slug]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prop) { http_response_code(404); echo "Not found"; exit; }

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? "");
  $json  = $_POST['json'] ?? "";

  $decoded = json_decode($json, true);
  if (!$decoded || !is_array($decoded)) {
    $error = "Invalid JSON.";
  } else {
    // vynutíme slug v JSON
    $decoded['slug'] = $slug;
    $decoded['title'] = $title ?: ($decoded['title'] ?? $slug);

    $stmt = db()->prepare("UPDATE properties SET title=?, json=?, updated_at=datetime('now') WHERE slug=?");
    $stmt->execute([$decoded['title'], json_encode($decoded, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), $slug]);
    header("Location: property_edit.php?slug=" . urlencode($slug));
    exit;
  }
}

include __DIR__ . "/_layout_top.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="m-0"><?=$prop['title']?></h3>
    <div class="text-muted small">Slug: <code><?=$slug?></code> • Link: <code>/property.html?p=<?=$slug?></code></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-dark" href="properties.php">Back</a>
    <a class="btn btn-dark" href="export_property.php?slug=<?=$slug?>">Publish JSON</a>
  </div>
</div>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

<div class="card p-3">
  <form method="post">
    <label class="form-label">Title</label>
    <input class="form-control mb-3" name="title" value="<?=htmlspecialchars($prop['title'])?>">
    <label class="form-label">Property JSON</label>
    <textarea class="form-control" name="json" rows="22" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px;"><?=htmlspecialchars($prop['json'])?></textarea>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">Uložení jen do DB. “Publish” vytvoří soubor do <code>/data/properties/</code>.</div>
      <button class="btn btn-dark">Save</button>
    </div>
  </form>
</div>
<?php include __DIR__ . "/_layout_bottom.php"; ?>
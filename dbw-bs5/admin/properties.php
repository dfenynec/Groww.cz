<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $slug  = trim($_POST['slug'] ?? "");
  $title = trim($_POST['title'] ?? "");

  if ($slug && $title) {
    $baseJson = [
      "slug" => $slug,
      "title" => $title,
      "seo" => ["title" => $title, "description" => ""],
      "location" => ["addressLine" => "", "mapsEmbedUrl" => ""],
      "heroImage" => "",
      "gallery" => [],
      "chips" => [],
      "description" => "",
      "quickFacts" => [],
      "amenitiesTop" => [],
      "amenitiesColumns" => [[],[],[]],
      "houseRules" => [],
      "faq" => [],
      "reviews" => ["rating" => 0, "count" => 0, "items" => []],
      "pricing" => [
        "currency" => "EUR",
        "baseNight" => 100,
        "weekendNight" => null,
        "weekendDays" => [5,6],
        "cleaningFee" => 40,
        "minNightsDefault" => 3,
        "rules" => []
      ],
      "booking" => ["maxGuests" => 4]
    ];

    $stmt = db()->prepare("INSERT INTO properties(slug,title,json) VALUES(?,?,?)");
    $stmt->execute([$slug, $title, json_encode($baseJson, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)]);
  }
  header("Location: properties.php");
  exit;
}

$rows = db()->query("SELECT slug,title,updated_at,published_json_path FROM properties ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/_layout_top.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="m-0">Properties</h3>
</div>

<div class="card p-3 mb-3">
  <form method="post" class="row g-2">
    <div class="col-md-4">
      <input class="form-control" name="slug" placeholder="slug (e.g. nissi-golden-sands-a15)" required>
    </div>
    <div class="col-md-6">
      <input class="form-control" name="title" placeholder="Title" required>
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-dark">Create</button>
    </div>
  </form>
</div>

<div class="card p-3">
  <table class="table align-middle mb-0">
    <thead><tr>
      <th>Slug</th><th>Title</th><th>Updated</th><th>Published JSON</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><code><?=$r['slug']?></code></td>
        <td><?=$r['title']?></td>
        <td class="text-muted small"><?=$r['updated_at']?></td>
        <td class="text-muted small"><?= $r['published_json_path'] ? basename($r['published_json_path']) : "—" ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-dark" href="property_edit.php?slug=<?=$r['slug']?>">Edit</a>
          <a class="btn btn-sm btn-dark" href="export_property.php?slug=<?=$r['slug']?>">Publish</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . "/_layout_bottom.php"; ?>
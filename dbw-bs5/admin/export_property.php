<?php
require_once __DIR__ . "/_db.php";
require_once __DIR__ . "/_auth.php";
require_login();

$slug = $_GET['slug'] ?? "";
$stmt = db()->prepare("SELECT slug,json FROM properties WHERE slug=?");
$stmt->execute([$slug]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { http_response_code(404); echo "Not found"; exit; }

$dir = __DIR__ . "/../data/properties";
if (!is_dir($dir)) mkdir($dir, 0775, true);

$path = $dir . "/" . $slug . ".json";
file_put_contents($path, $p['json']);

$stmt = db()->prepare("UPDATE properties SET published_json_path=?, updated_at=datetime('now') WHERE slug=?");
$stmt->execute([$path, $slug]);

header("Location: property_edit.php?slug=" . urlencode($slug));
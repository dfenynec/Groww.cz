<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_login();

$base = realpath(__DIR__ . '/..');
if ($base === false) { http_response_code(500); echo "Base path not found"; exit; }

$slug = preg_replace('~[^a-z0-9\-]~', '', strtolower((string)($_GET['slug'] ?? '')));
if ($slug === '') { echo "Missing slug"; exit; }

$path = $base . '/data/properties/' . $slug . '.json';
if (!file_exists($path)) { echo "Not found: " . htmlspecialchars($path); exit; }

function read_json_file(string $path): array {
  $raw = file_get_contents($path);
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

/** dot-path getter: "pricing.baseNight" */
function getv(array $a, string $path, $default = '') {
  $keys = explode('.', $path);
  $cur = $a;
  foreach ($keys as $k) {
    if (!is_array($cur) || !array_key_exists($k, $cur)) return $default;
    $cur = $cur[$k];
  }
  return $cur;
}

/** dot-path setter: creates nested arrays */
function setv(array &$a, string $path, $value): void {
  $keys = explode('.', $path);
  $cur =& $a;
  foreach ($keys as $i => $k) {
    if ($i === count($keys) - 1) {
      $cur[$k] = $value;
      return;
    }
    if (!isset($cur[$k]) || !is_array($cur[$k])) $cur[$k] = [];
    $cur =& $cur[$k];
  }
}

function to_int_or_null($v): ?int {
  $v = trim((string)$v);
  if ($v === '') return null;
  if (!preg_match('~^-?\d+$~', $v)) return null;
  return (int)$v;
}

function to_float_or_null($v): ?float {
  $v = trim((string)$v);
  if ($v === '') return null;
  $v = str_replace(',', '.', $v);
  if (!is_numeric($v)) return null;
  return (float)$v;
}

function normalize_url(string $u): string {
  return trim($u);
}

/* ---------------- Upload helpers ---------------- */

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
}

function safe_ext(string $name): string {
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $allowed = ['jpg','jpeg','png','webp'];
  return in_array($ext, $allowed, true) ? $ext : '';
}

function make_safe_filename(string $original): string {
  $base = strtolower(pathinfo($original, PATHINFO_FILENAME));
  $base = preg_replace('~[^a-z0-9\-]+~', '-', $base);
  $base = trim($base, '-');
  return $base !== '' ? $base : 'img';
}

/**
 * Save uploaded images into /images/properties/{slug}/
 * Returns relative paths for JSON gallery.
 */
function handle_gallery_uploads(string $basePath, string $slug, array &$warnings): array {
  if (empty($_FILES['gallery_upload']) || !is_array($_FILES['gallery_upload']['name'] ?? null)) return [];

  $names = $_FILES['gallery_upload']['name'];
  $tmp   = $_FILES['gallery_upload']['tmp_name'];
  $errs  = $_FILES['gallery_upload']['error'];
  $sizes = $_FILES['gallery_upload']['size'];

  $destDirAbs = $basePath . '/images/properties/' . $slug;
  ensure_dir($destDirAbs);

  $saved = [];

  $count = count($names);
  for ($i = 0; $i < $count; $i++) {
    $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) { $warnings[] = "Upload error: " . (string)$names[$i]; continue; }

    $ext = safe_ext((string)$names[$i]);
    if ($ext === '') { $warnings[] = "Skipped (unsupported type): " . (string)$names[$i]; continue; }

    $size = (int)($sizes[$i] ?? 0);
    if ($size > 12 * 1024 * 1024) { $warnings[] = "Skipped (>12MB): " . (string)$names[$i]; continue; }

    $tmpPath = (string)($tmp[$i] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) { $warnings[] = "Skipped (not uploaded): " . (string)$names[$i]; continue; }

    $safe = make_safe_filename((string)$names[$i]);
    $filename = gmdate('Ymd-His') . '-' . $safe . '.' . $ext;

    $destAbs = $destDirAbs . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destAbs)) {
      $warnings[] = "Failed to save: " . (string)$names[$i];
      continue;
    }

    $saved[] = 'images/properties/' . $slug . '/' . $filename;
  }

  return $saved;
}

/** Normalize gallery list: trim, remove empty, unique (keep order) */
function normalize_gallery(array $gallery): array {
  $out = [];
  $seen = [];
  foreach ($gallery as $g) {
    $g = trim((string)$g);
    if ($g === '') continue;
    if (isset($seen[$g])) continue;
    $seen[$g] = true;
    $out[] = $g;
  }
  return $out;
}

$err = null;
$ok  = null;
$warnings = [];

$current = read_json_file($path);
if (!isset($current['slug']) || !$current['slug']) $current['slug'] = $slug;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mode = (string)($_POST['mode'] ?? 'form');
  $new = $current;

  if ($mode === 'json') {
    $raw = (string)($_POST['json'] ?? '');
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      $err = "Invalid JSON: " . json_last_error_msg();
    } else {
      $decoded['slug'] = $slug;
      $new = $decoded;
    }
  } else {
    // ---- FORM -> JSON ----
    $new['slug'] = $slug;

    // basics
    setv($new, 'title', trim((string)($_POST['title'] ?? '')));
    setv($new, 'description', trim((string)($_POST['description'] ?? '')));
    setv($new, 'seo.title', trim((string)($_POST['seo_title'] ?? '')));
    setv($new, 'seo.description', trim((string)($_POST['seo_description'] ?? '')));

    // location
    setv($new, 'location.addressLine', trim((string)($_POST['addressLine'] ?? '')));
    setv($new, 'location.mapsEmbedUrl', normalize_url((string)($_POST['mapsEmbedUrl'] ?? '')));

    // external links
    setv($new, 'externalLinks.airbnb', normalize_url((string)($_POST['airbnbUrl'] ?? '')));
    setv($new, 'externalLinks.booking', normalize_url((string)($_POST['bookingUrl'] ?? '')));

    // iCal links (availability)
setv($new, 'ical.airbnb', normalize_url((string)($_POST['icalAirbnb'] ?? '')));
setv($new, 'ical.booking', normalize_url((string)($_POST['icalBooking'] ?? '')));

    // hero + gallery (hero můžeš klidně ignorovat v UI)
    setv($new, 'heroImage', trim((string)($_POST['heroImage'] ?? '')));

    // gallery remove (from UI)
    $remove = (string)($_POST['remove_gallery'] ?? '');

    $galleryRaw = trim((string)($_POST['gallery'] ?? ''));
    $gallery = array_values(array_filter(array_map('trim', preg_split("~\r\n|\n|\r~", $galleryRaw))));

    // apply remove
    if ($remove !== '') {
      $gallery = array_values(array_filter($gallery, fn($x) => trim((string)$x) !== $remove));
      $warnings[] = "Removed from gallery: " . $remove;
    }

    // uploads -> append
    $uploaded = handle_gallery_uploads($base, $slug, $warnings);
    foreach ($uploaded as $u) $gallery[] = $u;

    $gallery = normalize_gallery($gallery);
    setv($new, 'gallery', $gallery);

    // pricing
    $currency = trim((string)($_POST['currency'] ?? 'EUR'));
    setv($new, 'pricing.currency', $currency ?: 'EUR');

    $baseNight = to_float_or_null($_POST['baseNight'] ?? '');
    if ($baseNight !== null) setv($new, 'pricing.baseNight', $baseNight);

    $weekendNight = to_float_or_null($_POST['weekendNight'] ?? '');
    if ($weekendNight !== null) setv($new, 'pricing.weekendNight', $weekendNight);

    $cleaningFee = to_float_or_null($_POST['cleaningFee'] ?? '');
    if ($cleaningFee !== null) setv($new, 'pricing.cleaningFee', $cleaningFee);

    $minNightsDefault = to_int_or_null($_POST['minNightsDefault'] ?? '');
    if ($minNightsDefault !== null) setv($new, 'pricing.minNightsDefault', max(1, $minNightsDefault));

    // booking
    $maxGuests = to_int_or_null($_POST['maxGuests'] ?? '');
    if ($maxGuests !== null) setv($new, 'booking.maxGuests', max(1, $maxGuests));
  }

  if (!$err) {
    // backup
    $backupDir = $base . '/storage/property-backups';
    if (!is_dir($backupDir)) @mkdir($backupDir, 0775, true);

    $stamp = gmdate('Ymd-His');
    $backupPath = $backupDir . '/' . $slug . '-' . $stamp . '.json';
    @copy($path, $backupPath);

    $pretty = json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($pretty === false) {
      $err = "Failed to encode JSON.";
    } else {
      $res = file_put_contents($path, $pretty);
      if ($res === false) $err = "Failed to write file.";
      else {
        $ok = "Saved. Backup: " . basename($backupPath);
        if (!empty($warnings)) $ok .= " | " . implode(" | ", $warnings);
        $current = $new;
      }
    }
  }
}

$rawCurrent = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

$galleryArr = getv($current, 'gallery', []);
if (!is_array($galleryArr)) $galleryArr = [];
$galleryArr = normalize_gallery($galleryArr);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit property <?= htmlspecialchars($slug) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui;max-width:1100px;margin:30px auto;padding:0 16px}
    a{color:#0b5ed7;text-decoration:none}
    a:hover{text-decoration:underline}
    .topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .card{border:1px solid #e7e7e7;border-radius:12px;padding:16px;background:#fff}
    .msg-ok{background:#efe;border:1px solid #9f9;padding:10px;border-radius:10px;margin:12px 0}
    .msg-err{background:#fee;border:1px solid #f99;padding:10px;border-radius:10px;margin:12px 0}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;vertical-align:top}
    th{width:260px;text-align:left;color:#333}
    input, textarea, select{width:100%;box-sizing:border-box;padding:10px;border:1px solid #ddd;border-radius:10px;font:inherit}
    textarea{min-height:110px}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:820px){.row2{grid-template-columns:1fr}}
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .btn{padding:10px 14px;border-radius:10px;border:1px solid #ddd;background:#fff;cursor:pointer;display:inline-block}
    .btn-primary{border-color:#0b5ed7;background:#0b5ed7;color:#fff}
    details{margin-top:14px}
    code{background:#f6f6f6;padding:2px 6px;border-radius:8px}
    .gallery-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
    @media(max-width:980px){.gallery-grid{grid-template-columns:repeat(4,1fr)}}
    @media(max-width:640px){.gallery-grid{grid-template-columns:repeat(2,1fr)}}
    .g-item{border:1px solid #eee;border-radius:12px;overflow:hidden;background:#fafafa}
    .g-thumb{display:block;width:100%;height:90px;object-fit:cover;background:#eee}
    .g-meta{padding:8px;font-size:12px;color:#555;word-break:break-all}
    .g-actions{padding:8px;display:flex;gap:8px;flex-wrap:wrap}
    .btn-danger{border-color:#dc3545;background:#dc3545;color:#fff}
    .muted{font-size:13px;color:#666;margin-top:6px}
  </style>
</head>
<body>

  <div class="topbar">
    <h2 style="margin:0">Edit property: <?= htmlspecialchars($slug) ?></h2>
    <div>
      <a href="properties.php">← Properties</a>
      &nbsp;|&nbsp;
      <a href="dashboard.php">Dashboard</a>
      &nbsp;|&nbsp;
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <?php if ($err): ?><div class="msg-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="msg-ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <div class="card">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="mode" value="form">

      <table>
        <tr>
          <th>Title</th>
          <td><input name="title" value="<?= htmlspecialchars((string)getv($current,'title','')) ?>"></td>
        </tr>

        <tr>
          <th>Description</th>
          <td><textarea name="description"><?= htmlspecialchars((string)getv($current,'description','')) ?></textarea></td>
        </tr>
        <tr>
        <th>iCal links (availability sync)</th>
        <td class="row2">
          <div>
            <div style="font-size:13px;color:#555;margin-bottom:6px">Airbnb iCal URL</div>
            <input name="icalAirbnb" value="<?= htmlspecialchars((string)getv($current,'ical.airbnb','')) ?>">
          </div>
          <div>
            <div style="font-size:13px;color:#555;margin-bottom:6px">Booking iCal URL</div>
            <input name="icalBooking" value="<?= htmlspecialchars((string)getv($current,'ical.booking','')) ?>">
          </div>
        </td>
        </tr>
        <tr>
          <th>SEO title</th>
          <td><input name="seo_title" value="<?= htmlspecialchars((string)getv($current,'seo.title','')) ?>"></td>
        </tr>

        <tr>
          <th>SEO description</th>
          <td><textarea name="seo_description"><?= htmlspecialchars((string)getv($current,'seo.description','')) ?></textarea></td>
        </tr>

        <tr>
          <th>Address line</th>
          <td><input name="addressLine" value="<?= htmlspecialchars((string)getv($current,'location.addressLine','')) ?>"></td>
        </tr>

        <tr>
          <th>Google Maps embed URL</th>
          <td><input name="mapsEmbedUrl" value="<?= htmlspecialchars((string)getv($current,'location.mapsEmbedUrl','')) ?>"></td>
        </tr>

        <tr>
          <th>Trust links</th>
          <td class="row2">
            <div>
              <div style="font-size:13px;color:#555;margin-bottom:6px">Airbnb URL</div>
              <input name="airbnbUrl" value="<?= htmlspecialchars((string)getv($current,'externalLinks.airbnb','')) ?>">
            </div>
            <div>
              <div style="font-size:13px;color:#555;margin-bottom:6px">Booking.com URL</div>
              <input name="bookingUrl" value="<?= htmlspecialchars((string)getv($current,'externalLinks.booking','')) ?>">
            </div>
          </td>
        </tr>

        <tr>
          <th>Hero image</th>
          <td>
            <input name="heroImage" value="<?= htmlspecialchars((string)getv($current,'heroImage','')) ?>">
            <div class="muted">Hero můžeš klidně nechat prázdný (UI používá galerii).</div>
          </td>
        </tr>

        <tr>
          <th>Upload gallery images</th>
          <td>
            <input type="file" name="gallery_upload[]" multiple accept="image/jpeg,image/png,image/webp">
            <div class="muted">
              Obrázky se uloží do <code>/images/properties/<?= htmlspecialchars($slug) ?>/</code> a automaticky se přidají na konec galerie.
            </div>
          </td>
        </tr>

        <tr>
          <th>Gallery (1 URL per line)</th>
          <td>
            <textarea name="gallery"><?=
              htmlspecialchars(implode("\n", $galleryArr))
            ?></textarea>
            <div class="muted">Můžeš sem dávat i absolutní URL, nebo relativní <code>images/...</code>.</div>
          </td>
        </tr>

        <tr>
          <th>Current gallery</th>
          <td>
            <?php if (!$galleryArr): ?>
              <div class="muted">No images in gallery yet.</div>
            <?php else: ?>
              <div class="gallery-grid">
                <?php foreach ($galleryArr as $g): 
                  $src = $g;
                  // pokud je to relativní, uděláme z toho URL vůči /dbw-bs5/
                  if (!preg_match('~^https?://~i', $src)) {
                    $src = '../' . ltrim($src, '/');
                  }
                ?>
                  <div class="g-item">
                    <img class="g-thumb" src="<?= htmlspecialchars($src) ?>" alt="">
                    <div class="g-meta"><?= htmlspecialchars($g) ?></div>
                    <div class="g-actions">
                      <button class="btn btn-danger" type="submit" name="remove_gallery" value="<?= htmlspecialchars($g) ?>"
                        onclick="return confirm('Remove this image from gallery JSON? (File stays on server)')">
                        Remove
                      </button>
                      <a class="btn" href="<?= htmlspecialchars($src) ?>" target="_blank">Open</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>

        <tr>
          <th>Pricing</th>
          <td>
            <div class="row2">
              <div>
                <div style="font-size:13px;color:#555;margin-bottom:6px">Currency</div>
                <input name="currency" value="<?= htmlspecialchars((string)getv($current,'pricing.currency','EUR')) ?>">
              </div>
              <div>
                <div style="font-size:13px;color:#555;margin-bottom:6px">Min nights default</div>
                <input name="minNightsDefault" value="<?= htmlspecialchars((string)getv($current,'pricing.minNightsDefault','')) ?>" inputmode="numeric">
              </div>
            </div>

            <div class="row2" style="margin-top:12px">
              <div>
                <div style="font-size:13px;color:#555;margin-bottom:6px">Base night</div>
                <input name="baseNight" value="<?= htmlspecialchars((string)getv($current,'pricing.baseNight','')) ?>">
              </div>
              <div>
                <div style="font-size:13px;color:#555;margin-bottom:6px">Weekend night</div>
                <input name="weekendNight" value="<?= htmlspecialchars((string)getv($current,'pricing.weekendNight','')) ?>">
              </div>
            </div>

            <div style="margin-top:12px">
              <div style="font-size:13px;color:#555;margin-bottom:6px">Cleaning fee</div>
              <input name="cleaningFee" value="<?= htmlspecialchars((string)getv($current,'pricing.cleaningFee','')) ?>">
            </div>
          </td>
        </tr>

        <tr>
          <th>Booking</th>
          <td class="row2">
            <div>
              <div style="font-size:13px;color:#555;margin-bottom:6px">Max guests</div>
              <input name="maxGuests" value="<?= htmlspecialchars((string)getv($current,'booking.maxGuests','')) ?>" inputmode="numeric">
            </div>
            <div style="display:flex;align-items:flex-end;gap:10px;flex-wrap:wrap">
              <a class="btn" href="../property.html?p=<?= urlencode($slug) ?>" target="_blank">Open property page</a>
            </div>
          </td>
        </tr>
      </table>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn" href="properties.php">Cancel</a>
      </div>

      <p style="color:#666;font-size:13px;margin-top:10px">
        Uložením se vytvoří backup do <code>/storage/property-backups/</code>.
      </p>
    </form>
  </div>

  <details>
    <summary style="cursor:pointer;margin:14px 0">Advanced: edit raw JSON</summary>
    <div class="card">
      <form method="post">
        <input type="hidden" name="mode" value="json">
        <textarea name="json" style="width:100%;min-height:60vh;font-family:ui-monospace, SFMono-Regular, Menlo, monospace;font-size:13px;padding:12px;border-radius:10px;border:1px solid #ddd;"><?= htmlspecialchars($rawCurrent) ?></textarea>
        <div class="actions">
          <button class="btn btn-primary" type="submit">Save JSON</button>
        </div>
      </form>
    </div>
  </details>

</body>
</html>
<?php
session_start();

// 1) super jednoduché přihlášení (lepší je .htaccess Basic Auth)
$ADMIN_PASS = 'CHANGE_ME';
if (isset($_POST['pass'])) {
  if ($_POST['pass'] === $ADMIN_PASS) $_SESSION['ok']=true;
}
if (empty($_SESSION['ok'])) {
  echo '<form method="post" style="max-width:420px;margin:40px auto;font-family:system-ui">
    <h2>Admin login</h2>
    <input type="password" name="pass" placeholder="Password" style="width:100%;padding:10px">
    <button style="margin-top:10px;padding:10px 14px">Login</button>
  </form>';
  exit;
}

$configPath = __DIR__ . '/../../private/ical-config.json'; // uprav podle sebe
$cfg = [];
if (file_exists($configPath)) {
  $cfg = json_decode(file_get_contents($configPath), true) ?: [];
}

function clean_slug($s){
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '-', $s);
  $s = preg_replace('~-+~', '-', $s);
  return trim($s,'-');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['slug'])) {
  $slug = clean_slug($_POST['slug']);
  $airbnb = trim($_POST['airbnb'] ?? '');
  $booking = trim($_POST['booking'] ?? '');

  if ($slug) {
    $cfg[$slug] = [
      'airbnb' => $airbnb,
      'booking' => $booking
    ];
    file_put_contents($configPath, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    $msg = "Saved: $slug";
  }
}

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>iCal Admin</title></head>
<body style="font-family:system-ui;max-width:900px;margin:40px auto;padding:0 16px">
  <h1>iCal config</h1>
  <?php if (!empty($msg)) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; ?>

  <h3>Add / Update</h3>
  <form method="post" style="display:grid;gap:10px;max-width:760px">
    <input name="slug" placeholder="slug e.g. nissi-golden-sands-a15" required style="padding:10px">
    <input name="airbnb" placeholder="Airbnb iCal URL" style="padding:10px">
    <input name="booking" placeholder="Booking iCal URL" style="padding:10px">
    <button style="padding:10px 14px">Save</button>
  </form>

  <h3 style="margin-top:30px">Current config</h3>
  <pre style="background:#f6f6f6;padding:14px;border-radius:10px;overflow:auto"><?=
    htmlspecialchars(json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES))
  ?></pre>
</body></html>
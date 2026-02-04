<?php
declare(strict_types=1);

require_once __DIR__ . '/../_shared/db.php';
require_once __DIR__ . '/_auth.php';

if (is_logged_in()) { header("Location: dashboard.php"); exit; }

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $error = "Enter email and password.";
  } else {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM admin_users WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, (string)$u['password_hash'])) {
      login_user((int)$u['id'], (string)$u['email']);
      header("Location: dashboard.php");
      exit;
    } else {
      $error = "Invalid login.";
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin login</title></head>
<body style="font-family:system-ui;max-width:420px;margin:40px auto">
  <h2>Admin login</h2>

  <?php if ($error): ?>
    <div style="background:#fee;border:1px solid #f99;padding:10px;border-radius:8px;margin:12px 0;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <div style="margin:10px 0">
      <label>Email</label><br>
      <input name="email" type="email" style="width:100%;padding:10px" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div style="margin:10px 0">
      <label>Password</label><br>
      <input name="password" type="password" style="width:100%;padding:10px">
    </div>
    <button style="padding:10px 14px">Login</button>
    <div style="margin-top:10px;color:#666;font-size:13px">
      Default: admin@example.com / admin12345
    </div>
  </form>
</body>
</html>
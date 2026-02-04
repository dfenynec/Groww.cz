<?php
declare(strict_types=1);

require_once __DIR__ . '/../_shared/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $err = "Please enter email and password.";
  } else {
    try {
      $pdo = db();
      $stmt = $pdo->prepare("SELECT id, email, password_hash FROM admin_users WHERE email = :email LIMIT 1");
      $stmt->execute([':email' => $email]);
      $u = $stmt->fetch();

      if (!$u || !password_verify($pass, (string)$u['password_hash'])) {
        $err = "Invalid login.";
      } else {
        // login OK
        $_SESSION['admin_user_id'] = (int)$u['id'];
        $_SESSION['admin_email'] = (string)$u['email'];

        header("Location: dashboard.php");
        exit;
      }
    } catch (Throwable $e) {
      // fallback message (nechceme to dávat ven)
      $err = "Server error while logging in.";
      // ať to vidíš v error_log
      error_log("[admin login] " . $e->getMessage());
    }
  }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="font-family:system-ui;max-width:520px;margin:60px auto;padding:0 16px">
  <h2>Admin login</h2>

  <?php if ($err): ?>
    <div style="background:#fee;border:1px solid #f99;padding:12px;border-radius:10px;margin:12px 0;">
      <?= htmlspecialchars($err) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:grid;gap:10px">
    <label style="display:grid;gap:6px">
      <span>Email</span>
      <input name="email" type="email" autocomplete="username" value="<?= htmlspecialchars((string)($_POST['email'] ?? '')) ?>"
             style="padding:10px;border:1px solid #ddd;border-radius:10px">
    </label>

    <label style="display:grid;gap:6px">
      <span>Password</span>
      <input name="password" type="password" autocomplete="current-password"
             style="padding:10px;border:1px solid #ddd;border-radius:10px">
    </label>

    <button style="padding:10px 14px;border-radius:10px;border:1px solid #111;background:#111;color:#fff">
      Sign in
    </button>
  </form>

  <p style="color:#666;margin-top:14px;font-size:13px">
    Default login: <code>admin@example.com</code> / <code>admin12345</code>
  </p>
</body>
</html>
<?php
require_once __DIR__ . "/_db.php";
session_start();

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? "");
  $pass  = $_POST['password'] ?? "";

  $stmt = db()->prepare("SELECT id, password_hash FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($u && password_verify($pass, $u['password_hash'])) {
    $_SESSION['uid'] = $u['id'];
    header("Location: dashboard.php");
    exit;
  }
  $error = "Wrong email or password.";
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/bootstrap.min.css"></head>
<body class="bg-light">
<div class="container py-5" style="max-width:420px">
  <div class="card p-4">
    <h3 class="mb-3">Admin login</h3>
    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <form method="post">
      <label class="form-label">Email</label>
      <input class="form-control mb-3" name="email" type="email" required>
      <label class="form-label">Password</label>
      <input class="form-control mb-3" name="password" type="password" required>
      <button class="btn btn-dark w-100">Login</button>
    </form>
    <div class="text-muted small mt-3">
      Default: <code>admin@example.com</code> / <code>admin12345</code> (změň později!)
    </div>
  </div>
</div>
</body></html>
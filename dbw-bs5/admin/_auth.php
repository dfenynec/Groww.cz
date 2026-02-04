<?php
declare(strict_types=1);

require_once __DIR__ . '/../_shared/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function is_logged_in(): bool {
  return !empty($_SESSION['admin_user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header("Location: login.php");
    exit;
  }
}

function login_user(int $id, string $email): void {
  session_regenerate_id(true);
  $_SESSION['admin_user_id'] = $id;
  $_SESSION['admin_email'] = $email;
}

function logout_user(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
  }
  session_destroy();
}
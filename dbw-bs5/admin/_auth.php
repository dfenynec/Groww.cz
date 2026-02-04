<?php
declare(strict_types=1);

require_once __DIR__ . '/../_shared/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function is_logged_in(): bool {
  return !empty($_SESSION['admin_user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function current_admin_email(): string {
  return (string)($_SESSION['admin_email'] ?? '');
}
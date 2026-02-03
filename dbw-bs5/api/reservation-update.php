<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/_db.php';

session_start();

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

// Basic admin gate (z admin/_auth.php ukládáme $_SESSION['is_admin']=true)
if (empty($_SESSION['is_admin'])) {
  json_out(['error' => 'unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(['error' => 'method_not_allowed'], 405);
}

// CSRF
$csrf = (string)($_POST['csrf'] ?? '');
if (!$csrf || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  json_out(['error' => 'csrf'], 403);
}

$id = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');

if ($id <= 0) json_out(['error' => 'missing_id'], 400);
if (!in_array($action, ['approve','reject','cancel','delete'], true)) {
  json_out(['error' => 'invalid_action'], 400);
}

$pdo = db();

// načti záznam
$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) json_out(['error' => 'not_found'], 404);

if ($action === 'delete') {
  $del = $pdo->prepare("DELETE FROM reservations WHERE id = :id");
  $del->execute([':id' => $id]);
  json_out(['ok' => true, 'deleted' => $id]);
}

// map action -> status
$newStatus = match ($action) {
  'approve' => 'confirmed',
  'reject'  => 'rejected',
  'cancel'  => 'cancelled',
};

// approve: zkontroluj konflikty (aby admin omylem nepotvrdil kolizi)
if ($newStatus === 'confirmed') {
  $slug = (string)$row['property_slug'];
  $checkin = (string)$row['checkin'];
  $checkout = (string)$row['checkout'];

  // Pozor: db_has_conflict by našel i sám sebe, proto to uděláme SQL s id != :id
  $q = $pdo->prepare("
    SELECT checkin, checkout
    FROM reservations
    WHERE property_slug = :slug
      AND status = 'confirmed'
      AND id != :id
  ");
  $q->execute([':slug' => $slug, ':id' => $id]);
  foreach ($q->fetchAll() as $r) {
    if (ranges_overlap_exclusive($checkin, $checkout, $r['checkin'], $r['checkout'])) {
      json_out(['error' => 'conflict_with_confirmed'], 409);
    }
  }
}

$upd = $pdo->prepare("UPDATE reservations SET status = :status WHERE id = :id");
$upd->execute([':status' => $newStatus, ':id' => $id]);

json_out(['ok' => true, 'id' => $id, 'status' => $newStatus]);
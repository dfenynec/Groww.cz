<?php
declare(strict_types=1);
require_once __DIR__ . '/paths.php';

function db_path(): string {
  return db_file();
}

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $path = db_path();
  $pdo = new PDO('sqlite:' . $path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // Basic pragmas
  $pdo->exec("PRAGMA journal_mode = WAL;");
  $pdo->exec("PRAGMA foreign_keys = ON;");

  // MIGRACE (idempotent)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS reservations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      property_slug TEXT NOT NULL,
      checkin TEXT NOT NULL,   -- YYYY-MM-DD
      checkout TEXT NOT NULL,  -- YYYY-MM-DD (end-exclusive)
      guests INTEGER,

      guest_name TEXT,
      guest_email TEXT,
      guest_phone TEXT,
      message TEXT,

      source TEXT NOT NULL DEFAULT 'enquiry',   -- enquiry|admin|airbnb|booking
      status TEXT NOT NULL DEFAULT 'enquiry',   -- enquiry|confirmed|rejected|cancelled

      created_at TEXT NOT NULL
    );
  ");

  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resv_property_status ON reservations(property_slug, status);");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_resv_dates ON reservations(checkin, checkout);");

  return $pdo;
}

// --- helpers ---
function clean_slug(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s ?? '';
}

function is_valid_iso_date(string $s): bool {
  $dt = DateTime::createFromFormat('Y-m-d', $s, new DateTimeZone('UTC'));
  return $dt && $dt->format('Y-m-d') === $s;
}

function nights_between(string $checkin, string $checkout): int {
  $a = DateTime::createFromFormat('Y-m-d', $checkin, new DateTimeZone('UTC'));
  $b = DateTime::createFromFormat('Y-m-d', $checkout, new DateTimeZone('UTC'));
  if (!$a || !$b) return 0;
  $diff = $b->getTimestamp() - $a->getTimestamp();
  return (int) round($diff / 86400);
}

/**
 * overlap on end-exclusive ranges: [a_from, a_to) vs [b_from, b_to)
 * overlap exists if: a_from < b_to AND b_from < a_to
 */
function ranges_overlap_exclusive(string $a_from, string $a_to, string $b_from, string $b_to): bool {
  return ($a_from < $b_to) && ($b_from < $a_to);
}

/**
 * Vrátí true, pokud termín koliduje s CONFIRMED rezervací v DB.
 * (iCal kolize řeší availability pro picker – pro enquiry je tohle MVP kontrola)
 */
function db_has_conflict(string $slug, string $checkin, string $checkout): bool {
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT checkin, checkout
    FROM reservations
    WHERE property_slug = :slug
      AND status = 'confirmed'
  ");
  $stmt->execute([':slug' => $slug]);

  foreach ($stmt->fetchAll() as $row) {
    if (ranges_overlap_exclusive($checkin, $checkout, $row['checkin'], $row['checkout'])) {
      return true;
    }
  }
  return false;
}

/**
 * Načte minNightsDefault z /data/properties/{slug}.json
 */
function get_min_nights_for_property(string $slug): int {
  $base = realpath(__DIR__ . '/..');
  if ($base === false) return 1;

  $path = $base . '/data/properties/' . $slug . '.json';
  if (!file_exists($path)) return 1;

  $raw = file_get_contents($path);
  if ($raw === false) return 1;

  $json = json_decode($raw, true);
  $min = (int)($json['pricing']['minNightsDefault'] ?? 1);
  return max(1, $min);
}

$pdo->exec("
  CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL
  );
");
// seed default admin (jen pokud neexistuje)
$exists = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
if ((int)$exists === 0) {
  $email = 'admin@example.com';
  $hash = password_hash('admin12345', PASSWORD_DEFAULT);
  $now = gmdate('c');

  $stmt = $pdo->prepare("INSERT INTO admin_users (email, password_hash, created_at) VALUES (:e,:h,:t)");
  $stmt->execute([':e'=>$email, ':h'=>$hash, ':t'=>$now]);
}
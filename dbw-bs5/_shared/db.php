<?php
declare(strict_types=1);

/**
 * Shared SQLite helpers + idempotent migrations
 * - no $pdo usage outside functions (fixes your fatal error)
 */

function base_path(): string {
  $base = realpath(__DIR__ . '/..'); // /dbw-bs5
  if ($base === false) {
    throw new RuntimeException("Base path not found from " . __DIR__);
  }
  return $base;
}

function db_path(): string {
  return base_path() . '/storage/app.sqlite';
}

function ensure_storage_dir(): void {
  $dir = base_path() . '/storage';
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }
}

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  ensure_storage_dir();

  $path = db_path();

  $pdo = new PDO('sqlite:' . $path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // Pragmas
  $pdo->exec("PRAGMA journal_mode = WAL;");
  $pdo->exec("PRAGMA foreign_keys = ON;");

  // --- MIGRATIONS (idempotent) ---

  // Admin users (for login)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'admin',
      created_at TEXT NOT NULL
    );
  ");
  $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);");

  // Reservations / enquiries
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS reservations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      property_slug TEXT NOT NULL,
      checkin TEXT NOT NULL,    -- YYYY-MM-DD
      checkout TEXT NOT NULL,   -- YYYY-MM-DD (end-exclusive)
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

  // Seed default admin if none exists
  seed_default_admin($pdo);

  return $pdo;
}

function seed_default_admin(PDO $pdo): void {
  $count = (int)($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0);
  if ($count > 0) return;

  $email = 'admin@example.com';
  $pass  = 'admin12345';
  $hash  = password_hash($pass, PASSWORD_DEFAULT);

  $stmt = $pdo->prepare("INSERT INTO users(email, password_hash, role, created_at) VALUES(:e,:h,'admin',:c)");
  $stmt->execute([
    ':e' => $email,
    ':h' => $hash,
    ':c' => gmdate('c'),
  ]);
}

// -------- helpers used across api/admin --------

function clean_slug(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9\-]~', '', $s);
  return $s ?: '';
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
 * True if requested range collides with a CONFIRMED reservation in DB.
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
    if (ranges_overlap_exclusive($checkin, $checkout, (string)$row['checkin'], (string)$row['checkout'])) {
      return true;
    }
  }
  return false;
}

/**
 * Load minNightsDefault from /data/properties/{slug}.json
 */
function get_min_nights_for_property(string $slug): int {
  $path = base_path() . '/data/properties/' . $slug . '.json';
  if (!file_exists($path)) return 1;

  $raw = file_get_contents($path);
  if ($raw === false) return 1;

  $json = json_decode($raw, true);
  $min = (int)($json['pricing']['minNightsDefault'] ?? 1);
  return max(1, $min);
}

// ------- auth helpers (optional, but handy) -------

function user_find_by_email(string $email): ?array {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
  $stmt->execute([':e' => $email]);
  $row = $stmt->fetch();
  return $row ? $row : null;
}

function user_verify_login(string $email, string $password): ?array {
  $u = user_find_by_email($email);
  if (!$u) return null;
  if (!password_verify($password, (string)$u['password_hash'])) return null;
  return $u;
}
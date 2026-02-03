<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $path = __DIR__ . '/../storage/app.sqlite';
  if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);

  $pdo = new PDO('sqlite:' . $path);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA foreign_keys = ON;');
  return $pdo;
}

function install_schema() {
  $pdo = db();

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT UNIQUE NOT NULL,
      password_hash TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS properties (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      slug TEXT UNIQUE NOT NULL,
      title TEXT NOT NULL,
      json TEXT NOT NULL,
      published_json_path TEXT,
      updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    );
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS bookings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      property_slug TEXT NOT NULL,
      checkin TEXT NOT NULL,
      checkout TEXT NOT NULL,
      guests INTEGER,
      name TEXT,
      email TEXT,
      phone TEXT,
      message TEXT,
      status TEXT NOT NULL DEFAULT 'ENQUIRY', -- ENQUIRY|HOLD|CONFIRMED|CANCELLED|BLOCKED
      total_amount REAL,
      currency TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY(property_slug) REFERENCES properties(slug) ON DELETE CASCADE
    );
  ");

  // default admin user (jen při prázdné tabulce)
  $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($count === 0) {
    $email = 'admin@example.com';
    $pass  = 'admin12345';
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users(email, password_hash) VALUES(?, ?)");
    $stmt->execute([$email, $hash]);
  }
}
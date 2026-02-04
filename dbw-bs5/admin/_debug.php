<?php
declare(strict_types=1);

// 1) ukaž chyby přímo do stránky (jen dočasně!)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 2) převeď warningy/notice na výjimky (ať je vidíme)
set_error_handler(function ($severity, $message, $file, $line) {
  if (!(error_reporting() & $severity)) return false;
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// 3) když nastane fatální error, vypiš ho
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "FATAL ERROR\n";
    echo "Type: {$e['type']}\n";
    echo "Message: {$e['message']}\n";
    echo "File: {$e['file']}\n";
    echo "Line: {$e['line']}\n";
  }
});

header('Content-Type: text/plain; charset=utf-8');

echo "OK: _debug.php loaded\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "DIR: " . __DIR__ . "\n";

$base = realpath(__DIR__ . '/..'); // /dbw-bs5
echo "BASE: " . ($base ?: 'FALSE') . "\n";

$pathsToCheck = [
  __DIR__ . '/_auth.php',
  __DIR__ . '/_layout_top.php',
  __DIR__ . '/_db.php',
  $base ? $base . '/_shared/db.php' : '(no base)/_shared/db.php',
  $base ? $base . '/storage/app.sqlite' : '(no base)/storage/app.sqlite',
];

echo "\nCHECK FILES:\n";
foreach ($pathsToCheck as $p) {
  if (str_starts_with((string)$p, '(')) { echo "- $p\n"; continue; }
  echo "- $p => " . (file_exists($p) ? "exists" : "MISSING") . " | readable=" . (is_readable($p) ? "yes" : "no") . "\n";
}

echo "\nTRY INCLUDE _auth.php:\n";
require_once __DIR__ . '/_auth.php';
echo "✅ _auth.php included OK\n";

echo "\nTRY session_start():\n";
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
echo "✅ session OK (save_path=" . ini_get('session.save_path') . ")\n";

echo "\nDONE\n";
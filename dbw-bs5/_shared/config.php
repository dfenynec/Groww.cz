<?php
declare(strict_types=1);

require_once __DIR__ . '/paths.php';

function read_json_file(string $path): array {
  if (!file_exists($path)) throw new RuntimeException("Missing file: {$path}");
  if (!is_readable($path)) throw new RuntimeException("Not readable: {$path}");
  $raw = file_get_contents($path);
  if ($raw === false) throw new RuntimeException("Read failed: {$path}");
  $data = json_decode($raw, true);
  if (!is_array($data)) throw new RuntimeException("Invalid JSON: {$path} (" . json_last_error_msg() . ")");
  return $data;
}

function write_json_file(string $path, array $data): void {
  $dir = dirname($path);
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException("Cannot create dir: {$dir}");
    }
  }
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($json === false) throw new RuntimeException("JSON encode failed");
  if (file_put_contents($path, $json) === false) throw new RuntimeException("Write failed: {$path}");
}

function ical_config_read(): array {
  return read_json_file(ical_config_path());
}

function ical_config_write(array $cfg): void {
  write_json_file(ical_config_path(), $cfg);
}

/**
 * Vrátí pro slug pole URL: ['airbnb' => '...', 'booking' => '...'] (může být prázdné)
 */
function ical_urls_for_slug(string $slug): array {
  $cfg = ical_config_read();
  if (empty($cfg[$slug]) || !is_array($cfg[$slug])) return [];
  return [
    'airbnb' => (string)($cfg[$slug]['airbnb'] ?? ''),
    'booking' => (string)($cfg[$slug]['booking'] ?? ''),
  ];
}
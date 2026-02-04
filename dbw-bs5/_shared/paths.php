<?php
declare(strict_types=1);

/**
 * Centralizace cest pro celý projekt.
 * Všude používej tyhle funkce místo ručního skládání realpathů.
 */

function project_root(): string {
  static $root = null;
  if (is_string($root)) return $root;

  // /dbw-bs5/_shared -> /dbw-bs5
  $r = realpath(__DIR__ . '/..');
  if ($r === false) throw new RuntimeException("Project root not found");
  $root = $r;
  return $root;
}

function storage_dir(): string {
  return project_root() . '/storage';
}

function db_file(): string {
  return storage_dir() . '/app.sqlite';
}

function private_dir(): string {
  return project_root() . '/private';
}

function data_dir(): string {
  return project_root() . '/data';
}

function properties_dir(): string {
  return data_dir() . '/properties';
}

function property_json_path(string $slug): string {
  $slug = strtolower(trim($slug));
  $slug = preg_replace('~[^a-z0-9\-]~', '', $slug);
  return properties_dir() . '/' . $slug . '.json';
}

function ical_config_path(): string {
  return private_dir() . '/ical-config.json';
}
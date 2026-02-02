<?php
header('Content-Type: application/json; charset=utf-8');
$slug = isset($_GET['property']) ? $_GET['property'] : '';
echo json_encode([
  'ok' => true,
  'property' => $slug,
  'note' => 'availability endpoint is running'
]);
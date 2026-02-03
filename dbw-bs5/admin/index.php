<?php
require_once __DIR__ . "/_db.php";

if (isset($_GET['install'])) {
  install_schema();
  echo "Installed. Default login: admin@example.com / admin12345";
  exit;
}

header("Location: dashboard.php");
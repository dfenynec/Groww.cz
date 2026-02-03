<?php require_once __DIR__ . "/_auth.php"; ?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin</title>
  <link rel="stylesheet" href="../assets/bootstrap.min.css"><!-- pokud máš -->
  <style>
    body{background:#f6f7fb}
    .card{border:0;border-radius:14px}
    .nav a{margin-right:12px}
    code{font-size:.9em}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container py-2">
    <a class="navbar-brand fw-bold" href="dashboard.php">Admin</a>
    <div class="nav">
      <a class="nav-link" href="properties.php">Properties</a>
      <a class="nav-link" href="bookings.php">Bookings</a>
      <a class="nav-link" href="logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container my-4">
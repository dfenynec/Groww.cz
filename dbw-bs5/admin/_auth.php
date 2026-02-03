<?php
session_start();

function require_login() {
  if (empty($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
  }
}
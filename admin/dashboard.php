<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: api/login.php');
    exit;
}
require_once '../includes/db.php';

// Příklad: načtení statistik
$stmt = $pdo->query("SELECT COUNT(*) FROM templates");
$template_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$order_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Groww.cz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto py-10">
        <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow text-center">
                <div class="text-4xl font-bold text-orange-500"><?= htmlspecialchars($template_count) ?></div>
                <div class="text-gray-700 mt-2">Webových šablon</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow text-center">
                <div class="text-4xl font-bold text-orange-500"><?= htmlspecialchars($order_count) ?></div>
                <div class="text-gray-700 mt-2">Objednávek</div>
            </div>
        </div>
    </div>
</body>
</html>

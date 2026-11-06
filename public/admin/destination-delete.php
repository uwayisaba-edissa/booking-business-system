<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: ' . appUrl('/public/admin/destinations.php'));
    exit;
}

$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Security validation failed. Please try again.'];
    header('Location: ' . appUrl('/public/admin/destinations.php'));
    exit;
}

$id = (int) $_POST['id'];
$pdo = db();
$stmt = $pdo->prepare('DELETE FROM destinations WHERE id = :id');
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Destination deleted successfully.'];
header('Location: ' . appUrl('/public/admin/destinations.php'));
exit;

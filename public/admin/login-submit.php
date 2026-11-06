<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your login session expired. Please try again.'];
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please enter both email and password.'];
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

$stmt = db()->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = :email AND role = :role LIMIT 1');
$stmt->execute([':email' => $email, ':role' => 'admin']);
$user = $stmt->fetch();

if ($user === false || !password_verify($password, $user['password_hash'])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid admin credentials.'];
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

$_SESSION['admin_id'] = (int) $user['id'];
$_SESSION['admin_name'] = $user['full_name'];
header('Location: ' . appUrl('/public/admin/dashboard.php'));
exit;

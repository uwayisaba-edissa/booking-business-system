<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . appUrl('/public/login.php'));
    exit;
}

$csrfToken = (string) ($_SESSION['customer_csrf_token'] ?? '');
if ($csrfToken === '' || !hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your login session expired. Please try again.'];
    header('Location: ' . appUrl('/public/login.php'));
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$stmt = db()->prepare('SELECT id, full_name, password_hash, role FROM users WHERE email = :email AND role = :role LIMIT 1');
$stmt->execute([':email' => $email, ':role' => 'customer']);
$user = $stmt->fetch();

if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid customer email or password.'];
    header('Location: ' . appUrl('/public/login.php'));
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = $user['full_name'];
header('Location: ' . appUrl('/public/my-bookings.php'));
exit;

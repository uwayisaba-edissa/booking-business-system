<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . appUrl('/public/register.php'));
    exit;
}

$csrfToken = (string) ($_SESSION['customer_csrf_token'] ?? '');
if ($csrfToken === '' || !hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your registration session expired. Please try again.'];
    header('Location: ' . appUrl('/public/register.php'));
    exit;
}

$name = trim((string) ($_POST['full_name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim((string) ($_POST['phone'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || strlen($password) < 8) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Enter valid details and a password of at least 8 characters.'];
    header('Location: ' . appUrl('/public/register.php'));
    exit;
}

$pdo = db();
$existing = $pdo->prepare('SELECT id, role FROM users WHERE email = :email LIMIT 1');
$existing->execute([':email' => $email]);
if ($existing->fetch() !== false) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'An account already exists for that email. Please sign in.'];
    header('Location: ' . appUrl('/public/login.php'));
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO users (full_name, email, phone, password_hash, role)
     VALUES (:full_name, :email, :phone, :password_hash, :role)'
);
$stmt->execute([
    ':full_name' => $name,
    ':email' => $email,
    ':phone' => $phone,
    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ':role' => 'customer',
]);

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $pdo->lastInsertId();
$_SESSION['user_name'] = $name;
header('Location: ' . appUrl('/public/my-bookings.php'));
exit;

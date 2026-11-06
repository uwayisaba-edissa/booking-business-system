<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking_id'])) {
    header('Location: ' . appUrl('/public/admin/bookings.php'));
    exit;
}

$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Security validation failed. Please try again.'];
    header('Location: ' . appUrl('/public/admin/bookings.php'));
    exit;
}

$bookingId = (int) $_POST['booking_id'];
$bookingStatus = trim((string) ($_POST['booking_status'] ?? 'pending'));
$paymentStatus = trim((string) ($_POST['payment_status'] ?? 'pending'));

if (!in_array($bookingStatus, ['pending', 'confirmed', 'cancelled'], true)) {
    $bookingStatus = 'pending';
}

if (!in_array($paymentStatus, ['pending', 'paid'], true)) {
    $paymentStatus = 'pending';
}

$pdo = db();
$stmt = $pdo->prepare('UPDATE bookings SET booking_status = :booking_status, payment_status = :payment_status WHERE id = :id');
$stmt->execute([
    ':booking_status' => $bookingStatus,
    ':payment_status' => $paymentStatus,
    ':id' => $bookingId,
]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Booking and payment status updated successfully.'];
header('Location: ' . appUrl('/public/admin/bookings.php'));
exit;

<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$reference = trim((string) ($_POST['reference'] ?? ''));
$csrfToken = (string) ($_SESSION['payment_csrf_token'] ?? '');
if ($csrfToken === '' || !hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Payment session expired. Please try again.'];
    header('Location: ' . appUrl('/public/receipt.php?reference=' . urlencode($reference)));
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT user_id FROM bookings WHERE reference_code = :reference LIMIT 1');
$stmt->execute([':reference' => $reference]);
$booking = $stmt->fetch();
$allowed = $booking !== false
    && (isset($_SESSION['user_id']) && (int) $booking['user_id'] === (int) $_SESSION['user_id']
        || $reference !== '' && $reference === ($_SESSION['last_booking_reference'] ?? ''));

if (!$allowed) {
    http_response_code(403);
    exit('You are not allowed to pay for this booking.');
}

$update = $pdo->prepare(
    "UPDATE bookings SET payment_status = 'paid', booking_status = 'confirmed' WHERE reference_code = :reference"
);
$update->execute([':reference' => $reference]);
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Demo payment completed successfully.'];
header('Location: ' . appUrl('/public/receipt.php?reference=' . urlencode($reference)));
exit;

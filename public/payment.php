<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$reference = trim((string) ($_GET['reference'] ?? ''));
$stmt = db()->prepare(
    'SELECT b.*, d.title AS destination_title
     FROM bookings b
     JOIN destinations d ON d.id = b.destination_id
     WHERE b.reference_code = :reference
     LIMIT 1'
);
$stmt->execute([':reference' => $reference]);
$booking = $stmt->fetch();

$allowed = $booking !== false
    && (isset($_SESSION['user_id']) && (int) $booking['user_id'] === (int) $_SESSION['user_id']
        || ($reference !== '' && $reference === ($_SESSION['last_booking_reference'] ?? '')));

if (!$allowed) {
    http_response_code(403);
    exit('You are not allowed to pay for this booking.');
}

$csrfToken = $_SESSION['payment_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['payment_csrf_token'] = $csrfToken;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pay for Booking | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261104'); ?>" />
</head>
<body>
    <main class="container page-wrap">
        <section class="receipt-card payment-card">
            <p class="eyebrow small">Demo payment · RWF</p>
            <h2>Complete your trip payment</h2>
            <p class="muted">This is a local demo payment. No real money will be charged.</p>
            <div class="amount-box">
                <span><?= htmlspecialchars($booking['destination_title'], ENT_QUOTES, 'UTF-8'); ?></span>
                <strong>Fr <?= number_format((float) $booking['total_price'], 0, '.', ' '); ?></strong>
            </div>
            <form method="POST" action="<?= appUrl('/public/payment-submit.php'); ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                <input type="hidden" name="reference" value="<?= htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?>" />
                <button class="btn primary big full" type="submit">Simulate successful payment</button>
            </form>
            <a class="btn secondary full" href="<?= appUrl('/public/receipt.php?reference=' . urlencode($reference)); ?>">Back to receipt</a>
        </section>
    </main>
</body>
</html>

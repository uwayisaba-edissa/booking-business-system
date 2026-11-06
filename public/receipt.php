<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$reference = trim((string) ($_GET['reference'] ?? ''));
$pdo = db();

if ($reference === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'A booking reference is required to view a receipt.'];
    header('Location: ' . appUrl('/public/booking.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT b.*, d.title AS destination_title, d.location AS destination_location, u.full_name, u.email, u.phone
     FROM bookings b
     JOIN destinations d ON d.id = b.destination_id
     LEFT JOIN users u ON u.id = b.user_id
     WHERE b.reference_code = :reference
     LIMIT 1'
);
$stmt->execute([':reference' => $reference]);
$booking = $stmt->fetch();

if ($booking === false) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Receipt not found for the provided reference code.'];
    header('Location: ' . appUrl('/public/my-bookings.php'));
    exit;
}

$activityStmt = $pdo->prepare(
    'SELECT a.activity_name, a.extra_cost
     FROM booking_activities ba
     JOIN activities a ON a.id = ba.activity_id
     WHERE ba.booking_id = :booking_id
     ORDER BY a.activity_name ASC'
);
$activityStmt->execute([':booking_id' => (int) $booking['id']]);
$activities = $activityStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking Receipt | Visit Bigogwe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261104'); ?>" />
</head>
<body>
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="<?= appUrl('/public/index.php'); ?>">Visit Bigogwe</a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu">Menu</button>
            <nav id="site-menu">
                <a href="<?= appUrl('/public/index.php'); ?>">Home</a>
                <a href="<?= appUrl('/public/index.php#destinations'); ?>">Destinations</a>
                <a href="<?= appUrl('/public/booking.php'); ?>">Book Now</a>
                <a href="<?= appUrl('/public/my-bookings.php'); ?>">My bookings</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= appUrl('/public/logout.php'); ?>">Sign out</a>
                <?php else: ?>
                    <a href="<?= appUrl('/public/login.php'); ?>">Sign in</a>
                <?php endif; ?>
                <a href="<?= appUrl('/public/admin/login.php'); ?>">Admin</a>
            </nav>
        </div>
    </header>

    <main class="container page-wrap">
        <div class="receipt-card">
            <div class="receipt-header">
                <div>
                    <p class="eyebrow small">Payment receipt</p>
                    <h2>Thank you for your reservation</h2>
                </div>
                <span class="badge <?= $booking['payment_status'] === 'paid' ? 'success' : ''; ?>">
                    <?= $booking['payment_status'] === 'paid' ? 'Paid' : 'Payment pending'; ?>
                </span>
            </div>

            <div class="receipt-summary">
                <div>
                    <label>Reference</label>
                    <strong><?= htmlspecialchars($booking['reference_code'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div>
                    <label>Destination</label>
                    <strong><?= htmlspecialchars($booking['destination_title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div>
                    <label>Travel date</label>
                    <strong><?= htmlspecialchars($booking['booking_date'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div>
                    <label>Payment status</label>
                    <strong><?= htmlspecialchars($booking['payment_status'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div>
                    <label>Payment method</label>
                    <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $booking['payment_method'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div>
                    <label>Total due</label>
                    <strong>Fr <?= number_format((float) $booking['total_price'], 0, '.', ' '); ?></strong>
                </div>
            </div>

            <div class="receipt-layout">
                <div class="panel">
                    <h3>Traveler details</h3>
                    <ul class="info-list">
                        <li><span>Name</span><strong><?= htmlspecialchars($booking['full_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        <li><span>Email</span><strong><?= htmlspecialchars($booking['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        <li><span>Phone</span><strong><?= htmlspecialchars($booking['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></li>
                        <li><span>Guests</span><strong><?= (int) $booking['number_of_guests']; ?></strong></li>
                    </ul>
                </div>

                <div class="panel">
                    <h3>Included activities</h3>
                    <?php if (!$activities): ?>
                        <p class="muted">No optional activities were selected.</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($activities as $activity): ?>
                                <li>
                                    <span><?= htmlspecialchars($activity['activity_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong>Fr <?= number_format((float) $activity['extra_cost'], 0, '.', ' '); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="receipt-actions">
                <?php if ($booking['payment_status'] !== 'paid'): ?>
                    <a href="<?= appUrl('/public/payment.php?reference=' . urlencode((string) $booking['reference_code'])); ?>" class="btn primary">Pay now</a>
                <?php endif; ?>
                <a href="<?= appUrl('/public/my-bookings.php'); ?>" class="btn secondary">View my bookings</a>
                <a href="<?= appUrl('/public/booking.php'); ?>" class="btn primary">Book another trip</a>
            </div>
        </div>
    </main>
    <script>
        const menuToggle = document.querySelector('.menu-toggle');
        const siteMenu = document.getElementById('site-menu');
        menuToggle.addEventListener('click', () => {
            const isOpen = siteMenu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });
    </script>
</body>
</html>

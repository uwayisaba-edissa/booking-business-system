<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$results = [];
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare(
        'SELECT b.*, d.title AS destination_title, d.location AS destination_location, u.full_name, u.email, u.phone
         FROM bookings b
         JOIN destinations d ON d.id = b.destination_id
         JOIN users u ON u.id = b.user_id
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC'
    );
    $stmt->execute([':user_id' => (int) $_SESSION['user_id']]);
    $results = $stmt->fetchAll();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

    if ($email === '' || $phone === '') {
        $flash = ['type' => 'error', 'message' => 'Please enter both email and phone number.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($normalizedPhone) < 9) {
        $flash = ['type' => 'error', 'message' => 'Please enter a valid email address and phone number.'];
    } else {
        $stmt = $pdo->prepare(
            'SELECT b.*, d.title AS destination_title, d.location AS destination_location, u.full_name, u.email, u.phone
             FROM bookings b
             JOIN destinations d ON d.id = b.destination_id
             LEFT JOIN users u ON u.id = b.user_id
             WHERE LOWER(u.email) = :email
               AND REPLACE(REPLACE(REPLACE(REPLACE(u.phone, " ", ""), "+", ""), "-", ""), "(", "") LIKE :phone
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([':email' => $email, ':phone' => '%' . $normalizedPhone]);
        $results = $stmt->fetchAll();

        if (!$results) {
            $flash = ['type' => 'error', 'message' => 'No booking matched those details. Check the email and phone used during booking.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Bookings | Visit Bigogwe</title>
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
                <a href="<?= appUrl('/public/my-bookings.php'); ?>" class="active">My bookings</a>
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
        <div class="section-heading">
            <p class="eyebrow small">Traveler dashboard</p>
            <h2>My bookings</h2>
        </div>

        <?php if ($flash): ?>
            <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST" class="booking-form compact-form">
                <div class="form-grid single-grid">
                    <div class="form-row">
                        <label>Email address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" required />
                    </div>
                    <div class="form-row">
                        <label>Phone number</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars((string) ($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="+250 7xx xxx xxx" required />
                    </div>
                </div>
                <div class="submit-row">
                    <button type="submit" class="btn primary">Find my bookings</button>
                </div>
            </form>
        </div>

        <?php if ($results): ?>
            <div class="panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['reference_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($booking['destination_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($booking['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) $booking['number_of_guests']; ?></td>
                                <td>Fr <?= number_format((float) $booking['total_price'], 0, '.', ' '); ?></td>
                                <td><?= htmlspecialchars($booking['booking_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><a href="<?= appUrl('/public/receipt.php?reference=' . urlencode((string) $booking['reference_code'])); ?>" class="btn secondary small">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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

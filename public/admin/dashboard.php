<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}
$pdo = db();

$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$revenue = (float) $pdo->query('SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE payment_status = "paid"')->fetchColumn();
$upcomingVisitors = (int) $pdo->query('SELECT COUNT(*) FROM bookings WHERE booking_date >= CURDATE() AND booking_status IN ("pending", "confirmed")')->fetchColumn();
$bookings = $pdo->query(
    'SELECT b.*, d.title AS destination_title, u.full_name, u.phone FROM bookings b JOIN destinations d ON d.id = b.destination_id LEFT JOIN users u ON u.id = b.user_id ORDER BY b.created_at DESC LIMIT 10'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261103'); ?>" />
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <h2>Visit Bigogwe</h2>
            <nav>
                <a class="admin-site-link" href="<?= appUrl('/public/index.php'); ?>">View website</a>
                <a href="<?= appUrl('/public/admin/dashboard.php'); ?>" class="active">Dashboard</a>
                <a href="<?= appUrl('/public/admin/bookings.php'); ?>">Bookings</a>
                <a href="<?= appUrl('/public/admin/destinations.php'); ?>">Destinations</a>
                <a href="<?= appUrl('/public/admin/logout.php'); ?>">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Dashboard</h1>
                <span>Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span>Total bookings</span>
                    <strong><?= $totalBookings; ?></strong>
                </div>
                <div class="stat-card">
                    <span>Revenue</span>
                    <strong>Fr <?= number_format($revenue, 0, '.', ' '); ?></strong>
                </div>
                <div class="stat-card">
                    <span>Upcoming visitors</span>
                    <strong><?= $upcomingVisitors; ?></strong>
                </div>
            </div>

            <div class="panel">
                <h3>Recent reservations</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$bookings): ?>
                            <tr><td colspan="5">No bookings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['reference_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['full_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['destination_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['booking_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>

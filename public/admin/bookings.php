<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

$pdo = db();
$csrfToken = $_SESSION['admin_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_csrf_token'] = $csrfToken;
$searchTerm = trim((string) ($_GET['search'] ?? ''));

$query = 'SELECT b.*, d.title AS destination_title, u.full_name, u.phone FROM bookings b JOIN destinations d ON d.id = b.destination_id LEFT JOIN users u ON u.id = b.user_id';
$params = [];
if ($searchTerm !== '') {
    $query .= ' WHERE b.reference_code LIKE :search OR u.phone LIKE :search2';
    $params[':search'] = '%' . $searchTerm . '%';
    $params[':search2'] = '%' . $searchTerm . '%';
}
$query .= ' ORDER BY b.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Bookings | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261103'); ?>" />
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <h2>Visit Bigogwe</h2>
            <nav>
                <a href="<?= appUrl('/public/admin/dashboard.php'); ?>">Dashboard</a>
                <a href="<?= appUrl('/public/admin/bookings.php'); ?>" class="active">Bookings</a>
                <a href="<?= appUrl('/public/admin/destinations.php'); ?>">Destinations</a>
                <a href="<?= appUrl('/public/admin/logout.php'); ?>">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Booking management</h1>
            </div>

            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="panel">
                <form method="GET" class="search-row">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by reference code or phone number" />
                    <button type="submit" class="btn primary">Search</button>
                </form>
            </div>

            <div class="panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$bookings): ?>
                            <tr><td colspan="9">No bookings found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['reference_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['full_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?><br />
                                        <small><?= htmlspecialchars($booking['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($booking['destination_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['booking_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $booking['number_of_guests']; ?></td>
                                    <td>Fr <?= number_format((float) $booking['total_price'], 0, '.', ' '); ?></td>
                                    <td><?= htmlspecialchars($booking['payment_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($booking['booking_status'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" action="<?= appUrl('/public/admin/booking-action.php'); ?>">
                                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id']; ?>" />
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                                            <div class="stacked-controls">
                                                <select name="payment_status">
                                                    <option value="pending" <?= $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Payment pending</option>
                                                    <option value="paid" <?= $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                </select>
                                                <select name="booking_status">
                                                    <option value="pending" <?= $booking['booking_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="confirmed" <?= $booking['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                    <option value="cancelled" <?= $booking['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" class="btn primary small">Update</button>
                                            </div>
                                        </form>
                                    </td>
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

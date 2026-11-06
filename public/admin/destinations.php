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
$destinations = $pdo->query('SELECT * FROM destinations ORDER BY created_at DESC')->fetchAll();
$editDestination = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM destinations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editDestination = $stmt->fetch();
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Destinations | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261103'); ?>" />
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <h2>Visit Bigogwe</h2>
            <nav>
                <a href="<?= appUrl('/public/admin/dashboard.php'); ?>">Dashboard</a>
                <a href="<?= appUrl('/public/admin/bookings.php'); ?>">Bookings</a>
                <a href="<?= appUrl('/public/admin/destinations.php'); ?>" class="active">Destinations</a>
                <a href="<?= appUrl('/public/admin/logout.php'); ?>">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1>Destination management</h1>
            </div>

            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="panel">
                <h3><?= $editDestination ? 'Edit destination' : 'Add a new destination'; ?></h3>
                <form action="<?= appUrl('/public/admin/destination-save.php'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                    <?php if ($editDestination): ?>
                        <input type="hidden" name="id" value="<?= (int) $editDestination['id']; ?>" />
                    <?php endif; ?>

                    <div class="form-row">
                        <label>Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($editDestination['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                    </div>

                    <div class="form-row">
                        <label>Location</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($editDestination['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                    </div>

                    <div class="form-row">
                        <label>Featured image URL</label>
                        <input type="url" name="featured_image" value="<?= htmlspecialchars($editDestination['featured_image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>

                    <div class="form-row">
                        <label>Price per person (Fr)</label>
                        <input type="number" min="1" step="500" name="price_per_person" value="<?= htmlspecialchars((string) ($editDestination['price_per_person'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required />
                    </div>

                    <div class="form-row">
                        <label>Description</label>
                        <textarea name="description" rows="5" required><?= htmlspecialchars($editDestination['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <button type="submit" class="btn primary"><?= $editDestination ? 'Update destination' : 'Create destination'; ?></button>
                </form>
            </div>

            <div class="panel">
                <h3>Existing destinations</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$destinations): ?>
                            <tr><td colspan="4">No destinations available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($destinations as $destination): ?>
                                <tr>
                                    <td><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($destination['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>Fr <?= number_format((float) $destination['price_per_person'], 0, '.', ' '); ?></td>
                                    <td>
                                        <a class="btn secondary small" href="<?= appUrl('/public/admin/destinations.php?edit=' . (int) $destination['id']); ?>">Edit</a>
                                        <form method="POST" action="<?= appUrl('/public/admin/destination-delete.php'); ?>" class="inline-action" onsubmit="return confirm('Delete this destination?');">
                                            <input type="hidden" name="id" value="<?= (int) $destination['id']; ?>" />
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                                            <button type="submit" class="btn primary small">Delete</button>
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

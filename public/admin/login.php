<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';
$csrfToken = $_SESSION['admin_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_csrf_token'] = $csrfToken;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261103'); ?>" />
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card">
            <h2>Admin Login</h2>
            <p class="muted">Sign in to manage bookings and tours.</p>

            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form action="<?= appUrl('/public/admin/login-submit.php'); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                <div class="form-row">
                    <label>Email</label>
                    <input type="email" name="email" required />
                </div>
                <div class="form-row">
                    <label>Password</label>
                    <input type="password" name="password" required />
                </div>
                <button type="submit" class="btn primary full">Login</button>
            </form>
            <a class="btn secondary full admin-public-link" href="<?= appUrl('/public/index.php'); ?>">Go to customer website</a>
        </div>
    </div>
</body>
</html>

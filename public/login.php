<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$csrfToken = $_SESSION['customer_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['customer_csrf_token'] = $csrfToken;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Customer Login | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261104'); ?>" />
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow small">Traveler account</p>
            <h2>Welcome back</h2>
            <p class="muted">Sign in to manage your bookings and receipts.</p>
            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form action="<?= appUrl('/public/login-submit.php'); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                <div class="form-row">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" autocomplete="email" required />
                </div>
                <div class="form-row">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required />
                </div>
                <button type="submit" class="btn primary full">Sign in</button>
            </form>
            <p class="auth-switch">New traveler? <a href="<?= appUrl('/public/register.php'); ?>">Create an account</a></p>
            <a class="btn secondary full" href="<?= appUrl('/public/index.php'); ?>">Back to website</a>
        </section>
    </main>
</body>
</html>

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
    <title>Create Account | Visit Bigogwe</title>
    <link rel="stylesheet" href="<?= appUrl('/static/styles.css?v=20261104'); ?>" />
</head>
<body>
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow small">Start exploring</p>
            <h2>Create your account</h2>
            <p class="muted">Save your details and track every reservation in one place.</p>
            <?php if ($flash): ?>
                <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form action="<?= appUrl('/public/register-submit.php'); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
                <div class="form-row"><label for="full_name">Full name</label><input id="full_name" type="text" name="full_name" autocomplete="name" required /></div>
                <div class="form-row"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" required /></div>
                <div class="form-row"><label for="phone">Phone number</label><input id="phone" type="text" name="phone" autocomplete="tel" required /></div>
                <div class="form-row"><label for="password">Password</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required /></div>
                <button type="submit" class="btn primary full">Create account</button>
            </form>
            <p class="auth-switch">Already registered? <a href="<?= appUrl('/public/login.php'); ?>">Sign in</a></p>
        </section>
    </main>
</body>
</html>

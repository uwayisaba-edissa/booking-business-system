<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$stmt = $pdo->query(
    'SELECT d.*, COUNT(b.id) AS booking_count FROM destinations d LEFT JOIN bookings b ON b.destination_id = d.id GROUP BY d.id ORDER BY d.created_at DESC'
);
$destinations = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Visit Bigogwe | Tours & Destinations</title>
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

    <section class="hero">
        <div class="container hero-inner">
            <div>
                <p class="eyebrow">Discover unforgettable experiences</p>
                <h1>Explore Rwanda through remarkable destinations and memorable tours.</h1>
                <p class="lead">From scenic escapes to guided cultural adventures, we help visitors plan the perfect getaway.</p>
                <div class="cta-row">
                    <a class="btn primary" href="<?= appUrl('/public/booking.php'); ?>">Reserve a trip</a>
                    <a class="btn secondary" href="<?= appUrl('/public/index.php#destinations'); ?>">Browse tours</a>
                </div>
            </div>
            <div class="hero-card">
                <h3>Popular getaway</h3>
                <div class="feature-tag">Bigogwe scenic tour</div>
                <p>Mountain views, cultural experiences, and comfortable guided travel for groups and couples.</p>
                <div class="metric-row">
                    <div>
                        <strong>4.9/5</strong>
                        <span>Guest rating</span>
                    </div>
                    <div>
                        <strong>1,200+</strong>
                        <span>Happy travelers</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="container">
            <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    <?php endif; ?>

    <main class="container main-section" id="destinations">
        <div class="section-heading">
            <p class="eyebrow small">Available packages</p>
            <h2>Browse our destinations</h2>
            <p class="section-intro">Choose a ready-to-go experience and customize it with activities your group will love.</p>
        </div>

        <div class="destination-grid">
            <?php if (!$destinations): ?>
                <div class="panel empty-state">
                    <h3>New experiences are coming soon</h3>
                    <p class="muted">Please check back shortly while we prepare the next destinations.</p>
                </div>
            <?php endif; ?>
            <?php foreach ($destinations as $destination): ?>
                <article class="destination-card">
                    <img src="<?= htmlspecialchars($destination['featured_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <div class="card-body">
                        <div class="meta-row">
                            <span class="badge"><?= htmlspecialchars($destination['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="booking-count"><?= (int) $destination['booking_count']; ?> bookings</span>
                        </div>
                        <h3><?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?= htmlspecialchars($destination['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="price-row">
                            <strong>Fr <?= number_format((float) $destination['price_per_person'], 0, '.', ' '); ?></strong>
                            <span>per person</span>
                        </div>
                        <a class="btn primary full" href="<?= appUrl('/public/booking.php?destination=' . urlencode((string) $destination['id'])); ?>">Book this trip</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <section class="features-wrap">
        <div class="container feature-grid">
            <div class="feature-item">
                <h3>Tailored packages</h3>
                <p>Choose from curated scenic and cultural experiences, plus accessibility-friendly group options.</p>
            </div>
            <div class="feature-item">
                <h3>Transparent pricing</h3>
                <p>See your total at a glance with optional activities and guest counts included.</p>
            </div>
            <div class="feature-item">
                <h3>Trusted support</h3>
                <p>Booking confirmations, tracking, and local assistance throughout the visitor experience.</p>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container">
            <p>© 2026 Visit Bigogwe</p>
        </div>
    </footer>
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

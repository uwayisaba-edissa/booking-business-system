<?php


declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = db();
$destinations = $pdo->query('SELECT * FROM destinations ORDER BY title ASC')->fetchAll();
$selectedDestinationId = (int) ($_GET['destination'] ?? 0);

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Book a Tour | Visit Bigogwe</title>
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
                <a href="<?= appUrl('/public/booking.php'); ?>" class="active">Book Now</a>
                <a href="<?= appUrl('/public/my-bookings.php'); ?>">My bookings</a>
                <a href="<?= appUrl('/public/admin/login.php'); ?>">Admin</a>
            </nav>
        </div>
    </header>

    <main class="container page-wrap">
        <div class="section-heading">
            <p class="eyebrow small">Easy reservation</p>
            <h2>Reserve your destination experience</h2>
        </div>

        <?php if ($flash): ?>
            <div class="flash flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="booking-form" action="<?= appUrl('/public/booking-submit.php'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />

            <div class="form-grid">
                <div class="panel">
                    <h3>Visitor details</h3>

                    <div class="form-row">
                        <label>Full name</label>
                        <input type="text" name="full_name" placeholder="Enter full name" required />
                    </div>

                    <div class="form-row">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="name@example.com" required />
                    </div>

                    <div class="form-row">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="+2507xx..." required />
                    </div>

                    <div class="form-row">
                        <label>Destination</label>
                        <select name="destination_id" id="destination_id" required>
                            <option value="">Select destination</option>
                            <?php foreach ($destinations as $destination): ?>
                                <option value="<?= (int) $destination['id']; ?>" <?= ($selectedDestinationId === (int) $destination['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($destination['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row inline-two">
                        <div>
                            <label>Booking date</label>
                            <input type="date" name="booking_date" min="<?= date('Y-m-d'); ?>" required />
                        </div>
                        <div>
                            <label>Number of guests</label>
                            <input type="number" name="number_of_guests" min="1" value="1" required />
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Payment option</label>
                        <select name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Card</option>
                            <option value="cash">Cash on arrival</option>
                        </select>
                    </div>
                </div>

                <div class="panel price-panel">
                    <h3>Tour extras</h3>
                    <div id="activity_list">
                        <p class="muted">Select a destination to view optional activities.</p>
                    </div>

                    <div class="amount-box">
                        <span>Total price</span>
                        <strong id="total_price">Fr 0</strong>
                    </div>
                </div>
            </div>

            <div class="submit-row">
                <button type="submit" class="btn primary big">Confirm booking</button>
            </div>
        </form>
    </main>

    <script>
        const destinations = <?php echo json_encode($destinations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const destinationSelect = document.getElementById('destination_id');
        const activityList = document.getElementById('activity_list');
        const totalPriceEl = document.getElementById('total_price');
        const guestInput = document.querySelector('[name="number_of_guests"]');

        function loadActivities() {
            const id = Number(destinationSelect.value);
            const destination = destinations.find(item => Number(item.id) === id);

            if (!destination) {
                activityList.innerHTML = '<p class="muted">Select a destination to view optional activities.</p>';
                totalPriceEl.textContent = 'Fr 0';
                return;
            }

            fetch(`<?= appUrl('/public/activities.php'); ?>?destination_id=${id}`)
                .then(response => response.json())
                .then((activities) => {
                    if (!activities.length) {
                        activityList.innerHTML = '<p class="muted">No optional activities available for this destination.</p>';
                        updateTotal(destination.price_per_person, Number(guestInput.value || 1), []);
                        return;
                    }

                    const boxes = activities.map((activity) => {
                        return `
                            <label class="activity-option">
                                <input type="checkbox" name="activities[]" value="${activity.id}" data-extra-cost="${activity.extra_cost}" />
                                <span>${activity.activity_name}</span>
                                <strong>Fr ${Number(activity.extra_cost).toLocaleString()}</strong>
                            </label>
                        `;
                    }).join('');

                    activityList.innerHTML = boxes;
                    document.querySelectorAll('input[name="activities[]"]').forEach((checkbox) => {
                        checkbox.addEventListener('change', () => updateTotal(destination.price_per_person, Number(guestInput.value || 1), getSelectedActivities()));
                    });
                    updateTotal(destination.price_per_person, Number(guestInput.value || 1), getSelectedActivities());
                })
                .catch(() => {
                    activityList.innerHTML = '<p class="muted">Unable to load activities.</p>';
                });
        }

        function getSelectedActivities() {
            return [...document.querySelectorAll('input[name="activities[]"]:checked')].map(item => Number(item.dataset.extraCost));
        }

        function updateTotal(basePrice, guestCount, selectedCosts) {
            const extra = selectedCosts.reduce((sum, value) => sum + value, 0);
            const total = (basePrice * guestCount) + extra;
            totalPriceEl.textContent = `Fr ${Number(total).toLocaleString()}`;
        }

        destinationSelect.addEventListener('change', loadActivities);
        guestInput.addEventListener('input', () => {
            const destination = destinations.find(item => Number(item.id) === Number(destinationSelect.value));
            if (destination) {
                updateTotal(destination.price_per_person, Number(guestInput.value || 1), getSelectedActivities());
            }
        });
        if (destinationSelect.value) {
            loadActivities();
        }

        const menuToggle = document.querySelector('.menu-toggle');
        const siteMenu = document.getElementById('site-menu');
        menuToggle.addEventListener('click', () => {
            const isOpen = siteMenu.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
        });
    </script>
</body>
</html>

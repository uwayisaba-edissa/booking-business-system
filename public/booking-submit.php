<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Controllers/BookingController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

try {
    $controller = new BookingController();
    $result = $controller->process($_POST, $_SESSION['user_id'] ?? null);
    $_SESSION['last_booking_reference'] = $result['reference_code'];

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Booking created successfully. Reference: ' . $result['reference_code'],
    ];

    header('Location: ' . appUrl('/public/receipt.php?reference=' . urlencode((string) $result['reference_code'])));
    exit;
} catch (InvalidArgumentException $exception) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => $exception->getMessage(),
    ];

    header('Location: ' . appUrl('/public/booking.php'));
    exit;
} catch (Throwable $exception) {
    error_log($exception->getMessage());

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'We could not process your booking. Please try again later.',
    ];

    header('Location: ' . appUrl('/public/booking.php'));
    exit;
}

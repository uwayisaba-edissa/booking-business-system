<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . appUrl('/public/admin/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . appUrl('/public/admin/destinations.php'));
    exit;
}

$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');
if ($sessionToken === '' || !hash_equals($sessionToken, (string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Security validation failed. Please try again.'];
    header('Location: ' . appUrl('/public/admin/destinations.php'));
    exit;
}

$destinationId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$title = trim((string) ($_POST['title'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$featuredImage = trim((string) ($_POST['featured_image'] ?? ''));
$pricePerPerson = (float) ($_POST['price_per_person'] ?? 0);

if ($title === '' || $location === '' || $description === '' || $pricePerPerson <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please complete all destination fields with a valid price.'];
    header('Location: ' . appUrl('/public/admin/destinations.php'));
    exit;
}

$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
if ($slug === '') {
    $slug = 'destination';
}

$pdo = db();

if ($destinationId > 0) {
    $stmt = $pdo->prepare(
        'UPDATE destinations SET title = :title, slug = :slug, location = :location, description = :description, featured_image = :featured_image, price_per_person = :price_per_person WHERE id = :id'
    );
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':location' => $location,
        ':description' => $description,
        ':featured_image' => $featuredImage,
        ':price_per_person' => $pricePerPerson,
        ':id' => $destinationId,
    ]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Destination updated successfully.'];
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO destinations (title, slug, location, description, featured_image, price_per_person) VALUES (:title, :slug, :location, :description, :featured_image, :price_per_person)'
    );
    $stmt->execute([
        ':title' => $title,
        ':slug' => $slug,
        ':location' => $location,
        ':description' => $description,
        ':featured_image' => $featuredImage,
        ':price_per_person' => $pricePerPerson,
    ]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Destination created successfully.'];
}

header('Location: ' . appUrl('/public/admin/destinations.php'));
exit;

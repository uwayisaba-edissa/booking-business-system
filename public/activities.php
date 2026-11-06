<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$destinationId = (int) ($_GET['destination_id'] ?? 0);
if ($destinationId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = db()->prepare('SELECT id, activity_name, extra_cost FROM activities WHERE destination_id = :destination_id ORDER BY activity_name ASC');
$stmt->execute([':destination_id' => $destinationId]);

echo json_encode($stmt->fetchAll(), JSON_PRETTY_PRINT);

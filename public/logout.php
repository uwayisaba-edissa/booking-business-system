<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
unset($_SESSION['user_id'], $_SESSION['user_name']);
header('Location: ' . appUrl('/public/index.php'));
exit;

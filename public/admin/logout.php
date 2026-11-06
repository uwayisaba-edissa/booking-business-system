<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/database.php';
$_SESSION = [];
session_destroy();
header('Location: ' . appUrl('/public/admin/login.php'));
exit;

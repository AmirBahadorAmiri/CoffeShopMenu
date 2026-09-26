<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AdminAuth.php';
require_once __DIR__ . '/AdminRepository.php';
require_once __DIR__ . '/menu_helpers.php';

$dbConfig = require __DIR__ . '/../config/database.php';
$adminConfig = require __DIR__ . '/../config/admin.php';

AdminAuth::start($adminConfig);

try {
    $adminRepo = new AdminRepository(Database::connect($dbConfig));
} catch (Throwable $error) {
    error_log('[cafe-admin] database error: ' . $error->getMessage());
    http_response_code(503);
    echo 'خطا در اتصال به پایگاه داده.';
    exit;
}

return $adminRepo;

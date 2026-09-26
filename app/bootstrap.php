<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MenuRepository.php';
require_once __DIR__ . '/menu_helpers.php';

$config = require __DIR__ . '/../config/database.php';

try {
    $menu = (new MenuRepository(Database::connect($config)))->getMenu();
} catch (Throwable $error) {
    error_log('[cafe-menu] database error: ' . $error->getMessage());
    throw new MenuDataException('در حال حاضر امکان خواندن منو وجود ندارد.');
}

return $menu;

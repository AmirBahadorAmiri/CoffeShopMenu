<?php

declare(strict_types=1);

return [
    'host' => getenv('CAFE_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('CAFE_DB_PORT') ?: 3306),
    'name' => getenv('CAFE_DB_NAME') ?: 'cafe_menu',
    'user' => getenv('CAFE_DB_USER') ?: 'root',
    'password' => getenv('CAFE_DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];

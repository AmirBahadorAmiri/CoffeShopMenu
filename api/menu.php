<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'فقط درخواست خواندن مجاز است.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $menu = require __DIR__ . '/../app/bootstrap.php';
    echo json_encode($menu, JSON_UNESCAPED_UNICODE);
} catch (MenuDataException $error) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('[cafe-menu] api error: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'در حال حاضر امکان خواندن منو وجود ندارد.'], JSON_UNESCAPED_UNICODE);
}

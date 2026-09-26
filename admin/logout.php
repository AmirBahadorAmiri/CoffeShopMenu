<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
    if (AdminAuth::validateCsrf($token)) {
        AdminAuth::logout();
    }
}

header('Location: login.php');
exit;

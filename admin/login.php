<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';

if (AdminAuth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    try {
        if ($password === '' || !$adminRepo->verifyPassword($password)) {
            $error = 'رمز اشتباه است.';
        } else {
            AdminAuth::login();
            header('Location: index.php');
            exit;
        }
    } catch (Throwable $e) {
        error_log('[cafe-admin] login error: ' . $e->getMessage());
        $error = 'خطا در ورود. دوباره تلاش کن.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>ورود | مدیریت منوی کافه</title>
  <link rel="stylesheet" href="../assets/css/admin.css?v=1">
</head>
<body>
  <main class="login-wrap">
    <div class="login-card">
      <div class="admin-brand">
        <span class="admin-brand__mark" aria-hidden="true">
          <svg width="24" height="24" viewBox="0 0 34 34" fill="none" role="presentation">
            <path d="M7 12h16v9a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6v-9Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" />
            <path d="M23 14h2.5a3.5 3.5 0 0 1 0 7H23" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
            <path d="M12 6c0 2 2 2 2 4M18 6c0 2 2 2 2 4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
          </svg>
        </span>
        ورود به مدیریت منو
      </div>
      <?php if ($error !== null) : ?>
        <div class="flash flash--error" role="alert"><?= menu_escape($error) ?></div>
      <?php endif; ?>
      <form method="post" action="login.php">
        <div class="form-grid">
          <div class="field">
            <label for="password">رمز مدیریت</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required autofocus>
            <p class="field__hint">رمز پیش‌فرض admin123 است؛ بعد از ورود حتماً از صفحه «تغییر رمز» عوضش کن.</p>
          </div>
          <div>
            <button class="btn" type="submit">ورود</button>
          </div>
        </div>
      </form>
    </div>
  </main>
</body>
</html>

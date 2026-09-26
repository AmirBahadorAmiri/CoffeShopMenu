<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';
require_once __DIR__ . '/../app/admin_view.php';

AdminAuth::requireLogin('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
    if (!AdminAuth::validateCsrf($token)) {
        AdminAuth::flash('نشست منقضی شده؛ دوباره تلاش کن.', 'error');
        header('Location: password.php');
        exit;
    }

    $current = isset($_POST['current']) && is_string($_POST['current']) ? $_POST['current'] : '';
    $next = isset($_POST['next']) && is_string($_POST['next']) ? $_POST['next'] : '';
    $confirm = isset($_POST['confirm']) && is_string($_POST['confirm']) ? $_POST['confirm'] : '';

    try {
        if (!$adminRepo->verifyPassword($current)) {
            throw new RuntimeException('رمز فعلی اشتباه است.');
        }
        if (mb_strlen($next, 'UTF-8') < 6) {
            throw new RuntimeException('رمز جدید دست‌کم ۶ نویسه باشد.');
        }
        if ($next !== $confirm) {
            throw new RuntimeException('تکرار رمز جدید یکی نیست.');
        }
        $adminRepo->setPassword($next);
        AdminAuth::flash('رمز با موفقیت عوض شد.');
    } catch (RuntimeException $e) {
        AdminAuth::flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('[cafe-admin] password error: ' . $e->getMessage());
        AdminAuth::flash('خطای پایگاه داده.', 'error');
    }

    header('Location: password.php');
    exit;
}

admin_head('تغییر رمز', 'password');
admin_flash_html();
?>
<h1 class="admin-title">تغییر رمز مدیریت</h1>
<p class="admin-subtitle">رمز دست‌کم ۶ نویسه باشد و جایی یادداشتش کن.</p>

<section class="panel" aria-labelledby="passwordform-title">
  <div class="panel__head">
    <h2 class="panel__title" id="passwordform-title">رمز جدید</h2>
  </div>
  <form method="post" action="password.php">
    <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
    <div class="form-grid">
      <div class="field">
        <label for="current">رمز فعلی</label>
        <input type="password" id="current" name="current" autocomplete="current-password" required>
      </div>
      <div class="field">
        <label for="next">رمز جدید</label>
        <input type="password" id="next" name="next" autocomplete="new-password" required>
      </div>
      <div class="field">
        <label for="confirm">تکرار رمز جدید</label>
        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required>
      </div>
      <div>
        <button class="btn" type="submit">ذخیره رمز جدید</button>
      </div>
    </div>
  </form>
</section>
<?php
admin_foot();

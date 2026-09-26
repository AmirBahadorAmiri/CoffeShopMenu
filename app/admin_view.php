<?php

declare(strict_types=1);

function admin_head(string $title, string $current): void
{
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="robots" content="noindex, nofollow">
      <title><?= menu_escape($title) ?> | مدیریت منوی کافه</title>
      <link rel="stylesheet" href="../assets/css/admin.css?v=1">
    </head>
    <body>
    <div class="admin-shell">
      <header class="admin-topbar">
        <div class="admin-topbar__inner">
          <a class="admin-brand" href="index.php">
            <span class="admin-brand__mark" aria-hidden="true">
              <svg width="24" height="24" viewBox="0 0 34 34" fill="none" role="presentation">
                <path d="M7 12h16v9a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6v-9Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" />
                <path d="M23 14h2.5a3.5 3.5 0 0 1 0 7H23" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
                <path d="M12 6c0 2 2 2 2 4M18 6c0 2 2 2 2 4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
              </svg>
            </span>
            مدیریت منو
          </a>
          <nav class="admin-nav" aria-label="ناوبری مدیریت">
            <a class="admin-nav__link" href="index.php"<?= $current === 'dashboard' ? ' aria-current="page"' : '' ?>>داشبورد</a>
            <a class="admin-nav__link" href="categories.php"<?= $current === 'categories' ? ' aria-current="page"' : '' ?>>دسته‌ها</a>
            <a class="admin-nav__link" href="products.php"<?= $current === 'products' ? ' aria-current="page"' : '' ?>>اقلام و قیمت‌ها</a>
            <a class="admin-nav__link" href="../index.php">مشاهده منو</a>
            <a class="admin-nav__link" href="password.php"<?= $current === 'password' ? ' aria-current="page"' : '' ?>>تغییر رمز</a>
            <form class="inline-form" method="post" action="logout.php">
              <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
              <button class="admin-nav__link admin-nav__link--ghost" type="submit" style="background:none;border:1px solid currentColor;cursor:pointer;font:inherit">خروج</button>
            </form>
          </nav>
        </div>
      </header>
      <main class="admin-main">
    <?php
}

function admin_number(int $value): string
{
    static $formatter = null;
    if ($formatter === null && class_exists(NumberFormatter::class)) {
        $formatter = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
    }

    if ($formatter instanceof NumberFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted)) {
            return $formatted;
        }
    }

    return str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        (string) $value
    );
}

function admin_flash_html(): void
{
    $flash = AdminAuth::getFlash();
    if ($flash === null) {
        return;
    }

    $type = ($flash['type'] ?? '') === 'error' ? 'flash--error' : 'flash--ok';
    ?>
    <div class="flash <?= $type ?>" role="status"><?= menu_escape((string) ($flash['text'] ?? '')) ?></div>
    <?php
}

function admin_foot(): void
{
    ?>
      </main>
      <footer class="admin-footer">پنل مدیریت منوی کافه — تغییرات بلافاصله روی تابلوی قیمت اعمال می‌شود.</footer>
    </div>
    </body>
    </html>
    <?php
}

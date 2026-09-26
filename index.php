<?php

declare(strict_types=1);

$menu = null;
$menuError = null;

try {
    $menu = require __DIR__ . '/app/bootstrap.php';
} catch (MenuDataException $error) {
    $menuError = $error->getMessage();
} catch (Throwable $error) {
    error_log('[cafe-menu] page error: ' . $error->getMessage());
    $menuError = 'در حال حاضر امکان خواندن منو وجود ندارد.';
}

$categories = [];
if (is_array($menu) && isset($menu['categories']) && is_array($menu['categories'])) {
    $categories = $menu['categories'];
}

$generatedAt = (is_array($menu) && isset($menu['generated_at']) && is_string($menu['generated_at']))
    ? $menu['generated_at']
    : null;

function menu_format_count(int $value): string
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

    return menu_to_persian_digits((string) $value);
}

function menu_format_clock(?string $generatedAt): string
{
    if ($generatedAt === null || $generatedAt === '') {
        return '';
    }

    try {
        $moment = new DateTimeImmutable($generatedAt);
    } catch (Throwable $error) {
        return '';
    }

    return menu_to_persian_digits($moment->format('H:i'));
}

function menu_to_persian_digits(string $value): string
{
    return str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        $value
    );
}

function menu_product_price(array $product): string
{
    $available = isset($product['is_available']) && $product['is_available'] === true;
    if (!$available) {
        return 'ناموجود';
    }

    $price = $product['price_toman'] ?? null;
    return format_toman(is_int($price) ? $price : null);
}

$statusState = 'live';
if ($menuError !== null) {
    $statusState = 'error';
} elseif ($categories === []) {
    $statusState = 'checking';
}

$statusText = 'متصل به منوی زنده';
if ($statusState === 'checking') {
    $statusText = 'در حال بررسی منو';
} elseif ($statusState === 'error') {
    $statusText = 'خطا در خواندن منو';
}

$clock = menu_format_clock($generatedAt);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="تابلوی زنده قیمت های کافی شاپ؛ فقط برای مشاهده قیمت ها.">
  <title>منوی زنده کافی شاپ</title>
  <link rel="preload" href="assets/fonts/vazirmatn-arabic-400.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/vazirmatn-arabic-700.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/css/menu.css?v=3">
</head>
<body>
  <a class="skip-link" href="#menu-main">پرش به محتوای منو</a>
  <header class="site-header">
    <div class="container site-header__grid">
      <div class="brand">
        <span class="brand__mark" aria-hidden="true">
          <svg width="34" height="34" viewBox="0 0 34 34" fill="none" role="presentation">
            <path d="M7 12h16v9a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6v-9Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round" />
            <path d="M23 14h2.5a3.5 3.5 0 0 1 0 7H23" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
            <path d="M12 6c0 2 2 2 2 4M18 6c0 2 2 2 2 4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <h1 class="brand__title">منوی کافی شاپ</h1>
          <p class="brand__subtitle">تابلوی زنده قیمت ها</p>
        </div>
      </div>
      <div>
      </div>
    </div>
  </header>

  <?php if ($categories !== []) : ?>
    <nav class="category-nav" aria-label="دسته های منو">
      <div class="container">
        <ul class="category-nav__list" id="category-nav-list">
          <?php foreach ($categories as $category) : ?>
            <?php $anchor = menu_anchor_id((string) ($category['slug'] ?? '')); ?>
            <li><a class="category-nav__link" href="#<?= menu_escape($anchor) ?>"><?= menu_escape((string) ($category['name'] ?? '')) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  <?php endif; ?>

  <main id="menu-main" class="page-main" data-menu-endpoint="api/menu.php" data-generated-at="<?= menu_escape((string) ($generatedAt ?? '')) ?>">
    <div class="container">
      <?php if ($menuError !== null) : ?>
        <div class="notice notice--error" id="menu-error" role="alert">
          <h2 class="notice__title">خطا در خواندن منو</h2>
          <p><?= menu_escape($menuError) ?> آخرین قیمت های موفق همچنان نمایش داده می شود.</p>
        </div>
      <?php else : ?>
        <div class="notice notice--error" id="menu-error" role="alert" hidden></div>
      <?php endif; ?>

      <?php if ($categories === [] && $menuError === null) : ?>
        <div class="notice" id="menu-empty" role="status">
          <h2 class="notice__title">منویی ثبت نشده است</h2>
          <p>هنوز هیچ دسته فعالی در پایگاه داده وجود ندارد.</p>
        </div>
      <?php else : ?>
        <div class="notice" id="menu-empty" role="status" hidden></div>
      <?php endif; ?>

      <div class="menu-grid" id="menu-grid">
        <?php foreach ($categories as $categoryIndex => $category) : ?>
          <?php
          $anchor = menu_anchor_id((string) ($category['slug'] ?? ''));
          $products = (isset($category['products']) && is_array($category['products'])) ? $category['products'] : [];
          $titleId = 'category-title-' . $categoryIndex;
          ?>
          <section class="menu-section" id="<?= menu_escape($anchor) ?>" aria-labelledby="<?= menu_escape($titleId) ?>">
            <div class="menu-section__head">
              <h2 class="menu-section__title" id="<?= menu_escape($titleId) ?>"><?= menu_escape((string) ($category['name'] ?? '')) ?></h2>
              <span class="menu-section__count"><?= menu_escape(menu_format_count(count($products)) . ' قلم') ?></span>
            </div>
            <ul class="menu-list">
              <?php foreach ($products as $product) : ?>
                <?php
                $available = isset($product['is_available']) && $product['is_available'] === true;
                $description = isset($product['description']) && is_string($product['description']) ? trim($product['description']) : '';
                ?>
                <li class="menu-item<?= $available ? '' : ' menu-item--unavailable' ?>">
                  <span class="menu-item__name"><?= menu_escape((string) ($product['name'] ?? '')) ?></span>
                  <span class="menu-item__leader" aria-hidden="true"></span>
                  <span class="menu-item__price"><?= menu_escape(menu_product_price($product)) ?></span>
                  <?php if ($description !== '') : ?>
                    <p class="menu-item__description"><?= menu_escape($description) ?></p>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <footer class="page-footer">
    <div class="container">
      <p>قیمت ها به تومان است. برای تغییر قیمت ها، رکورد مربوطه در پایگاه داده کافه به روز می شود.</p>
    </div>
  </footer>

  <script src="assets/js/menu.js" defer></script>
</body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';
require_once __DIR__ . '/../app/admin_view.php';

AdminAuth::requireLogin('login.php');

$stats = $adminRepo->stats();
$unavailable = $adminRepo->unavailableProducts();
$recent = $adminRepo->recentlyUpdated(8);

admin_head('داشبورد', 'dashboard');
admin_flash_html();
?>
<h1 class="admin-title">داشبورد</h1>
<p class="admin-subtitle">نمای زنده وضعیت منوی کافه — هر تغییری بلافاصله روی تابلوی قیمت دیده می‌شود.</p>

<div class="stat-grid">
  <div class="stat-card stat-card--hero">
    <div class="stat-card__number"><?= menu_escape(admin_number($stats['products'])) ?></div>
    <p class="stat-card__label">کل اقلام منو</p>
  </div>
  <div class="stat-card">
    <div class="stat-card__number"><?= menu_escape(admin_number($stats['available'])) ?></div>
    <p class="stat-card__label">موجود</p>
  </div>
  <div class="stat-card">
    <div class="stat-card__number"><?= menu_escape(admin_number($stats['unavailable'])) ?></div>
    <p class="stat-card__label">ناموجود</p>
  </div>
  <div class="stat-card">
    <div class="stat-card__number"><?= menu_escape(admin_number($stats['active_categories'])) ?></div>
    <p class="stat-card__label">دسته فعال</p>
  </div>
</div>

<div class="panel-grid panel-grid--two">
  <section class="panel" aria-labelledby="unavailable-title">
    <div class="panel__head">
      <h2 class="panel__title" id="unavailable-title">اقلام ناموجود</h2>
      <a class="panel__link" href="products.php">مدیریت اقلام</a>
    </div>
    <?php if ($unavailable === []) : ?>
      <p class="empty-note">همه اقلام موجودند.</p>
    <?php else : ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>قلم</th><th>دسته</th><th>عملیات</th></tr>
          </thead>
          <tbody>
            <?php foreach ($unavailable as $item) : ?>
              <tr>
                <td><?= menu_escape((string) $item['name']) ?></td>
                <td><?= menu_escape((string) $item['category_name']) ?></td>
                <td>
                  <form class="inline-form" method="post" action="products.php">
                    <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <button class="btn btn--small" type="submit">موجود کن</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="panel" aria-labelledby="recent-title">
    <div class="panel__head">
      <h2 class="panel__title" id="recent-title">آخرین تغییرات قیمت</h2>
      <a class="panel__link" href="products.php">همه اقلام</a>
    </div>
    <?php if ($recent === []) : ?>
      <p class="empty-note">هنوز قلمی ثبت نشده است.</p>
    <?php else : ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>قلم</th><th>قیمت</th><th>وضعیت</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $item) : ?>
              <tr>
                <td><?= menu_escape((string) $item['name']) ?><br><small><?= menu_escape((string) $item['category_name']) ?></small></td>
                <td class="num"><?= menu_escape(format_toman($item['price_toman'] === null ? null : (int) $item['price_toman'])) ?></td>
                <td>
                  <?php if ((int) $item['is_available'] === 1) : ?>
                    <span class="badge badge--ok">موجود</span>
                  <?php else : ?>
                    <span class="badge badge--warn">ناموجود</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php
admin_foot();

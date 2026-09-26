<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';
require_once __DIR__ . '/../app/admin_view.php';

AdminAuth::requireLogin('login.php');

$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
    if (!AdminAuth::validateCsrf($token)) {
        AdminAuth::flash('نشست منقضی شده؛ دوباره تلاش کن.', 'error');
        header('Location: categories.php');
        exit;
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    try {
        if ($action === 'save') {
            $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
            $slug = isset($_POST['slug']) && is_string($_POST['slug']) ? AdminRepository::cleanSlug($_POST['slug']) : '';
            $sort = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
            $active = isset($_POST['is_active']);

            if ($name === '') {
                throw new RuntimeException('نام دسته خالی است.');
            }
            if ($sort < 0 || $sort > 9999) {
                throw new RuntimeException('ترتیب باید بین ۰ تا ۹۹۹۹ باشد.');
            }
            if ($slug !== '' && $adminRepo->categorySlugExists($slug, $id)) {
                throw new RuntimeException('این نامک تکراری است.');
            }

            if ($id > 0) {
                $adminRepo->updateCategory($id, $name, $slug === '' ? 'cat-' . $id : $slug, $sort, $active);
                AdminAuth::flash('دسته به‌روز شد.');
            } else {
                $adminRepo->createCategory($name, $slug, $sort, $active);
                AdminAuth::flash('دسته جدید ساخته شد.');
            }
        } elseif ($action === 'toggle' && $id > 0) {
            $adminRepo->toggleCategoryActive($id);
            AdminAuth::flash('وضعیت نمایش دسته عوض شد.');
        } elseif ($action === 'delete' && $id > 0) {
            if ($adminRepo->categoryProductCount($id) > 0) {
                throw new RuntimeException('این دسته قلم دارد؛ اول اقلامش را جابه‌جا یا حذف کن.');
            }
            $adminRepo->deleteCategory($id);
            AdminAuth::flash('دسته حذف شد.');
        }
    } catch (RuntimeException $e) {
        AdminAuth::flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('[cafe-admin] categories error: ' . $e->getMessage());
        AdminAuth::flash('خطای پایگاه داده.', 'error');
    }

    header('Location: categories.php');
    exit;
}

if (isset($_GET['edit'])) {
    $editing = $adminRepo->getCategory((int) $_GET['edit']);
}

$categories = $adminRepo->listCategories();

admin_head('دسته‌ها', 'categories');
admin_flash_html();
?>
<h1 class="admin-title">مدیریت دسته‌ها</h1>
<p class="admin-subtitle">دسته غیرفعال در تابلوی منو نمایش داده نمی‌شود.</p>

<div class="panel-grid">
  <section class="panel" aria-labelledby="catform-title">
    <div class="panel__head">
      <h2 class="panel__title" id="catform-title"><?= $editing === null ? 'دسته جدید' : 'ویرایش دسته' ?></h2>
      <?php if ($editing !== null) : ?>
        <a class="panel__link" href="categories.php">انصراف</a>
      <?php endif; ?>
    </div>
    <form method="post" action="categories.php">
      <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing === null ? 0 : (int) $editing['id'] ?>">
      <div class="form-grid">
        <div class="field">
          <label for="cat-name">نام دسته</label>
          <input type="text" id="cat-name" name="name" required maxlength="100" value="<?= menu_escape($editing === null ? '' : (string) $editing['name']) ?>">
        </div>
        <div class="field">
          <label for="cat-slug">نامک لاتین (اختیاری)</label>
          <input type="text" id="cat-slug" name="slug" maxlength="120" dir="ltr" value="<?= menu_escape($editing === null ? '' : (string) $editing['slug']) ?>">
          <p class="field__hint">خالی بماند خودکار ساخته می‌شود، مثل cat-5</p>
        </div>
        <div class="field">
          <label for="cat-sort">ترتیب نمایش</label>
          <input type="number" id="cat-sort" name="sort_order" min="0" max="9999" value="<?= $editing === null ? 0 : (int) $editing['sort_order'] ?>">
        </div>
        <div class="field field--check">
          <input type="checkbox" id="cat-active" name="is_active" value="1"<?= ($editing === null || (int) $editing['is_active'] === 1) ? ' checked' : '' ?>>
          <label for="cat-active">فعال و نمایشی در منو</label>
        </div>
        <div>
          <button class="btn" type="submit"><?= $editing === null ? 'ساخت دسته' : 'ذخیره تغییرات' ?></button>
        </div>
      </div>
    </form>
  </section>

  <section class="panel" aria-labelledby="catlist-title">
    <div class="panel__head">
      <h2 class="panel__title" id="catlist-title">فهرست دسته‌ها</h2>
    </div>
    <?php if ($categories === []) : ?>
      <p class="empty-note">هنوز دسته‌ای ثبت نشده است.</p>
    <?php else : ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>نام</th><th>اقلام</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $category) : ?>
              <tr>
                <td><?= menu_escape((string) $category['name']) ?></td>
                <td class="num"><?= menu_escape(admin_number((int) $category['product_count'])) ?></td>
                <td class="num"><?= menu_escape(admin_number((int) $category['sort_order'])) ?></td>
                <td>
                  <?php if ((int) $category['is_active'] === 1) : ?>
                    <span class="badge badge--ok">فعال</span>
                  <?php else : ?>
                    <span class="badge badge--muted">غیرفعال</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-row">
                    <a class="btn btn--small btn--ghost" href="categories.php?edit=<?= (int) $category['id'] ?>">ویرایش</a>
                    <form class="inline-form" method="post" action="categories.php">
                      <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                      <button class="btn btn--small btn--ghost" type="submit">تغییر وضعیت</button>
                    </form>
                    <form class="inline-form" method="post" action="categories.php" onsubmit="return confirm('دسته حذف شود؟');">
                      <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                      <button class="btn btn--small btn--danger" type="submit">حذف</button>
                    </form>
                  </div>
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

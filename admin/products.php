<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/admin_bootstrap.php';
require_once __DIR__ . '/../app/admin_view.php';

AdminAuth::requireLogin('login.php');

$editing = null;
$filter = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
    if (!AdminAuth::validateCsrf($token)) {
        AdminAuth::flash('نشست منقضی شده؛ دوباره تلاش کن.', 'error');
        header('Location: products.php');
        exit;
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    try {
        if ($action === 'save') {
            $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
            $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
            $slug = isset($_POST['slug']) && is_string($_POST['slug']) ? AdminRepository::cleanSlug($_POST['slug']) : '';
            $description = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
            $priceRaw = isset($_POST['price_toman']) && is_string($_POST['price_toman']) ? trim($_POST['price_toman']) : '';
            $sort = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
            $available = isset($_POST['is_available']);

            if ($adminRepo->getCategory($categoryId) === null) {
                throw new RuntimeException('دسته معتبر انتخاب کن.');
            }
            if ($name === '') {
                throw new RuntimeException('نام قلم خالی است.');
            }
            if (mb_strlen($name, 'UTF-8') > 120) {
                throw new RuntimeException('نام قلم خیلی بلند است.');
            }

            $price = null;
            if ($priceRaw !== '') {
                if (!preg_match('/^\d{1,9}$/', $priceRaw)) {
                    throw new RuntimeException('قیمت باید عدد صحیح مثبت باشد.');
                }
                $price = (int) $priceRaw;
                if ($price <= 0) {
                    throw new RuntimeException('قیمت باید بیشتر از صفر باشد.');
                }
            }
            if (!$available) {
                $price = null;
            }
            if ($sort < 0 || $sort > 9999) {
                throw new RuntimeException('ترتیب باید بین ۰ تا ۹۹۹۹ باشد.');
            }
            if ($slug !== '' && $adminRepo->productSlugExists($categoryId, $slug, $id)) {
                throw new RuntimeException('این نامک در این دسته تکراری است.');
            }

            $descriptionValue = $description === '' ? null : $description;

            if ($id > 0) {
                $adminRepo->updateProduct(
                    $id, $categoryId, $name, $slug === '' ? 'item-' . $id : $slug,
                    $descriptionValue, $price, $available, $sort
                );
                AdminAuth::flash('قلم به‌روز شد.');
            } else {
                $adminRepo->createProduct($categoryId, $name, $slug, $descriptionValue, $price, $available, $sort);
                AdminAuth::flash('قلم جدید ساخته شد.');
            }
        } elseif ($action === 'toggle' && $id > 0) {
            $adminRepo->toggleAvailability($id);
            AdminAuth::flash('وضعیت موجودی عوض شد.');
        } elseif ($action === 'delete' && $id > 0) {
            $adminRepo->deleteProduct($id);
            AdminAuth::flash('قلم حذف شد.');
        }
    } catch (RuntimeException $e) {
        AdminAuth::flash($e->getMessage(), 'error');
    } catch (Throwable $e) {
        error_log('[cafe-admin] products error: ' . $e->getMessage());
        AdminAuth::flash('خطای پایگاه داده.', 'error');
    }

    $redirect = 'products.php';
    if ($filter !== null) {
        $redirect .= '?category=' . $filter;
    }
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['edit'])) {
    $editing = $adminRepo->getProduct((int) $_GET['edit']);
}

$categories = $adminRepo->listCategories();
$products = $adminRepo->listProducts($filter);

admin_head('اقلام و قیمت‌ها', 'products');
admin_flash_html();
?>
<h1 class="admin-title">اقلام و قیمت‌ها</h1>
<p class="admin-subtitle">قیمت به تومان است. برداشتن تیک «موجود» یعنی نمایش «ناموجود» در تابلو.</p>

<div class="panel-grid">
  <section class="panel" aria-labelledby="productform-title">
    <div class="panel__head">
      <h2 class="panel__title" id="productform-title"><?= $editing === null ? 'قلم جدید' : 'ویرایش قلم' ?></h2>
      <?php if ($editing !== null) : ?>
        <a class="panel__link" href="products.php<?= $filter !== null ? '?category=' . $filter : '' ?>">انصراف</a>
      <?php endif; ?>
    </div>
    <?php if ($categories === []) : ?>
      <p class="empty-note">اول از صفحه دسته‌ها یک دسته بساز.</p>
    <?php else : ?>
      <form method="post" action="products.php<?= $filter !== null ? '?category=' . $filter : '' ?>">
        <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editing === null ? 0 : (int) $editing['id'] ?>">
        <div class="form-grid">
          <div class="field">
            <label for="product-category">دسته</label>
            <select id="product-category" name="category_id" required>
              <?php
              $selectedCategory = $editing === null ? $filter : (int) $editing['category_id'];
              foreach ($categories as $category) :
              ?>
                <option value="<?= (int) $category['id'] ?>"<?= ((int) $category['id'] === (int) $selectedCategory) ? ' selected' : '' ?>>
                  <?= menu_escape((string) $category['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="product-name">نام قلم</label>
            <input type="text" id="product-name" name="name" required maxlength="120" value="<?= menu_escape($editing === null ? '' : (string) $editing['name']) ?>">
          </div>
          <div class="field">
            <label for="product-price">قیمت (تومان)</label>
            <input type="number" id="product-price" name="price_toman" min="1" max="999999999" inputmode="numeric" dir="ltr" value="<?= $editing === null || $editing['price_toman'] === null ? '' : (int) $editing['price_toman'] ?>">
            <p class="field__hint">برای «ناموجود» خالی بگذار یا تیک موجودی را بردار.</p>
          </div>
          <div class="field">
            <label for="product-sort">ترتیب نمایش</label>
            <input type="number" id="product-sort" name="sort_order" min="0" max="9999" value="<?= $editing === null ? 0 : (int) $editing['sort_order'] ?>">
          </div>
          <div class="field">
            <label for="product-slug">نامک لاتین (اختیاری)</label>
            <input type="text" id="product-slug" name="slug" maxlength="140" dir="ltr" value="<?= menu_escape($editing === null ? '' : (string) $editing['slug']) ?>">
          </div>
          <div class="field field--check">
            <input type="checkbox" id="product-available" name="is_available" value="1"<?= ($editing === null || (int) $editing['is_available'] === 1) ? ' checked' : '' ?>>
            <label for="product-available">موجود است</label>
          </div>
          <div class="field">
            <label for="product-description">توضیح (اختیاری)</label>
            <textarea id="product-description" name="description" maxlength="255"><?= menu_escape($editing === null || $editing['description'] === null ? '' : (string) $editing['description']) ?></textarea>
          </div>
          <div>
            <button class="btn" type="submit"><?= $editing === null ? 'ساخت قلم' : 'ذخیره تغییرات' ?></button>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <section class="panel" aria-labelledby="productlist-title">
    <div class="panel__head">
      <h2 class="panel__title" id="productlist-title">فهرست اقلام</h2>
    </div>
    <form method="get" action="products.php" style="margin-bottom:var(--space-4)">
      <div class="field">
        <label for="filter-category">فیلتر دسته</label>
        <select id="filter-category" name="category" onchange="this.form.submit()">
          <option value="">همه دسته‌ها</option>
          <?php foreach ($categories as $category) : ?>
            <option value="<?= (int) $category['id'] ?>"<?= ($filter !== null && (int) $category['id'] === $filter) ? ' selected' : '' ?>>
              <?= menu_escape((string) $category['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
    <?php if ($products === []) : ?>
      <p class="empty-note">قلمی در این دسته نیست.</p>
    <?php else : ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>قلم</th><th>قیمت</th><th>ترتیب</th><th>وضعیت</th><th>عملیات</th></tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product) : ?>
              <tr>
                <td>
                  <?= menu_escape((string) $product['name']) ?><br>
                  <small><?= menu_escape((string) $product['category_name']) ?></small>
                </td>
                <td class="num"><?= menu_escape(format_toman($product['price_toman'] === null ? null : (int) $product['price_toman'])) ?></td>
                <td class="num"><?= menu_escape(admin_number((int) $product['sort_order'])) ?></td>
                <td>
                  <?php if ((int) $product['is_available'] === 1) : ?>
                    <span class="badge badge--ok">موجود</span>
                  <?php else : ?>
                    <span class="badge badge--warn">ناموجود</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-row">
                    <a class="btn btn--small btn--ghost" href="products.php?<?= $filter !== null ? 'category=' . $filter . '&amp;' : '' ?>edit=<?= (int) $product['id'] ?>">ویرایش</a>
                    <form class="inline-form" method="post" action="products.php<?= $filter !== null ? '?category=' . $filter : '' ?>">
                      <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                      <button class="btn btn--small btn--ghost" type="submit">تغییر وضعیت</button>
                    </form>
                    <form class="inline-form" method="post" action="products.php<?= $filter !== null ? '?category=' . $filter : '' ?>" onsubmit="return confirm('این قلم حذف شود؟');">
                      <input type="hidden" name="csrf" value="<?= menu_escape(AdminAuth::csrfToken()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
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

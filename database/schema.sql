CREATE DATABASE IF NOT EXISTS cafe_menu
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cafe_menu;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY ix_categories_active_sort (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  description VARCHAR(255) NULL,
  price_toman INT UNSIGNED NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_category_slug (category_id, slug),
  KEY ix_products_visible_sort (category_id, is_available, sort_order, id),
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id)
    REFERENCES categories (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_products_price_positive
    CHECK (price_toman IS NULL OR price_toman > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (id, name, slug, sort_order, is_active)
VALUES
  (1, 'نوشیدنی‌های گرم', 'hot-drinks', 1, 1),
  (2, 'نوشیدنی‌های سرد', 'cold-drinks', 2, 1),
  (3, 'دسر و کیک', 'desserts', 3, 1),
  (4, 'صبحانه و میان‌وعده', 'breakfast', 4, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active);

INSERT INTO products (id, category_id, name, slug, description, price_toman, is_available, sort_order)
VALUES
  (1, 1, 'اسپرسو تک', 'single-espresso', 'عصاره غلیظ قهوه عربیکا', 65000, 1, 1),
  (2, 1, 'اسپرسو دبل', 'double-espresso', 'دو شات اسپرسو با کرمای غلیظ', 85000, 1, 2),
  (3, 1, 'آمریکانو', 'americano', 'اسپرسو رقیق شده با آب داغ', 95000, 1, 3),
  (4, 1, 'لاته', 'latte', 'اسپرسو با شیر بخار داده شده', 120000, 1, 4),
  (5, 1, 'کاپوچینو', 'cappuccino', 'ترکیب کلاسیک اسپرسو، شیر و فوم', 115000, 1, 5),
  (6, 1, 'موکا', 'mocha', 'اسپرسو با شکلات و شیر', 135000, 1, 6),
  (7, 1, 'هات چاکلت', 'hot-chocolate', 'شکلات داغ غلیظ و خامه‌ای', 125000, 1, 7),
  (8, 1, 'چای ماسالا', 'masala-chai', 'چای ادویه‌ای گرم با شیر', 110000, 1, 8),
  (9, 2, 'آیس لاته', 'iced-latte', 'اسپرسو سرد با شیر و یخ', 130000, 1, 1),
  (10, 2, 'آیس آمریکانو', 'iced-americano', 'اسپرسو سرد رقیق شده با آب و یخ', 105000, 1, 2),
  (11, 2, 'آیس موکا', 'iced-mocha', 'اسپرسو سرد با شکلات و شیر', 145000, 1, 3),
  (12, 2, 'فراپه وانیل', 'vanilla-frappe', 'نوشیدنی یخی میکس شده با وانیل', 150000, 1, 4),
  (13, 2, 'لیموناد نعناع', 'mint-lemonade', 'لیمو تازه با نعناع و یخ', 95000, 1, 5),
  (14, 2, 'موهیتو کلاسیک', 'classic-mojito', 'نعناع، لیمو و سودا', 110000, 1, 6),
  (15, 2, 'اسموتی توت‌فرنگی', 'strawberry-smoothie', 'توت‌فرنگی تازه با شیر', 140000, 1, 7),
  (16, 2, 'شیک شکلات', 'chocolate-shake', 'شیر و شکلات میکس شده با بستنی', 155000, 1, 8),
  (17, 3, 'کیک شکلاتی', 'chocolate-cake', 'برش تازه با سس شکلات', 95000, 1, 1),
  (18, 3, 'چیزکیک نیویورکی', 'new-york-cheesecake', 'بافت خامه‌ای با پایه بیسکوییت', 120000, 1, 2),
  (19, 3, 'تیرامیسو', 'tiramisu', 'دسر ایتالیایی با قهوه و ماسکارپونه', NULL, 0, 3),
  (20, 4, 'کروسان کره‌ای', 'butter-croissant', 'کروسان تازه روزانه', 75000, 1, 1),
  (21, 4, 'املت مخصوص', 'special-omelette', 'تخم‌مرغ با قارچ و پنیر', 145000, 1, 2),
  (22, 4, 'پنکیک عسل', 'honey-pancake', 'پنکیک نرم با عسل طبیعی', 135000, 1, 3)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  description = VALUES(description),
  price_toman = VALUES(price_toman),
  is_available = VALUES(is_available),
  sort_order = VALUES(sort_order);

CREATE TABLE IF NOT EXISTS settings (
  name VARCHAR(100) NOT NULL,
  value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (name, value)
VALUES ('admin_password_hash', '$2y$10$.NVD/6YPSs6u9JesMvogLOTGWWbaE6o1Zslio7ZrBI9H/gwfK8ZIO');

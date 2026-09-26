# ☕ CoffeShopMenu

> 🌍 [English version](./README.md)

یک **تابلو قیمت زنده کافه** — منوی فارسی راست‌به‌چپ که روی سرور رندر می‌شود و هر ۳۰ ثانیه خودش را بروز می‌کند، به‌همراه یک پنل مدیریت محافظت‌شده با رمز عبور برای ویرایش دسته‌بندی‌ها، محصولات و قیمت‌ها. بدون هیچ فریم‌ورک، بدون npm و بدون مرحله build: فقط PHP 8 خالص + PDO/MySQL و CSS دست‌نویس.

[![پلتفرم](https://img.shields.io/badge/platform-Apache%20%2F%20XAMPP-E8730C?style=flat-square&logo=apache&logoColor=white)](https://www.apache.org/)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%20%7C%20MariaDB%2010.4-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![زبان](https://img.shields.io/badge/language-PHP-777BB4?style=flat-square&logo=php&logoColor=white)](#-تکنولوژیها)
[![مجوز](https://img.shields.io/badge/license-MIT-1E3932?style=flat-square)](./LICENSE)

<p align="center">
  <img src="./docs/menu-board.png" alt="تابلوی زنده قیمت کافه — چهار دسته‌بندی با قیمت به تومان" width="820">
</p>

## 📥 کلون

> **[⬇️ دریافت سورس از گیت‌هاب](https://github.com/AmirBahadorAmiri/CoffeShopMenu)**

```bash
git clone https://github.com/AmirBahadorAmiri/CoffeShopMenu.git
```

## ✨ امکانات

### ☕ تابلو قیمت زنده

- تابلوی راست‌به‌چپ که سمت سرور رندر می‌شود (`index.php`) — کل منو حتی با جاوااسکریپت غیرفعال هم خوانا است
- ناوبری پرش به دسته‌بندی، به‌صورت خودکار از روی همهٔ دسته‌بندی‌های فعال ساخته می‌شود، همراه با لینک‌های لنگر
- قیمت‌ها به **تومان** و با ارقام فارسی از طریق `NumberFormatter('fa_IR')` (`format_toman()`, `menu_to_persian_digits()`)
- اقلام ناموجود کم‌رنگ می‌شوند و به‌جای نمایش قیمت، برچسب «ناموجود» می‌گیرند
- تعداد اقلام هر دسته‌بندی و ساعت آخرین بروزرسانی به وقت `Asia/Tehran`
- فونت Vazirmatn به‌صورت `woff2` (400/700) روی هاست خودمان پیش‌بارگذاری شده — بدون Google Fonts، بدون CDN

### 🔄 بروزرسانی خودکار

- `assets/js/menu.js` هر **۳۰ ثانیه** یک‌بار `api/menu.php` را با `cache: "no-store"` صدا می‌زند
- فقط وقتی `generated_at` تغییر کند دوباره رندر می‌کند، بنابراین قیمت‌ها موقع خواندن هرگز چشمک نمی‌زنند یا جابه‌جا نمی‌شوند
- تا وقتی تب پنهان است متوقف می‌شود، بعد با فوکوس گرفتن یا رویداد `online` فوراً بروز می‌شود و وضعیت `offline` را نشان می‌دهد
- کاهش نرم خطا: اگر دیتابیس دچار مشکل شود، آخرین قیمت‌های موفق پشت یک پیام ملایم روی صفحه باقی می‌مانند

### 🔐 پنل مدیریت (`/admin/`)

- ورود با یک رمز عبور — `password_verify()` همراه با `password_needs_rehash()` خودکار در هر بار ورود
- سخت‌سازی نشست در `AdminAuth::start()`: `HttpOnly`، `SameSite=Lax`، `Secure` روی HTTPS، `session_regenerate_id(true)` و **مهلت بی‌کاری ۱۲۰ دقیقه‌ای** از `config/admin.php`
- **توکن CSRF روی هر فرم تغییردهنده** — `AdminAuth::csrfToken()` / `AdminAuth::validateCsrf()` که با `hash_equals()` مقایسه می‌شوند
- داشبورد با آمار زنده (اقلام / موجود / ناموجود / دسته‌بندی‌های فعال)، **بازگردانی یک‌کلیکی اقلام ناموجود** و ۸ تغییر آخر قیمت
- CRUD دسته‌بندی: نام، `slug` یکتا، ترتیب نمایش، کلید فعال/غیرفعال
- CRUD محصول: توضیح، قیمت به تومان، کلید موجودی و فیلتر بر اساس دسته‌بندی (`?category=`)
- صفحهٔ تغییر رمز که رمز فعلی را می‌خواهد و حداقل ۶ کاراکتر لازم دارد
- `noindex, nofollow` روی همهٔ صفحات مدیریت، به‌علاوهٔ `Options -Indexes`

### 🔌 API جیسون

`GET api/menu.php` دقیقاً همان چیزی را برمی‌گرداند که تابلو رندر می‌کند:

```json
{
  "ok": true,
  "currency": "تومان",
  "generated_at": "2026-09-26T18:04:00+03:30",
  "categories": [
    {
      "id": 1,
      "name": "نوشیدنی‌های گرم",
      "slug": "hot-drinks",
      "products": [
        { "id": 1, "name": "اسپرسو تک", "description": "عصاره غلیظ قهوه عربیکا", "price_toman": 65000, "is_available": true }
      ]
    }
  ]
}
```

- برای هر چیزی غیر از `GET` کد `405`، در صورت خطای دیتابیس `503`، هدرهای کش `no-store` و `JSON_UNESCAPED_UNICODE`

### 🗄 لایه داده

- `Database::connect()` — PDO با `ERRMODE_EXCEPTION`، `FETCH_ASSOC` و **prepared statement بومی** (`EMULATE_PREPARES => false`)
- `MenuRepository::getMenu()` — کل تابلو در **۲ کوئری** (دسته‌بندی‌ها، بعد محصولات)، هرگز N+1
- `AdminRepository` — CRUD کاملاً پارامتری با `bindValue()` آگاه به integer و null
- InnoDB + `utf8mb4_unicode_ci`، کلید خارجی `ON DELETE RESTRICT`، قید `CHECK (price_toman > 0)` و ایندکس‌های پوششی برای همان مسیر خواندن
- `database/schema.sql` دیتابیس را می‌سازد **و** ۴ دسته‌بندی / ۲۲ قلم را seed می‌کند
- تنظیمات اتصال از متغیرهای محیطی `CAFE_DB_*` با مقادیر پیش‌فرض سازگار با XAMPP خوانده می‌شوند

### 🎨 طراحی

- CSS دست‌نویس بر پایهٔ custom property — کل پالت و مقیاس تایپوگرافی در [`DESIGN.md`](./DESIGN.md) مستند شده است
- پس‌زمینهٔ کرم گرم، رنگ برند سبز تیره، و **خطّ نقطه‌چین دفتری** که هر قلم را به قیمتش وصل می‌کند
- `dir="rtl"`، لینک پرش، بخش‌های `aria-labelledby` و حلقهٔ فوکوس قابل مشاهده
- بدون فریم‌ورک، بدون باندلر و بدون هیچ وابستگی زمان اجرا

### 🛡 سخت‌سازی امنیت

- `Require all denied` در `app/`، `config/` و `database/` — کد سورس، اطلاعات اتصال و SQL خام هرگز از وب قابل خواندن نیست
- هر مقدار پویا از `menu_escape()` عبور می‌کند (`htmlspecialchars` با `ENT_QUOTES | ENT_SUBSTITUTE`)
- خطاهای دیتابیس با `error_log()` ثبت می‌شوند و هرگز به صفحه نشت نمی‌کنند

## 🛠 تکنولوژی‌ها

| بخش | ابزار |
|---|---|
| زبان | PHP 8.0+ — `declare(strict_types=1)`، ارتقای constructor، بدون فریم‌ورک |
| دیتابیس | MySQL 8.0.16+ / MariaDB 10.4+ — InnoDB، `utf8mb4_unicode_ci` |
| دسترسی به داده | PDO (`pdo_mysql`) با prepared statement بومی |
| وب‌سرور | Apache 2.4 از طریق XAMPP — قوانین منع دسترسی در `.htaccess` |
| فرانت‌اند | CSS خالص + JavaScript نسخهٔ ES2017 — بدون فریم‌ورک، بدون npm، بدون build |
| تایپوگرافی | Vazirmatn، فایل `woff2` روی هاست خودمان (لاتین + عربی) |
| اعداد | `Intl.NumberFormat` / `NumberFormatter` (`fa-IR`) با fallback دستی برای نگاشت ارقام |
| احراز هویت | نشست‌های بومی PHP + `password_hash` / `password_verify` (bcrypt) |
| آیکون‌ها | فقط SVG درون‌خطی |

## 📁 ساختار پروژه

```text
CoffeShopMenu/
├── index.php                 # تابلوی زندهٔ قیمت برای مشتری (راست‌به‌چپ، رندر سمت سرور)
├── api/
│   └── menu.php              # GET -> خروجی جیسون منو
├── app/                      # کتابخانهٔ هسته — دسترسی وب ممنوع
│   ├── bootstrap.php         # کنترلر ورودی عمومی: آرایهٔ منو را می‌سازد
│   ├── admin_bootstrap.php   # کنترلر ورودی مدیریت: نشست + ریپازیتوری
│   ├── Database.php          # کارخانهٔ PDO
│   ├── MenuRepository.php    # مدل خواندن پشت تابلو
│   ├── AdminRepository.php   # CRUD مدیریت + تنظیمات + آمار داشبورد
│   ├── AdminAuth.php         # چرخهٔ عمر نشست، CSRF، پیام‌های فلش
│   ├── menu_helpers.php      # escape، slug لنگرها، قالب‌بندی تومان
│   └── admin_view.php        # چیدمان مدیریت: head / nav / flash / foot
├── admin/                    # داشبورد محافظت‌شده با رمز عبور
│   ├── login.php             # تنها صفحهٔ مدیریتی بدون احراز هویت
│   ├── index.php             # داشبورد: آمار، اقلام ناموجود، آخرین ویرایش‌ها
│   ├── categories.php        # CRUD دسته‌بندی + کلید فعال‌سازی
│   ├── products.php          # CRUD محصول + موجودی + فیلتر دسته‌بندی
│   ├── password.php          # تغییر رمز مدیر
│   └── logout.php
├── assets/
│   ├── css/menu.css          # استایل‌های تابلوی عمومی
│   ├── css/admin.css         # استایل‌های پنل مدیریت
│   ├── js/menu.js            # polling هر ۳۰ ثانیه + رندر شرطی
│   └── fonts/                # Vazirmatn woff2 — لاتین + عربی، 400/700
├── config/                   # دسترسی وب ممنوع
│   ├── database.php          # متغیرهای محیطی CAFE_DB_* با پیش‌فرض XAMPP
│   └── admin.php             # نام نشست + مهلت بی‌کاری
├── database/
│   └── schema.sql            # ساخت دیتابیس، جدول‌ها، ایندکس‌ها، دادهٔ اولیه
├── docs/
│   └── menu-board.png        # اسکرین‌شات تابلوی زنده برای README
├── DESIGN.md                 # سیستم طراحی: پالت، مقیاس تایپوگرافی، قواعد
└── LICENSE                   # MIT
```

نقاط ورود کلیدی:

- **`index.php`** — تابلوی رو به مشتری؛ سمت سرور رندر می‌شود و حتی وقتی دیتابیس قطع است به‌شکل سخت شکست نمی‌خورد
- **`api/menu.php`** — قرارداد جیسونی که `assets/js/menu.js` آن را مصرف می‌کند
- **`admin/login.php`** — تنها درِ عمومی ورود به بخش مدیریت
- **`app/bootstrap.php`** / **`app/admin_bootstrap.php`** — تنها دو نقطهٔ ورود؛ بقیهٔ فایل‌ها از این‌ها include می‌شوند

## 🚀 نصب و اجرا

1. **پیش‌نیازها را بررسی کن** — PHP 8.0+ و MySQL 8.0.16+ (نگاه کنید به [پیش‌نیازها](#-پیشنیازها)).

2. **پروژه را کلون کن:**

   ```bash
   git clone https://github.com/AmirBahadorAmiri/CoffeShopMenu.git
   ```

3. **آن را به ریشهٔ وب منتقل کن** — در XAMPP روی ویندوز:

   ```powershell
   Move-Item .\CoffeShopMenu C:\xampp\htdocs\CoffeShopMenu
   ```

   نام پوشه به بخشی از URL تبدیل می‌شود، بنابراین تابلو در `http://localhost/CoffeShopMenu/` در دسترس خواهد بود.

4. **دیتابیس را بساز و دادهٔ اولیه را وارد کن** — `schema.sql` دیتابیس `cafe_menu`، هر سه جدول و ۴ دسته‌بندی با ۲۲ قلم را می‌سازد:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

   یا محتوای آن را در تب **SQL** پنل phpMyAdmin بچسبان و اجرا کن.

5. **اپلیکیشن را به دیتابیست وصل کن** — اختیاری؛ مقادیر پیش‌فرض از قبل با یک نصب تازهٔ XAMPP می‌خوانند (`127.0.0.1:3306`، `cafe_menu`، `root`، رمز عبور خالی):

   ```powershell
   $env:CAFE_DB_HOST     = "127.0.0.1"
   $env:CAFE_DB_PORT     = "3306"
   $env:CAFE_DB_NAME     = "cafe_menu"
   $env:CAFE_DB_USER     = "root"
   $env:CAFE_DB_PASSWORD = ""
   ```

6. **Apache و MySQL را از پنل کنترل XAMPP اجرا کن**، بعد تابلو را باز کن:

   ```text
   http://localhost/CoffeShopMenu/
   ```

7. **پنل مدیریت را باز کن** و وارد شو:

   ```text
   http://localhost/CoffeShopMenu/admin/
   ```

   رمز اولیه **`admin123`** است — فوراً از صفحهٔ «تغییر رمز» آن را عوض کن.

> **آفلاین از روی طراحی:** فونت‌ها، CSS و JavaScript همه از سیستم خودت سرو می‌شوند، بنابراین تابلو بدون اتصال اینترنت هم کار می‌کند — ایده‌آل برای شبکهٔ محلی یک کافه. بروزرسانی ۳۰ ثانیه‌ای فقط با `api/menu.php` روی همان هاست حرف می‌زند و تا وقتی داده واقعاً تغییر نکند هرگز دوباره رندر نمی‌کند.

## 📋 پیش‌نیازها

- **PHP 8.0 یا جدیدتر** (نسخهٔ 8.2+ توصیه می‌شود) همراه با `pdo_mysql`، `mbstring` و `json`
- افزونهٔ **`ext-intl`** برای قالب‌بندی درست ارقام فارسی توصیه می‌شود — بدون آن اپلیکیشن به `menu_to_persian_digits()` برمی‌گردد
- **MySQL 8.0.16+** یا **MariaDB 10.4+** — لازم برای اعمال قید `CHECK`
- **Apache 2.4** با `AllowOverride All` تا قوانین منع دسترسی در `.htaccess` اعمال شوند (پیکربندی پیش‌فرض XAMPP از قبل درست است)
- **یک مرورگر به‌روز** — برای بروزرسانی زنده به ES2017، `fetch` و `Intl` نیاز است. بدون Node.js، npm یا مرحلهٔ build.

## 🤝 مشارکت

مشارکت با Pull Request خوش‌آمد است. برای یکدست ماندن کدبیس:

- `declare(strict_types=1)` را بالای هر فایل PHP نگه دار
- همیشه از prepared statement استفاده کن — هرگز مقداری را داخل SQL درج نکن
- هر خروجی پویا را از `menu_escape()` عبور بده
- هرگز اطلاعات اتصال واقعی را کامیت نکن — از متغیرهای محیطی `CAFE_DB_*` استفاده کن

باگی پیدا کردی؟ لطفاً یک issue باز کن و مراحل بازتولیدش را به‌همراه نسخهٔ PHP و MySQL خودت بنویس تا برطرفش کنیم.

---

ساخته‌شده با ❤️ توسط [AmirBahadorAmiri](https://github.com/AmirBahadorAmiri) — نیروی گرفته از اسپرسو در آبادان. منتشرشده تحت [مجوز MIT](./LICENSE).

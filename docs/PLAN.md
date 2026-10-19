# Travel Booking System — v0.1 Execution Plan

**خطة تنفيذ أول إصدار، خطوة بخطوة، مصمَّمة عشان الـ AI يمشي عليها وإنت تتابع معاه**

*مبنية على Master Plan v2.0 — 9 أكتوبر 2026*

> الوثيقة دي هي PLAN.md. المواصفات والقرارات في SPEC.md (الـ Master Plan v2.0). لو في تعارض بينهم، SPEC.md هو الأصل، وننبّه ونحدّثه قبل ما نكتب كود.

---

## 0. إزاي تستخدم الخطة دي

1. هتحط الوثيقتين في المشروع: `docs/SPEC.md` و `docs/PLAN.md`، ومعاهم `docs/PROGRESS.md` (قسم 3).
2. في أول كل جلسة مع الـ AI تلصق **Master Prompt** (قسم 2).
3. بعدها تقوله: **نفّذ Step N من docs/PLAN.md**.
4. الـ AI ينفّذ الخطوة، ويشرح، ويديك خطوات الاختبار، ويوقف.
5. إنت تختبر. لو نجح كل بند في **Done When**: تعمل Commit وتنتقل للخطوة اللي بعدها. لو لأ: ترجع للـ AI بالخطأ نفسه (قسم 14).

**القاعدة الذهبية:** ما تبدأش خطوة قبل ما اللي قبلها تعدّي اختباراتها وتتعمل لها Commit.

### نظرة عامة على المراحل

| المرحلة | الخطوات | الناتج |
| --- | --- | --- |
| 0. التجهيز | 0.1 إلى 0.5 | بيئة شغالة + Git + وثائق + بيانات تجريبية |
| 1. الأساس | Step 1 إلى 3 | بلاجن بيتفعّل، جدول الحجوزات، صفحة الإعدادات |
| 2. الرحلات والمقاعد | Step 4 إلى 5 | قراءة الرحلة + حساب المقاعد + Shortcodes |
| 3. محرك الحجز | Step 6 إلى 7 | التسعير + إنشاء حجز آمن (قفل + تحقق) |
| 4. الواجهة | Step 8 | فورم الحجز + الحماية |
| 5. الأدمن | Step 9 | قائمة الحجوزات وإجراءاتها |
| 6. الإيميلات | Step 10 | إيميل للعميل وإيميل للأدمن |
| 7. الجودة | Step 11 | اختبارات القبول + مراجعة الأمان + Plugin Check |
| 8. الإصدار | Step 12 | v0.1.0 معمول له ZIP وTag ومجرَّب على Staging |

---

## 1. تعريف النجاح لـ v0.1

الإصدار ناجح لما السيناريو ده يشتغل من أوله لآخره على موقع تجريبي:

```text
رحلة فيها 20 مقعد، سعر الفرد 200
  ← عميل يحجز 4 أفراد من الفورم
  ← يستلم رقم TB-2026-00001 وإيميل Booking Received
  ← المتاح يبقى 16
  ← الأدمن يشوف الحجز Pending / Unpaid
  ← الأدمن يدوس Confirm + Mark as Paid ← المتاح لسه 16
  ← الأدمن يلغي الحجز ← المتاح يرجع 20
  ← طلبين متزامنين على آخر مقعدين: واحد بس ينجح
```

واختبارات القبول الـ 12 في قسم 18 من SPEC.md كلها تنجح.

---


---

## 3. ملف التقدّم: docs/PROGRESS.md

ده الذاكرة بتاعة المشروع بين الجلسات. الـ AI بيقراه في أول كل جلسة وبيحدّثه في آخرها.

```markdown
# PROGRESS

## Steps
- [ ] Step 1  Skeleton
- [ ] Step 2  Installer (table + capability)
- [ ] Step 3  Settings
- [ ] Step 4  Tour reader
- [ ] Step 5  Repository + Availability + seat shortcodes
- [ ] Step 6  Pricing
- [ ] Step 7  Booking Manager
- [ ] Step 8  Booking form
- [ ] Step 9  Admin bookings
- [ ] Step 10 Emails
- [ ] Step 11 QA
- [ ] Step 12 Release

## Current step
(اكتب هنا الخطوة الحالية وحالتها)

## Decisions / deviations from SPEC
(أي حاجة اتغيّرت عن SPEC.md ولازم نحدّث الوثيقة بيها)

## Known issues
(مشاكل معروفة مؤجلة)
```

---

## المرحلة 0: التجهيز

### 0.1 قرار الاسم والـ Prefix

الاسم الشغّال في الوثيقة: `Travel Booking System`، والـ Prefix: `tbs_` والكلاسات `TBS_`.

- لو هتغيّر الـ Prefix، غيّره **قبل Step 1**. بعد كده التغيير في كل الكود مؤلم.
- اسم النشر النهائي على WordPress.org ممكن يتحسم بعدين (قبل v0.4)، لكن الـ Prefix لازم يكون مميّز ومش شائع.

**اللي تعمله:** قرر وسجّله في `PROGRESS.md` تحت Decisions.

### 0.2 موقع تجريبي على LocalWP

1. نزّل LocalWP (مجاني) واعمل موقع جديد اسمه `tbs-dev`.
2. في `wp-config.php` فعّل:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

أي خطأ PHP هيتسجل في `wp-content/debug.log`. ده أول مكان تبص فيه لو حاجة مش شغالة.

3. اختار PHP 8.x، وسيب الثيم الافتراضي.
4. LocalWP فيه أدوات جاهزة هتحتاجها: **Site Shell** (فيه WP-CLI)، **Database > Adminer** (لعرض الجداول)، **Mailhog** (يلقط الإيميلات من غير إرسال حقيقي).

### 0.3 بيانات تجريبية

البلاجن بيقرا رحلات موجودة، فنحتاج رحلات تجريبية. من غير ما نركّب ACF، اعمل ملف في `wp-content/mu-plugins/tbs-test-trips.php`:

```php
<?php
add_action( 'init', function () {
    register_post_type( 'trip', array(
        'label'    => 'Trips',
        'public'   => true,
        'supports' => array( 'title', 'editor', 'custom-fields' ),
    ) );
} );
```

بعدين من Trips > Add New اعمل 3 رحلات (فعّل Custom Fields من Preferences > Panels في محرر القطع):

| الرحلة | capacity | price | trip\_date | الغرض |
| --- | --- | --- | --- | --- |
| Paris Discovery | 20 | 200 | 20261210 | الحالة الطبيعية (تاريخ بصيغة ACF) |
| Luxor Escape | 12 | $1,200.50 | 2026-12-20 | سعر وتاريخ بصيغ مختلفة |
| Empty Trip | (فاضي) | 100 | (فاضي) | حالة المقاعد الفاضية |

الرحلات دي هي اللي هنجرّب عليها كل الخطوات.

### 0.4 المستودع (Git)

1. اعمل مستودع **خاص (Private)** على GitHub باسم البلاجن.
2. اعمل Clone له **جوه** `wp-content/plugins/` ليكون هو فولدر البلاجن نفسه: `.../plugins/travel-booking-system`.
3. أنشئ فولدر `docs/` وحط فيه: `SPEC.md` (صدّر الـ Master Plan v2.0 كـ Markdown)، `PLAN.md` (صدّر الوثيقة دي)، `PROGRESS.md` (من قسم 3).
4. `.gitignore`: `.DS_Store`, `*.log`, `node_modules/`, `*.zip`.
5. أول Commit: `docs: add spec, plan and progress files`.

### 0.5 أداة الـ AI

الأفضل أداة بتشتغل **على فولدر المشروع مباشرة** وتقرا وتكتب الملفات بنفسها (زي Claude Code أو Cursor)، بدل النسخ واللصق من الشات. كده بتقدر تقرا SPEC.md وPLAN.md وPROGRESS.md وتعدّل أكتر من ملف في الخطوة.

لو هتستخدم الشات العادي: الصق محتوى الملفات الثلاثة في أول الجلسة، وبعد كل خطوة الصق له ملفات الخطوة اللي اتغيّرت.

**Done When (المرحلة 0):**

- [ ] الموقع التجريبي شغال و`debug.log` بيتكتب.
- [ ] الرحلات الثلاثة ظاهرة في لوحة التحكم ببياناتها.
- [ ] مجلد البلاجن جوه `plugins/` ومربوط بـ GitHub.
- [ ] ملفات `docs/` الثلاثة موجودة ومتعمل لها Commit.

---

## المرحلة 1: الأساس

### Step 1: هيكل البلاجن

**الهدف:** بلاجن فاضي بيتفعّل ومعاه التحميل الأساسي.

**الملفات:**

```text
travel-booking-system/
├── travel-booking-system.php
├── uninstall.php               (حارس WP_UNINSTALL_PLUGIN بس دلوقتي)
├── includes/class-plugin.php
└── includes/functions.php      (فاضي دلوقتي)
```

**المطلوب:**

- Header كامل: Plugin Name, Description, Version `0.1.0`, Requires at least `6.0`, Requires PHP `7.4`, Author, License `GPL-2.0-or-later`, Text Domain `travel-booking-system`, Domain Path `/languages`.
- ثوابت: `TBS_VERSION`, `TBS_FILE`, `TBS_PATH`, `TBS_URL`.
- كلاس `TBS_Plugin` بنمط Singleton (`instance()`)، بيحمّل الملفات بـ `require_once` يدوي (بدون Autoloader معقّد).
- تحميل الترجمة (`load_plugin_textdomain`) على `init`.
- Hooks التفعيل وإلغاء التفعيل موجودة وفاضية (هتتملي في Step 2).
- لو نسخة PHP أقدم من 7.4: يظهر إشعار في الأدمن ويوقف تحميل البلاجن، بدل ما الموقع يقع بـ Fatal Error.

**ممنوع في الخطوة دي:** أي جدول، أي إعدادات، أي واجهة.

**Done When:**

- [ ] البلاجن بيظهر في Plugins بالاسم والوصف الصحيحين.
- [ ] التفعيل وإلغاء التفعيل وإعادة التفعيل من غير أخطاء.
- [ ] ملف `debug.log` فاضي من أي Notice أو Warning.

**Prompt:**

```text
نفّذ Step 1 من docs/PLAN.md بالظبط، والتزم بالقواعد الثابتة.
```

**Commit:** `feat: plugin skeleton`

### Step 2: الجدول والصلاحية

**الهدف:** جدول الحجوزات بيتعمل وقت التفعيل، والأدمن عنده صلاحية إدارة الحجوزات.

**الملفات:** `includes/class-installer.php` (جديد)، وتعديل الملف الرئيسي وكلاس `TBS_Plugin` وتعريف `tbs_table_name()` في `functions.php`.

**المطلوب:**

- الجدول بنفس الأعمدة والـ Indexes الموجودة في SPEC.md قسم 3 بالظبط.
- اسم الجدول من `$wpdb->prefix . 'tbs_bookings'`، وفي دالة `tbs_table_name()` عشان كل الكود يستعملها.
- الإنشاء بـ `dbDelta()`. تنبيهات هامة لأن dbDelta حسّاسة: كل عمود في سطر لوحده، ومسافتين بين `PRIMARY KEY` والقوس، واستخدام `$wpdb->get_charset_collate()`.
- ثابت `TBS_DB_VERSION` (قيمته `1`) وتخزينه في option اسمه `tbs_db_version`.
- على `plugins_loaded`: لو النسخة المخزّنة أقدم من `TBS_DB_VERSION` يعيد تشغيل الإنشاء (تجهيز للـ Migrations).
- صلاحية `tbs_manage_bookings` تتضاف لدور `administrator` وقت التفعيل.
- إعادة التفعيل ما تمسحش بيانات موجودة.

**ممنوع في الخطوة دي:** أي إدخال أو قراءة بيانات من الجدول.

**Done When:**

- [ ] الجدول ظاهر في Adminer بكل الأعمدة والـ Indexes (PRIMARY, reference, tour\_seats, customer\_email).
- [ ] الأمر `wp option get tbs_db_version` يرجّع `1`.
- [ ] الأمر `wp cap list administrator | grep tbs` يظهر `tbs_manage_bookings`.
- [ ] إلغاء التفعيل ثم التفعيل تاني ما بيوقعش ولا بيمسح الجدول.

**Prompt:**

```text
نفّذ Step 2 من docs/PLAN.md بالظبط، والتزم بالقواعد الثابتة. اشرحلي ليه dbDelta حسّاسة في الصياغة.
```

**Commit:** `feat: bookings table and capability`

### Step 3: صفحة الإعدادات

**الهدف:** صاحب الموقع يربط البلاجن برحلاته من لوحة التحكم.

**الملفات:** `includes/class-settings.php`، `admin/class-admin-menu.php`، `admin/class-settings-page.php`، وتعديل `uninstall.php`.

**المطلوب:**

- كلاس `TBS_Settings` فيه `defaults()` و`get( $key )` و`all()`. كل الإعدادات في option واحد `tbs_settings`.

| المفتاح | الافتراضي | الوصف |
| --- | --- | --- |
| `tour_post_type` | (فاضي) | نوع محتوى الرحلات |
| `seats_field` | `total_seats` | حقل عدد المقاعد |
| `price_field` | `tour_price` | حقل سعر الفرد |
| `date_field` | (فاضي) | حقل تاريخ الرحلة (اختياري) |
| `currency` | `USD` | كود العملة (3 حروف) |
| `payment_instructions_branch` | (فاضي) | تعليمات الدفع في الفرع |
| `payment_instructions_transfer` | (فاضي) | تعليمات التحويل |
| `admin_email` | إيميل الموقع | إيميل استلام الحجوزات الجديدة |
| `max_guests` | `10` | أقصى عدد أفراد في الحجز |
| `hold_hours_branch` | `48` | مهلة حجز الفرع (ساعات) |
| `hold_hours_transfer` | `24` | مهلة حجز التحويل (ساعات) |
| `delete_data_on_uninstall` | `0` | مسح الحجوزات عند حذف البلاجن |

- قائمة رئيسية `Travel Bookings` (أيقونة `dashicons-tickets-alt`، صلاحية `tbs_manage_bookings`) فيها: `All Bookings` (صفحة مؤقتة مكتوب فيها: تتبني في Step 9) و`Settings` (صلاحية `manage_options`).
- الصفحة بـ Settings API (`register_setting` مع `sanitize_callback`).
- نوع الرحلات اختيار من قايمة منسدلة بأنواع المحتوى العامة (public) ما عدا attachment.
- تحت كل حقل نص مساعد بمثال (مثلًا: Rahal يستخدم `total_seats`).
- التنضيف: العملة تتحول لحروف كبيرة وتترفض لو مش 3 حروف (ترجع للافتراضي)؛ الأرقام `absint` مع حد أدنى 1؛ الإيميل `sanitize_email` + `is_email`؛ النصوص الطويلة `sanitize_textarea_field`؛ أسماء الحقول: حروف وأرقام و `_` و `-` بس.
- `uninstall.php`: لو `delete_data_on_uninstall` = 1 يمسح الجدول والـ options، غير كده ما يعملش حاجة.

**Done When:**

- [ ] ضبط الإعدادات للرحلات التجريبية (`trip` / `capacity` / `price` / `trip_date` / `USD`) وحفظها يرجّعها صح بعد Refresh.
- [ ] كتابة `abcd` في العملة ترجع للافتراضي، و`0` في أقصى عدد أفراد يتصلّح.
- [ ] لصق `<script>alert(1)</script>` في تعليمات الدفع بيظهر كنص ما بيتنفّذش.
- [ ] مستخدم Editor ما يقدرش يفتح صفحة Settings.

**Prompt:**

```text
نفّذ Step 3 من docs/PLAN.md بالظبط، والتزم بالقواعد الثابتة.
```

**Commit:** `feat: settings page`

---

## المرحلة 2: الرحلات والمقاعد

### Step 4: قارئ بيانات الرحلة (Tour)

**الهدف:** البلاجن يقرا المقاعد والسعر والتاريخ من أي رحلة حسب الإعدادات، ويتعامل مع البيانات الغلط من غير ما يقع.

**الملفات:** `includes/class-tour.php`، وتعديل `admin/class-settings-page.php` (صندوق Connection Check).

**الدوال المطلوبة (كلها static في `TBS_Tour`):**

| الدالة | بترجّع | القاعدة |
| --- | --- | --- |
| `exists( $id )` | bool | الـ Post موجود، نوعه = `tour_post_type` من الإعدادات، وحالته publish |
| `get_title( $id )` | string | عنوان الرحلة |
| `get_total_seats( $id )` | int | رقم صحيح. فاضي أو نص أو سالب = `0` |
| `get_price_minor( $id )` | int | سعر الفرد بأصغر وحدة (قسم 8 في SPEC) |
| `get_date( $id )` | string أو null | بصيغة `Y-m-d`، أو `null` لو مفيش حقل تاريخ أو القيمة غلط |
| `is_bookable( $id )` | bool | موجودة + مقاعد أكبر من 0 + سعر أكبر من 0 |

**قواعد التحويل:**

- **التاريخ:** يقبل صيغتين بس: `20261210` (ACF) و`2026-12-10`. أي حاجة تانية = `null` (مفيش تخمين).
- **السعر:** يشيل أي حرف ما عدا الأرقام والنقطة والفاصلة، يشيل الفاصلة (بتتعامل كفاصل آلاف)، ويحوّل `(int) round( $value * 100 )`. ده المكان الوحيد المسموح فيه بـ float، لأنه تحويل نص كتبه إنسان.
- **Known limitation:** الصيغة الأوروبية `1.200,50` مش مدعومة في v0.1 (تتسجل في PROGRESS.md).

**صندوق Connection Check في صفحة Settings:** بعد حفظ الإعدادات، يجيب أول رحلة منشورة من النوع المختار ويعرض:

```text
Tour found: Paris Discovery (ID 123)
Seats: 20   Price: 200.00 USD   Date: 2026-12-10   Bookable: Yes
```

ولو حقل ناقص أو غلط يعرض تحذير بالاسم: `Seats field 'capacity' is empty or not a number on this tour.` ده بيوفّر على صاحب الموقع ساعات من الحيرة.

**ممنوع في الخطوة دي:** أي قراءة من جدول الحجوزات.

**Done When** (جرّب بـ Site Shell واستبدل 123 بأرقام رحلاتك: `wp eval 'var_dump( TBS_Tour::get_price_minor( 123 ) );'`):

| الرحلة | المقاعد | السعر | التاريخ | Bookable |
| --- | --- | --- | --- | --- |
| Paris Discovery | 20 | 20000 | 2026-12-10 | true |
| Luxor Escape | 12 | 120050 | 2026-12-20 | true |
| Empty Trip | 0 | 10000 | null | false |

- [ ] الجدول فوق متطابق.
- [ ] ID غير موجود أو Post عادي (مش trip) = `exists()` ترجع false من غير أخطاء.
- [ ] Connection Check بيظهر صح وبيحذّر لو غيّرت اسم الحقل لاسم غلط.

**Prompt:**

```text
نفّذ Step 4 من docs/PLAN.md بالظبط. اشرحلي ليه التاريخ بيقبل صيغتين بس.
```

**Commit:** `feat: tour reader and connection check`

### Step 5: الحجوزات (Repository) + حساب المقاعد + Shortcodes

**الهدف:** نحسب المقاعد المتاحة صح من جدول الحجوزات، ونعرضها.

**الملفات:** `includes/class-booking-repository.php`، `includes/class-availability.php`، `frontend/class-shortcodes.php`، `includes/functions.php`.

**Repository** (كل استعلام للجدول في الكلاس ده بس، وكله `$wpdb->prepare`):

- `insert( array $row )` ترجّع ID أو false.
- `get( $id )` و`get_by_reference( $ref )`.
- `update( $id, array $data )` وبتحدّث `updated_at` لوحدها.
- `sum_reserved_seats( $tour_id, $date )`: نفس استعلام SPEC قسم 3 (confirmed، أو pending لم تنتهِ مهلته). لو `$date` فاضي يستخدم `tour_date IS NULL` (استعلامين منفصلين لأن `prepare` ما بيعملش NULL).

**Availability:**

- `get_total( $tour_id )` من `TBS_Tour`.
- `get_booked( $tour_id, $date = null )`.
- `get_available( $tour_id, $date = null )` = `max( 0, total - booked )`. لو `$date` فاضي ياخد تاريخ الرحلة من `TBS_Tour::get_date()`.

**دوال عامة (`functions.php`):** `tbs_get_total_seats()` و`tbs_get_booked_seats()` و`tbs_get_available_seats()`، كل واحدة بـ `( $tour_id, $date = null )`.

**Shortcodes:** `[tbs_available_seats]` و`[tbs_total_seats]` و`[tbs_booked_seats]`. الـ attributes: `tour_id` (الافتراضي الرحلة الحالية) و`date`. المتاح يعرض `12 seats available` (بـ `_n()` للمفرد والجمع)، والباقي رقم بس. رحلة غير صالحة = ما يعرضش حاجة. بدون كاش في v0.1.

**Done When:** أضف صفوف يدويًا في Adminer على رحلة Paris (استبدل `tour_id` بالـ ID الحقيقي، والـ NULL والتواريخ زي ما هي):

| tour\_date | guests | booking\_status | expires\_at |
| --- | --- | --- | --- |
| 2026-12-10 | 4 | confirmed | NULL |
| 2026-12-10 | 6 | pending | بعد ساعة من دلوقتي |
| 2026-12-10 | 3 | cancelled | NULL |
| 2026-12-20 | 5 | confirmed | NULL |

(باقي الأعمدة الإجبارية املأها بأي قيم، التواريخ بتوقيت UTC.)

- [ ] `[tbs_available_seats date='2026-12-10']` = **10** (الملغي ما اتحسبش).
- [ ] `[tbs_available_seats date='2026-12-20']` = **15**.
- [ ] غيّر `expires_at` للحجز المعلّق إلى وقت فات ← يوم 10 ديسمبر يبقى **16** (من غير أي Cron).
- [ ] `[tbs_available_seats tour_id='999999']` ما يعرضش حاجة ومفيش أخطاء.
- [ ] راجع الكود: كل استعلام بـ `prepare`.

**بعد الاختبار:** امسح الصفوف التجريبية.

**Prompt:**

```text
نفّذ Step 5 من docs/PLAN.md بالظبط. اعرضلي استعلام SQL النهائي واشرح كل شرط فيه.
```

**Commit:** `feat: availability calculation and seat shortcodes`

---

## المرحلة 3: محرك الحجز

### Step 6: التسعير (Pricing)

**الهدف:** حساب السعر على السيرفر، بأرقام صحيحة.

**الملفات:** `includes/class-pricing.php`.

**المطلوب:**

- `TBS_Pricing::calculate( $tour_id, $guests )` ترجّع Array أو `WP_Error`:

```php
array(
  'currency'              => 'USD',
  'price_per_guest_minor' => 20000,
  'subtotal_minor'        => 80000,
  'discount_minor'        => 0,      // v0.1: دايمًا صفر
  'total_minor'           => 80000,
)
```

- الأخطاء: عدد الأفراد مش رقم صحيح أو أقل من 1، أو رحلة غير صالحة.
- الإجمالي يعدّي على `apply_filters( 'tbs_booking_total', $total, $data )`.
- `TBS_Pricing::format( $minor, $currency )` للعرض: `800.00 USD` (بـ `number_format_i18n`).
- الدالة ما تقراش أي سعر من الفورم أو الـ `$_POST` أصلًا.

**برّه v0.1 (ما تعملهوش):** الخصومات، أسعار الأطفال، الكوبونات.

**Done When:**

- [ ] Paris × 4 = subtotal `80000`، total `80000`، العملة `USD`.
- [ ] Luxor × 3 = `360150`.
- [ ] guests = 0 أو `abc` ← `WP_Error`.
- [ ] `format( 80000, 'USD' )` = `800.00 USD`.

**Prompt:**

```text
نفّذ Step 6 من docs/PLAN.md بالظبط. وريني أمثلة تشغيل بـ wp eval.
```

**Commit:** `feat: server-side pricing`

### Step 7: Booking Manager (قلب البلاجن)

**الهدف:** إنشاء حجز آمن، وتغيير حالاته بقواعد صارمة. ده أهم وأخطر Step في v0.1، فاتأنّى فيه.

**الملفات:** `includes/class-booking-manager.php`، وإضافات على Repository لو لزم.

#### `TBS_Booking_Manager::create( array $args )`

المدخلات: `tour_id`, `guests`, `name`, `email`, `phone`, `payment_method` (`branch` أو `transfer`). **التاريخ بيتاخد من الرحلة، مش من الطلب.** الناتج: ID الحجز أو `WP_Error`.

**الترتيب بالظبط:**

1. **تحقق (قبل أي قفل):**
   - الرحلة `is_bookable`.
   - الأفراد رقم صحيح من 1 إلى `max_guests`.
   - الاسم من 2 إلى 100 حرف. الإيميل `is_email`. التليفون أرقام و`+` ومسافات و`-` وأقواس، من 7 إلى 20 حرف.
   - طريقة الدفع من القائمة المسموحة.
   - لو للرحلة تاريخ وفات ← خطأ `tour_past`.
2. **السعر:** `TBS_Pricing::calculate()`.
3. **القفل:** `GET_LOCK` بمهلة 5 ثواني. اسم القفل: `'tbs_' . md5( DB_NAME . $wpdb->prefix . $tour_id . '|' . $date )`. فشل القفل ← خطأ `busy`.
4. **جوه القفل (`try / finally` وفك القفل دايمًا في `finally`):**
   - **منع التكرار:** لو فيه حجز Pending لنفس الإيميل والرحلة والتاريخ وعدد الأفراد اتعمل من أقل من 60 ثانية ← رجّع ID الحجز ده بدل ما تعمل واحد جديد (ده حل ضغطة الزر المزدوجة).
   - **حد الحجوزات المعلقة:** لو فيه حجزين Pending لسه مهلتهم ما خلصتش لنفس الإيميل والرحلة والتاريخ ← خطأ `too_many_pending`.
   - **المقاعد:** لو الأفراد أكتر من المتاح ← خطأ `no_seats`. الرسالة: `Sorry, only 16 seats are available.` (أو `Sorry, this tour is fully booked.` لو صفر).
   - **الإدخال:** `pending` + `unpaid`، `expires_at` = دلوقتي + مهلة الطريقة (من الإعدادات)، `tour_title_snapshot`، الأسعار والعملة، التوقيتات UTC.
   - **الرقم المرجعي:** من الـ ID بصيغة `TB-YYYY-NNNNN` (SPEC قسم 7).
   - **شبكة الأمان:** إعادة حساب المحجوز بعد الإدخال. لو عدّى إجمالي المقاعد ← احذف الصف وارجع `no_seats`.
5. **بعد فك القفل:** `do_action( 'tbs_booking_created', $id )` وارجع ID.

#### `change_status( $id, $new_status )`

الانتقالات المسموحة (SPEC قسم 5)، أي حاجة غيرها ← `WP_Error`:

| من | إلى |
| --- | --- |
| pending | confirmed, rejected, cancelled, expired |
| confirmed | completed (بعد تاريخ الرحلة فقط), cancelled |

- عند `confirmed`: `expires_at` تبقى `NULL`.
- **حالة خاصة:** تأكيد حجز Pending مهلته خلصت (والـ Cron لسه ما حوّلهوش Expired). المقاعد ممكن تكون اتحجزت لحد تاني، فنعيد فحص المتاح **تحت نفس القفل** (الحجز المنتهي مش محسوب فيه). لو مفيش مقاعد كفاية ← خطأ `seats_taken`.
- بعد التغيير: `do_action( 'tbs_booking_status_changed', $id, $old, $new )`، ولو confirmed كمان `do_action( 'tbs_booking_confirmed', $id )`.

#### `mark_paid( $id )`

يغيّر `payment_status` إلى `paid` (للحجز الغير ملغي أو مرفوض فقط).

**Decision (تتسجل في PROGRESS.md):** تعديل على SPEC قسم 4: اسم القفل بقى `md5(...)` بدل نص مقروء، لأن أسماء `GET_LOCK` على مستوى سيرفر MySQL كله مش قاعدة بيانات واحدة، فلازم تتضمّن اسم القاعدة والـ Prefix. اطلب من الـ AI يحدّث SPEC.md قسم 4 بنفس التعديل.

**Done When** (بـ `wp eval` على رحلة Paris، 20 مقعد):

- [ ] حجز 4 أفراد ← ID، المرجع `TB-2026-0000X`، `tbs_get_available_seats` = 16.
- [ ] حجز 17 فرد ← الخطأ `Sorry, only 16 seats are available.`
- [ ] أفراد 0، أو إيميل `abc`، أو طريقة دفع `bitcoin` ← أخطاء مناسبة.
- [ ] تكرار نفس الطلب مرتين ورا بعض ← نفس الـ ID، وصف واحد في الجدول.
- [ ] Confirm ← `confirmed` و`expires_at` = NULL. Cancel ← المتاح يرجع 20.
- [ ] الانتقال من `cancelled` إلى `confirmed` ← خطأ.
- [ ] Completed قبل تاريخ الرحلة ← خطأ.
- [ ] حجز pending مهلته انتهت (عدّل `expires_at` يدويًا) وفي نفس الوقت حد حجز كل المقاعد، تأكيد القديم ← `seats_taken`.

**اختبار السباق (الأهم):** اطلب من الـ AI يكتب `tests/race-test.php` (للتطوير بس، مش داخل ZIP الإصدار) بيحاول يحجز فردين على رحلة. جهّز الرحلة بحجز 18 فرد (فاضل 2)، وبعدين من Site Shell:

```bash
wp eval-file wp-content/plugins/travel-booking-system/tests/race-test.php &
wp eval-file wp-content/plugins/travel-booking-system/tests/race-test.php &
wait
```

المتوقع: **واحد بس ينجح** والتاني ياخد خطأ، والمحجوز النهائي 20 مش 22. كرّرها 5 مرات (ألغِ الحجز الناجح بين كل مرة). لو مرة واحدة نجحوا الاتنين، اقف وارجع للـ AI.

**Prompt:**

```text
نفّذ Step 7 من docs/PLAN.md بالظبط. قبل ما تكتب كود، اشرحلي بكلام بسيط إزاي GET_LOCK بيمنع الحجز المزدوج.
```

**Commit:** `feat: booking manager with locking and status rules`

---

## المرحلة 4: الواجهة

### Step 8: فورم الحجز

**الهدف:** العميل يحجز من صفحة الرحلة، ويشوف تأكيد الحجز.

**الملفات:** `frontend/class-booking-form.php`، `frontend/class-form-handler.php`، `frontend/assets/css/frontend.css`، `frontend/assets/js/booking-form.js`، وإضافة `[tbs_booking_form]` في `class-shortcodes.php`.

**العرض (`[tbs_booking_form]`):**

- Attributes: `tour_id` (الافتراضي الرحلة الحالية).
- رحلة غير صالحة ← `This tour is not available for booking.` ولو المتاح صفر ← `This tour is fully booked.` (من غير فورم).
- الحقول: الاسم، الإيميل، التليفون، عدد الأفراد (`min=1`، و`max` = أصغر قيمة بين المتاح و`max_guests`)، طريقة الدفع (Radio: فرع أو تحويل).
- حقول مخفية: `tour_id`، Nonce، `action`. **ما فيش `tour_date` ولا سعر في الفورم.** السيرفر بياخدهم من الرحلة.
- **Honeypot:** حقل نصي اسمه `tbs_website` مخفي بـ CSS (برّه الشاشة) و`tabindex=-1` و`autocomplete=off`.
- سطر الإجمالي المتوقع بـ JavaScript للعرض فقط، ومكتوب جنبه أن السعر النهائي بيحسبه السيرفر.

**الإرسال (AJAX):**

- `fetch` على `admin-ajax.php` بـ `action=tbs_submit_booking` (للزوار وغير المسجلين: `wp_ajax_` و`wp_ajax_nopriv_`).
- الزر يتعطّل وقت الإرسال. الأخطاء تظهر تحت الفورم في منطقة `aria-live`. النجاح يستبدل الفورم بتأكيد فيه: الرقم المرجعي، الإجمالي، وتعليمات الدفع حسب الطريقة المختارة.

**ترتيب المعالج على السيرفر (`class-form-handler.php`):**

1. فحص الـ Nonce ← فشل = رد خطأ عام.
2. فحص الـ Honeypot ← لو مليان = رد خطأ عام (من غير ما نوضح السبب).
3. Rate Limit: 5 محاولات في الساعة لكل IP (Transient، المفتاح بـ `md5( $ip . wp_salt() )`، والـ IP من `REMOTE_ADDR` فقط). كل محاولة بتتحسب.
4. `wp_unslash` ثم sanitize لكل حقل.
5. `TBS_Booking_Manager::create()`.
6. أي `WP_Error` يتحول لرسالة **من قائمة رسائل مسموحة** (مش نص الخطأ الخام).
7. النجاح ← JSON فيه الرسالة والرقم والإجمالي والتعليمات.

**الأصول (CSS و JS):** تتحمّل في الصفحات اللي فيها الشورت كود بس. الـ CSS بسيط، بيورّث خط الثيم، وبأسماء Classes بادئتها `tbs-`، ومتوافق مع RTL (خصائص منطقية زي `margin-inline-start`).

**Known limitation (تتسجل في PROGRESS.md):**

- بلاجنات الكاش بتخزّن الـ Nonce، فالفورم ممكن يفشل بعد فترة. الحل المؤقت: استثناء صفحة الرحلة من الكاش. الحل الدائم في v0.2.
- الـ IP خلف Cloudflare أو Proxy بيظهر غلط، فالـ Rate Limit هيتأثر. يتحل في v0.2.

**Done When:**

- [ ] حط `[tbs_booking_form]` في محتوى رحلة Paris نفسها. الفورم بيظهر.
- [ ] حجز 4 أفراد ← رسالة نجاح بالرقم المرجعي وتعليمات الدفع، وصف جديد في Adminer بـ `pending` و`unpaid`.
- [ ] تعديل `max` في DevTools وإرسال 99 فرد ← رفض من السيرفر.
- [ ] إضافة حقل `total=1` في الطلب (DevTools) ← الإجمالي المخزّن محسوب من سعر الرحلة.
- [ ] ملء الـ Honeypot ← الطلب يترفض.
- [ ] 6 محاولات في ساعة ← السادسة تترفض. (للتصفير: `wp transient delete --all`)
- [ ] ضغطة مزدوجة سريعة على الزر ← حجز واحد بس.
- [ ] إيميل غلط ← رسالة خطأ تحت الفورم من غير تحميل الصفحة.
- [ ] الفورم شغال على Luxor، وبيظهر `fully booked` على رحلة بعد ما تحجز كل مقاعدها.

**Prompt:**

```text
نفّذ Step 8 من docs/PLAN.md بالظبط. بعد ما تخلص، اعملي قايمة بكل نقطة في الكود بتحمي من هجوم أو إساءة استخدام، وفين موجودة.
```

**Commit:** `feat: booking form with protections`

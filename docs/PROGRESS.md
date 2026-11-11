# PROGRESS — Rahal Booking Plugin

## Steps

- [x] Step 0  Phase 0 setup (structure + docs + test data)
- [x] Step 1  Skeleton (plugin file + class-plugin.php + functions.php)
- [x] Step 2  Installer (table + capability)
- [x] Step 3  Settings
- [x] Step 4  Tour reader
- [x] Step 5  Repository + Availability + seat shortcodes
- [x] Step 6  Pricing
- [x] Step 7  Booking Manager
- [x] Step 8  Booking form
- [x] Step 9  Admin bookings
- [x] Step 10 Emails
- [x] Step 11 QA
- [x] Step 12 Release

## Current step

**الإصدار v0.1.0 مكتمل وجاهز للنشر!** (تم إنجاز كل الخطوات من 0 إلى 12 بنجاح في 11 نوفمبر 2026).

## Decisions / deviations from SPEC

| التاريخ | القرار |
| --- | --- |
| 19 أكتوبر 2026 | اسم البلاجن: **Rahal** — اسم النشر قد يتغير قبل v0.4 |
| 19 أكتوبر 2026 | Prefix الدوال والـ options: `rahl_` — الكلاسات: `RAHL_` — Text Domain: `rahal` |
| 19 أكتوبر 2026 | اسم جدول الحجوزات: `{prefix}rahl_bookings` (بدل tbs_ الموجود في SPEC) |
| 19 أكتوبر 2026 | رقم الحجز المرجعي بصيغة `RHL-YYYY-NNNNN` (بدل TB-) |
| 19 أكتوبر 2026 | Option اسم الإعدادات: `rahl_settings` — Option نسخة قاعدة البيانات: `rahl_db_version` |
| 19 أكتوبر 2026 | صلاحية إدارة الحجوزات: `rahl_manage_bookings` |
| 20 أكتوبر 2026 | شورت كودز المقاعد: `rahl_available_seats`, `rahl_total_seats`, `rahl_booked_seats` (بدل `tbs_*` في SPEC) |
| Step 4 | Known limitation: الصيغة الأوروبية للسعر `1.200,50` غير مدعومة في v0.1 |
| 30 أكتوبر 2026 | اسم قفل `GET_LOCK`: بصيغة `rahl_` + `md5( DB_NAME . prefix . tour_id . '|' . date )` لمنع التصادم عبر قواعد بيانات سيرفر MySQL وعدم تجاوز 64 حرفاً |
| 30 أكتوبر 2026 | قاعدة اختبار: عدم كتابة أوامر اختبارية متعددة الأسطر داخل `wp eval` لأنها تفشل على بعض البيئات/أنظمة التشغيل؛ بل كتابة سكريبت اختبار ملف PHP مستقل في مجلد `tests/` وتشغيله بأمر واحد عبر `wp eval-file tests/...` أو أوامر سطر واحد صريحة (one-liner) |
| 30 أكتوبر 2026 | تعديل صيغة المرجع إلى `RHL-YYYY-NNNNN-XXXX` (بإضافة 4 أحرف وأرقام عشوائية غير ملتبسة لتصعيب التخمين مع سهولة القراءة هاتفياً) |
| 30 أكتوبر 2026 | تقديم ميزة إنشاء حجز يدوي من الداشبورد (Add New Booking) للأدمن وموظفي الفرع/الهاتف في v0.1 بدلاً من تأجيلها، لتمكين إدارة حجوزات شباك التذاكر والهاتف مباشرة |
| 10 نوفمبر 2026 | إضافة الشورت كود `[rahl_booking_button]` لتمكين فتح فورم الحجز كنافذة Popup Modal منبثقة عند الضغط على أزرار كروت الرحلات، مع توريث ألوان الثيم تلقائياً |

## Known issues

- **Known limitation (Step 8):** بلاجنات الكاش قد تخزّن الـ Nonce في صفحة الرحلة، فالفورم قد يفشل بعد فترة للزوار. الحل المؤقت لـ v0.1: استثناء صفحات الرحلات من الـ Cache، وسيتم اعتماد تجديد الـ Nonce عبر REST/AJAX في v0.2.
- **Known limitation (Step 8):** في حال وجود خادم وسيط (Cloudflare أو Reverse Proxy)، عنوان IP العميل من `REMOTE_ADDR` قد يعود بعنوان البروكسي؛ معالجة عناوين البروكسي الموثوقة ستضاف في v0.2.

## Step 5 notes

- استعلام المحجوز: `confirmed` **أو** `pending` مع `expires_at > UTC_TIMESTAMP()` (المقاعد ترجع فور انتهاء المهلة من غير Cron).
- `tour_date IS NULL` و` tour_date = %s` استعلامان منفصلان (prepare + NULL).
- بدون Transient cache في v0.1 (حسب PLAN).
- **اختبار مقبول:** seed 4 صفوف → 10/15 → expire pending → 16/15 → cleanup (Local `rahl-dev`, tour_id=9).

## Step 6 notes

- `RAHL_Pricing::calculate()` تحسب كل شيء على السيرفر — لا تقبل أي سعر من `$_POST`.
- `discount_minor = 0` دايمًا في v0.1 (الخصومات في v0.3).
- الفلتر `rahl_booking_total` موجود وجاهز للتوسعة لاحقًا.
- `format()` تستخدم `number_format_i18n` للتوافق مع اللغة.
- `RAHL_Pricing` يُحمَّل في `class-plugin.php` بعد `class-availability.php`.

## Step 7 notes

- `RAHL_Booking_Manager::create()`:
  - التحقق المسبق الصارم (is_bookable، أفراد، اسم، إيميل، هاتف، طريقة دفع، تاريخ الرحلة الماضي).
  - حماية القفل `GET_LOCK` بمهلة 5 ثوانٍ مع `try ... finally` لفك القفل دائماً.
  - منع تكرار الإرسال بالبحث عن حجز Pending لنفس العميل والرحلة والأفراد تم في آخر 60 ثانية (`find_recent_duplicate`).
  - تحديد سقف الحجوزات المعلقة النشطة لنفس العميل (2 كحد أقصى) عبر `count_active_pending`.
  - حساب `expires_at` بحسب طريقة الدفع بتوقيت UTC.
  - شبكة الأمان Safety Net: إعادة التحقق من إجمالي المحجوز بعد الإدخال وحذف الحجز (`RAHL_Booking_Repository::delete`) فوراً لو حدث تجاوز غير متوقع.
  - الرقم المرجعي بصيغة `RHL-YYYY-NNNNN`.
- `RAHL_Booking_Manager::change_status()`:
  - التحقق من جداول الانتقالات الصارمة.
  - تصفير `expires_at` عند الانتقال إلى `confirmed`.
  - منع التحويل إلى `completed` قبل تاريخ الرحلة.
  - فحص إعادة المقاعد تحت `GET_LOCK` في حال تأكيد حجز pending منتهي المهلة، وإرجاع `seats_taken` في حال حُجزت المقاعد لعميل آخر.
- `RAHL_Booking_Manager::mark_paid()`:
  - تحويل `payment_status` إلى `paid` مع منع التعديل على الحجوزات الملغية أو المرفوضة.
- إنشاء سكريبت اختبار السباق `tests/race-test.php`.

## Step 8 notes

- إنشاء `RAHL_Booking_Form` في `frontend/class-booking-form.php`:
  - الشورت كود `[rahl_booking_form tour_id=""]`.
  - لا يقبل فورم الحجز تمرير تاريخ أو سعر في الـ HTML كحقول إدخال.
  - تحميل ملفات الأصول (CSS/JS) فقط عند وجود الشورت كود في الصفحة.
  - إدراج حقل Honeypot باسم `rahl_website` مخفي تماماً ومستبعد من الـ TabIndex.
  - حساب ومعاينة السعر الإجمالي المتوقع بالـ JS للمستخدم مع تنبيه صريح بأن السعر النهائي محسوب على السيرفر.
- إنشاء `RAHL_Form_Handler` في `frontend/class-form-handler.php`:
  - تسجيل الـ AJAX على `wp_ajax_rahl_submit_booking` و `wp_ajax_nopriv_rahl_submit_booking`.
  - فحص الـ Nonce (`rahl_booking_nonce`).
  - فحص الـ Honeypot ورفض الطلب بصمت بدون إعطاء تلميحات للبوتات.
  - تطبيق Rate Limit صارم: 5 محاولات في الساعة لكل IP (`REMOTE_ADDR`) عبر Transients.
  - معالجة وإرجاع الأخطاء من قائمة بيضاء للرسائل الآمنة (Whitelist).
- إنشاء أصول الواجهة:
  - `frontend/assets/css/frontend.css` (خصائص منطقية للتوافق الكامل مع RTL، تصميم كروت أنيق ومتجاوب، ووراثة خطوط الثيم).
  - `frontend/assets/js/booking-form.js` (تحديث السعر التلقائي، تعطيل زر الإرسال مع مؤشر انتظار لمنع النقرات المتكررة، استبدال الفورم ببطاقة تأكيد الحجز والتعليمات عند النجاح).
- إنشاء سكريبت فحص متكامل: `tests/step8-checks.php`.

## Step 9 notes

- إنشاء `RAHL_Bookings_List_Table` في `admin/class-bookings-list-table.php`:
  - مبني وفق معايير ووردبريس على الكلاس القياسي `WP_List_Table`.
  - الأعمدة: Reference (مع Row Actions)، Customer (اسم، إيميل، هاتف)، Tour & Date، Guests، Total مع طريقة الدفع، Payment Badge، Status Badge، و Date.
  - فلاتر Views في الأعلى تعرض أعداد الحالات الحية (All, Pending, Confirmed, Completed, Cancelled, Rejected, Expired).
  - فلتر بحالة الدفع (All, Paid, Unpaid) وصندوق بحث شامل (مرجع، اسم، إيميل، هاتف).
  - تقسيم الصفحات Pagination (20 حجزاً في الصفحة) وترتيب Sortable بالأعمدة الأساسية.
- إنشاء `RAHL_Admin_Bookings` في `admin/class-admin-bookings.php`:
  - معالجة إجراءات الأدمن: Confirm, Confirm & Pay, Reject, Cancel, Complete, Mark as Paid.
  - حماية كل إجراء بفحص صلاحية `rahl_manage_bookings` + Nonce صالح خاص بالحجز.
  - إظهار رسائل WordPress Admin Notices عند النجاح أو الفشل.
- تحديث `RAHL_Booking_Repository`:
  - إضافة دالة `query( array $args )` بـ Prepared SQL تدعم البحث والتصفية والتقسيم.
  - إضافة دالة `count( array $args )` لحساب الإجمالي المطابق.
  - إضافة دالة `count_by_status()` لحساب أعداد كل حالة بـ GROUP BY واحد فعال.
- ربط صفحة `All Bookings` في `RAHL_Admin_Menu` بـ `RAHL_Admin_Bookings::render()`.
- إنشاء سكريبت فحص متكامل: `tests/step9-checks.php`.

## Step 10 notes

- إنشاء `RAHL_Emails` في `includes/class-emails.php`:
  - الربط مع الحدث `rahl_booking_created` عند إنشاء أي حجز جديد.
  - إرسال إيميل العميل: `Booking Received` متضمناً جدول تفاصيل الحجز، الرقم المرجعي، المبلغ المنسق، وتعليمات الدفع ومهلة الحجز.
  - إرسال إيميل الأدمن: `New Booking Alert` متضمناً بيانات العميل (الاسم، البريد، الهاتف) ورابط مباشر لفتح صفحة الحجز في لوحة التحكم.
  - قوالب HTML بريدية احترافية قابلة للتخصيص من الثيم:
    - `templates/emails/customer-booking-received.php`
    - `templates/emails/admin-new-booking.php`
  - مبدأ تحمل الأخطاء (Fault Tolerance): الإرسال محمي بـ `try/catch` وفشل `wp_mail` يُسجل بهدوء في الـ log ولا يوقف أو يلغي الحجز أبداً.
  - فلاتر مرنة للمطورين على العناوين والترويسات (`rahl_email_customer_subject`, `rahl_email_admin_subject`, إلخ).
- إنشاء سكريبت فحص متكامل: `tests/step10-checks.php`.

## Step 11 notes

- تنفيذ ومطابقة جميع اختبارات القبول الـ 12 المنصوص عليها في `SPEC.md` (القسم 18).
- إنشاء سكريبت فحص آلي شامل لجميع اختبارات القبول: `tests/step11-acceptance-suite.php`.
- مراجعة أمان الكود بالكامل (Security Audit):
  - فحص حارس `defined( 'ABSPATH' ) || exit;` في كافة ملفات الـ PHP (و `defined( 'WP_UNINSTALL_PLUGIN' )` لملف uninstall.php).
  - التأكد من خلو الكود بالكامل من أي استخدام قديم أو عشوائي للـ prefix، وتوحيده على `rahl_` و `RAHL_`.
  - التحقق من تطبيق `wp_unslash` و sanitization على كل المدخلات، و escaping على كل المخرجات.
  - التحقق من استخدام `$wpdb->prepare` في جميع الاستعلامات التي تستقبل متغيرات.
  - التحقق من حماية الـ Nonces وصلاحيات الأدمن `rahl_manage_bookings`.

## Step 12 notes

- مراجعة وتحديث `readme.txt` وتطابقه مع معايير WordPress.org وشرح كافة الشورت كودز المتاحة والميزات المنفذة.
- إنشاء حزمة الإصدار الإنتاجي النظيفة `rahal-0.1.0.zip` واستبعاد ملفات التطوير والاختبارات (`tests/`, `docs/`, `.git/`, `.gitignore`).
- فحص سلامة الحزمة والتأكد من مطابقتها لهيكل إضافات ووردبريس القياسي.
- اكتمال أول إصدار رسمي **v0.1.0** من بلاجن **Rahal Booking System** بنجاح.

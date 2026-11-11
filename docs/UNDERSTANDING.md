# دليل فهم بلاجن Rahal — تعلّم بالكود الحقيقي

> رحلة تعليمية خطوة بخطوة على الملفات الفعلية للبلاجن.
> بعد كل مرحلة في سؤال — لو مش فاهم حاجة قول.

---

# المرحلة 0: فولدر البلاجن نفسه

## إيه اللي بيحصل لما WordPress يشوف فولدر جديد في plugins؟

WordPress عنده فولدر واحد بيبص فيه لكل البلاجنات:

```
wp-content/
└── plugins/
    ├── contact-form-7/       ← بلاجن تاني موجود
    ├── woocommerce/          ← بلاجن تاني
    └── rahal/                ← البلاجن بتاعنا ← WordPress لقاه هنا
```

WordPress بيدخل على `rahal/` ويدوّر على أول ملف PHP فيه **Header** بيعرّفه. لو لقى، بيضيف البلاجن في قايمة Plugins في لوحة التحكم.

**الـ Header ده زي بطاقة تعريف.** بدونها، WordPress مش هيعرف إن الفولدر ده بلاجن.

---

# المرحلة 1: الملف الرئيسي — `rahal.php`

## فين الملف ده؟

```
rahal/
└── rahal.php    ← ده أول ملف بيتشغّل في البلاجن
```

## الكود الكامل للملف + شرح كل سطر

```php
<?php
```

**`<?php`**
ده اللي بيقول للسيرفر «ما بعد الكلمة دي ده كود PHP». كل ملف PHP لازم يبدأ بيه.

---

```php
/**
 * Plugin Name:       Rahal Booking
 * Plugin URI:        https://github.com/rahal-booking
 * Description:       A standalone booking engine...
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rahal Team
 * License:           GPL-2.0-or-later
 * Text Domain:       rahal
 * Domain Path:       /languages
 */
```

**الـ Plugin Header**
ده مش كود — ده تعليق خاص (doc comment) بيقراه WordPress عشان يعرف معلومات البلاجن.

| السطر | معناه |
|---|---|
| `Plugin Name` | الاسم اللي بيظهر في لوحة التحكم |
| `Version` | رقم الإصدار الحالي |
| `Requires PHP` | أقل نسخة PHP بتشتغل معاها |
| `Text Domain` | معرّف فريد للترجمة — لازم يطابق اسم الفولدر |

**مثال عام:** لو هتعمل بلاجن تاني اسمه "Event Manager"، الـ Header بتاعه هيكون:
```php
/**
 * Plugin Name: Event Manager
 * Version:     1.0.0
 * Text Domain: event-manager
 */
```

---

```php
defined( 'ABSPATH' ) || exit;
```

**الحارس (Security Guard)**
`ABSPATH` ده ثابت (constant) WordPress بيعمله لما يشتغل. قيمته هي المسار الكامل لفولدر WordPress.

السطر ده بيقول: «لو `ABSPATH` مش معرّف، يعني حد فتح الملف مباشرة من المتصفح (مش من WordPress) — اطلع فوراً (`exit`)».

**ليه ده مهم؟**
لو حد عمل `https://yoursite.com/wp-content/plugins/rahal/rahal.php` مباشرة، من غير الحارس ده ممكن يشوف أخطاء أو معلومات حساسة.

**مثال عام:** كل ملف PHP في أي بلاجن لازم يبدأ بالسطر ده تحت `<?php` مباشرة.

---

```php
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action( 'admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'Rahal Booking requires PHP 7.4 or higher.', 'rahal' )
        );
    });
    return;
}
```

**فحص نسخة PHP**
`PHP_VERSION` ثابت تلقائي بيحتوي على نسخة PHP الشغالة على السيرفر.
`version_compare` دالة PHP بتقارن نسختين.

لو النسخة أقدم من 7.4:
- `add_action( 'admin_notices', ... )` → بيضيف إشعار أحمر في لوحة التحكم.
- `return` → بيوقف تشغيل باقي الملف (يعني البلاجن ما بيكملش تحميله).

**النتيجة:** الموقع ما بيقعش بـ Fatal Error — بيظهر رسالة محترمة بدل كده.

**مثال عام:** أي بلاجن بيستخدم ميزات PHP حديثة لازم يعمل نفس الفحص ده.

---

```php
define( 'RAHL_VERSION', '0.1.0' );
define( 'RAHL_DB_VERSION', 1 );
define( 'RAHL_FILE', __FILE__ );
define( 'RAHL_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAHL_URL',  plugin_dir_url( __FILE__ ) );
```

**الثوابت (Constants)**
`define( 'اسم', 'قيمة' )` بتعمل ثابت — قيمته ما بتتغيرش طول عمر الطلب.

| الثابت | القيمة | الاستخدام |
|---|---|---|
| `RAHL_VERSION` | `'0.1.0'` | رقم النسخة — بيتستخدم في تحميل CSS/JS |
| `RAHL_DB_VERSION` | `1` | نسخة قاعدة البيانات — لإدارة المايجريشن |
| `RAHL_FILE` | المسار الكامل لـ rahal.php | لازم لـ register_activation_hook |
| `RAHL_PATH` | المسار الكامل للفولدر + / | بيتستخدم في require_once |
| `RAHL_URL` | الـ URL الكامل للفولدر | بيتستخدم في تحميل CSS/JS |

**`__FILE__`** ده ثابت PHP تلقائي — دايمًا بيحتوي على المسار الكامل للملف اللي فيه.

**مثال استخدام:**
```php
// بدل ما تكتب المسار يدوياً
require_once 'C:/xampp/htdocs/wp/wp-content/plugins/rahal/includes/class-settings.php';

// بتكتب
require_once RAHL_PATH . 'includes/class-settings.php';
// RAHL_PATH = 'C:/xampp/.../rahal/'
// النتيجة نفسها بس أنظف وصح على أي سيرفر
```

---

```php
require_once RAHL_PATH . 'includes/class-installer.php';
require_once RAHL_PATH . 'includes/class-plugin.php';
require_once RAHL_PATH . 'includes/functions.php';
```

**تحميل الملفات**
`require_once` بتجيب محتوى ملف PHP وتشغّله كأنه مكتوب هنا.
`once` معناها: لو الملف ده اتحمّل قبل كده، ما تحمّلهوش تاني (تفادي التكرار).

**ليه 3 ملفات بس هنا؟**
- `class-installer.php`: محتاجه قبل أي حاجة عشان Hook التفعيل.
- `class-plugin.php`: هو اللي هيحمّل باقي الكلاسات.
- `functions.php`: دوال عامة محتاجة من أول لحظة.

الباقي (class-settings, class-tour, ...) بيتحمّل من جوّه `class-plugin.php`.

---

```php
register_activation_hook( RAHL_FILE, array( 'RAHL_Plugin', 'activate' ) );
register_deactivation_hook( RAHL_FILE, array( 'RAHL_Plugin', 'deactivate' ) );
```

**Hooks التفعيل وإلغاء التفعيل**
دول دالتين خاصتين بـ WordPress بتربط "حدث" بـ "كود يشتغل عند الحدث".

| الدالة | بتشتغل إمتى |
|---|---|
| `register_activation_hook` | لما الأدمن يضغط "Activate" |
| `register_deactivation_hook` | لما الأدمن يضغط "Deactivate" |

`array( 'RAHL_Plugin', 'activate' )` معناها: «نادي دالة `activate` من كلاس `RAHL_Plugin`».

---

```php
RAHL_Plugin::instance();
```

**بداية تشغيل البلاجن**
ده آخر سطر — بينادي `instance()` من كلاس `RAHL_Plugin` (هنشرحه في المرحلة الجاية).
`::` بتستخدم عشان `instance` دالة `static` (مش محتاج `new`).

---

## الصورة الكاملة لـ `rahal.php`

```
1. الحارس (ABSPATH)           ← أمان: امنع الدخول المباشر
2. فحص PHP                    ← أمان: اتأكد إن البيئة مناسبة
3. تعريف الثوابت              ← منظمة: مسارات وإصدارات
4. تحميل 3 ملفات أساسية       ← تهيئة: جهّز الكلاسات الضرورية
5. Hooks التفعيل/إلغاء التفعيل ← سلوك: اتصرف عند تفعيل/إيقاف البلاجن
6. RAHL_Plugin::instance()    ← تشغيل: ابدأ البلاجن فعلاً
```

**مثال عام — لو هتعمل بلاجن Event Manager:**
```php
<?php
/**
 * Plugin Name: Event Manager
 * Version:     1.0.0
 * Text Domain: event-manager
 */

defined( 'ABSPATH' ) || exit;

define( 'EM_VERSION', '1.0.0' );
define( 'EM_PATH', plugin_dir_path( __FILE__ ) );
define( 'EM_URL',  plugin_dir_url( __FILE__ ) );

require_once EM_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'EM_Plugin', 'activate' ) );

EM_Plugin::instance();
```

نفس الهيكل بالظبط — بس بأسماء مختلفة.

---

# المرحلة 2: `includes/class-plugin.php` (المدير / Orchestrator)

فاكر لما قلنا في `rahal.php` إن السطر الأخير هو:
```php
RAHL_Plugin::instance();
```
هنا بقى بندخل في الكلاس اللي ماسك زمام كل حاجة في البلاجن.

---

## 1. نمط التصميم: ما هو الـ Singleton ولماذا نستخدمه؟

في JavaScript، لو عملت ملف وكتبت فيه كائن (Object) وعملتله `export default`:
```javascript
// JS: موديول عادي بيكون نسخة واحدة مشتركة (Singleton طبيعي في JS)
const plugin = {
    init() { /* ... */ }
};
export default plugin;
```
أي ملف هيعمل `import` هياخد نفس النسخة المشتركة بالضبط.

**لكن في PHP:**
لو عملت كلاس عادي:
```php
$a = new RAHL_Plugin();
$b = new RAHL_Plugin();
```
هنا أصبح عندك نسختين مختلفتين في الذاكرة! تخيل لو النسختين دول بدأوا يضيفوا نفس الـ Hooks للـ WordPress.. كل الأكشنات والعمليات هتتكرر مرتين!

**الحل: نمط الـ Singleton (النسخة الوحيدة):**
فكرته: **ممنوع أي حد يعمل `new RAHL_Plugin()` من برّه.**

تعال نشوف إزاي معمول في الكود:

```php
private static $instance = null; // متغير static يحفظ النسخة الوحيدة

// 1. الدالة الوحيدة اللي مسموح تجيب منها النسخة
public static function instance() {
    if ( null === self::$instance ) {
        self::$instance = new self(); // اتعمل new مرة واحدة فقط هنا داخلياً
        self::$instance->init();      // شغّل البلاجن
    }
    return self::$instance;
}

// 2. الـ Constructor معمول private!
// لو حد حاول يكتب $x = new RAHL_Plugin(); الكود هيطلع Fatal Error ويمنعه
private function __construct() {}
```

---

## 2. تفكيك دالة `init()` — خطوة بخطوة

الدالة دي هي نقطة الانطلاق اللي بتنظم كل الملفات:

### أ) تحميل ملفات اللغات والترجمة (`load_textdomain`)
```php
add_action( 'init', array( $this, 'load_textdomain' ), 0 );
```
- **ليه `array( $this, 'load_textdomain' )`؟**
  - في ووردبريس، لو عاوز تنادي ميثود تابعة لـ Object داخل Hook، بتبعتها كـ Array: `array( كائن_الـ_Class, 'اسم_الدالة' )`.
- **ليه Priority `0`؟**
  - الـ Priority الافتراضي في ووردبريس هو `10`. كتبنا `0` عشان تتنفذ بدري جداً قبل أي إضافة تانية، بحيث تكون الترجمة جاهزة لأي نصوص تظهر على الموقع.

### ب) فحص ترقية قاعدة البيانات (`maybe_upgrade`)
```php
add_action( 'plugins_loaded', array( 'RAHL_Installer', 'maybe_upgrade' ) );
```
- عند اكتمال تحميل كل البلاجينات (`plugins_loaded`)، بيتأكد الكود لو نزل إصدار جديد هل محتاجين نعدل على الجداول؟

### ج) تحميل ملفات الكلاسات المشتركة
```php
require_once RAHL_PATH . 'includes/class-settings.php';
require_once RAHL_PATH . 'includes/class-tour.php';
require_once RAHL_PATH . 'includes/class-booking-repository.php';
require_once RAHL_PATH . 'includes/class-availability.php';
require_once RAHL_PATH . 'includes/class-pricing.php';
require_once RAHL_PATH . 'frontend/class-shortcodes.php';
```
ليه بنحمّل دول هنا تحديداً؟
- لأنها ملفات أساسية محتاجها الموقع سواء كان الزائر يتصفح كعميل في الواجهة (Frontend)، أو مدير لوحة التحكم في الأدمن (Admin).

### د) الفصل الذكي للأداء: `is_admin()`
```php
if ( is_admin() ) {
    require_once RAHL_PATH . 'admin/class-admin-menu.php';
    require_once RAHL_PATH . 'admin/class-settings-page.php';

    RAHL_Admin_Menu::init();
    RAHL_Settings_Page::init();
}
```
- **ما هي دالة `is_admin()`؟**
  - دالة ووردبريس بتعرفك هل الزائر الحالي فاتح لوحة التحكم (`/wp-admin/`) أم واجهة الموقع العادية.
- **ليه الشرط ده جوهري؟**
  - لو زائر عادي بيحجز رحلة من الموبايل، ليه نستهلك الذاكرة ونحمّل ملفات القوائم وصفحات الإعدادات الخاصة بالأدمن؟ ده مبدأ مهم جداً في الأداء (Performance).

---

## 3. دوال التفعيل وإلغاء التفعيل (`activate` و `deactivate`)

```php
public static function activate() {
    RAHL_Installer::run();              // إنشاء جدول الحجوزات في قاعدة البيانات
    RAHL_Installer::add_capabilities(); // إعطاء الصلاحيات لمدير الموقع
    flush_rewrite_rules();              // تحديث الروابط الدائمة (Permalinks)
}

public static function deactivate() {
    // إلغاء التفعيل لا يمسح البيانات
    flush_rewrite_rules();
}
```

- **قاعدة ذهبية في ووردبريس:**
  - عند **Deactivate** (إلغاء التفعيل): أوقف العمليات فقط ولكن **لا تحذف أي بيانات أو جداول من قاعدة البيانات** (فقد يكون العميل يعطّل البلاجن مؤقتاً لتجربة شيء).
  - حذف البيانات يكون فقط في ملف `uninstall.php` إذا قرر العميل حذف البلاجن نهائياً.

---

## 💡 الخلاصة والقالب العام (لو هتعمل بلاجن تانية)

`class-plugin.php` هو دائماً:
1. **Singleton**: لمنع تكرار النسخ في الذاكرة.
2. **Init Router**: يقرأ الحالة (`is_admin`) ويستدعي فقط الملفات المناسبة.
3. **Execution Point**: ينظم الـ Hooks بالترتيب الزمني الصحيح للووردبريس.

---

**✅ خلصنا المرحلة 2: `class-plugin.php`**

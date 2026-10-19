<?php
/**
 * Plugin Name:       Rahal Booking
 * Plugin URI:        https://github.com/rahal-booking
 * Description:       A standalone booking engine for WordPress travel sites. Attach booking capabilities to any existing tour/trip post type without rebuilding your content structure.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rahal Team
 * Author URI:        https://rahal.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rahal
 * Domain Path:       /languages
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

/**
 * فحص نسخة PHP قبل تحميل أي شيء.
 * لو النسخة أقدم من 7.4 نعرض إشعار في الأدمن ونوقف التحميل.
 */
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action(
        'admin_notices',
        function () {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__(
                    'Rahal Booking requires PHP 7.4 or higher. Please upgrade PHP to use this plugin.',
                    'rahal'
                )
            );
        }
    );
    return; // إيقاف تحميل بقية الملف
}

// ─── الثوابت ────────────────────────────────────────────────────────────────

/** نسخة البلاجن */
define( 'RAHL_VERSION', '0.1.0' );

/** نسخة قاعدة البيانات — زوّد بمقدار 1 عند أي تغيير في هيكل الجدول */
define( 'RAHL_DB_VERSION', 1 );

/** المسار الكامل للملف الرئيسي */
define( 'RAHL_FILE', __FILE__ );

/** المسار الكامل لفولدر البلاجن (بدون trailing slash) */
define( 'RAHL_PATH', plugin_dir_path( __FILE__ ) );

/** الـ URL الكامل لفولدر البلاجن (بدون trailing slash) */
define( 'RAHL_URL', plugin_dir_url( __FILE__ ) );

// ─── تحميل الملف الرئيسي ────────────────────────────────────────────────────

require_once RAHL_PATH . 'includes/class-installer.php';
require_once RAHL_PATH . 'includes/class-plugin.php';
require_once RAHL_PATH . 'includes/functions.php';

// ─── Hooks التفعيل وإلغاء التفعيل ──────────────────────────────────────────

register_activation_hook(
    RAHL_FILE,
    array( 'RAHL_Plugin', 'activate' )
);

register_deactivation_hook(
    RAHL_FILE,
    array( 'RAHL_Plugin', 'deactivate' )
);

// ─── تشغيل البلاجن ──────────────────────────────────────────────────────────

RAHL_Plugin::instance();

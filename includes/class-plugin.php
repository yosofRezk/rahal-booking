<?php
/**
 * RAHL_Plugin — كلاس الـ Singleton الرئيسي.
 *
 * مسؤول عن:
 * - تحميل كل مكونات البلاجن بترتيب صحيح.
 * - تسجيل الترجمة.
 * - Hooks التفعيل وإلغاء التفعيل.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Plugin {

    /**
     * Instance وحيد من الكلاس.
     *
     * @var RAHL_Plugin|null
     */
    private static $instance = null;

    /**
     * يرجّع النسخة الوحيدة من الكلاس (Singleton).
     *
     * @return RAHL_Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * المنشئ خاص — استخدم instance() بدلاً منه.
     */
    private function __construct() {}

    /**
     * تهيئة البلاجن: تحميل الترجمة وربط الـ Hooks.
     */
    private function init() {
        // تحميل الترجمة على init مع priority 0 عشان يبقى قبل أي نص
        add_action( 'init', array( $this, 'load_textdomain' ), 0 );

        // فحص Migration على plugins_loaded (بعد تحميل كل البلاجنات)
        add_action( 'plugins_loaded', array( 'RAHL_Installer', 'maybe_upgrade' ) );

        // تحميل الكلاسات الأساسية المشتركة (Admin + Frontend)
        require_once RAHL_PATH . 'includes/class-settings.php';
        require_once RAHL_PATH . 'includes/class-tour.php';
        require_once RAHL_PATH . 'includes/class-booking-repository.php';
        require_once RAHL_PATH . 'includes/class-availability.php';
        require_once RAHL_PATH . 'frontend/class-shortcodes.php';

        add_action( 'init', array( 'RAHL_Shortcodes', 'init' ) );

        // تحميل مكونات الأدمن
        if ( is_admin() ) {
            require_once RAHL_PATH . 'admin/class-admin-menu.php';
            require_once RAHL_PATH . 'admin/class-settings-page.php';

            RAHL_Admin_Menu::init();
            RAHL_Settings_Page::init();
        }
    }

    /**
     * تحميل ملف الترجمة.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'rahal',
            false,
            dirname( plugin_basename( RAHL_FILE ) ) . '/languages'
        );
    }

    /**
     * يشتغل عند تفعيل البلاجن.
     * (هيتملي في Step 2 بإنشاء الجدول والصلاحيات)
     */
    public static function activate() {
        RAHL_Installer::run();
        RAHL_Installer::add_capabilities();

        // فلّش الـ Rewrite Rules بعد التفعيل
        flush_rewrite_rules();
    }

    /**
     * يشتغل عند إلغاء تفعيل البلاجن.
     * (إلغاء التفعيل لا يمسح البيانات — ده في uninstall.php)
     */
    public static function deactivate() {
        // إلغاء التفعيل لا يمسح البيانات — ده في uninstall.php
        // Step 9+: إلغاء جدولة الـ Cron
        flush_rewrite_rules();
    }
}

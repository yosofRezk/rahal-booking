<?php
/**
 * RAHL_Admin_Menu — ينشئ قائمة الأدمن الرئيسية وصفحاتها.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Admin_Menu {

    /**
     * يربط الـ Hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
    }

    /**
     * يسجّل القوائم في لوحة التحكم.
     */
    public static function register_menus() {
        // القائمة الرئيسية: Rahal Bookings
        add_menu_page(
            __( 'Rahal Bookings', 'rahal' ),          // عنوان الصفحة (title tag)
            __( 'Rahal Bookings', 'rahal' ),          // نص القائمة
            'rahl_manage_bookings',                    // الصلاحية المطلوبة
            'rahl-bookings',                           // الـ slug
            array( __CLASS__, 'render_bookings_page' ), // الدالة المسؤولة عن العرض
            'dashicons-tickets-alt',                   // الأيقونة
            58                                         // الموضع في القائمة (بعد Media)
        );

        // صفحة All Bookings (نفس الصفحة الرئيسية)
        add_submenu_page(
            'rahl-bookings',
            __( 'All Bookings', 'rahal' ),
            __( 'All Bookings', 'rahal' ),
            'rahl_manage_bookings',
            'rahl-bookings',
            array( __CLASS__, 'render_bookings_page' )
        );

        // صفحة Settings — تحتاج manage_options (أعلى من rahl_manage_bookings)
        add_submenu_page(
            'rahl-bookings',
            __( 'Rahal Settings', 'rahal' ),
            __( 'Settings', 'rahal' ),
            'manage_options',
            'rahl-settings',
            array( 'RAHL_Settings_Page', 'render' )
        );
    }

    /**
     * صفحة All Bookings المؤقتة — هتتبني في Step 9.
     */
    public static function render_bookings_page() {
        if ( ! current_user_can( 'rahl_manage_bookings' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'rahal' ) );
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'All Bookings', 'rahal' ) . '</h1>';
        echo '<p>' . esc_html__( 'Booking list will be built in Step 9.', 'rahal' ) . '</p></div>';
    }
}

<?php
/**
 * RAHL_Installer — إنشاء جدول الحجوزات وإدارة نسخة قاعدة البيانات والصلاحيات.
 *
 * يشتغل وقت تفعيل البلاجن، وعلى plugins_loaded لو النسخة أقدم.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Installer {

    /**
     * ينشئ الجدول ويضيف الصلاحية.
     * بيتنادى من activation hook ومن plugins_loaded لو فيه migration.
     */
    public static function run() {
        self::create_table();
        self::store_db_version();
    }

    /**
     * ينشئ جدول الحجوزات باستخدام dbDelta.
     *
     * ملاحظات dbDelta الحساسة:
     * - كل عمود في سطر لوحده.
     * - مسافتان بين PRIMARY KEY والقوس.
     * - استخدام $wpdb->get_charset_collate().
     * - كل INDEX في سطر لوحده بعد تعريف الأعمدة.
     */
    private static function create_table() {
        global $wpdb;

        $table   = rahl_table_name();
        $collate = $wpdb->get_charset_collate();

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta لا تدعم Prepared Statements
        $sql = "CREATE TABLE {$table} (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference             VARCHAR(32)  NOT NULL DEFAULT '',
  tour_id               BIGINT UNSIGNED NOT NULL,
  tour_date             DATE NULL,
  guests                SMALLINT UNSIGNED NOT NULL,
  customer_name         VARCHAR(190) NOT NULL,
  customer_email        VARCHAR(190) NOT NULL,
  customer_phone        VARCHAR(40)  NOT NULL,
  currency              CHAR(3)      NOT NULL,
  price_per_guest_minor BIGINT NOT NULL,
  subtotal_minor        BIGINT NOT NULL,
  discount_minor        BIGINT NOT NULL DEFAULT 0,
  total_minor           BIGINT NOT NULL,
  booking_status        VARCHAR(20)  NOT NULL DEFAULT 'pending',
  payment_status        VARCHAR(20)  NOT NULL DEFAULT 'unpaid',
  payment_method        VARCHAR(30)  NOT NULL DEFAULT '',
  transaction_id        VARCHAR(100) NOT NULL DEFAULT '',
  tour_title_snapshot   VARCHAR(255) NOT NULL DEFAULT '',
  expires_at            DATETIME NULL,
  created_at            DATETIME NOT NULL,
  updated_at            DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY reference (reference),
  KEY tour_seats (tour_id, tour_date, booking_status, expires_at),
  KEY customer_email (customer_email)
) {$collate};";
        // phpcs:enable

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * يخزّن نسخة قاعدة البيانات في الـ options.
     */
    private static function store_db_version() {
        update_option( 'rahl_db_version', RAHL_DB_VERSION );
    }

    /**
     * يضيف صلاحية rahl_manage_bookings لدور administrator.
     * بيتنادى مرة واحدة عند التفعيل.
     */
    public static function add_capabilities() {
        $role = get_role( 'administrator' );
        if ( $role ) {
            $role->add_cap( 'rahl_manage_bookings' );
        }
    }

    /**
     * يشيل صلاحية rahl_manage_bookings من دور administrator.
     * بيتنادى عند إلغاء التفعيل.
     */
    public static function remove_capabilities() {
        $role = get_role( 'administrator' );
        if ( $role ) {
            $role->remove_cap( 'rahl_manage_bookings' );
        }
    }

    /**
     * يتحقق لو النسخة المخزّنة أقدم من النسخة الحالية ويعمل Migration.
     * بيتنادى على plugins_loaded.
     */
    public static function maybe_upgrade() {
        $installed = (int) get_option( 'rahl_db_version', 0 );

        if ( $installed < RAHL_DB_VERSION ) {
            self::run();
        }
    }
}

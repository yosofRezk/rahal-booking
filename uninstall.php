<?php
/**
 * Rahal Booking — Uninstall Script
 *
 * يشتغل عند حذف البلاجن نهائياً من لوحة التحكم.
 *
 * القاعدة: ما نمسحش بيانات الحجوزات إلا لو صاحب الموقع اختار ده صراحة
 * في الإعدادات (delete_data_on_uninstall = 1).
 *
 * @package Rahal
 */

// حارس أمان: السكريبت ده لازم يتشغل من ووردبريس بس.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Step 3: الإعدادات بتتحمّل هنا
$settings = get_option( 'rahl_settings', array() );

if ( ! empty( $settings['delete_data_on_uninstall'] ) && '1' === (string) $settings['delete_data_on_uninstall'] ) {
    global $wpdb;

    // حذف جدول الحجوزات
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- اسم الجدول ثابت ومحمي بـ esc_sql
    $wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $wpdb->prefix . 'rahl_bookings' ) );

    // حذف الـ Options
    delete_option( 'rahl_settings' );
    delete_option( 'rahl_db_version' );
}

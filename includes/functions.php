<?php
/**
 * دوال المطوّرين العامة لبلاجن Rahal.
 *
 * كل الدوال هنا للاستخدام الخارجي من الثيمات والبلاجنات الأخرى.
 * الدوال الداخلية توضع في الكلاسات المعنية.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

// ─── الدوال المساعدة ─────────────────────────────────────────────────────────

/**
 * يرجّع اسم جدول الحجوزات مع البريفكس الصحيح.
 *
 * استخدم هذه الدالة دائماً بدل كتابة اسم الجدول يدوياً.
 *
 * @return string اسم الجدول كاملاً (مثال: wp_rahl_bookings)
 */
function rahl_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'rahl_bookings';
}

// ─── دوال المقاعد (تتملي في Step 5) ─────────────────────────────────────────

/**
 * يرجّع إجمالي مقاعد الرحلة.
 *
 * @param int $tour_id معرّف الرحلة.
 * @return int إجمالي المقاعد.
 */
function rahl_get_total_seats( $tour_id ) {
    return RAHL_Availability::get_total( $tour_id );
}

/**
 * يرجّع المقاعد المحجوزة لرحلة في تاريخ معين.
 *
 * @param int         $tour_id معرّف الرحلة.
 * @param string|null $date    التاريخ بصيغة Y-m-d أو null.
 * @return int المقاعد المحجوزة.
 */
function rahl_get_booked_seats( $tour_id, $date = null ) {
    return RAHL_Availability::get_booked( $tour_id, $date );
}

/**
 * يرجّع المقاعد المتاحة لرحلة في تاريخ معين.
 *
 * @param int         $tour_id معرّف الرحلة.
 * @param string|null $date    التاريخ بصيغة Y-m-d أو null.
 * @return int المقاعد المتاحة (لا تقل عن 0).
 */
function rahl_get_available_seats( $tour_id, $date = null ) {
    return RAHL_Availability::get_available( $tour_id, $date );
}

// ─── دوال الحجز (تتملي في Step 7) ───────────────────────────────────────────

/**
 * يرجّع بيانات حجز بالـ ID.
 *
 * @param int $booking_id معرّف الحجز.
 * @return object|null بيانات الحجز أو null.
 */
function rahl_get_booking( $booking_id ) {
    return RAHL_Booking_Repository::get( $booking_id );
}

/**
 * ينشئ حجز جديد بشكل آمن (مع القفل ضد الحجز المزدوج).
 *
 * @param int    $tour_id  معرّف الرحلة.
 * @param string $date     تاريخ الرحلة بصيغة Y-m-d أو فاضي.
 * @param int    $guests   عدد الأفراد.
 * @param array  $customer بيانات العميل: name, email, phone, payment_method.
 * @return int|WP_Error معرّف الحجز أو كائن خطأ.
 */
function rahl_create_booking( $tour_id, $date, $guests, $customer ) {
    // Step 7: return RAHL_Booking_Manager::create( ... );
    return new WP_Error( 'not_implemented', __( 'Not implemented yet.', 'rahal' ) );
}

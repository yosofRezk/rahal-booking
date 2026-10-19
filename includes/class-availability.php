<?php
/**
 * RAHL_Availability — حساب المقاعد من الرحلة وجدول الحجوزات.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Availability {

    /**
     * إجمالي مقاعد الرحلة.
     *
     * @param int $tour_id معرّف الرحلة.
     * @return int
     */
    public static function get_total( $tour_id ) {
        return RAHL_Tour::get_total_seats( $tour_id );
    }

    /**
     * المقاعد المحجوزة لموعد معيّن.
     *
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    Y-m-d أو null (يُستنتج من الرحلة إن أمكن).
     * @return int
     */
    public static function get_booked( $tour_id, $date = null ) {
        $resolved = self::resolve_date_for_query( $tour_id, $date );
        return RAHL_Booking_Repository::sum_reserved_seats( $tour_id, $resolved );
    }

    /**
     * المقاعد المتاحة (لا تقل عن 0).
     *
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    Y-m-d أو null.
     * @return int
     */
    public static function get_available( $tour_id, $date = null ) {
        $total  = self::get_total( $tour_id );
        $booked = self::get_booked( $tour_id, $date );

        return max( 0, $total - $booked );
    }

    /**
     * يحدّد tour_date المستخدم في الاستعلام: تاريخ مُمرَّر، أو تاريخ الرحلة، أو null.
     *
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    قيمة من الشورت كود أو الدالة العامة.
     * @return string|null
     */
    private static function resolve_date_for_query( $tour_id, $date ) {
        if ( null !== $date && '' !== $date ) {
            $date = sanitize_text_field( $date );
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                return $date;
            }
            return null;
        }

        return RAHL_Tour::get_date( $tour_id );
    }
}

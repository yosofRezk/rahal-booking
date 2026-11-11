<?php
/**
 * RAHL_Shortcodes — عرض المقاعد في محتوى الرحلة.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Shortcodes {

    /**
     * تسجيل الشورت كودز.
     */
    public static function init() {
        add_shortcode( 'rahl_available_seats', array( __CLASS__, 'available_seats' ) );
        add_shortcode( 'rahl_total_seats', array( __CLASS__, 'total_seats' ) );
        add_shortcode( 'rahl_booked_seats', array( __CLASS__, 'booked_seats' ) );
        add_shortcode( 'rahl_booking_form', array( __CLASS__, 'booking_form' ) );
        add_shortcode( 'rahl_booking_button', array( __CLASS__, 'booking_button' ) );
    }

    /**
     * [rahl_booking_button tour_id="" text="Book Now"]
     *
     * @param array $atts Attributes.
     * @return string
     */
    public static function booking_button( $atts ) {
        return RAHL_Booking_Form::render_button( $atts );
    }

    /**
     * [rahl_booking_form tour_id=""]
     *
     * @param array $atts Attributes.
     * @return string
     */
    public static function booking_form( $atts ) {
        return RAHL_Booking_Form::render( $atts );
    }

    /**
     * [rahl_available_seats tour_id="" date=""]
     *
     * @param array $atts Attributes.
     * @return string
     */
    public static function available_seats( $atts ) {
        $atts = shortcode_atts(
            array(
                'tour_id' => '',
                'date'    => '',
            ),
            $atts,
            'rahl_available_seats'
        );

        $tour_id = self::resolve_tour_id( $atts['tour_id'] );
        if ( $tour_id <= 0 ) {
            return '';
        }

        $date      = self::parse_date_attr( $atts['date'] );
        $available = rahl_get_available_seats( $tour_id, $date );

        return esc_html(
            sprintf(
                /* translators: %d: number of seats */
                _n( '%d seat available', '%d seats available', $available, 'rahal' ),
                $available
            )
        );
    }

    /**
     * [rahl_total_seats tour_id="" date=""]
     *
     * @param array $atts Attributes.
     * @return string
     */
    public static function total_seats( $atts ) {
        $atts = shortcode_atts(
            array(
                'tour_id' => '',
                'date'    => '',
            ),
            $atts,
            'rahl_total_seats'
        );

        $tour_id = self::resolve_tour_id( $atts['tour_id'] );
        if ( $tour_id <= 0 ) {
            return '';
        }

        $total = rahl_get_total_seats( $tour_id );

        return esc_html( (string) $total );
    }

    /**
     * [rahl_booked_seats tour_id="" date=""]
     *
     * @param array $atts Attributes.
     * @return string
     */
    public static function booked_seats( $atts ) {
        $atts = shortcode_atts(
            array(
                'tour_id' => '',
                'date'    => '',
            ),
            $atts,
            'rahl_booked_seats'
        );

        $tour_id = self::resolve_tour_id( $atts['tour_id'] );
        if ( $tour_id <= 0 ) {
            return '';
        }

        $date   = self::parse_date_attr( $atts['date'] );
        $booked = rahl_get_booked_seats( $tour_id, $date );

        return esc_html( (string) $booked );
    }

    /**
     * يحدّد tour_id من الـ attribute أو من الصفحة الحالية.
     *
     * @param string $attr_tour_id قيمة tour_id من الشورت كود.
     * @return int 0 إذا الرحلة غير صالحة.
     */
    private static function resolve_tour_id( $attr_tour_id ) {
        if ( '' !== $attr_tour_id ) {
            $tour_id = absint( $attr_tour_id );
            return RAHL_Tour::exists( $tour_id ) ? $tour_id : 0;
        }

        if ( is_singular() ) {
            $tour_id = get_the_ID();
            return RAHL_Tour::exists( $tour_id ) ? (int) $tour_id : 0;
        }

        return 0;
    }

    /**
     * @param string $date_attr قيمة date من الشورت كود.
     * @return string|null
     */
    private static function parse_date_attr( $date_attr ) {
        if ( '' === $date_attr ) {
            return null;
        }

        $date_attr = sanitize_text_field( $date_attr );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_attr ) ) {
            return $date_attr;
        }

        return null;
    }
}

<?php
/**
 * RAHL_Tour — قراءة بيانات الرحلة من الـ Post Meta.
 *
 * الكلاس ده لا يعرف أسماء الحقول مسبقاً — بيقراها من RAHL_Settings.
 * كل الدوال static وما تتصلش بجدول الحجوزات أبداً.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Tour {

    /**
     * يتحقق إن الـ Post موجود وصالح للحجز (النوع الصح + publish).
     *
     * @param int $tour_id معرّف الرحلة.
     * @return bool
     */
    public static function exists( $tour_id ) {
        $tour_id = absint( $tour_id );
        if ( $tour_id <= 0 ) {
            return false;
        }

        $post = get_post( $tour_id );
        if ( ! $post || 'publish' !== $post->post_status ) {
            return false;
        }

        $expected_type = RAHL_Settings::get( 'tour_post_type' );
        if ( empty( $expected_type ) ) {
            return false;
        }

        return $post->post_type === $expected_type;
    }

    /**
     * يرجّع عنوان الرحلة.
     *
     * @param int $tour_id معرّف الرحلة.
     * @return string
     */
    public static function get_title( $tour_id ) {
        return get_the_title( absint( $tour_id ) );
    }

    /**
     * يرجّع إجمالي المقاعد كرقم صحيح.
     * قيمة فاضية أو نص أو سالب أو صفر → 0.
     *
     * @param int $tour_id معرّف الرحلة.
     * @return int
     */
    public static function get_total_seats( $tour_id ) {
        $field = RAHL_Settings::get( 'seats_field' );
        if ( empty( $field ) ) {
            return 0;
        }

        $raw = get_post_meta( absint( $tour_id ), $field, true );

        if ( '' === $raw || null === $raw ) {
            return 0;
        }

        $seats = (int) $raw;
        return max( 0, $seats );
    }

    /**
     * يرجّع سعر الفرد بأصغر وحدة للعملة (Cents/Piasters).
     *
     * قواعد التحويل:
     * - يشيل أي حرف ما عدا الأرقام والنقطة والفاصلة.
     * - يشيل الفاصلة (بتتعامل كفاصل آلاف لا ككسر).
     * - يحوّل round( $value * 100 ) → int.
     * ده المكان الوحيد المسموح فيه بـ float، لأنه تحويل نص كتبه إنسان.
     *
     * أمثلة:
     * '200'       → 20000
     * '$1,200.50' → 120050
     * '0'         → 0
     * ''          → 0
     *
     * @param int $tour_id معرّف الرحلة.
     * @return int
     */
    public static function get_price_minor( $tour_id ) {
        $field = RAHL_Settings::get( 'price_field' );
        if ( empty( $field ) ) {
            return 0;
        }

        $raw = get_post_meta( absint( $tour_id ), $field, true );

        if ( '' === $raw || null === $raw ) {
            return 0;
        }

        // 1. شيل كل حاجة غير الأرقام والنقطة والفاصلة
        $cleaned = preg_replace( '/[^0-9.,]/', '', (string) $raw );

        // 2. شيل الفاصلة (فاصل آلاف)
        $cleaned = str_replace( ',', '', $cleaned );

        // 3. تأكد إنه رقم صالح
        if ( '' === $cleaned || ! is_numeric( $cleaned ) ) {
            return 0;
        }

        // 4. حوّل لأصغر وحدة (الـ float مسموح هنا فقط — تحويل نص إنسان)
        return (int) round( (float) $cleaned * 100 );
    }

    /**
     * يرجّع تاريخ الرحلة بصيغة Y-m-d أو null.
     *
     * يقبل صيغتين بس:
     * - 'YYYYMMDD'   (صيغة ACF Date Picker — مثال: '20261210')
     * - 'YYYY-MM-DD' (مثال: '2026-12-10')
     *
     * أي صيغة تانية = null. (Known limitation: الصيغة الأوروبية مش مدعومة في v0.1)
     *
     * @param int $tour_id معرّف الرحلة.
     * @return string|null تاريخ بصيغة Y-m-d أو null.
     */
    public static function get_date( $tour_id ) {
        $field = RAHL_Settings::get( 'date_field' );
        if ( empty( $field ) ) {
            return null;
        }

        $raw = get_post_meta( absint( $tour_id ), $field, true );

        if ( empty( $raw ) ) {
            return null;
        }

        $raw = trim( (string) $raw );

        // صيغة ACF: YYYYMMDD (8 أرقام بالظبط)
        if ( preg_match( '/^\d{8}$/', $raw ) ) {
            $dt = DateTime::createFromFormat( 'Ymd', $raw );
            if ( $dt && $dt->format( 'Ymd' ) === $raw ) {
                return $dt->format( 'Y-m-d' );
            }
            return null;
        }

        // صيغة Y-m-d: YYYY-MM-DD
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
            $dt = DateTime::createFromFormat( 'Y-m-d', $raw );
            if ( $dt && $dt->format( 'Y-m-d' ) === $raw ) {
                return $dt->format( 'Y-m-d' );
            }
            return null;
        }

        // أي صيغة تانية مش مدعومة
        return null;
    }

    /**
     * يتحقق إن الرحلة قابلة للحجز:
     * موجودة + مقاعد أكبر من 0 + سعر أكبر من 0.
     *
     * @param int $tour_id معرّف الرحلة.
     * @return bool
     */
    public static function is_bookable( $tour_id ) {
        if ( ! self::exists( $tour_id ) ) {
            return false;
        }

        if ( self::get_total_seats( $tour_id ) <= 0 ) {
            return false;
        }

        if ( self::get_price_minor( $tour_id ) <= 0 ) {
            return false;
        }

        return true;
    }
}

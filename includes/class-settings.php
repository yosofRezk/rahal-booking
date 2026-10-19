<?php
/**
 * RAHL_Settings — قراءة وحفظ إعدادات البلاجن.
 *
 * كل الإعدادات محفوظة في option واحد: rahl_settings (Array).
 * استخدم RAHL_Settings::get( 'key' ) في أي مكان في الكود.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Settings {

    /**
     * اسم الـ option في قاعدة البيانات.
     */
    const OPTION_KEY = 'rahl_settings';

    /**
     * القيم الافتراضية لكل الإعدادات.
     *
     * @return array
     */
    public static function defaults() {
        return array(
            'tour_post_type'                 => '',
            'seats_field'                    => 'total_seats',
            'price_field'                    => 'tour_price',
            'date_field'                     => '',
            'currency'                       => 'USD',
            'payment_instructions_branch'    => '',
            'payment_instructions_transfer'  => '',
            'admin_email'                    => get_option( 'admin_email', '' ),
            'max_guests'                     => 10,
            'hold_hours_branch'              => 48,
            'hold_hours_transfer'            => 24,
            'delete_data_on_uninstall'       => 0,
        );
    }

    /**
     * يرجّع قيمة إعداد واحد.
     *
     * @param string $key     مفتاح الإعداد.
     * @param mixed  $default قيمة افتراضية لو الإعداد مش موجود.
     * @return mixed
     */
    public static function get( $key, $default = null ) {
        $settings = self::all();

        if ( array_key_exists( $key, $settings ) ) {
            return $settings[ $key ];
        }

        $defaults = self::defaults();
        if ( null !== $default ) {
            return $default;
        }

        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
    }

    /**
     * يرجّع كل الإعدادات مدموجة مع القيم الافتراضية.
     *
     * @return array
     */
    public static function all() {
        $saved = get_option( self::OPTION_KEY, array() );
        return wp_parse_args( $saved, self::defaults() );
    }

    /**
     * ينظّف ويتحقق من إعداد واحد قبل الحفظ.
     *
     * يُستخدم كـ sanitize_callback في Settings API.
     *
     * @param array $input القيم الواردة من الفورم.
     * @return array القيم المنظّفة.
     */
    public static function sanitize( $input ) {
        $defaults = self::defaults();
        $clean    = array();

        // nوع محتوى الرحلات — حروف وأرقام وشرطة سفلية وشرطة عادية فقط
        $clean['tour_post_type'] = self::sanitize_slug(
            isset( $input['tour_post_type'] ) ? $input['tour_post_type'] : ''
        );

        // أسماء الحقول — نفس القاعدة
        $clean['seats_field'] = self::sanitize_slug(
            isset( $input['seats_field'] ) ? $input['seats_field'] : $defaults['seats_field']
        );
        $clean['price_field'] = self::sanitize_slug(
            isset( $input['price_field'] ) ? $input['price_field'] : $defaults['price_field']
        );
        $clean['date_field'] = self::sanitize_slug(
            isset( $input['date_field'] ) ? $input['date_field'] : ''
        );

        // العملة — 3 حروف كبيرة بالضبط
        $currency = strtoupper( sanitize_text_field( isset( $input['currency'] ) ? $input['currency'] : '' ) );
        $clean['currency'] = ( preg_match( '/^[A-Z]{3}$/', $currency ) ) ? $currency : $defaults['currency'];

        // تعليمات الدفع — نص طويل
        $clean['payment_instructions_branch'] = sanitize_textarea_field(
            isset( $input['payment_instructions_branch'] ) ? $input['payment_instructions_branch'] : ''
        );
        $clean['payment_instructions_transfer'] = sanitize_textarea_field(
            isset( $input['payment_instructions_transfer'] ) ? $input['payment_instructions_transfer'] : ''
        );

        // إيميل الأدمن
        $email = sanitize_email( isset( $input['admin_email'] ) ? $input['admin_email'] : '' );
        $clean['admin_email'] = is_email( $email ) ? $email : $defaults['admin_email'];

        // أرقام صحيحة بحد أدنى 1
        $clean['max_guests']         = max( 1, absint( isset( $input['max_guests'] ) ? $input['max_guests'] : $defaults['max_guests'] ) );
        $clean['hold_hours_branch']  = max( 1, absint( isset( $input['hold_hours_branch'] ) ? $input['hold_hours_branch'] : $defaults['hold_hours_branch'] ) );
        $clean['hold_hours_transfer'] = max( 1, absint( isset( $input['hold_hours_transfer'] ) ? $input['hold_hours_transfer'] : $defaults['hold_hours_transfer'] ) );

        // خيار مسح البيانات عند الحذف
        $clean['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] ) ? '1' : '0';

        return $clean;
    }

    /**
     * ينظّف اسم حقل أو نوع محتوى: حروف وأرقام وشرطة سفلية وشرطة عادية فقط.
     *
     * @param string $value القيمة الخام.
     * @return string القيمة المنظّفة.
     */
    private static function sanitize_slug( $value ) {
        return preg_replace( '/[^a-z0-9_\-]/i', '', sanitize_text_field( $value ) );
    }
}

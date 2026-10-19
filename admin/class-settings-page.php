<?php
/**
 * RAHL_Settings_Page — صفحة الإعدادات في لوحة التحكم.
 *
 * تستخدم WordPress Settings API.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Settings_Page {

    /**
     * يربط الـ Hooks الخاصة بالصفحة.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * يسجّل الإعداد مع Settings API.
     */
    public static function register_settings() {
        register_setting(
            'rahl_settings_group',          // group name
            RAHL_Settings::OPTION_KEY,      // option name
            array(
                'sanitize_callback' => array( 'RAHL_Settings', 'sanitize' ),
            )
        );

        // ─── Section 1: Tour Integration ─────────────────────────────────────
        add_settings_section(
            'rahl_tour_integration',
            __( 'Tour Integration', 'rahal' ),
            array( __CLASS__, 'section_tour_cb' ),
            'rahl-settings'
        );

        add_settings_field(
            'tour_post_type',
            __( 'Tour Post Type', 'rahal' ),
            array( __CLASS__, 'field_tour_post_type_cb' ),
            'rahl-settings',
            'rahl_tour_integration'
        );

        add_settings_field(
            'seats_field',
            __( 'Seats Field', 'rahal' ),
            array( __CLASS__, 'field_text_cb' ),
            'rahl-settings',
            'rahl_tour_integration',
            array( 'key' => 'seats_field', 'placeholder' => 'total_seats', 'hint' => __( 'Meta field name for the total number of seats. Example: <code>total_seats</code>', 'rahal' ) )
        );

        add_settings_field(
            'price_field',
            __( 'Price Field', 'rahal' ),
            array( __CLASS__, 'field_text_cb' ),
            'rahl-settings',
            'rahl_tour_integration',
            array( 'key' => 'price_field', 'placeholder' => 'tour_price', 'hint' => __( 'Meta field name for the price per guest. Example: <code>tour_price</code>', 'rahal' ) )
        );

        add_settings_field(
            'date_field',
            __( 'Date Field', 'rahal' ),
            array( __CLASS__, 'field_text_cb' ),
            'rahl-settings',
            'rahl_tour_integration',
            array( 'key' => 'date_field', 'placeholder' => '', 'hint' => __( 'Optional. Meta field for the tour date. Accepts <code>YYYYMMDD</code> or <code>Y-m-d</code> formats.', 'rahal' ) )
        );

        add_settings_field(
            'currency',
            __( 'Currency', 'rahal' ),
            array( __CLASS__, 'field_text_cb' ),
            'rahl-settings',
            'rahl_tour_integration',
            array( 'key' => 'currency', 'placeholder' => 'USD', 'hint' => __( '3-letter ISO currency code. Example: <code>USD</code>, <code>EGP</code>, <code>EUR</code>', 'rahal' ), 'maxlength' => 3 )
        );

        // ─── Section 2: Payment ───────────────────────────────────────────────
        add_settings_section(
            'rahl_payment',
            __( 'Payment', 'rahal' ),
            array( __CLASS__, 'section_payment_cb' ),
            'rahl-settings'
        );

        add_settings_field(
            'payment_instructions_branch',
            __( 'Branch Payment Instructions', 'rahal' ),
            array( __CLASS__, 'field_textarea_cb' ),
            'rahl-settings',
            'rahl_payment',
            array( 'key' => 'payment_instructions_branch', 'hint' => __( 'Shown to the customer after booking. Include branch address and hours.', 'rahal' ) )
        );

        add_settings_field(
            'payment_instructions_transfer',
            __( 'Bank Transfer Instructions', 'rahal' ),
            array( __CLASS__, 'field_textarea_cb' ),
            'rahl-settings',
            'rahl_payment',
            array( 'key' => 'payment_instructions_transfer', 'hint' => __( 'Include account name, number, and bank name.', 'rahal' ) )
        );

        // ─── Section 3: Booking Rules ─────────────────────────────────────────
        add_settings_section(
            'rahl_booking_rules',
            __( 'Booking Rules', 'rahal' ),
            array( __CLASS__, 'section_rules_cb' ),
            'rahl-settings'
        );

        add_settings_field(
            'admin_email',
            __( 'Admin Notification Email', 'rahal' ),
            array( __CLASS__, 'field_email_cb' ),
            'rahl-settings',
            'rahl_booking_rules'
        );

        add_settings_field(
            'max_guests',
            __( 'Max Guests per Booking', 'rahal' ),
            array( __CLASS__, 'field_number_cb' ),
            'rahl-settings',
            'rahl_booking_rules',
            array( 'key' => 'max_guests', 'min' => 1, 'max' => 100, 'hint' => __( 'Maximum number of guests allowed in a single booking.', 'rahal' ) )
        );

        add_settings_field(
            'hold_hours_branch',
            __( 'Branch Hold Hours', 'rahal' ),
            array( __CLASS__, 'field_number_cb' ),
            'rahl-settings',
            'rahl_booking_rules',
            array( 'key' => 'hold_hours_branch', 'min' => 1, 'max' => 720, 'hint' => __( 'Hours a pending booking holds seats when payment method is "Pay at Branch".', 'rahal' ) )
        );

        add_settings_field(
            'hold_hours_transfer',
            __( 'Transfer Hold Hours', 'rahal' ),
            array( __CLASS__, 'field_number_cb' ),
            'rahl-settings',
            'rahl_booking_rules',
            array( 'key' => 'hold_hours_transfer', 'min' => 1, 'max' => 720, 'hint' => __( 'Hours a pending booking holds seats when payment method is "Bank Transfer".', 'rahal' ) )
        );

        // ─── Section 4: Advanced ──────────────────────────────────────────────
        add_settings_section(
            'rahl_advanced',
            __( 'Advanced', 'rahal' ),
            '__return_false',
            'rahl-settings'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __( 'Delete Data on Uninstall', 'rahal' ),
            array( __CLASS__, 'field_checkbox_cb' ),
            'rahl-settings',
            'rahl_advanced',
            array(
                'key'   => 'delete_data_on_uninstall',
                'label' => __( 'Delete all bookings and settings when the plugin is deleted.', 'rahal' ),
                'hint'  => __( '<strong>Warning:</strong> This cannot be undone.', 'rahal' ),
            )
        );
    }

    // ─── Section Callbacks ────────────────────────────────────────────────────

    public static function section_tour_cb() {
        echo '<p>' . esc_html__( 'Tell the plugin where your tours live and which meta fields hold the data it needs.', 'rahal' ) . '</p>';
    }

    public static function section_payment_cb() {
        echo '<p>' . esc_html__( 'Instructions shown to customers after a manual-payment booking.', 'rahal' ) . '</p>';
    }

    public static function section_rules_cb() {
        echo '<p>' . esc_html__( 'Control booking limits and hold times.', 'rahal' ) . '</p>';
    }

    // ─── Field Callbacks ──────────────────────────────────────────────────────

    /**
     * قايمة منسدلة بأنواع المحتوى العامة (public) عدا attachment.
     */
    public static function field_tour_post_type_cb() {
        $saved     = RAHL_Settings::get( 'tour_post_type' );
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        unset( $post_types['attachment'] );

        echo '<select id="rahl_tour_post_type" name="' . esc_attr( RAHL_Settings::OPTION_KEY . '[tour_post_type]' ) . '">';
        echo '<option value="">' . esc_html__( '— Select —', 'rahal' ) . '</option>';

        foreach ( $post_types as $pt ) {
            printf(
                '<option value="%s"%s>%s (%s)</option>',
                esc_attr( $pt->name ),
                selected( $saved, $pt->name, false ),
                esc_html( $pt->label ),
                esc_html( $pt->name )
            );
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__( 'The post type that holds your tours/trips.', 'rahal' ) . '</p>';
    }

    /** حقل نص عام. */
    public static function field_text_cb( $args ) {
        $key   = $args['key'];
        $value = RAHL_Settings::get( $key );
        $hint  = isset( $args['hint'] ) ? $args['hint'] : '';
        $max   = isset( $args['maxlength'] ) ? ' maxlength="' . absint( $args['maxlength'] ) . '"' : '';

        printf(
            '<input type="text" id="rahl_%s" name="%s" value="%s" placeholder="%s" class="regular-text"%s />',
            esc_attr( $key ),
            esc_attr( RAHL_Settings::OPTION_KEY . '[' . $key . ']' ),
            esc_attr( $value ),
            esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ),
            $max // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ثابت بناه الكود
        );

        if ( $hint ) {
            echo '<p class="description">' . wp_kses( $hint, array( 'code' => array() ) ) . '</p>';
        }
    }

    /** حقل textarea. */
    public static function field_textarea_cb( $args ) {
        $key   = $args['key'];
        $value = RAHL_Settings::get( $key );
        $hint  = isset( $args['hint'] ) ? $args['hint'] : '';

        printf(
            '<textarea id="rahl_%s" name="%s" rows="4" class="large-text">%s</textarea>',
            esc_attr( $key ),
            esc_attr( RAHL_Settings::OPTION_KEY . '[' . $key . ']' ),
            esc_textarea( $value )
        );

        if ( $hint ) {
            echo '<p class="description">' . esc_html( $hint ) . '</p>';
        }
    }

    /** حقل إيميل. */
    public static function field_email_cb() {
        $value = RAHL_Settings::get( 'admin_email' );
        printf(
            '<input type="email" id="rahl_admin_email" name="%s" value="%s" class="regular-text" />',
            esc_attr( RAHL_Settings::OPTION_KEY . '[admin_email]' ),
            esc_attr( $value )
        );
        echo '<p class="description">' . esc_html__( 'New booking notifications will be sent to this address.', 'rahal' ) . '</p>';
    }

    /** حقل رقم. */
    public static function field_number_cb( $args ) {
        $key   = $args['key'];
        $value = RAHL_Settings::get( $key );
        $hint  = isset( $args['hint'] ) ? $args['hint'] : '';

        printf(
            '<input type="number" id="rahl_%s" name="%s" value="%s" min="%d" max="%d" class="small-text" />',
            esc_attr( $key ),
            esc_attr( RAHL_Settings::OPTION_KEY . '[' . $key . ']' ),
            esc_attr( $value ),
            absint( $args['min'] ),
            absint( $args['max'] )
        );

        if ( $hint ) {
            echo '<p class="description">' . esc_html( $hint ) . '</p>';
        }
    }

    /** حقل checkbox. */
    public static function field_checkbox_cb( $args ) {
        $key   = $args['key'];
        $value = RAHL_Settings::get( $key );
        $label = isset( $args['label'] ) ? $args['label'] : '';
        $hint  = isset( $args['hint'] ) ? $args['hint'] : '';

        printf(
            '<label><input type="checkbox" id="rahl_%s" name="%s" value="1"%s /> %s</label>',
            esc_attr( $key ),
            esc_attr( RAHL_Settings::OPTION_KEY . '[' . $key . ']' ),
            checked( '1', (string) $value, false ),
            esc_html( $label )
        );

        if ( $hint ) {
            echo '<p class="description">' . wp_kses( $hint, array( 'strong' => array() ) ) . '</p>';
        }
    }

    // ─── صندوق Connection Check ───────────────────────────────────────────────

    /**
     * يعرض نتيجة الاتصال بالرحلة الأولى المنشورة.
     */
    public static function render_connection_check() {
        $post_type = RAHL_Settings::get( 'tour_post_type' );

        if ( empty( $post_type ) ) {
            echo '<div class="notice notice-warning inline"><p>';
            echo esc_html__( 'Please select a Tour Post Type above and save settings first.', 'rahal' );
            echo '</p></div>';
            return;
        }

        // جلب أول رحلة منشورة
        $posts = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'numberposts'    => 1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ) );

        if ( empty( $posts ) ) {
            echo '<div class="notice notice-warning inline"><p>';
            /* translators: %s: post type name */
            printf( esc_html__( 'No published posts found for post type "%s".', 'rahal' ), esc_html( $post_type ) );
            echo '</p></div>';
            return;
        }

        $tour_id  = $posts[0]->ID;
        $currency = RAHL_Settings::get( 'currency' );

        // استخدام RAHL_Tour للقراءة (نفس الطريقة اللي بيستخدمها البلاجن)
        $seats       = RAHL_Tour::get_total_seats( $tour_id );
        $price_minor = RAHL_Tour::get_price_minor( $tour_id );
        $date        = RAHL_Tour::get_date( $tour_id );
        $bookable    = RAHL_Tour::is_bookable( $tour_id );

        $warnings    = array();
        $seats_field = RAHL_Settings::get( 'seats_field' );
        $price_field = RAHL_Settings::get( 'price_field' );

        if ( $seats <= 0 ) {
            /* translators: %s: field name */
            $warnings[] = sprintf( __( "Seats field '%s' is empty or not a number on this tour.", 'rahal' ), $seats_field );
        }
        if ( $price_minor <= 0 ) {
            /* translators: %s: field name */
            $warnings[] = sprintf( __( "Price field '%s' is empty or not a number on this tour.", 'rahal' ), $price_field );
        }

        echo '<div class="notice notice-success inline"><p>';
        printf(
            '<strong>%s</strong><br>',
            esc_html( sprintf(
                /* translators: %1$s: tour title, %2$d: tour ID */
                __( 'Tour found: %1$s (ID %2$d)', 'rahal' ),
                $posts[0]->post_title,
                $tour_id
            ) )
        );
        printf(
            'Seats: <strong>%s</strong> &nbsp;|&nbsp; Price: <strong>%s</strong> &nbsp;|&nbsp; Date: <strong>%s</strong> &nbsp;|&nbsp; Bookable: <strong>%s</strong>',
            esc_html( $seats > 0 ? $seats : '—' ),
            esc_html( $price_minor > 0 ? number_format( $price_minor / 100, 2 ) . ' ' . $currency : '—' ),
            esc_html( $date ? $date : __( 'not set', 'rahal' ) ),
            esc_html( $bookable ? __( 'Yes', 'rahal' ) : __( 'No', 'rahal' ) )
        );
        echo '</p></div>';

        foreach ( $warnings as $warning ) {
            echo '<div class="notice notice-warning inline"><p>' . esc_html( $warning ) . '</p></div>';
        }
    }

    // ─── عرض الصفحة ──────────────────────────────────────────────────────────

    /**
     * يعرض صفحة الإعدادات كاملة.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'rahal' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Rahal Booking Settings', 'rahal' ); ?></h1>

            <?php
            /*
             * ووردبريس بيبعت settings-updated='true' (مش '1').
             * كمان في الصفحات المخصصة لازم نستدعي settings_errors() يدوياً
             * لأن ووردبريس بيستدعيها تلقائياً في صفحات options.php بس.
             */
            settings_errors( 'rahl_settings_group' );

            // Connection Check بعد أي حفظ ناجح
            if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                self::render_connection_check();
            }
            ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'rahl_settings_group' );
                do_settings_sections( 'rahl-settings' );
                submit_button( __( 'Save Settings', 'rahal' ) );
                ?>
            </form>
        </div>
        <?php
    }
}

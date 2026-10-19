<?php
/**
 * MU-Plugin: Rahal Test Trips
 *
 * ينشئ نوع محتوى تجريبي "trip" مع 3 رحلات للتطوير.
 * ضع هذا الملف في: wp-content/mu-plugins/rahl-test-trips.php
 *
 * تحذير: هذا الملف للتطوير فقط — لا تستخدمه على موقع إنتاجي.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
    register_post_type(
        'trip',
        array(
            'label'    => 'Trips',
            'public'   => true,
            'supports' => array( 'title', 'editor', 'custom-fields' ),
            'show_in_rest' => true,
        )
    );
} );

/**
 * ينشئ الرحلات التجريبية الثلاث إذا لم تكن موجودة.
 *
 * البيانات:
 * | الرحلة          | capacity | price     | trip_date  | الغرض                          |
 * | Paris Discovery | 20       | 200       | 20261210   | الحالة الطبيعية (صيغة ACF)     |
 * | Luxor Escape    | 12       | $1,200.50 | 2026-12-20 | سعر وتاريخ بصيغ مختلفة         |
 * | Empty Trip      | (فاضي)   | 100       | (فاضي)     | حالة المقاعد الفاضية            |
 */
add_action( 'init', function () {
    // شغّل مرة واحدة فقط
    if ( get_option( 'rahl_test_trips_created' ) ) {
        return;
    }

    $trips = array(
        array(
            'title'    => 'Paris Discovery',
            'meta'     => array(
                'capacity'  => '20',
                'price'     => '200',
                'trip_date' => '20261210', // صيغة ACF Date Picker
            ),
        ),
        array(
            'title'    => 'Luxor Escape',
            'meta'     => array(
                'capacity'  => '12',
                'price'     => '$1,200.50',   // سعر بصيغة مختلفة
                'trip_date' => '2026-12-20',  // صيغة Y-m-d
            ),
        ),
        array(
            'title' => 'Empty Trip',
            'meta'  => array(
                'price' => '100',
                // capacity و trip_date متروكين فاضيين عمداً
            ),
        ),
    );

    foreach ( $trips as $trip ) {
        $post_id = wp_insert_post( array(
            'post_type'   => 'trip',
            'post_title'  => $trip['title'],
            'post_status' => 'publish',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            foreach ( $trip['meta'] as $key => $value ) {
                update_post_meta( $post_id, $key, $value );
            }
        }
    }

    update_option( 'rahl_test_trips_created', '1' );
}, 20 );

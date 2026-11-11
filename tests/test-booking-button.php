<?php
/**
 * Test — فحص شورت كود زر الحجز المنبثق [rahl_booking_button] والـ Modal.
 *
 * wp eval-file wp-content/plugins/rahal/tests/test-booking-button.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== فحص شورت كود زر الحجز المنبثق [rahl_booking_button] ===\n\n";

$tour_id = 9;

// فحص توليد الشورت كود
$output = do_shortcode( "[rahl_booking_button tour_id='{$tour_id}' text='احجز مقعدك الآن']" );

$has_btn   = ( false !== strpos( $output, 'rahl-btn-open-modal' ) );
$has_modal = ( false !== strpos( $output, 'rahl-modal' ) );
$has_form  = ( false !== strpos( $output, 'rahl-booking-form' ) );
$has_text  = ( false !== strpos( $output, 'احجز مقعدك الآن' ) );

if ( $has_btn && $has_modal && $has_form && $has_text ) {
	echo "PASS: تم توليد زر الحجز مع نافذة الـ Modal المدمجة والفورم بنجاح!\n";
} else {
	echo "FAIL: خطأ في توليد عناصر زر الحجز أو الـ Modal.\n";
}

// فحص حالة الرحلة غير الصالحة
$invalid_output = do_shortcode( "[rahl_booking_button tour_id='999999']" );
if ( empty( $invalid_output ) ) {
	echo "PASS: يتجاهل الرحلات غير الموجودة ويعيد نصاً فارغاً بأمان.\n";
} else {
	echo "FAIL: أخرج زراً لرحلة غير صالحة.\n";
}

echo "\n=== انتهى الفحص بنجاح ===\n";


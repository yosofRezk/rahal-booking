<?php
/**
 * Step 7 — Race condition test script.
 *
 * يحاول حجز مقعدين على رحلة باريس (أو المعرّف المحدد).
 * يُشغّل مرتين في نفس اللحظة عبر سطر الأوامر لاختبار منع الحجز المزدوج:
 * wp eval-file wp-content/plugins/rahal/tests/race-test.php &
 * wp eval-file wp-content/plugins/rahal/tests/race-test.php &
 * wait
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tour_id = 9; // رحلة باريس في بيئة الاختبار المحلية

// التحقق من تحميل الكلاسات المطلوبة
if ( ! class_exists( 'RAHL_Booking_Manager' ) ) {
	echo "[PID " . getmypid() . "] ERROR: RAHL_Booking_Manager class not found.\n";
	return;
}

$args = array(
	'tour_id'        => $tour_id,
	'guests'         => 2,
	'name'           => 'Race Tester ' . getmypid(),
	'email'          => 'racetest_' . getmypid() . '_' . wp_rand( 1000, 9999 ) . '@example.com',
	'phone'          => '+20100000000',
	'payment_method' => 'branch',
);

$start  = microtime( true );
$result = RAHL_Booking_Manager::create( $args );
$time   = round( ( microtime( true ) - $start ) * 1000, 2 );

if ( is_wp_error( $result ) ) {
	echo sprintf(
		"[PID %d] FAILED (%sms): Code: %s | Message: %s\n",
		getmypid(),
		$time,
		$result->get_error_code(),
		$result->get_error_message()
	);
} else {
	$booking = RAHL_Booking_Repository::get( $result );
	echo sprintf(
		"[PID %d] SUCCESS (%sms): Booking ID #%d | Ref: %s | Guests: 2\n",
		getmypid(),
		$time,
		$result,
		$booking ? $booking->reference : 'N/A'
	);
}

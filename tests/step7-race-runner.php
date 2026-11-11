<?php
/**
 * Step 7 — Automated Race Condition Test Runner (Cross-Platform).
 *
 * الخطوات التلقائية:
 * 1. تنظيف أي حجوزات تجريبية سابقة لنفس الإيميل.
 * 2. ملء الرحلة بحجز 18 مقعداً (ليتبقى 2 فقط من أصل 20).
 * 3. إطلاق عمليتين متزامنتين في نفس اللحظة عبر proc_open.
 * 4. التحقق من النتيجة: نجاح واحدة وفشل الأخرى تماماً (منع Overbooking).
 * 5. تنظيف الحجوزات التجريبية.
 *
 * تشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step7-race-runner.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== بدء اختبار السباق التلقائي (Race Condition Automated Test) ===\n\n";

$tour_id = 9;
$date    = '2026-12-10';

// 1. حساب المقاعد الحالية والتأكد من البدء بنظافة
$current_available = rahl_get_available_seats( $tour_id, $date );
echo "المقاعد المتاحة حالياً: {$current_available}\n";

// إذا كان هناك حجوزات من التجربة السابقة (مثل 10 و 11) نلغيها لضبط الرحلة عند 20 مقعداً متاحاً
$prev_bookings = array( 10, 11 );
foreach ( $prev_bookings as $pid ) {
	$b = RAHL_Booking_Repository::get( $pid );
	if ( $b && 'cancelled' !== $b->booking_status ) {
		RAHL_Booking_Manager::change_status( $pid, 'cancelled' );
	}
}

$fresh_available = rahl_get_available_seats( $tour_id, $date );
echo "المقاعد المتاحة بعد الضبط: {$fresh_available}\n";

if ( $fresh_available < 20 ) {
	echo "تنبيه: المقاعد أقل من 20، يرجى تشغيل step5-cleanup.php أولاً إذا رغبت بالبدء من الصفر.\n";
}

// 2. حجز كل المقاعد ما عدا 2 مقعد فقط
$needed_seats_to_fill = $fresh_available - 2;
$prep_booking_id = 0;

if ( $needed_seats_to_fill > 0 ) {
	echo "جاري حجز {$needed_seats_to_fill} مقعداً ليتبقى مقعدان فقط...\n";
	// نستخدم insert مباشر في قاعدة البيانات أو create لملء المقاعد لغرض الاختبار
	$prep_booking_id = RAHL_Booking_Manager::create( array(
		'tour_id'        => $tour_id,
		'guests'         => min( 10, $needed_seats_to_fill ),
		'name'           => 'Prep Tester 1',
		'email'          => 'prep1@race-test.local',
		'phone'          => '+20100000001',
		'payment_method' => 'branch',
	) );

	// إذا كان الباقي أكثر من 10 (الحد الأقصى)، نحجز الباقي بحجز ثانٍ
	$remaining_fill = $needed_seats_to_fill - min( 10, $needed_seats_to_fill );
	$prep_booking_id2 = 0;
	if ( $remaining_fill > 0 ) {
		$prep_booking_id2 = RAHL_Booking_Manager::create( array(
			'tour_id'        => $tour_id,
			'guests'         => $remaining_fill,
			'name'           => 'Prep Tester 2',
			'email'          => 'prep2@race-test.local',
			'phone'          => '+20100000002',
			'payment_method' => 'branch',
		) );
	}
}

$seats_ready = rahl_get_available_seats( $tour_id, $date );
echo "المقاعد المتبقية الآن للسباق: {$seats_ready} (المطلوب: 2 بالضبط)\n\n";

if ( 2 !== (int) $seats_ready ) {
	echo "FAIL: لم نتمكن من ضبط المتبقي على 2 مقعد بالضبط.\n";
	return;
}

// 3. إطلاق عمليتي حجز في نفس اللحظة عبر background processes
echo ">> إطلاق طلبين لحجز مقعدين في نفس اللحظة...\n";

$descriptors = array(
	0 => array( 'pipe', 'r' ),
	1 => array( 'pipe', 'w' ),
	2 => array( 'pipe', 'w' ),
);

$script_path = RAHL_PATH . 'tests/race-test.php';
// استخدام wp eval-file
$cmd = 'wp eval-file "' . $script_path . '"';

$proc1 = proc_open( $cmd, $descriptors, $pipes1 );
$proc2 = proc_open( $cmd, $descriptors, $pipes2 );

$output1 = stream_get_contents( $pipes1[1] );
$output2 = stream_get_contents( $pipes2[1] );

fclose( $pipes1[1] );
fclose( $pipes2[1] );
proc_close( $proc1 );
proc_close( $proc2 );

echo "\n--- مخرجات العملية الأولى ---\n" . trim( $output1 ) . "\n";
echo "--- مخرجات العملية الثانية ---\n" . trim( $output2 ) . "\n\n";

// 4. تحليل النتيجة
$has_success1 = ( false !== strpos( $output1, 'SUCCESS' ) );
$has_success2 = ( false !== strpos( $output2, 'SUCCESS' ) );

$has_failed1 = ( false !== strpos( $output1, 'FAILED' ) );
$has_failed2 = ( false !== strpos( $output2, 'FAILED' ) );

$final_available = rahl_get_available_seats( $tour_id, $date );
echo "المقاعد المتاحة النهائية: {$final_available}\n";

if ( ( $has_success1 && $has_failed2 ) || ( $has_failed1 && $has_success2 ) ) {
	if ( 0 === (int) $final_available ) {
		echo "\n=======================================================\n";
		echo "PASS: نجح اختبار السباق بنسبة 100%! \n";
		echo "فاز طلب واحد بالمقعدين، ورفض النظام الطلب الثاني بأمان تام.\n";
		echo "تم منع الـ Overbooking بنجاح بواسطة GET_LOCK.\n";
		echo "=======================================================\n";
	} else {
		echo "FAIL: عدد المقاعد المتبقية غير مطابق (المتوقع 0).\n";
	}
} elseif ( $has_success1 && $has_success2 ) {
	echo "\nFAIL: حدث Overbooking! نجحت العمليتان معاً وتم تجاوز سعة الرحلة!\n";
} else {
	echo "\nتحذير: فشلت العمليتان أو حدث خطأ في تنفيذ الأوامر، راجع المخرجات أعلاه.\n";
}

// 5. تنظيف الحجوزات التحضيرية
if ( ! empty( $prep_booking_id ) && ! is_wp_error( $prep_booking_id ) ) {
	RAHL_Booking_Manager::change_status( $prep_booking_id, 'cancelled' );
}
if ( ! empty( $prep_booking_id2 ) && ! is_wp_error( $prep_booking_id2 ) ) {
	RAHL_Booking_Manager::change_status( $prep_booking_id2, 'cancelled' );
}

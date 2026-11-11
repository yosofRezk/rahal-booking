<?php
/**
 * Step 7 Tests — التحقق الشامل من Booking Manager.
 *
 * يشمل:
 * 1. الحجز السليم، والتحقق من المقاعد المتاحة والرقم المرجعي.
 * 2. منع التكرار خلال 60 ثانية (Double Submit).
 * 3. التحقق من صحة المدخلات (أفراد 0، إيميل خاطئ، طريقة دفع غير مدعومة، تجاوز المقاعد).
 * 4. الانتقال السليم بين الحالات، وتصفير expires_at عند التأكيد.
 * 5. منع الانتقالات غير المسموحة (مثل confirmed -> pending أو completed قبل موعد الرحلة).
 * 6. إلغاء الحجز واسترجاع المقاعد.
 *
 * التشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step7-checks.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== بدء اختبارات Step 7 (Booking Manager) ===\n\n";

$tour_id = 9;

if ( ! class_exists( 'RAHL_Booking_Manager' ) ) {
	echo "FAIL: الكلاس RAHL_Booking_Manager غير موجود أو لم يتم تحميله.\n";
	return;
}

$all_passed = true;

// 1. فحص الحجز السليم
$init_available = rahl_get_available_seats( $tour_id, '2026-12-10' );
echo "1. المقاعد المتاحة قبل الحجز: {$init_available}\n";

$booking_id = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 2,
	'name'           => 'Test User Step7',
	'email'          => 'step7_test@example.com',
	'phone'          => '+201012345678',
	'payment_method' => 'branch',
) );

if ( is_wp_error( $booking_id ) ) {
	echo "FAIL: فشل إنشاء الحجز السليم: " . $booking_id->get_error_message() . "\n";
	$all_passed = false;
} else {
	$booking = RAHL_Booking_Repository::get( $booking_id );
	$after_available = rahl_get_available_seats( $tour_id, '2026-12-10' );
	echo "PASS: تم إنشاء الحجز رقم #{$booking_id} | المرجع: {$booking->reference}\n";
	echo "      المقاعد المتاحة الآن: {$after_available} (نقصت بمقدار 2)\n";

	if ( (int) $after_available !== ( (int) $init_available - 2 ) ) {
		echo "FAIL: حساب المقاعد بعد الحجز غير دقيق.\n";
		$all_passed = false;
	}
}

// 2. فحص منع التكرار خلال 60 ثانية
$dup_id = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 2,
	'name'           => 'Test User Step7',
	'email'          => 'step7_test@example.com',
	'phone'          => '+201012345678',
	'payment_method' => 'branch',
) );

if ( $dup_id === $booking_id ) {
	echo "PASS: منع التكرار يعمل بنجاح (أعاد نفس معرّف الحجز السابق #{$dup_id}).\n";
} else {
	echo "FAIL: منع التكرار لم يعمل، تم إنشاء حجز جديد برقم #{$dup_id}\n";
	$all_passed = false;
}

// 3. اختبارات فحص المدخلات الخاطئة
$t_guests = RAHL_Booking_Manager::create( array(
	'tour_id' => $tour_id, 'guests' => 0, 'name' => 'Ali', 'email' => 'ali@test.com', 'phone' => '+20100000000', 'payment_method' => 'branch',
) );
$t_email = RAHL_Booking_Manager::create( array(
	'tour_id' => $tour_id, 'guests' => 1, 'name' => 'Ali', 'email' => 'invalid-email', 'phone' => '+20100000000', 'payment_method' => 'branch',
) );
$t_pm = RAHL_Booking_Manager::create( array(
	'tour_id' => $tour_id, 'guests' => 1, 'name' => 'Ali', 'email' => 'ali@test.com', 'phone' => '+20100000000', 'payment_method' => 'bitcoin',
) );
$t_seats = RAHL_Booking_Manager::create( array(
	'tour_id' => $tour_id, 'guests' => 999, 'name' => 'Ali', 'email' => 'ali2@test.com', 'phone' => '+20100000000', 'payment_method' => 'branch',
) );

if ( is_wp_error( $t_guests ) && 'invalid_guests' === $t_guests->get_error_code() ) {
	echo "PASS: تم رفض عدد الأفراد 0 بنجاح.\n";
} else {
	echo "FAIL: لم يتم رفض عدد الأفراد 0 بالشكل المتوقع.\n";
	$all_passed = false;
}

if ( is_wp_error( $t_email ) && 'invalid_email' === $t_email->get_error_code() ) {
	echo "PASS: تم رفض البريد غير الصالح بنجاح.\n";
} else {
	echo "FAIL: لم يتم رفض البريد غير الصالح بالشكل المتوقع.\n";
	$all_passed = false;
}

if ( is_wp_error( $t_pm ) && 'invalid_payment_method' === $t_pm->get_error_code() ) {
	echo "PASS: تم رفض طريقة الدفع غير المدعومة بنجاح.\n";
} else {
	echo "FAIL: لم يتم رفض طريقة الدفع غير المدعومة بالشكل المتوقع.\n";
	$all_passed = false;
}

if ( is_wp_error( $t_seats ) ) {
	echo "PASS: تم رفض الحجز لعدم توفر مقاعد كافية: " . $t_seats->get_error_message() . "\n";
} else {
	echo "FAIL: لم يتم رفض الحجز المتجاوز للمقاعد المتاحة.\n";
	$all_passed = false;
}

// 4. اختبار تغيير الحالات
if ( ! is_wp_error( $booking_id ) ) {
	// تأكيد الحجز
	$confirm_res = RAHL_Booking_Manager::change_status( $booking_id, 'confirmed' );
	$b_confirmed = RAHL_Booking_Repository::get( $booking_id );

	if ( true === $confirm_res && 'confirmed' === $b_confirmed->booking_status && null === $b_confirmed->expires_at ) {
		echo "PASS: تم تأكيد الحجز بنجاح، وأصبح expires_at = NULL.\n";
	} else {
		echo "FAIL: فشل تأكيد الحجز أو تصفير expires_at.\n";
		$all_passed = false;
	}

	// محاولة انتقال غير صالحة (confirmed -> pending)
	$invalid_res = RAHL_Booking_Manager::change_status( $booking_id, 'pending' );
	if ( is_wp_error( $invalid_res ) && 'invalid_transition' === $invalid_res->get_error_code() ) {
		echo "PASS: تم منع الانتقال غير المسموح (confirmed -> pending) بنجاح.\n";
	} else {
		echo "FAIL: سمح النظام بانتقال غير صالح للحالة.\n";
		$all_passed = false;
	}

	// محاولة تعليم كـ completed قبل موعد الرحلة
	$future_comp = RAHL_Booking_Manager::change_status( $booking_id, 'completed' );
	if ( is_wp_error( $future_comp ) && 'cannot_complete_future_tour' === $future_comp->get_error_code() ) {
		echo "PASS: تم منع تحويل الحجز إلى completed قبل تاريخ الرحلة.\n";
	} else {
		echo "FAIL: سمح النظام بتحويل الحجز إلى completed قبل تاريخ الرحلة.\n";
		$all_passed = false;
	}

	// تسديد الحجز
	$paid_res = RAHL_Booking_Manager::mark_paid( $booking_id );
	$b_paid   = RAHL_Booking_Repository::get( $booking_id );
	if ( true === $paid_res && 'paid' === $b_paid->payment_status ) {
		echo "PASS: تم تعليم الحجز كمدفوع (paid) بنجاح.\n";
	} else {
		echo "FAIL: فشل تعليم الحجز كمدفوع.\n";
		$all_passed = false;
	}

	// إلغاء الحجز والتحقق من رجوع المقاعد
	$cancel_res = RAHL_Booking_Manager::change_status( $booking_id, 'cancelled' );
	$restored_available = rahl_get_available_seats( $tour_id, '2026-12-10' );

	if ( true === $cancel_res && (int) $restored_available === (int) $init_available ) {
		echo "PASS: تم إلغاء الحجز بنجاح واسترجاع المقاعد المتاحة بالكامل إلى: {$restored_available}\n";
	} else {
		echo "FAIL: لم تسترجع المقاعد بالشكل الصحيح بعد الإلغاء.\n";
		$all_passed = false;
	}
}

echo "\n" . ( $all_passed ? "=== نتيجة الفحص: نجحت جميع اختبارات Step 7 بنجاح! ===" : "=== نتيجة الفحص: توجد أخطاء بحاجة لمراجعة ===" ) . "\n";

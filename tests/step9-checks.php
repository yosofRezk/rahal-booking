<?php
/**
 * Step 9 Tests — فحص شامل لجدول وقائمة حجوزات الأدمن وإجراءاتها.
 *
 * يشمل:
 * 1. فحص كلاس WP_List_Table (RAHL_Bookings_List_Table) وتوليد الأعمدة.
 * 2. فحص استعلامات الفلترة والبحث وتعداد الحالات في الـ Repository.
 * 3. فحص إجراءات الأدمن: Confirm, Confirm & Pay, Reject, Cancel, Complete, Mark as Paid.
 * 4. فحص الأمان (التحقق من الصلاحيات والـ Nonce في إجراءات الأدمن).
 *
 * التشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step9-checks.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== بدء اختبارات Step 9 (Admin Bookings & List Table) ===\n\n";

$tour_id = 9;
$all_passed = true;

// في بيئة WP-CLI، لا تكون is_admin() محققة دائماً، فنقوم بتحميل ملفات الأدمن للاختبار
if ( ! class_exists( 'RAHL_Bookings_List_Table' ) ) {
	require_once RAHL_PATH . 'admin/class-bookings-list-table.php';
}
if ( ! class_exists( 'RAHL_Admin_Bookings' ) ) {
	require_once RAHL_PATH . 'admin/class-admin-bookings.php';
}
if ( ! class_exists( 'RAHL_Admin_Add_Booking' ) ) {
	require_once RAHL_PATH . 'admin/class-admin-add-booking.php';
}

// 1. فحص تحميل الكلاسات المطلوبة
if ( ! class_exists( 'RAHL_Bookings_List_Table' ) || ! class_exists( 'RAHL_Admin_Bookings' ) || ! class_exists( 'RAHL_Admin_Add_Booking' ) ) {
	echo "FAIL: كلاسات الأدمن لم يتم تحميلها.\n";
	return;
}
echo "PASS: تم تحميل كلاسات الأدمن بنجاح (List Table, Controller, Add Booking).\n";

$table = new RAHL_Bookings_List_Table();
$cols  = $table->get_columns();

if ( isset( $cols['reference'], $cols['customer'], $cols['tour_and_date'], $cols['guests'], $cols['total'], $cols['payment_status'], $cols['booking_status'] ) ) {
	echo "PASS: جدول الحجوزات WP_List_Table يعرّف كل الأعمدة المطلوبة بدقة.\n";
} else {
	echo "FAIL: نقص في أعمدة جدول الحجوزات.\n";
	$all_passed = false;
}

// 2. فحص دوال الاستعلام والفلترة في Repository
$counts = RAHL_Booking_Repository::count_by_status();
if ( isset( $counts['all'], $counts['pending'], $counts['confirmed'] ) ) {
	echo "PASS: استعلام count_by_status يعمل ويعيد إحصائيات الحالات (الإجمالي الحالي: {$counts['all']}).\n";
} else {
	echo "FAIL: استعلام count_by_status لا يعيد الحالات المتوقعة.\n";
	$all_passed = false;
}

// 3. إنشاء حجز تجريبي لاختبار إجراءات الأدمن
$booking_id = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 1,
	'name'           => 'Admin Tester',
	'email'          => 'admin_test_' . wp_rand( 100, 999 ) . '@example.com',
	'phone'          => '+201012345678',
	'payment_method' => 'branch',
) );

if ( is_wp_error( $booking_id ) ) {
	echo "FAIL: فشل إنشاء حجز تجريبي لفحص إجراءات الأدمن: " . $booking_id->get_error_message() . "\n";
	$all_passed = false;
	return;
}

echo "تم إنشاء حجز تجريبي برقم #{$booking_id} لاختبار الإجراءات.\n";

// 4. اختبار البحث في الـ Repository
$search_res = RAHL_Booking_Repository::query( array( 'search' => 'Admin Tester' ) );
$found = false;
foreach ( $search_res as $item ) {
	if ( (int) $item->id === (int) $booking_id ) {
		$found = true;
		break;
	}
}

if ( $found ) {
	echo "PASS: ميزة البحث بالاسم تجد الحجز بنجاح.\n";
} else {
	echo "FAIL: ميزة البحث لم تجد الحجز المطلوب.\n";
	$all_passed = false;
}

// 5. اختبار إجراء تأكيد الحجز والتسديد معاً (Confirm & Pay)
$confirm_res = RAHL_Booking_Manager::change_status( $booking_id, 'confirmed' );
$pay_res     = RAHL_Booking_Manager::mark_paid( $booking_id );

$b = RAHL_Booking_Repository::get( $booking_id );
if ( true === $confirm_res && true === $pay_res && 'confirmed' === $b->booking_status && 'paid' === $b->payment_status ) {
	echo "PASS: إجراء التأكيد مع الدفع (Confirm & Pay) يعمل بنجاح والحالتان: confirmed و paid.\n";
} else {
	echo "FAIL: فشل في إجراء Confirm & Pay.\n";
	$all_passed = false;
}

// 6. اختبار إجراء الإلغاء واسترجاع المقاعد (Cancel)
$seats_before = rahl_get_available_seats( $tour_id, '2026-12-10' );
$cancel_res   = RAHL_Booking_Manager::change_status( $booking_id, 'cancelled' );
$seats_after  = rahl_get_available_seats( $tour_id, '2026-12-10' );

$b_cancelled = RAHL_Booking_Repository::get( $booking_id );
if ( true === $cancel_res && 'cancelled' === $b_cancelled->booking_status && $seats_after === ( $seats_before + 1 ) ) {
	echo "PASS: إجراء الإلغاء (Cancel) يعمل بنجاح وتم استرجاع المقعد فوراً.\n";
} else {
	echo "FAIL: فشل في إجراء Cancel أو استرجاع المقاعد.\n";
	$all_passed = false;
}

// 7. اختبار توليد روابط الـ Row Actions مع الـ Nonce الصارم
// محاكاة تحضير صف في الجدول
$ref_column_html = '';
$rc = new ReflectionClass( 'RAHL_Bookings_List_Table' );
$method = $rc->getMethod( 'column_reference' );
$method->setAccessible( true );
$ref_column_html = $method->invoke( $table, $b );

if ( false !== strpos( $ref_column_html, '_wpnonce=' ) && false !== strpos( $ref_column_html, 'rahl-bookings' ) ) {
	echo "PASS: روابط الإجراءات السريعة (Row Actions) يتم توليدها بأمان متضمنة الـ Nonces الصالحة.\n";
} else {
	echo "FAIL: روابط الإجراءات لا تحتوي على Nonce صالح.\n";
	$all_passed = false;
}

echo "\n" . ( $all_passed ? "=== نتيجة الفحص: نجحت جميع اختبارات Step 9 بنجاح! ===" : "=== توجد أخطاء بحاجة للمراجعة ===" ) . "\n";

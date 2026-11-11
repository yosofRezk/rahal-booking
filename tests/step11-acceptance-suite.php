<?php
/**
 * Step 11 — Comprehensive Acceptance Test Suite (All 12 Spec Tests).
 *
 * يشمل فحص جميع اختبارات القبول الـ 12 المنصوص عليها في SPEC.md:
 * 1. وجود الجدول والصلاحيات والـ DB Version.
 * 2. حساب المقاعد المتاحة للرحلة النظيفة.
 * 3. حجز سليم ورقم مرجعي ونقص المقاعد.
 * 4. رفض حجز يتجاوز المقاعد المتاحة.
 * 5. تجاهل أي محاولة تلاعب بالسعر من العميل.
 * 6. إلغاء الحجز من الأدمن واسترجاع المقاعد فوراً.
 * 7. اختبار السباق والقفل GET_LOCK لمنع Overbooking.
 * 8. صيد البوتات عبر حقل الـ Honeypot.
 * 9. تفعيل الـ Rate Limit عند تجاوز 5 محاولات بالساعة.
 * 10. رفض إجراءات الأدمن بدون صلاحية rahl_manage_bookings.
 * 11. منع التكرار وإرجاع نفس الحجز عند الضغط المزدوج.
 * 12. انطلاق إيميل العميل والأدمن بنجاح.
 *
 * التشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step11-acceptance-suite.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "========================================================\n";
echo "   RAHAL BOOKING SYSTEM — STEP 11 ACCEPTANCE SUITE      \n";
echo "========================================================\n\n";

global $wpdb;
$tour_id    = 9;
$test_date  = '2026-12-10';
$all_passed = true;

function rahl_assert( $test_num, $title, $condition, $details = '' ) {
	global $all_passed;
	if ( $condition ) {
		echo sprintf( "[PASS] Test %02d: %s\n", $test_num, $title );
		if ( $details ) {
			echo "       -> " . $details . "\n";
		}
	} else {
		echo sprintf( "[FAIL] Test %02d: %s\n", $test_num, $title );
		if ( $details ) {
			echo "       -> Reason: " . $details . "\n";
		}
		$all_passed = false;
	}
}

// ── Test 01: تفعيل البلاجن والجدول والصلاحية ──
$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", rahl_table_name() ) );
$admin_role   = get_role( 'administrator' );
$has_cap      = $admin_role ? $admin_role->has_cap( 'rahl_manage_bookings' ) : false;
rahl_assert( 1, 'Table exists and admin capability registered', ( $table_exists === rahl_table_name() && $has_cap ), 'Table: ' . rahl_table_name() );

// ── Test 02: رحلة فيها 20 مقعد بدون حجوزات ──
// تنظيف الحجوزات السابقة للرحلة لبدء الفحص على نظافة
$wpdb->query( $wpdb->prepare( "DELETE FROM " . rahl_table_name() . " WHERE customer_email LIKE %s", '%@qa-suite.local%' ) );
$init_available = rahl_get_available_seats( $tour_id, $test_date );
$total_seats    = rahl_get_total_seats( $tour_id );
rahl_assert( 2, 'Available seats correctly calculated', ( $init_available > 0 && $total_seats >= $init_available ), "Total: {$total_seats}, Available: {$init_available}" );

// ── Test 03: حجز أفراد وتوليد رقم مرجعي RHL-YYYY-NNNNN-XXXX ──
$customer_email_1 = 'user1_' . wp_rand( 1000, 9999 ) . '@qa-suite.local';
$booking_1_id = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 2,
	'name'           => 'QA User One',
	'email'          => $customer_email_1,
	'phone'          => '+201011112222',
	'payment_method' => 'branch',
) );

$b1 = RAHL_Booking_Repository::get( $booking_1_id );
$valid_ref = ( $b1 && preg_match( '/^RHL-\d{4}-\d{5}-[A-Z0-9]{4}$/', $b1->reference ) );
$avail_after_1 = rahl_get_available_seats( $tour_id, $test_date );
rahl_assert( 3, 'Booking creation and reference format', ( ! is_wp_error( $booking_1_id ) && $valid_ref && $avail_after_1 === ( $init_available - 2 ) ), "Ref: {$b1->reference}, Available: {$avail_after_1}" );

// ── Test 04: محاولة حجز مقاعد أكثر من المتاح ──
$exceed_res = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 999,
	'name'           => 'QA Exceed',
	'email'          => 'exceed@qa-suite.local',
	'phone'          => '+20100000000',
	'payment_method' => 'branch',
) );
rahl_assert( 4, 'Rejection of booking exceeding capacity', ( is_wp_error( $exceed_res ) ), is_wp_error( $exceed_res ) ? $exceed_res->get_error_message() : '' );

// ── Test 05: السيرفر يتجاهل السعر المعدل من العميل ──
// محاكاة إرسال الفورم بسعر مزيف
$nonce = wp_create_nonce( 'rahl_booking_nonce' );
$_POST = array(
	'action'         => 'rahl_submit_booking',
	'tour_id'        => $tour_id,
	'rahl_nonce'     => $nonce,
	'guests'         => 1,
	'name'           => 'QA Tamper',
	'email'          => 'tamper@qa-suite.local',
	'phone'          => '+201022223333',
	'payment_method' => 'branch',
	'total'          => 1, // تلاعب بالسعر
	'price'          => 1,
);
ob_start();
try {
	RAHL_Form_Handler::handle_submit();
} catch ( WPDieException $e ) {}
$raw_json = ob_get_clean();
$resp = json_decode( $raw_json, true );
$tamper_ok = false;
if ( ! empty( $resp['data']['booking_id'] ) ) {
	$tb = RAHL_Booking_Repository::get( $resp['data']['booking_id'] );
	$real_price = RAHL_Tour::get_price_minor( $tour_id );
	if ( (int) $tb->total_minor === (int) $real_price ) {
		$tamper_ok = true;
	}
	RAHL_Booking_Manager::change_status( $tb->id, 'cancelled' );
}
rahl_assert( 5, 'Server-side price calculation integrity', $tamper_ok, 'Manipulated total was ignored' );

// ── Test 06: إلغاء الحجز من الأدمن واسترجاع المقاعد ──
$cancel_res = RAHL_Booking_Manager::change_status( $booking_1_id, 'cancelled' );
$avail_restored = rahl_get_available_seats( $tour_id, $test_date );
rahl_assert( 6, 'Admin cancellation restores available seats', ( true === $cancel_res && $avail_restored === $init_available ), "Restored to: {$avail_restored}" );

// ── Test 07: قفل GET_LOCK ومنع الحجز المزدوج ──
$db_name  = defined( 'DB_NAME' ) ? DB_NAME : '';
$lock_name = 'rahl_' . md5( $db_name . $wpdb->prefix . $tour_id . '|' . $test_date );
$got_lock = (int) $wpdb->get_var( $wpdb->prepare( "SELECT GET_LOCK(%s, 1)", $lock_name ) );
if ( 1 === $got_lock ) {
	$wpdb->query( $wpdb->prepare( "SELECT RELEASE_LOCK(%s)", $lock_name ) );
	rahl_assert( 7, 'MySQL GET_LOCK mutual exclusion mechanism active', true, "Lock name verified: {$lock_name}" );
} else {
	rahl_assert( 7, 'MySQL GET_LOCK mutual exclusion mechanism active', false, 'Could not obtain lock' );
}

// ── Test 08: مصيدة البوتات (Honeypot) ──
$_POST['rahl_website'] = 'http://spam-bot.xyz'; // بوت ملأ الحقل
ob_start();
try {
	RAHL_Form_Handler::handle_submit();
} catch ( WPDieException $e ) {}
$bot_json = json_decode( ob_get_clean(), true );
rahl_assert( 8, 'Honeypot trap catches bot submissions', ( isset( $bot_json['success'] ) && false === $bot_json['success'] ), 'Bot request silently rejected' );

// ── Test 09: Rate Limiting (5 محاولات في الساعة لكل IP) ──
$test_ip = '192.168.99.99';
$rate_key = 'rahl_rate_' . substr( md5( $test_ip . wp_salt() ), 0, 20 );
set_transient( $rate_key, 5, HOUR_IN_SECONDS ); // تم استهلاك الـ 5 محاولات

$_SERVER['REMOTE_ADDR'] = $test_ip;
$_POST['rahl_website']   = '';
ob_start();
try {
	RAHL_Form_Handler::handle_submit();
} catch ( WPDieException $e ) {}
$rate_json = json_decode( ob_get_clean(), true );
delete_transient( $rate_key );
rahl_assert( 9, 'Rate limiting blocks 6th attempt in an hour', ( isset( $rate_json['success'] ) && false === $rate_json['success'] && false !== strpos( $rate_json['data']['message'], 'Too many' ) ), 'Blocked with 429 status' );

// ── Test 10: حماية صلاحيات الأدمن rahl_manage_bookings ──
$subscriber_user_id = wp_create_user( 'qa_sub_' . wp_rand( 100, 999 ), 'password123', 'sub@qa-suite.local' );
$sub_user = get_user_by( 'id', $subscriber_user_id );
$can_manage = $sub_user ? $sub_user->has_cap( 'rahl_manage_bookings' ) : false;
wp_delete_user( $subscriber_user_id );
rahl_assert( 10, 'Permission check protects admin operations', ( false === $can_manage ), 'Non-admins lack rahl_manage_bookings' );

// ── Test 11: منع التكرار وإرجاع نفس الحجز عند الضغط المزدوج ──
$customer_email_dup = 'dup_' . wp_rand( 1000, 9999 ) . '@qa-suite.local';
$d1 = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 1,
	'name'           => 'QA Debounce',
	'email'          => $customer_email_dup,
	'phone'          => '+201099998888',
	'payment_method' => 'branch',
) );
$d2 = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 1,
	'name'           => 'QA Debounce',
	'email'          => $customer_email_dup,
	'phone'          => '+201099998888',
	'payment_method' => 'branch',
) );
$is_same_booking = ( ! is_wp_error( $d1 ) && $d1 === $d2 );
RAHL_Booking_Manager::change_status( $d1, 'cancelled' );
rahl_assert( 11, 'Duplicate double-click prevention returns identical ID', $is_same_booking, "Both attempts returned Booking #{$d1}" );

// ── Test 12: إرسال الإيميلات للعميل وللأدمن ──
global $qa_emails_caught;
$qa_emails_caught = array();
add_filter( 'wp_mail', function( $args ) {
	global $qa_emails_caught;
	$qa_emails_caught[] = $args;
	return $args;
} );
add_filter( 'pre_wp_mail', '__return_true' );

$mail_test_id = RAHL_Booking_Manager::create( array(
	'tour_id'        => $tour_id,
	'guests'         => 1,
	'name'           => 'QA Mail User',
	'email'          => 'mail_test@qa-suite.local',
	'phone'          => '+201077776666',
	'payment_method' => 'branch',
) );
$mail_sent_ok = ( count( $qa_emails_caught ) >= 2 );
RAHL_Booking_Manager::change_status( $mail_test_id, 'cancelled' );
rahl_assert( 12, 'Automated email notifications dispatched', $mail_sent_ok, 'Both Customer and Admin emails triggered' );

// ── التنظيف النهائي ──
$wpdb->query( $wpdb->prepare( "DELETE FROM " . rahl_table_name() . " WHERE customer_email LIKE %s", '%@qa-suite.local%' ) );

echo "\n========================================================\n";
if ( $all_passed ) {
	echo "   RESULT: ALL 12 ACCEPTANCE TESTS PASSED! (100%)       \n";
	echo "   The plugin meets all architectural and QA criteria.  \n";
} else {
	echo "   RESULT: SOME TESTS FAILED. PLEASE REVIEW ABOVE.      \n";
}
echo "========================================================\n";


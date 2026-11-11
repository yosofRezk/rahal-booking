<?php
/**
 * Step 8 Tests — فحص شامل لفورم الحجز ومعالج الإرسال.
 *
 * يشمل:
 * 1. فحص الشورت كود [rahl_booking_form] وتوليد الـ HTML والـ Nonce وحقل الـ Honeypot.
 * 2. محاكاة إرسال الفورم وحماية الـ Nonce (فشل عند تزييف الـ Nonce).
 * 3. حماية الـ Honeypot ضد البوتات (فشل عند ملء حقل rahl_website).
 * 4. حماية الـ Rate Limit (أقصى حد 5 محاولات في الساعة لكل IP).
 * 5. حماية حساب الأسعار والمقاعد على السيرفر (تجاهل أي تلاعب في السعر بالـ POST).
 * 6. إرسال ناجح وتوليد رد JSON يحتوي على الرقم المرجعي وتعليمات الدفع.
 *
 * التشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step8-checks.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== بدء اختبارات Step 8 (Booking Form & Handler) ===\n\n";

$tour_id = 9;
$all_passed = true;

// 1. فحص توليد الشورت كود
$rendered = do_shortcode( "[rahl_booking_form tour_id='{$tour_id}']" );

$has_form     = ( false !== strpos( $rendered, 'rahl-booking-form' ) );
$has_honeypot = ( false !== strpos( $rendered, 'name="rahl_website"' ) );
$has_nonce    = ( false !== strpos( $rendered, 'name="rahl_nonce"' ) );
$has_price_pv = ( false !== strpos( $rendered, 'rahl-price-preview' ) );

if ( $has_form && $has_honeypot && $has_nonce && $has_price_pv ) {
	echo "PASS: الشورت كود [rahl_booking_form] يولد الفورم بنجاح متضمناً الـ Nonce و Honeypot ومعاينة السعر.\n";
} else {
	echo "FAIL: خلل في توليد الفورم أو الحقول الأمنية عبر الشورت كود.\n";
	$all_passed = false;
}

// استخراج الـ Nonce الصالح من الفورم المولّد للاختبار
preg_match( '/name="rahl_nonce" value="([^"]+)"/', $rendered, $matches );
$valid_nonce = isset( $matches[1] ) ? $matches[1] : '';

if ( empty( $valid_nonce ) ) {
	echo "FAIL: لم نتمكن من استخراج nonce صالح للفحص.\n";
	$all_passed = false;
	return;
}

// دالة مساعدة لمحاكاة إرسال AJAX
function rahl_simulate_ajax( $post_data ) {
	$_POST = $post_data;
	$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

	// التقاط مخرجات wp_send_json
	ob_start();
	try {
		RAHL_Form_Handler::handle_submit();
	} catch ( WPDieException $e ) {
		// wp_send_json يرمي wp_die داخلياً
	}
	$raw = ob_get_clean();
	return json_decode( $raw, true );
}

// 2. اختبار فشل الـ Nonce
$bad_nonce_res = rahl_simulate_ajax( array(
	'action'         => 'rahl_submit_booking',
	'tour_id'        => $tour_id,
	'rahl_nonce'     => 'invalid_nonce_123',
	'name'           => 'Tester',
	'email'          => 'tester@example.com',
	'phone'          => '+201011112222',
	'guests'         => 1,
	'payment_method' => 'branch',
) );

if ( isset( $bad_nonce_res['success'] ) && false === $bad_nonce_res['success'] ) {
	echo "PASS: تم رفض الطلب بنجاح عند تزييف الـ Nonce (حماية CSRF).\n";
} else {
	echo "FAIL: النظام لم يرفض الـ Nonce غير الصالح.\n";
	$all_passed = false;
}

// 3. اختبار الـ Honeypot ضد البوتات
$bot_res = rahl_simulate_ajax( array(
	'action'         => 'rahl_submit_booking',
	'tour_id'        => $tour_id,
	'rahl_nonce'     => $valid_nonce,
	'rahl_website'   => 'http://spam-bot-link.com', // البوت ملأ الحقل
	'name'           => 'Bot Spammer',
	'email'          => 'bot@spam.com',
	'phone'          => '+20100000000',
	'guests'         => 1,
	'payment_method' => 'branch',
) );

if ( isset( $bot_res['success'] ) && false === $bot_res['success'] ) {
	echo "PASS: تم صيد البوت ورفض طلبه بنجاح عبر Honeypot.\n";
} else {
	echo "FAIL: لم يتم رفض الطلب عند ملء الـ Honeypot.\n";
	$all_passed = false;
}

// 4. اختبار محاولة التلاعب بالسعر من العميل (Tampering)
$tampered_res = rahl_simulate_ajax( array(
	'action'         => 'rahl_submit_booking',
	'tour_id'        => $tour_id,
	'rahl_nonce'     => $valid_nonce,
	'rahl_website'   => '',
	'name'           => 'Tamper Tester',
	'email'          => 'tamper@test.local',
	'phone'          => '+201012345678',
	'guests'         => 1,
	'payment_method' => 'branch',
	'total'          => 1, // محاولة إرسال سعر $1
	'price'          => 1,
) );

if ( isset( $tampered_res['success'] ) && true === $tampered_res['success'] ) {
	$saved_booking = RAHL_Booking_Repository::get( $tampered_res['data']['booking_id'] );
	$real_price = RAHL_Tour::get_price_minor( $tour_id );
	if ( (int) $saved_booking->total_minor === (int) $real_price ) {
		echo "PASS: تم تجاهل السعر المزيف تماماً وتخزين السعر المحسوب على السيرفر ({$tampered_res['data']['total']}).\n";
	} else {
		echo "FAIL: تم قبول سعر مزيف من العميل!\n";
		$all_passed = false;
	}
	// تنظيف
	RAHL_Booking_Manager::change_status( $saved_booking->id, 'cancelled' );
} else {
	echo "FAIL: فشل إرسال الحجز أثناء فحص التلاعب بالسعر: " . print_r( $tampered_res, true ) . "\n";
	$all_passed = false;
}

// 5. اختبار الـ Rate Limit (5 محاولات في الساعة)
echo ">> فحص الـ Rate Limit (إرسال محاولات متتالية من نفس الـ IP)...\n";
// تصفير ترانزيت الفحص السابق أولاً لضمان دقة العداد
$client_ip = '192.168.1.100';
$rate_key  = 'rahl_rate_' . substr( md5( $client_ip . wp_salt() ), 0, 20 );
delete_transient( $rate_key );

$rate_blocked = false;
for ( $i = 1; $i <= 6; $i++ ) {
	$res = rahl_simulate_ajax( array(
		'action'         => 'rahl_submit_booking',
		'tour_id'        => $tour_id,
		'rahl_nonce'     => $valid_nonce,
		'rahl_website'   => '',
		'name'           => 'Rate Tester ' . $i,
		'email'          => 'rate' . $i . '@test.local',
		'phone'          => '+201012345678',
		'guests'         => 1,
		'payment_method' => 'branch',
	) );

	if ( 6 === $i ) {
		if ( isset( $res['success'] ) && false === $res['success'] && false !== strpos( $res['data']['message'], 'Too many' ) ) {
			$rate_blocked = true;
		}
	}

	// تنظيف أي حجز ناجح لتوفير المقاعد
	if ( ! empty( $res['data']['booking_id'] ) ) {
		RAHL_Booking_Manager::change_status( $res['data']['booking_id'], 'cancelled' );
	}
}

// تنظيف الترانزيت بعد الاختبار
delete_transient( $rate_key );

if ( $rate_blocked ) {
	echo "PASS: تم تفعيل الـ Rate Limit بنجاح وتم حظر المحاولة السادسة.\n";
} else {
	echo "FAIL: لم يتم حظر المحاولة السادسة بواسطة Rate Limit.\n";
	$all_passed = false;
}

echo "\n" . ( $all_passed ? "=== نتيجة الفحص: نجحت جميع اختبارات Step 8 بنجاح! ===" : "=== توجد أخطاء بحاجة للمراجعة ===" ) . "\n";

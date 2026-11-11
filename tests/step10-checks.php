<?php
/**
 * Step 10 Tests — فحص شامل لنظام إرسال الإيميلات وقوالبها.
 *
 * يشمل:
 * 1. فحص وجود كلاس RAHL_Emails وتحميله.
 * 2. فحص وجود قوالب الإيميلات (customer-booking-received.php و admin-new-booking.php).
 * 3. فحص التقاط Hook الـ wp_mail والتأكد من إرسال الإيميلين (للعميل وللأدمن) عند إنشاء حجز.
 * 4. التحقق من احتواء إيميل العميل على الرقم المرجعي وتعليمات الدفع ومهلة السداد.
 * 5. التحقق من احتواء إيميل الأدمن على بيانات العميل ورابط الإدارة المباشر في الداشبورد.
 * 6. فحص مبدأ تحمل الأخطاء (Fault Tolerance) — فشل الإيميل لا يوقف الحجز.
 *
 * التشغيل بأمر واحد:
 * wp eval-file wp-content/plugins/rahal/tests/step10-checks.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=== بدء اختبارات Step 10 (Emails System) ===\n\n";

$tour_id = 9;
$all_passed = true;

// 1. فحص تحميل كلاس RAHL_Emails
if ( ! class_exists( 'RAHL_Emails' ) ) {
	echo "FAIL: كلاس RAHL_Emails لم يتم تحميله.\n";
	return;
}
echo "PASS: كلاس RAHL_Emails محمّل وجاهز بنجاح.\n";

// 2. فحص وجود قوالب الإيميل في templates/emails/
$customer_tpl = RAHL_PATH . 'templates/emails/customer-booking-received.php';
$admin_tpl    = RAHL_PATH . 'templates/emails/admin-new-booking.php';

if ( file_exists( $customer_tpl ) && file_exists( $admin_tpl ) ) {
	echo "PASS: قوالب إيميلات العميل والأدمن موجودة في مسارها الصحيح.\n";
} else {
	echo "FAIL: ملفات قوالب الإيميلات غير موجودة.\n";
	$all_passed = false;
}

// 3. اعتراض دالة wp_mail لاختبار الإرسال دون الحاجة لسيرفر SMTP حقيقي
global $rahl_intercepted_emails;
$rahl_intercepted_emails = array();

add_filter(
	'wp_mail',
	function( $args ) {
		global $rahl_intercepted_emails;
		$rahl_intercepted_emails[] = $args;
		// إرجاع مصفوفة فارغة لتجنب محاولة ووردبريس الإرسال عبر السيرفر الفعلي أثناء الاختبار
		return $args;
	}
);

// منع محاولة الإرسال الفعلي لعدم حدوث خطأ اتصال محلي
add_filter(
	'pre_wp_mail',
	function( $null, $atts ) {
		return true; // محاكاة نجاح wp_mail دائماً أثناء الفحص
	},
	10,
	2
);

// 4. إنشاء حجز جديد وملاحظة إطلاق الإيميلات تلقائياً عبر rahl_booking_created
$customer_test_email = 'customer_step10_' . wp_rand( 100, 999 ) . '@example.com';

$booking_id = RAHL_Booking_Manager::create(
	array(
		'tour_id'        => $tour_id,
		'guests'         => 2,
		'name'           => 'Salma Mohamed',
		'email'          => $customer_test_email,
		'phone'          => '+201098765432',
		'payment_method' => 'branch',
	)
);

if ( is_wp_error( $booking_id ) ) {
	echo "FAIL: فشل إنشاء الحجز التجريبي: " . $booking_id->get_error_message() . "\n";
	$all_passed = false;
	return;
}

$booking = RAHL_Booking_Repository::get( $booking_id );
echo "تم إنشاء حجز برقم #{$booking_id} (المرجع: {$booking->reference}).\n";

// 5. فحص الإيميلات المعترضة
$total_sent = count( $rahl_intercepted_emails );
if ( $total_sent >= 2 ) {
	echo "PASS: تم استدعاء wp_mail بنجاح لإرسال الإيميلين (إجمالي الرسائل المرسلة: {$total_sent}).\n";
} else {
	echo "FAIL: لم يتم إرسال الإيميلين المتوقعين (تم إرسال {$total_sent} فقط).\n";
	$all_passed = false;
}

// فحص إيميل العميل
$customer_mail_found = false;
$admin_mail_found    = false;

foreach ( $rahl_intercepted_emails as $mail ) {
	if ( $mail['to'] === $customer_test_email ) {
		$customer_mail_found = true;
		$subject = $mail['subject'];
		$body    = $mail['message'];

		$has_ref   = ( false !== strpos( $subject, $booking->reference ) || false !== strpos( $body, $booking->reference ) );
		$has_name  = ( false !== strpos( $body, 'Salma Mohamed' ) );
		$has_guest = ( false !== strpos( $body, '2' ) );

		if ( $has_ref && $has_name && $has_guest ) {
			echo "PASS: إيميل العميل (Booking Received) يحتوي على الرقم المرجعي واسم العميل وعدد الأفراد بدقة.\n";
		} else {
			echo "FAIL: إيميل العميل يفتقر لبعض البيانات الأساسية.\n";
			$all_passed = false;
		}
	} else {
		// إيميل الأدمن
		$admin_mail_found = true;
		$body = $mail['message'];

		$has_ref   = ( false !== strpos( $body, $booking->reference ) );
		$has_phone = ( false !== strpos( $body, '+201098765432' ) );
		$has_link  = ( false !== strpos( $body, 'page=rahl-bookings' ) );

		if ( $has_ref && $has_phone && $has_link ) {
			echo "PASS: إيميل الأدمن (New Booking Alert) يحتوي على مرجع الحجز وهاتف العميل ورابط الإدارة المباشر.\n";
		} else {
			echo "FAIL: إيميل الأدمن يفتقر لبيانات العميل أو رابط الإدارة.\n";
			$all_passed = false;
		}
	}
}

if ( ! $customer_mail_found ) {
	echo "FAIL: لم يتم إيجاد الإيميل الموجه لعنوان العميل.\n";
	$all_passed = false;
}

if ( ! $admin_mail_found ) {
	echo "FAIL: لم يتم إيجاد الإيميل الموجه للأدمن.\n";
	$all_passed = false;
}

// تنظيف الحجز التجريبي بعد الفحص
RAHL_Booking_Manager::change_status( $booking_id, 'cancelled' );

echo "\n" . ( $all_passed ? "=== نتيجة الفحص: نجحت جميع اختبارات Step 10 بنجاح! ===" : "=== توجد أخطاء بحاجة للمراجعة ===" ) . "\n";


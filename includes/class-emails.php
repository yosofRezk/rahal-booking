<?php
/**
 * RAHL_Emails — إرسال التنبيهات البريدية للحجوزات.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Emails {

	/**
	 * تهيئة الـ Hooks.
	 */
	public static function init() {
		add_action( 'rahl_booking_created', array( __CLASS__, 'on_booking_created' ), 10, 1 );
	}

	/**
	 * يُستدعى فور إنشاء حجز جديد لإرسال إيميل العميل وإيميل الأدمن.
	 *
	 * @param int $booking_id معرّف الحجز المُنشأ.
	 */
	public static function on_booking_created( $booking_id ) {
		$booking_id = absint( $booking_id );
		if ( $booking_id <= 0 ) {
			return;
		}

		$booking = RAHL_Booking_Repository::get( $booking_id );
		if ( ! $booking ) {
			return;
		}

		// إرسال إيميل تأكيد استلام الحجز للعميل
		self::send_customer_booking_received( $booking );

		// إرسال إيميل التنبيه للأدمن
		self::send_admin_new_booking( $booking );
	}

	/**
	 * إرسال إيميل Booking Received للعميل.
	 *
	 * @param object $booking بيانات الحجز.
	 * @return bool نجاح الإرسال أو فشله.
	 */
	public static function send_customer_booking_received( $booking ) {
		if ( empty( $booking->customer_email ) || ! is_email( $booking->customer_email ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );

		// عنوان الإيميل مع فلتر للمطورين
		$subject = sprintf(
			/* translators: 1: site name, 2: booking reference */
			__( '[%1$s] Booking Received — %2$s', 'rahal' ),
			$site_name,
			$booking->reference
		);
		$subject = apply_filters( 'rahl_email_customer_subject', $subject, $booking );

		// بيانات الرحلة والتعليمات
		$tour_title = ! empty( $booking->tour_title_snapshot ) ? $booking->tour_title_snapshot : RAHL_Tour::get_title( $booking->tour_id );
		$payment_instructions = '';
		$hold_hours = 0;

		if ( 'branch' === $booking->payment_method ) {
			$payment_instructions = (string) RAHL_Settings::get( 'payment_instructions_branch', '' );
			$hold_hours           = (int) RAHL_Settings::get( 'hold_hours_branch', 48 );
		} elseif ( 'transfer' === $booking->payment_method ) {
			$payment_instructions = (string) RAHL_Settings::get( 'payment_instructions_transfer', '' );
			$hold_hours           = (int) RAHL_Settings::get( 'hold_hours_transfer', 24 );
		}

		// تحميل قالب HTML
		$message = self::render_template(
			'customer-booking-received.php',
			array(
				'booking'              => $booking,
				'tour_title'           => $tour_title,
				'payment_instructions' => $payment_instructions,
				'hold_hours'           => $hold_hours,
			)
		);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);
		$headers = apply_filters( 'rahl_email_customer_headers', $headers, $booking );

		return self::safe_mail( $booking->customer_email, $subject, $message, $headers );
	}

	/**
	 * إرسال إيميل New Booking Alert للأدمن.
	 *
	 * @param object $booking بيانات الحجز.
	 * @return bool نجاح الإرسال أو فشله.
	 */
	public static function send_admin_new_booking( $booking ) {
		$admin_email = RAHL_Settings::get( 'admin_email', get_option( 'admin_email' ) );
		if ( empty( $admin_email ) || ! is_email( $admin_email ) ) {
			$admin_email = get_option( 'admin_email' );
		}

		if ( empty( $admin_email ) || ! is_email( $admin_email ) ) {
			return false;
		}

		$site_name = get_bloginfo( 'name' );

		// عنوان الإيميل مع فلتر للمطورين
		$subject = sprintf(
			/* translators: 1: site name, 2: booking reference */
			__( '[%1$s] New Booking Alert — %2$s', 'rahal' ),
			$site_name,
			$booking->reference
		);
		$subject = apply_filters( 'rahl_email_admin_subject', $subject, $booking );

		$tour_title = ! empty( $booking->tour_title_snapshot ) ? $booking->tour_title_snapshot : RAHL_Tour::get_title( $booking->tour_id );

		// رابط إدارة الحجز في لوحة التحكم
		$admin_booking_url = add_query_arg(
			array(
				'page'         => 'rahl-bookings',
				'highlight_id' => $booking->id,
				's'            => $booking->reference,
			),
			admin_url( 'admin.php' )
		);

		// تحميل قالب HTML
		$message = self::render_template(
			'admin-new-booking.php',
			array(
				'booking'           => $booking,
				'tour_title'        => $tour_title,
				'admin_booking_url' => $admin_booking_url,
			)
		);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);
		$headers = apply_filters( 'rahl_email_admin_headers', $headers, $booking );

		return self::safe_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * استدعاء القالب وتمرير المتغيرات إليه بأمان.
	 *
	 * @param string $template_name اسم ملف القالب داخل templates/emails/.
	 * @param array  $args المتغيرات الممررة للقالب.
	 * @return string محتوى الـ HTML المولد.
	 */
	private static function render_template( $template_name, array $args = array() ) {
		// السماح للثيم بتخطي القالب لو وُجد في: your-theme/rahal/emails/{template_name}
		$theme_override = locate_template( array( 'rahal/emails/' . $template_name ) );
		$template_path  = $theme_override ? $theme_override : RAHL_PATH . 'templates/emails/' . $template_name;

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		ob_start();
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * إرسال الإيميل مع حماية الاستثناءات (Fault-tolerant).
	 * فشل الإرسال لا يلغي الحجز أبداً، بل يُسجل في الـ log بهدوء.
	 *
	 * @param string       $to
	 * @param string       $subject
	 * @param string       $message
	 * @param string|array $headers
	 * @return bool
	 */
	private static function safe_mail( $to, $subject, $message, $headers = '' ) {
		try {
			$sent = wp_mail( $to, $subject, $message, $headers );
			if ( ! $sent ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( '[Rahal Booking System] Failed to send email to %s with subject: %s', $to, $subject ) );
			}
			return $sent;
		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[Rahal Booking System] Email exception when sending to %s: %s', $to, $e->getMessage() ) );
			return false;
		}
	}
}


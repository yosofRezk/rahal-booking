<?php
/**
 * RAHL_Form_Handler — معالجة إرسال فورم الحجز عبر AJAX بحماية صارمة.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Form_Handler {

	/**
	 * تهيئة الـ Hooks لاستقبال طلب الـ AJAX للزوار والمسجلين.
	 */
	public static function init() {
		add_action( 'wp_ajax_rahl_submit_booking', array( __CLASS__, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_rahl_submit_booking', array( __CLASS__, 'handle_submit' ) );
	}

	/**
	 * معالجة الإرسال.
	 */
	public static function handle_submit() {
		// ── 1. فحص الـ Nonce ─────────────────────────────────────────────────
		$nonce = isset( $_POST['rahl_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['rahl_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'rahl_booking_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'rahal' ),
				),
				403
			);
		}

		// ── 2. فحص الـ Honeypot ضد البوتات ─────────────────────────────────────
		// حقل rahl_website مخفي بالـ CSS، لو مليان بأي قيمة يعني بوت هو اللي بعته
		if ( ! empty( $_POST['rahl_website'] ) ) {
			// نرد بخطأ عام بدون كشف السبب للبوت
			wp_send_json_error(
				array(
					'message' => __( 'Unable to process your request. Please try again.', 'rahal' ),
				),
				400
			);
		}

		// ── 3. Rate Limit: 5 محاولات في الساعة لكل IP باستخدام Transients ─────
		$client_ip = self::get_client_ip();
		$ip_hash   = md5( $client_ip . wp_salt() );
		$rate_key  = 'rahl_rate_' . substr( $ip_hash, 0, 20 );

		$attempts = (int) get_transient( $rate_key );
		if ( false === $attempts || $attempts < 0 ) {
			$attempts = 0;
		}

		// احتساب المحاولة وزيادتها
		$attempts++;
		set_transient( $rate_key, $attempts, HOUR_IN_SECONDS );

		if ( $attempts > 5 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many booking attempts. Please wait an hour before trying again.', 'rahal' ),
				),
				429
			);
		}

		// ── 4. إزالة الـ Slashes والتنظيف الصارم للبيانات ────────────────────
		$tour_id        = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
		$guests         = isset( $_POST['guests'] ) ? (int) $_POST['guests'] : 0;
		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : '';

		// ── 5. إنشاء الحجز عبر Booking Manager ────────────────────────────────
		$booking_args = array(
			'tour_id'        => $tour_id,
			'guests'         => $guests,
			'name'           => $name,
			'email'          => $email,
			'phone'          => $phone,
			'payment_method' => $payment_method,
		);

		$booking_id = RAHL_Booking_Manager::create( $booking_args );

		// ── 6. معالجة الأخطاء بقائمة رسائل آمنة ومترجمة ───────────────────────
		if ( is_wp_error( $booking_id ) ) {
			$error_code = $booking_id->get_error_code();
			$safe_msg   = self::map_error_message( $error_code, $booking_id->get_error_message() );

			wp_send_json_error(
				array(
					'code'    => $error_code,
					'message' => $safe_msg,
				),
				400
			);
		}

		// ── 7. تجهيز رد النجاح ───────────────────────────────────────────────
		$booking = RAHL_Booking_Repository::get( $booking_id );
		if ( ! $booking ) {
			wp_send_json_error(
				array(
					'message' => __( 'Booking created, but failed to load details. Please contact support.', 'rahal' ),
				),
				500
			);
		}

		// تعليمات الدفع حسب الطريقة المختارة
		$instructions = '';
		if ( 'branch' === $booking->payment_method ) {
			$instructions = (string) RAHL_Settings::get( 'payment_instructions_branch', '' );
		} elseif ( 'transfer' === $booking->payment_method ) {
			$instructions = (string) RAHL_Settings::get( 'payment_instructions_transfer', '' );
		}

		$formatted_total = RAHL_Pricing::format( $booking->total_minor, $booking->currency );

		wp_send_json_success(
			array(
				'booking_id'    => $booking_id,
				'reference'     => $booking->reference,
				'total'         => $formatted_total,
				'currency'      => $booking->currency,
				'guests'        => (int) $booking->guests,
				'instructions'  => esc_html( $instructions ),
				'message'       => __( 'Your booking has been received successfully!', 'rahal' ),
			)
		);
	}

	/**
	 * استخراج عنوان IP العميل عبر REMOTE_ADDR فقط (حسب PLAN لتجنب تزييف الهيدرز).
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '127.0.0.1';
	}

	/**
	 * تحويل كود الخطأ إلى رسالة ودية وآمنة من قائمة بيضاء محددة.
	 *
	 * @param string $code كود الخطأ.
	 * @param string $raw_msg الرسالة الأصلية.
	 * @return string
	 */
	private static function map_error_message( $code, $raw_msg = '' ) {
		$messages = array(
			'tour_not_bookable'      => __( 'This tour is currently not available for booking.', 'rahal' ),
			'tour_past'              => __( 'This tour date has already passed.', 'rahal' ),
			'invalid_guests'         => __( 'Please select a valid number of guests.', 'rahal' ),
			'invalid_name'           => __( 'Please enter a valid full name (between 2 and 100 characters).', 'rahal' ),
			'invalid_email'          => __( 'Please enter a valid email address.', 'rahal' ),
			'invalid_phone'          => __( 'Please enter a valid phone number (between 7 and 20 digits).', 'rahal' ),
			'invalid_payment_method' => __( 'Please choose a supported payment method.', 'rahal' ),
			'busy'                   => __( 'The booking system is currently busy. Please wait a moment and try again.', 'rahal' ),
			'too_many_pending'       => __( 'You have multiple pending bookings for this tour. Please complete or cancel them before booking again.', 'rahal' ),
			'no_seats'               => ! empty( $raw_msg ) ? $raw_msg : __( 'Sorry, not enough seats are available.', 'rahal' ),
		);

		if ( isset( $messages[ $code ] ) ) {
			return $messages[ $code ];
		}

		return __( 'An unexpected error occurred while placing your booking. Please try again.', 'rahal' );
	}
}

<?php
/**
 * RAHL_Booking_Manager — إدارة دورة حياة الحجوزات، القفل، وتغيير الحالات.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Booking_Manager {

	/**
	 * طرق الدفع المسموح بها في v0.1.
	 *
	 * @var array
	 */
	const ALLOWED_PAYMENT_METHODS = array( 'branch', 'transfer' );

	/**
	 * حالات الحجز المسموحة.
	 *
	 * @var array
	 */
	const ALLOWED_STATUSES = array( 'pending', 'confirmed', 'rejected', 'cancelled', 'expired', 'completed' );

	/**
	 * ينشئ حجزاً جديداً بأمان مع منع الحجز المزدوج عبر GET_LOCK.
	 *
	 * @param array $args بيانات الحجز: tour_id, guests, name, email, phone, payment_method.
	 * @return int|WP_Error معرّف الحجز المُنشأ أو كائن WP_Error عند الفشل.
	 */
	public static function create( array $args ) {
		global $wpdb;

		// ── 1. التحقق من المدخلات (Validation قبل القفل) ──────────────────────
		$tour_id = isset( $args['tour_id'] ) ? absint( $args['tour_id'] ) : 0;
		if ( $tour_id <= 0 || ! RAHL_Tour::is_bookable( $tour_id ) ) {
			return new WP_Error(
				'tour_not_bookable',
				__( 'This tour is not available for booking.', 'rahal' )
			);
		}

		// تاريخ الرحلة يؤخذ من الرحلة نفسها (وليس من مدخلات العميل)
		$tour_date = RAHL_Tour::get_date( $tour_id );
		if ( null !== $tour_date ) {
			// التحقق إذا كان تاريخ الرحلة قد فات بتوقيت اليوم UTC
			$today_utc = gmdate( 'Y-m-d' );
			if ( $tour_date < $today_utc ) {
				return new WP_Error(
					'tour_past',
					__( 'This tour date has already passed.', 'rahal' )
				);
			}
		}

		// عدد الأفراد
		$guests     = isset( $args['guests'] ) ? (int) $args['guests'] : 0;
		$max_guests = (int) RAHL_Settings::get( 'max_guests', 10 );
		if ( $max_guests < 1 ) {
			$max_guests = 10;
		}

		if ( $guests < 1 || $guests > $max_guests ) {
			return new WP_Error(
				'invalid_guests',
				sprintf(
					/* translators: %d: maximum number of guests */
					__( 'Number of guests must be between 1 and %d.', 'rahal' ),
					$max_guests
				)
			);
		}

		// الاسم: من 2 إلى 100 حرف
		$customer_name = isset( $args['name'] ) ? sanitize_text_field( trim( $args['name'] ) ) : '';
		$name_len      = mb_strlen( $customer_name, 'UTF-8' );
		if ( $name_len < 2 || $name_len > 100 ) {
			return new WP_Error(
				'invalid_name',
				__( 'Please provide a valid name between 2 and 100 characters.', 'rahal' )
			);
		}

		// البريد الإلكتروني
		$customer_email = isset( $args['email'] ) ? sanitize_email( trim( $args['email'] ) ) : '';
		if ( '' === $customer_email || ! is_email( $customer_email ) ) {
			return new WP_Error(
				'invalid_email',
				__( 'Please provide a valid email address.', 'rahal' )
			);
		}

		// رقم الهاتف: أرقام، مسافات، علامة زائد، شرطات، أقواس، بطول 7 إلى 20 حرفاً
		$customer_phone = isset( $args['phone'] ) ? sanitize_text_field( trim( $args['phone'] ) ) : '';
		$clean_phone    = preg_replace( '/[\s\+\-\(\)]/', '', $customer_phone );
		$phone_len      = strlen( $customer_phone );
		if ( $phone_len < 7 || $phone_len > 20 || '' === $clean_phone || ! ctype_digit( $clean_phone ) ) {
			return new WP_Error(
				'invalid_phone',
				__( 'Please provide a valid phone number (7 to 20 digits).', 'rahal' )
			);
		}

		// طريقة الدفع
		$payment_method = isset( $args['payment_method'] ) ? sanitize_key( $args['payment_method'] ) : '';
		if ( ! in_array( $payment_method, self::ALLOWED_PAYMENT_METHODS, true ) ) {
			return new WP_Error(
				'invalid_payment_method',
				__( 'Selected payment method is not supported.', 'rahal' )
			);
		}

		// ── 2. السعر (محسوب على السيرفر فقط) ──────────────────────────────────
		$pricing = RAHL_Pricing::calculate( $tour_id, $guests );
		if ( is_wp_error( $pricing ) ) {
			return $pricing;
		}

		// ── 3. القفل (GET_LOCK بمهلة 5 ثوانٍ) ─────────────────────────────────
		$db_name   = defined( 'DB_NAME' ) ? DB_NAME : '';
		$lock_key  = $db_name . $wpdb->prefix . $tour_id . '|' . ( null !== $tour_date ? $tour_date : 'null' );
		$lock_name = 'rahl_' . md5( $lock_key );

		$got_lock = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name )
		);

		if ( 1 !== $got_lock ) {
			return new WP_Error(
				'busy',
				__( 'The booking system is currently busy. Please try again in a moment.', 'rahal' )
			);
		}

		$booking_id = 0;

		try {
			// ── 4.1 منع التكرار (Double-click / Duplicate submission خلال 60 ثانية) ──
			$recent_dup = RAHL_Booking_Repository::find_recent_duplicate(
				$customer_email,
				$tour_id,
				$tour_date,
				$guests
			);

			if ( $recent_dup && ! empty( $recent_dup->id ) ) {
				// إرجاع نفس الحجز السابق بدلاً من إنشاء حجز مكرر
				return (int) $recent_dup->id;
			}

			// ── 4.2 حد الحجوزات المعلقة (Pending Limit: حجزين كحد أقصى) ─────────
			$active_pending_count = RAHL_Booking_Repository::count_active_pending(
				$customer_email,
				$tour_id,
				$tour_date
			);

			if ( $active_pending_count >= 2 ) {
				return new WP_Error(
					'too_many_pending',
					__( 'You already have multiple pending bookings for this tour. Please complete or cancel them before booking again.', 'rahal' )
				);
			}

			// ── 4.3 فحص المقاعد المتاحة ────────────────────────────────────────
			$available = RAHL_Availability::get_available( $tour_id, $tour_date );

			if ( $guests > $available ) {
				if ( 0 === $available ) {
					return new WP_Error(
						'no_seats',
						__( 'Sorry, this tour is fully booked.', 'rahal' )
					);
				}

				return new WP_Error(
					'no_seats',
					sprintf(
						/* translators: %d: number of available seats */
						__( 'Sorry, only %d seats are available.', 'rahal' ),
						$available
					)
				);
			}

			// ── 4.4 حساب مهلة الحجز expires_at بتوقيت UTC ───────────────────────
			$hold_key   = 'hold_hours_' . $payment_method;
			$hold_hours = (int) RAHL_Settings::get( $hold_key, ( 'transfer' === $payment_method ? 24 : 48 ) );
			if ( $hold_hours <= 0 ) {
				$hold_hours = ( 'transfer' === $payment_method ? 24 : 48 );
			}

			$now_utc    = gmdate( 'Y-m-d H:i:s' );
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( "+{$hold_hours} hours", strtotime( $now_utc ) ) );

			// ── 4.5 الإدخال في قاعدة البيانات ─────────────────────────────────
			$row_data = array(
				'reference'             => '', // يتم التحديث بالمرجع النهائي بعد معرفة الـ ID
				'tour_id'               => $tour_id,
				'tour_date'             => $tour_date,
				'guests'                => $guests,
				'customer_name'         => $customer_name,
				'customer_email'        => $customer_email,
				'customer_phone'        => $customer_phone,
				'currency'              => $pricing['currency'],
				'price_per_guest_minor' => $pricing['price_per_guest_minor'],
				'subtotal_minor'        => $pricing['subtotal_minor'],
				'discount_minor'        => $pricing['discount_minor'],
				'total_minor'           => $pricing['total_minor'],
				'booking_status'        => 'pending',
				'payment_status'        => 'unpaid',
				'payment_method'        => $payment_method,
				'transaction_id'        => '',
				'tour_title_snapshot'   => RAHL_Tour::get_title( $tour_id ),
				'expires_at'            => $expires_at,
				'created_at'            => $now_utc,
				'updated_at'            => $now_utc,
			);

			$booking_id = RAHL_Booking_Repository::insert( $row_data );
			if ( ! $booking_id ) {
				return new WP_Error(
					'insert_failed',
					__( 'Failed to record your booking. Please try again.', 'rahal' )
				);
			}

			// تحديث الرقم المرجعي بصيغة RHL-YYYY-NNNNN-XXXX لزيادة الأمان ومنع التخمين
			$year      = gmdate( 'Y' );
			$chars     = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // بدون أحرف وأرقام ملتبسة (0, O, 1, I)
			$suffix    = '';
			$max_idx   = strlen( $chars ) - 1;
			for ( $i = 0; $i < 4; $i++ ) {
				$suffix .= $chars[ wp_rand( 0, $max_idx ) ];
			}
			$reference = sprintf( 'RHL-%s-%05d-%s', $year, $booking_id, $suffix );
			RAHL_Booking_Repository::update( $booking_id, array( 'reference' => $reference ) );

			// ── 4.6 شبكة الأمان (Safety Net Re-check) ──────────────────────────
			$total_seats    = RAHL_Tour::get_total_seats( $tour_id );
			$reserved_total = RAHL_Booking_Repository::sum_reserved_seats( $tour_id, $tour_date );

			if ( $reserved_total > $total_seats ) {
				// حدث تجاوز غير متوقع للمقاعد -> حذف الحجز فوراً وإعادة خطأ
				RAHL_Booking_Repository::delete( $booking_id );
				$booking_id = 0;

				return new WP_Error(
					'no_seats',
					__( 'Sorry, this tour is fully booked.', 'rahal' )
				);
			}
		} finally {
			// فك القفل دائماً
			$wpdb->query(
				$wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name )
			);
		}

		// ── 5. بعد فك القفل: إطلاق الـ action وإرجاع معرّف الحجز ─────────────
		if ( $booking_id > 0 ) {
			/**
			 * يطلق بعد إنشاء الحجز بنجاح.
			 *
			 * @param int $booking_id معرّف الحجز المُنشأ.
			 */
			do_action( 'rahl_booking_created', $booking_id );

			return $booking_id;
		}

		return new WP_Error(
			'booking_failed',
			__( 'An unexpected error occurred while completing your booking.', 'rahal' )
		);
	}

	/**
	 * يغيّر حالة الحجز وفقاً لقواعد الانتقال الصارمة.
	 *
	 * الانتقالات المسموحة:
	 * - pending   -> confirmed, rejected, cancelled, expired
	 * - confirmed -> completed (فقط بعد أو في تاريخ الرحلة), cancelled
	 *
	 * @param int    $id         معرّف الحجز.
	 * @param string $new_status الحالة الجديدة المرغوبة.
	 * @return true|WP_Error صحيح عند النجاح أو WP_Error عند الفشل.
	 */
	public static function change_status( $id, $new_status ) {
		global $wpdb;

		$id      = absint( $id );
		$booking = RAHL_Booking_Repository::get( $id );

		if ( ! $booking ) {
			return new WP_Error(
				'booking_not_found',
				__( 'Booking not found.', 'rahal' )
			);
		}

		$new_status = sanitize_key( $new_status );
		if ( ! in_array( $new_status, self::ALLOWED_STATUSES, true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid booking status.', 'rahal' )
			);
		}

		$old_status = $booking->booking_status;

		// إذا كانت نفس الحالة، لا داعي للتغيير
		if ( $old_status === $new_status ) {
			return true;
		}

		// ── فحص الانتقالات المسموح بها ────────────────────────────────────────
		$valid_transition = false;

		if ( 'pending' === $old_status ) {
			$valid_transition = in_array( $new_status, array( 'confirmed', 'rejected', 'cancelled', 'expired' ), true );
		} elseif ( 'confirmed' === $old_status ) {
			$valid_transition = in_array( $new_status, array( 'completed', 'cancelled' ), true );
		}

		if ( ! $valid_transition ) {
			return new WP_Error(
				'invalid_transition',
				sprintf(
					/* translators: 1: old status, 2: new status */
					__( 'Cannot change booking status from "%1$s" to "%2$s".', 'rahal' ),
					$old_status,
					$new_status
				)
			);
		}

		// ── التحقق عند الانتقال إلى completed (يجب أن يكون بعد أو في تاريخ الرحلة) ──
		if ( 'completed' === $new_status ) {
			if ( ! empty( $booking->tour_date ) ) {
				$today_utc = gmdate( 'Y-m-d' );
				if ( $booking->tour_date > $today_utc ) {
					return new WP_Error(
						'cannot_complete_future_tour',
						__( 'Cannot mark booking as completed before the tour date.', 'rahal' )
					);
				}
			}
		}

		// ── حالة خاصة: تأكيد حجز pending انتهت مهلته ─────────────────────────
		// لو expires_at انتهت، مقاعد هذا الحجز لم تعد محسوبة في sum_reserved_seats،
		// وقد يكون شخص آخر حجزها. لذا يجب أخذ القفل وإعادة التحقق من توفر المقاعد.
		$is_confirming = ( 'confirmed' === $new_status );
		$needs_recheck = false;

		if ( 'pending' === $old_status && $is_confirming ) {
			if ( ! empty( $booking->expires_at ) ) {
				$now_utc = gmdate( 'Y-m-d H:i:s' );
				if ( $booking->expires_at <= $now_utc ) {
					$needs_recheck = true;
				}
			}
		}

		$update_data = array(
			'booking_status' => $new_status,
		);

		if ( $is_confirming ) {
			$update_data['expires_at'] = null;
		}

		if ( $needs_recheck ) {
			$tour_id   = (int) $booking->tour_id;
			$tour_date = $booking->tour_date;
			$db_name   = defined( 'DB_NAME' ) ? DB_NAME : '';
			$lock_key  = $db_name . $wpdb->prefix . $tour_id . '|' . ( null !== $tour_date ? $tour_date : 'null' );
			$lock_name = 'rahl_' . md5( $lock_key );

			$got_lock = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock_name )
			);

			if ( 1 !== $got_lock ) {
				return new WP_Error(
					'busy',
					__( 'The booking system is currently busy. Please try again in a moment.', 'rahal' )
				);
			}

			try {
				$available = RAHL_Availability::get_available( $tour_id, $tour_date );
				if ( (int) $booking->guests > $available ) {
					return new WP_Error(
						'seats_taken',
						__( 'Cannot confirm booking: the seats have already been taken by another booking.', 'rahal' )
					);
				}

				$updated = RAHL_Booking_Repository::update( $id, $update_data );
				if ( ! $updated ) {
					return new WP_Error(
						'update_failed',
						__( 'Failed to update booking status.', 'rahal' )
					);
				}
			} finally {
				$wpdb->query(
					$wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name )
				);
			}
		} else {
			$updated = RAHL_Booking_Repository::update( $id, $update_data );
			if ( ! $updated ) {
				return new WP_Error(
					'update_failed',
					__( 'Failed to update booking status.', 'rahal' )
				);
			}
		}

		// ── إطلاق الـ Actions ────────────────────────────────────────────────
		/**
		 * يطلق عند تغيّر حالة أي حجز.
		 *
		 * @param int    $id         معرّف الحجز.
		 * @param string $old_status الحالة السابقة.
		 * @param string $new_status الحالة الجديدة.
		 */
		do_action( 'rahl_booking_status_changed', $id, $old_status, $new_status );

		if ( $is_confirming ) {
			/**
			 * يطلق عند تأكيد الحجز.
			 *
			 * @param int $id معرّف الحجز.
			 */
			do_action( 'rahl_booking_confirmed', $id );
		}

		return true;
	}

	/**
	 * يحدّد حالة الدفع كمدفوعة (paid).
	 * مسموح فقط للحجوزات غير الملغية وغير المرفوضة.
	 *
	 * @param int $id معرّف الحجز.
	 * @return true|WP_Error
	 */
	public static function mark_paid( $id ) {
		$id      = absint( $id );
		$booking = RAHL_Booking_Repository::get( $id );

		if ( ! $booking ) {
			return new WP_Error(
				'booking_not_found',
				__( 'Booking not found.', 'rahal' )
			);
		}

		if ( in_array( $booking->booking_status, array( 'cancelled', 'rejected' ), true ) ) {
			return new WP_Error(
				'cannot_pay_inactive_booking',
				__( 'Cannot mark payment for a cancelled or rejected booking.', 'rahal' )
			);
		}

		if ( 'paid' === $booking->payment_status ) {
			return true;
		}

		$updated = RAHL_Booking_Repository::update(
			$id,
			array( 'payment_status' => 'paid' )
		);

		if ( ! $updated ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update payment status.', 'rahal' )
			);
		}

		/**
		 * يطلق عند تسديد الحجز.
		 *
		 * @param int $id معرّف الحجز.
		 */
		do_action( 'rahl_booking_paid', $id );

		return true;
	}
}

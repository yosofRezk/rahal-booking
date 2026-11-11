<?php
/**
 * RAHL_Pricing — حساب سعر الحجز على السيرفر.
 *
 * القواعد:
 * - كل المبالغ بأصغر وحدة للعملة (cents/piasters) — أرقام صحيحة دايمًا.
 * - الكلاس ما بيقراش أي سعر من $_POST أو الـ Request.
 * - في v0.1: بدون خصومات (discount_minor = 0 دايمًا).
 * - الإجمالي بيعدّي على filter 'rahl_booking_total' قبل الرجوع.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Pricing {

	/**
	 * يحسب سعر الحجز كاملاً بناءً على الرحلة وعدد الأفراد.
	 *
	 * الناتج array يحتوي على:
	 *   currency              — كود العملة (3 حروف، مثال: 'USD')
	 *   price_per_guest_minor — سعر الفرد بأصغر وحدة (مثال: 20000 = $200.00)
	 *   subtotal_minor        — المجموع قبل الخصم (price_per_guest_minor × guests)
	 *   discount_minor        — الخصم (0 في v0.1)
	 *   total_minor           — الإجمالي النهائي بعد الخصم
	 *
	 * @param int $tour_id معرّف الرحلة.
	 * @param int $guests  عدد الأفراد.
	 * @return array|WP_Error مصفوفة بيانات السعر أو WP_Error.
	 */
	public static function calculate( $tour_id, $guests ) {
		// ── تحقق من عدد الأفراد ────────────────────────────────────────────
		$guests = (int) $guests;
		if ( $guests < 1 ) {
			return new WP_Error(
				'invalid_guests',
				__( 'Number of guests must be at least 1.', 'rahal' )
			);
		}

		// ── تحقق من الرحلة ─────────────────────────────────────────────────
		if ( ! RAHL_Tour::is_bookable( $tour_id ) ) {
			return new WP_Error(
				'tour_not_bookable',
				__( 'This tour is not available for booking.', 'rahal' )
			);
		}

		// ── السعر من الرحلة (ما من الـ Request) ───────────────────────────
		$price_per_guest_minor = RAHL_Tour::get_price_minor( $tour_id );
		$currency              = RAHL_Settings::get( 'currency' );

		// ── الحساب ─────────────────────────────────────────────────────────
		$subtotal_minor  = $price_per_guest_minor * $guests;
		$discount_minor  = 0; // v0.1: بدون خصومات

		// الإجمالي قبل الفلتر
		$total_minor = $subtotal_minor - $discount_minor;

		$data = array(
			'currency'              => $currency,
			'price_per_guest_minor' => $price_per_guest_minor,
			'subtotal_minor'        => $subtotal_minor,
			'discount_minor'        => $discount_minor,
			'total_minor'           => $total_minor,
		);

		/**
		 * فلتر لتعديل الإجمالي قبل الرجوع (v0.3: ستُضاف الخصومات هنا).
		 *
		 * @param int   $total_minor الإجمالي بأصغر وحدة.
		 * @param array $data        كل بيانات التسعير.
		 */
		$data['total_minor'] = (int) apply_filters( 'rahl_booking_total', $total_minor, $data );

		return $data;
	}

	/**
	 * يُنسّق مبلغاً بأصغر وحدة للعرض.
	 *
	 * مثال: format( 80000, 'USD' ) → '800.00 USD'
	 *
	 * @param int    $minor    المبلغ بأصغر وحدة.
	 * @param string $currency كود العملة.
	 * @return string
	 */
	public static function format( $minor, $currency ) {
		$amount = number_format_i18n( $minor / 100, 2 );
		return $amount . ' ' . esc_html( strtoupper( $currency ) );
	}
}

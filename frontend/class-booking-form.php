<?php
/**
 * RAHL_Booking_Form — عرض فورم الحجز للرحلة.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Booking_Form {

	/**
	 * هل تم تسجيل الأصول (CSS/JS) بالفعل؟
	 *
	 * @var bool
	 */
	private static $assets_registered = false;

	/**
	 * تسجيل واستدعاء الأصول عند الحاجة فقط.
	 */
	public static function register_assets() {
		if ( self::$assets_registered ) {
			return;
		}

		wp_register_style(
			'rahl-frontend',
			RAHL_URL . 'frontend/assets/css/frontend.css',
			array(),
			RAHL_VERSION
		);

		wp_register_script(
			'rahl-booking-form',
			RAHL_URL . 'frontend/assets/js/booking-form.js',
			array(),
			RAHL_VERSION,
			true
		);

		wp_localize_script(
			'rahl-booking-form',
			'rahlBookingData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => 'rahl_submit_booking',
				'nonce'     => wp_create_nonce( 'rahl_booking_nonce' ),
				'i18n'      => array(
					'submitting'    => __( 'Submitting your booking...', 'rahal' ),
					'submit'        => __( 'Book Now', 'rahal' ),
					'networkError'  => __( 'A connection error occurred. Please try again.', 'rahal' ),
					'estimated'     => __( 'Estimated total:', 'rahal' ),
					'finalPriceNote'=> __( 'Final price is calculated on the server.', 'rahal' ),
				),
			)
		);

		self::$assets_registered = true;
	}

	/**
	 * توليد HTML الخاص بفورم الحجز.
	 *
	 * @param array $atts خصائص الشورت كود (tour_id).
	 * @return string HTML الفورم أو رسالة الحالة.
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'tour_id' => '',
			),
			$atts,
			'rahl_booking_form'
		);

		$tour_id = self::resolve_tour_id( $atts['tour_id'] );
		if ( $tour_id <= 0 || ! RAHL_Tour::is_bookable( $tour_id ) ) {
			return '<div class="rahl-booking-message rahl-booking-warning">' .
				esc_html__( 'This tour is not available for booking.', 'rahal' ) .
			'</div>';
		}

		$tour_date = RAHL_Tour::get_date( $tour_id );
		if ( null !== $tour_date && $tour_date < gmdate( 'Y-m-d' ) ) {
			return '<div class="rahl-booking-message rahl-booking-warning">' .
				esc_html__( 'This tour date has already passed.', 'rahal' ) .
			'</div>';
		}

		$available = RAHL_Availability::get_available( $tour_id, $tour_date );
		if ( $available <= 0 ) {
			return '<div class="rahl-booking-message rahl-booking-info">' .
				esc_html__( 'This tour is fully booked.', 'rahal' ) .
			'</div>';
		}

		// تسجيل وتحميل ملفات CSS و JS في الصفحة التي تحتوي على الفورم فقط
		self::register_assets();
		wp_enqueue_style( 'rahl-frontend' );
		wp_enqueue_script( 'rahl-booking-form' );

		$max_allowed_setting = (int) RAHL_Settings::get( 'max_guests', 10 );
		if ( $max_allowed_setting < 1 ) {
			$max_allowed_setting = 10;
		}
		$max_guests = min( $available, $max_allowed_setting );

		$price_minor = RAHL_Tour::get_price_minor( $tour_id );
		$currency    = RAHL_Settings::get( 'currency', 'USD' );

		ob_start();
		?>
		<div class="rahl-booking-wrapper" id="rahl-booking-wrapper-<?php echo esc_attr( $tour_id ); ?>">
			<form class="rahl-booking-form" method="post" novalidate data-tour-id="<?php echo esc_attr( $tour_id ); ?>" data-price-minor="<?php echo esc_attr( $price_minor ); ?>" data-currency="<?php echo esc_attr( $currency ); ?>">
				<input type="hidden" name="action" value="rahl_submit_booking">
				<input type="hidden" name="tour_id" value="<?php echo esc_attr( $tour_id ); ?>">
				<?php wp_nonce_field( 'rahl_booking_nonce', 'rahl_nonce' ); ?>

				<!-- Honeypot ضد البوتات المزعجة (مخفي عبر CSS ومستبعد من التركيز) -->
				<div class="rahl-hp-field" aria-hidden="true">
					<label for="rahl_website_<?php echo esc_attr( $tour_id ); ?>"><?php esc_html_e( 'Leave this field blank', 'rahal' ); ?></label>
					<input type="text" name="rahl_website" id="rahl_website_<?php echo esc_attr( $tour_id ); ?>" tabindex="-1" autocomplete="off">
				</div>

				<div class="rahl-form-group">
					<label for="rahl_name_<?php echo esc_attr( $tour_id ); ?>">
						<?php esc_html_e( 'Full Name', 'rahal' ); ?> <span class="rahl-required" aria-hidden="true">*</span>
					</label>
					<input type="text" name="name" id="rahl_name_<?php echo esc_attr( $tour_id ); ?>" class="rahl-input" required maxlength="100" autocomplete="name">
				</div>

				<div class="rahl-form-group">
					<label for="rahl_email_<?php echo esc_attr( $tour_id ); ?>">
						<?php esc_html_e( 'Email Address', 'rahal' ); ?> <span class="rahl-required" aria-hidden="true">*</span>
					</label>
					<input type="email" name="email" id="rahl_email_<?php echo esc_attr( $tour_id ); ?>" class="rahl-input" required maxlength="100" autocomplete="email">
				</div>

				<div class="rahl-form-group">
					<label for="rahl_phone_<?php echo esc_attr( $tour_id ); ?>">
						<?php esc_html_e( 'Phone Number', 'rahal' ); ?> <span class="rahl-required" aria-hidden="true">*</span>
					</label>
					<input type="tel" name="phone" id="rahl_phone_<?php echo esc_attr( $tour_id ); ?>" class="rahl-input" required maxlength="20" autocomplete="tel">
				</div>

				<div class="rahl-form-group">
					<label for="rahl_guests_<?php echo esc_attr( $tour_id ); ?>">
						<?php esc_html_e( 'Number of Guests', 'rahal' ); ?> <span class="rahl-required" aria-hidden="true">*</span>
					</label>
					<input type="number" name="guests" id="rahl_guests_<?php echo esc_attr( $tour_id ); ?>" class="rahl-input rahl-input-guests" min="1" max="<?php echo esc_attr( $max_guests ); ?>" value="1" required>
					<span class="rahl-field-hint">
						<?php
						printf(
							/* translators: %d: available seats */
							esc_html__( 'Up to %d guests allowed.', 'rahal' ),
							(int) $max_guests
						);
						?>
					</span>
				</div>

				<div class="rahl-form-group">
					<span class="rahl-label-block">
						<?php esc_html_e( 'Payment Method', 'rahal' ); ?> <span class="rahl-required" aria-hidden="true">*</span>
					</span>
					<div class="rahl-radio-group">
						<label class="rahl-radio-label">
							<input type="radio" name="payment_method" value="branch" checked>
							<span><?php esc_html_e( 'Pay at Branch', 'rahal' ); ?></span>
						</label>
						<label class="rahl-radio-label">
							<input type="radio" name="payment_method" value="transfer">
							<span><?php esc_html_e( 'Bank Transfer', 'rahal' ); ?></span>
						</label>
					</div>
				</div>

				<!-- ملخص السعر المتوقع (للعرض فقط عبر JS، والسيرفر يحسب النهائي) -->
				<div class="rahl-price-preview" aria-live="polite">
					<span class="rahl-price-preview-label"><?php esc_html_e( 'Estimated Total:', 'rahal' ); ?></span>
					<strong class="rahl-price-preview-amount"><?php echo esc_html( RAHL_Pricing::format( $price_minor, $currency ) ); ?></strong>
					<span class="rahl-price-preview-note"><?php esc_html_e( '(Final price is calculated on the server)', 'rahal' ); ?></span>
				</div>

				<!-- منطقة التنبيهات والأخطاء الحية (Accessible aria-live) -->
				<div class="rahl-form-feedback" aria-live="polite" style="display:none;"></div>

				<div class="rahl-form-submit-group">
					<button type="submit" class="rahl-submit-btn">
						<span class="rahl-btn-text"><?php esc_html_e( 'Book Now', 'rahal' ); ?></span>
						<span class="rahl-spinner" aria-hidden="true" style="display:none;"></span>
					</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * توليد HTML لزر الحجز المنبثق (Popup Modal).
	 *
	 * [rahl_booking_button tour_id="" text="Book Now"]
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function render_button( $atts ) {
		$atts = shortcode_atts(
			array(
				'tour_id' => '',
				'text'    => '',
				'class'   => '',
			),
			$atts,
			'rahl_booking_button'
		);

		$tour_id = self::resolve_tour_id( $atts['tour_id'] );
		if ( $tour_id <= 0 || ! RAHL_Tour::is_bookable( $tour_id ) ) {
			return '';
		}

		$tour_date = RAHL_Tour::get_date( $tour_id );
		if ( null !== $tour_date && $tour_date < gmdate( 'Y-m-d' ) ) {
			return '';
		}

		$available = RAHL_Availability::get_available( $tour_id, $tour_date );
		$btn_text  = ! empty( $atts['text'] ) ? sanitize_text_field( $atts['text'] ) : __( 'Book Now', 'rahal' );
		$classes   = 'rahl-btn-open-modal ' . sanitize_html_class( $atts['class'] );

		// لو الرحلة ممتلئة تماماً
		if ( $available <= 0 ) {
			return sprintf(
				'<button type="button" class="%s" disabled>%s</button>',
				esc_attr( $classes ),
				esc_html__( 'Fully Booked', 'rahal' )
			);
		}

		// تسجيل الأصول وتحميلها
		self::register_assets();
		wp_enqueue_style( 'rahl-frontend' );
		wp_enqueue_script( 'rahl-booking-form' );

		// توليد محتوى الفورم ليكون داخل الـ Modal
		$form_html = self::render( array( 'tour_id' => $tour_id ) );
		$tour_title = RAHL_Tour::get_title( $tour_id );

		ob_start();
		?>
		<div class="rahl-booking-btn-container">
			<button type="button" class="<?php echo esc_attr( $classes ); ?>" data-modal-target="rahl-modal-<?php echo esc_attr( $tour_id ); ?>">
				<?php echo esc_html( $btn_text ); ?>
			</button>

			<!-- نافذة الـ Popup Modal -->
			<div id="rahl-modal-<?php echo esc_attr( $tour_id ); ?>" class="rahl-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="rahl-modal-title-<?php echo esc_attr( $tour_id ); ?>">
				<div class="rahl-modal-backdrop" data-modal-close></div>
				<div class="rahl-modal-dialog">
					<div class="rahl-modal-header">
						<h2 id="rahl-modal-title-<?php echo esc_attr( $tour_id ); ?>" class="rahl-modal-title">
							<?php echo esc_html( $tour_title ); ?>
						</h2>
						<button type="button" class="rahl-modal-close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'rahal' ); ?>">
							&times;
						</button>
					</div>
					<div class="rahl-modal-body">
						<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * استنتاج معرف الرحلة من الوسم أو الصفحة الحالية.
	 *
	 * @param mixed $attr_tour_id المعرف الممرر في الشورت كود.
	 * @return int
	 */
	private static function resolve_tour_id( $attr_tour_id ) {
		if ( '' !== $attr_tour_id && null !== $attr_tour_id ) {
			$tour_id = absint( $attr_tour_id );
			return RAHL_Tour::exists( $tour_id ) ? $tour_id : 0;
		}

		if ( is_singular() ) {
			$tour_id = get_the_ID();
			return RAHL_Tour::exists( $tour_id ) ? (int) $tour_id : 0;
		}

		return 0;
	}
}

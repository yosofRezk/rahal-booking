<?php
/**
 * RAHL_Admin_Add_Booking — شاشة إنشاء حجز يدوي مباشر من لوحة التحكم.
 *
 * مخصصة للأدمن وموظفي الفرع لحجز رحلات للعملاء القادمين للفرع أو المتصلين هاتفياً.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Admin_Add_Booking {

	/**
	 * الاحتفاظ بمدخلات الفورم السابقة عند حدوث خطأ لمنع تفريغ الحقول.
	 *
	 * @var array
	 */
	private static $form_data = array();

	/**
	 * كود الحقل الذي به خطأ لتحديده وتنبيه المستخدم.
	 *
	 * @var string
	 */
	private static $error_field = '';

	/**
	 * معالجة الإرسال (تُستدعى على admin_init لتمكين الـ Redirect بأمان).
	 */
	public static function handle_submission() {
		if ( empty( $_POST['rahl_admin_add_booking_submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'rahl_manage_bookings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'rahal' ) );
		}

		check_admin_referer( 'rahl_admin_add_booking_nonce', 'rahl_add_nonce' );

		$tour_id        = isset( $_POST['tour_id'] ) ? absint( $_POST['tour_id'] ) : 0;
		$guests         = isset( $_POST['guests'] ) ? (int) $_POST['guests'] : 0;
		$name           = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
		$email          = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
		$phone          = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
		$payment_method = isset( $_POST['payment_method'] ) ? sanitize_key( wp_unslash( $_POST['payment_method'] ) ) : 'branch';
		$initial_status = isset( $_POST['initial_status'] ) ? sanitize_key( wp_unslash( $_POST['initial_status'] ) ) : 'confirmed';

		// حفظ المدخلات مؤقتاً لعرضها لو حدث خطأ
		self::$form_data = array(
			'tour_id'        => $tour_id,
			'guests'         => $guests,
			'customer_name'  => $name,
			'customer_email' => $email,
			'customer_phone' => $phone,
			'payment_method' => $payment_method,
			'initial_status' => $initial_status,
		);

		$booking_id = RAHL_Booking_Manager::create(
			array(
				'tour_id'        => $tour_id,
				'guests'         => $guests,
				'name'           => $name,
				'email'          => $email,
				'phone'          => $phone,
				'payment_method' => $payment_method,
			)
		);

		if ( is_wp_error( $booking_id ) ) {
			$err_code = $booking_id->get_error_code();
			self::$error_field = $err_code;

			add_settings_error(
				'rahl_add_booking',
				$err_code,
				$booking_id->get_error_message(),
				'error'
			);
			return;
		}

		// إذا اختار الأدمن تأكيد الحجز فوراً
		if ( 'confirmed' === $initial_status ) {
			RAHL_Booking_Manager::change_status( $booking_id, 'confirmed' );
			if ( 'branch' === $payment_method ) {
				RAHL_Booking_Manager::mark_paid( $booking_id );
			}
		}

		$booking = RAHL_Booking_Repository::get( $booking_id );
		$ref = $booking ? $booking->reference : '#' . $booking_id;

		// البقاء في نفس الصفحة وعرض بطاقة النجاح مع خياري الإضافة مجدداً أو الانتقال
		$redirect_url = add_query_arg(
			array(
				'page'               => 'rahl-add-booking',
				'rahl_booking_added' => 1,
				'created_id'         => $booking_id,
				'ref'                => rawurlencode( $ref ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * عرض الصفحة والفورم.
	 */
	public static function render() {
		if ( ! current_user_can( 'rahl_manage_bookings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'rahal' ) );
		}

		$tour_post_type = RAHL_Settings::get( 'tour_post_type', '' );
		$tours = array();

		if ( ! empty( $tour_post_type ) ) {
			$tours = get_posts(
				array(
					'post_type'      => $tour_post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
		}

		// استرجاع القيم السابقة لو حدث خطأ
		$val_tour    = isset( self::$form_data['tour_id'] ) ? self::$form_data['tour_id'] : 0;
		$val_guests  = isset( self::$form_data['guests'] ) ? self::$form_data['guests'] : 1;
		$val_name    = isset( self::$form_data['customer_name'] ) ? self::$form_data['customer_name'] : '';
		$val_email   = isset( self::$form_data['customer_email'] ) ? self::$form_data['customer_email'] : '';
		$val_phone   = isset( self::$form_data['customer_phone'] ) ? self::$form_data['customer_phone'] : '';
		$val_pm      = isset( self::$form_data['payment_method'] ) ? self::$form_data['payment_method'] : 'branch';
		$val_status  = isset( self::$form_data['initial_status'] ) ? self::$form_data['initial_status'] : 'confirmed';
		$err         = self::$error_field;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add New Booking', 'rahal' ); ?></h1>
			<hr class="wp-header-end">

			<?php settings_errors( 'rahl_add_booking' ); ?>

			<?php if ( ! empty( $_GET['rahl_booking_added'] ) ) : ?>
				<?php
				$new_ref = ! empty( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
				$new_id  = ! empty( $_GET['created_id'] ) ? absint( $_GET['created_id'] ) : 0;
				$all_bookings_url = add_query_arg(
					array(
						'page'         => 'rahl-bookings',
						'highlight_id' => $new_id,
					),
					admin_url( 'admin.php' )
				);
				$add_another_url = admin_url( 'admin.php?page=rahl-add-booking' );
				?>
				<div class="notice notice-success is-dismissible" style="padding: 16px; border-inline-start-color: #10b981;">
					<h3 style="margin-top:0; color: #065f46;">
						&#10003; <?php printf( esc_html__( 'Booking %s created successfully!', 'rahal' ), '<code>' . esc_html( $new_ref ) . '</code>' ); ?>
					</h3>
					<p style="margin-bottom: 12px; font-size: 14px;">
						<?php esc_html_e( 'What would you like to do next?', 'rahal' ); ?>
					</p>
					<div style="display: flex; gap: 10px; flex-wrap: wrap;">
						<a href="<?php echo esc_url( $add_another_url ); ?>" class="button button-primary">
							<span class="dashicons dashicons-plus-alt2" style="line-height: 1.4;"></span> <?php esc_html_e( 'Add Another Booking', 'rahal' ); ?>
						</a>
						<a href="<?php echo esc_url( $all_bookings_url ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-list-view" style="line-height: 1.4;"></span> <?php esc_html_e( 'View in All Bookings', 'rahal' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Use this form to create a manual booking for walk-in customers or phone reservations.', 'rahal' ); ?>
			</p>

			<style>
				.rahl-field-error {
					border-color: #d63638 !important;
					box-shadow: 0 0 2px rgba(214, 54, 56, 0.8) !important;
				}
			</style>

			<form method="post" action="" style="max-width: 650px; margin-top: 20px;">
				<?php wp_nonce_field( 'rahl_admin_add_booking_nonce', 'rahl_add_nonce' ); ?>
				<input type="hidden" name="rahl_admin_add_booking_submit" value="1">

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rahl_tour_id"><?php esc_html_e( 'Select Tour', 'rahal' ); ?> <span class="description" style="color:#d63638;">*</span></label>
							</th>
							<td>
								<?php if ( empty( $tours ) ) : ?>
									<p style="color:#d63638;"><?php esc_html_e( 'No published tours found. Please create tours or verify settings.', 'rahal' ); ?></p>
								<?php else : ?>
									<select name="tour_id" id="rahl_tour_id" class="regular-text <?php echo ( 'tour_not_bookable' === $err || 'tour_past' === $err ) ? 'rahl-field-error' : ''; ?>" required>
										<option value=""><?php esc_html_e( '— Select a Tour —', 'rahal' ); ?></option>
										<?php foreach ( $tours as $t ) : ?>
											<?php
											$date = RAHL_Tour::get_date( $t->ID );
											$avail = RAHL_Availability::get_available( $t->ID, $date );
											$label = esc_html( $t->post_title );
											if ( $date ) {
												$label .= ' (' . $date . ')';
											}
											$label .= ' — ' . sprintf( __( '%d seats left', 'rahal' ), $avail );
											?>
											<option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $val_tour, $t->ID ); ?> <?php disabled( $avail <= 0 ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_guests"><?php esc_html_e( 'Number of Guests', 'rahal' ); ?> <span class="description" style="color:#d63638;">*</span></label>
							</th>
							<td>
								<input type="number" name="guests" id="rahl_guests" class="small-text <?php echo ( 'invalid_guests' === $err || 'no_seats' === $err ) ? 'rahl-field-error' : ''; ?>" value="<?php echo esc_attr( $val_guests ); ?>" min="1" max="100" required>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_customer_name"><?php esc_html_e( 'Customer Name', 'rahal' ); ?> <span class="description" style="color:#d63638;">*</span></label>
							</th>
							<td>
								<input type="text" name="customer_name" id="rahl_customer_name" class="regular-text <?php echo ( 'invalid_name' === $err ) ? 'rahl-field-error' : ''; ?>" value="<?php echo esc_attr( $val_name ); ?>" required maxlength="100">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_customer_email"><?php esc_html_e( 'Customer Email', 'rahal' ); ?> <span class="description" style="color:#d63638;">*</span></label>
							</th>
							<td>
								<input type="email" name="customer_email" id="rahl_customer_email" class="regular-text <?php echo ( 'invalid_email' === $err || 'too_many_pending' === $err ) ? 'rahl-field-error' : ''; ?>" value="<?php echo esc_attr( $val_email ); ?>" required maxlength="100">
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_customer_phone"><?php esc_html_e( 'Customer Phone', 'rahal' ); ?> <span class="description" style="color:#d63638;">*</span></label>
							</th>
							<td>
								<input type="tel" name="customer_phone" id="rahl_customer_phone" class="regular-text <?php echo ( 'invalid_phone' === $err ) ? 'rahl-field-error' : ''; ?>" value="<?php echo esc_attr( $val_phone ); ?>" required maxlength="20">
								<?php if ( 'invalid_phone' === $err ) : ?>
									<p class="description" style="color:#d63638;"><?php esc_html_e( 'Please enter a valid phone number (between 7 and 20 digits, digits and + only).', 'rahal' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_payment_method"><?php esc_html_e( 'Payment Method', 'rahal' ); ?></label>
							</th>
							<td>
								<select name="payment_method" id="rahl_payment_method">
									<option value="branch" <?php selected( $val_pm, 'branch' ); ?>><?php esc_html_e( 'Pay at Branch (Cash / Desk)', 'rahal' ); ?></option>
									<option value="transfer" <?php selected( $val_pm, 'transfer' ); ?>><?php esc_html_e( 'Bank Transfer', 'rahal' ); ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rahl_initial_status"><?php esc_html_e( 'Initial Booking Status', 'rahal' ); ?></label>
							</th>
							<td>
								<select name="initial_status" id="rahl_initial_status">
									<option value="confirmed" <?php selected( $val_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed (Already paid or verified)', 'rahal' ); ?></option>
									<option value="pending" <?php selected( $val_status, 'pending' ); ?>><?php esc_html_e( 'Pending (Awaiting payment)', 'rahal' ); ?></option>
								</select>
								<p class="description">
									<?php esc_html_e( 'If Confirmed is chosen and Pay at Branch is selected, payment status will be set to Paid.', 'rahal' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Create Booking', 'rahal' ), 'primary', 'rahl_submit_booking' ); ?>
			</form>
		</div>
		<?php
	}
}

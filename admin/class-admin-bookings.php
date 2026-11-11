<?php
/**
 * RAHL_Admin_Bookings — إدارة وعرض صفحة كل الحجوزات ومعالجة إجراءات الأدمن.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Admin_Bookings {

	/**
	 * معالجة الإجراءات وتوجيه الصفحة وعرض الجدول.
	 */
	public static function render() {
		if ( ! current_user_can( 'rahl_manage_bookings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'rahal' ) );
		}

		self::handle_actions();

		$table = new RAHL_Bookings_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bookings', 'rahal' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rahl-add-booking' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'rahal' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( ! empty( $_GET['rahl_booking_added'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						$created_ref = ! empty( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
						printf(
							/* translators: %s: booking reference */
							esc_html__( 'Booking %s created successfully.', 'rahal' ),
							'<strong>' . esc_html( $created_ref ) . '</strong>'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<style>
				.rahl-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 4px;
					font-size: 11px;
					font-weight: 600;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}
				.rahl-badge-success { background: #d1fae5; color: #065f46; }
				.rahl-badge-warning { background: #fef3c7; color: #92400e; }
				.rahl-badge-danger  { background: #fee2e2; color: #991b1b; }
				.rahl-badge-info    { background: #e0f2fe; color: #0369a1; }
				.rahl-badge-muted   { background: #f1f5f9; color: #475569; }

				@keyframes rahlHighlightPulse {
					0% { background-color: #fef08a; }
					50% { background-color: #fef9c3; }
					100% { background-color: transparent; }
				}
				.rahl-row-highlight {
					animation: rahlHighlightPulse 2.5s ease-out forwards;
				}
			</style>

			<?php if ( ! empty( $_GET['highlight_id'] ) ) : ?>
				<script>
					document.addEventListener('DOMContentLoaded', function() {
						var targetRow = document.getElementById('rahl-booking-row-<?php echo absint( $_GET['highlight_id'] ); ?>');
						if (targetRow) {
							targetRow.classList.add('rahl-row-highlight');
							targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
						}
					});
				</script>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="page" value="rahl-bookings" />
				<?php
				if ( ! empty( $_GET['booking_status'] ) ) {
					echo '<input type="hidden" name="booking_status" value="' . esc_attr( sanitize_key( $_GET['booking_status'] ) ) . '" />';
				}
				$table->views();
				$table->search_box( __( 'Search Bookings', 'rahal' ), 'rahl_booking_search' );
				$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * معالجة نقرات الإجراءات (Confirm, Reject, Cancel, Complete, Mark as Paid).
	 */
	private static function handle_actions() {
		if ( empty( $_GET['action'] ) || empty( $_GET['booking_id'] ) ) {
			return;
		}

		$action     = sanitize_key( $_GET['action'] );
		$booking_id = absint( $_GET['booking_id'] );

		if ( $booking_id <= 0 ) {
			return;
		}

		// التحقق من الـ Nonce الصارم لكل حجز
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'rahl_admin_action_' . $booking_id ) ) {
			add_settings_error(
				'rahl_bookings',
				'invalid_nonce',
				__( 'Security verification failed. Please refresh and try again.', 'rahal' ),
				'error'
			);
			return;
		}

		$error_message = '';
		$success_msg   = '';

		switch ( $action ) {
			case 'rahl_confirm':
				$result = RAHL_Booking_Manager::change_status( $booking_id, 'confirmed' );
				if ( is_wp_error( $result ) ) {
					$error_message = $result->get_error_message();
				} else {
					$success_msg = __( 'Booking confirmed successfully.', 'rahal' );
				}
				break;

			case 'rahl_confirm_and_pay':
				$res1 = RAHL_Booking_Manager::change_status( $booking_id, 'confirmed' );
				if ( is_wp_error( $res1 ) ) {
					$error_message = $res1->get_error_message();
				} else {
					$res2 = RAHL_Booking_Manager::mark_paid( $booking_id );
					if ( is_wp_error( $res2 ) ) {
						$error_message = $res2->get_error_message();
					} else {
						$success_msg = __( 'Booking confirmed and marked as paid successfully.', 'rahal' );
					}
				}
				break;

			case 'rahl_reject':
				$result = RAHL_Booking_Manager::change_status( $booking_id, 'rejected' );
				if ( is_wp_error( $result ) ) {
					$error_message = $result->get_error_message();
				} else {
					$success_msg = __( 'Booking rejected.', 'rahal' );
				}
				break;

			case 'rahl_cancel':
				$result = RAHL_Booking_Manager::change_status( $booking_id, 'cancelled' );
				if ( is_wp_error( $result ) ) {
					$error_message = $result->get_error_message();
				} else {
					$success_msg = __( 'Booking cancelled. Reserved seats have been restored.', 'rahal' );
				}
				break;

			case 'rahl_complete':
				$result = RAHL_Booking_Manager::change_status( $booking_id, 'completed' );
				if ( is_wp_error( $result ) ) {
					$error_message = $result->get_error_message();
				} else {
					$success_msg = __( 'Booking marked as completed.', 'rahal' );
				}
				break;

			case 'rahl_mark_paid':
				$result = RAHL_Booking_Manager::mark_paid( $booking_id );
				if ( is_wp_error( $result ) ) {
					$error_message = $result->get_error_message();
				} else {
					$success_msg = __( 'Payment status updated to Paid.', 'rahal' );
				}
				break;
		}

		if ( ! empty( $error_message ) ) {
			add_settings_error( 'rahl_bookings', 'action_failed', $error_message, 'error' );
		} elseif ( ! empty( $success_msg ) ) {
			add_settings_error( 'rahl_bookings', 'action_success', $success_msg, 'success' );
		}

		// عرض التنبيهات
		settings_errors( 'rahl_bookings' );
	}
}

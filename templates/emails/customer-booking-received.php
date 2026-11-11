<?php
/**
 * قالب إيميل تأكيد استلام الحجز للعميل.
 *
 * @var object $booking
 * @var string $tour_title
 * @var string $payment_instructions
 * @var int    $hold_hours
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

$site_name = get_bloginfo( 'name' );
$date_display = ! empty( $booking->tour_date ) ? $booking->tour_date : __( 'Flexible / Open Date', 'rahal' );
$total_formatted = RAHL_Pricing::format( $booking->total_minor, $booking->currency );
$method_label = ( 'transfer' === $booking->payment_method ) ? __( 'Bank Transfer', 'rahal' ) : __( 'Pay at Branch', 'rahal' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Booking Received', 'rahal' ); ?></title>
	<style>
		body {
			margin: 0;
			padding: 0;
			background-color: #f1f5f9;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
			color: #334155;
			line-height: 1.6;
		}
		.email-container {
			max-width: 600px;
			margin: 30px auto;
			background: #ffffff;
			border-radius: 8px;
			overflow: hidden;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
			border: 1px solid #e2e8f0;
		}
		.email-header {
			background-color: #2563eb;
			color: #ffffff;
			padding: 24px;
			text-align: center;
		}
		.email-header h1 {
			margin: 0;
			font-size: 22px;
			font-weight: 700;
		}
		.email-body {
			padding: 30px 24px;
		}
		.greeting {
			font-size: 16px;
			margin-bottom: 16px;
		}
		.booking-details-table {
			width: 100%;
			border-collapse: collapse;
			margin: 20px 0;
		}
		.booking-details-table th,
		.booking-details-table td {
			padding: 10px 14px;
			border-bottom: 1px solid #f1f5f9;
			font-size: 14px;
		}
		.booking-details-table th {
			width: 35%;
			text-align: start;
			color: #64748b;
			font-weight: 600;
			background-color: #f8fafc;
		}
		.booking-details-table td {
			font-weight: 500;
			color: #0f172a;
		}
		.badge-status {
			display: inline-block;
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 12px;
			font-weight: 600;
			background-color: #fef3c7;
			color: #92400e;
		}
		.instructions-box {
			background-color: #fffbeb;
			border: 1px solid #fef3c7;
			border-radius: 6px;
			padding: 16px;
			margin-top: 24px;
			color: #92400e;
		}
		.instructions-box h3 {
			margin-top: 0;
			margin-bottom: 8px;
			font-size: 15px;
			color: #b45309;
		}
		.email-footer {
			background-color: #f8fafc;
			padding: 18px 24px;
			text-align: center;
			font-size: 13px;
			color: #94a3b8;
			border-top: 1px solid #e2e8f0;
		}
	</style>
</head>
<body>
	<div class="email-container">
		<div class="email-header">
			<h1><?php echo esc_html( $site_name ); ?></h1>
		</div>
		<div class="email-body">
			<div class="greeting">
				<?php
				printf(
					/* translators: %s: customer name */
					esc_html__( 'Hello %s,', 'rahal' ),
					'<strong>' . esc_html( $booking->customer_name ) . '</strong>'
				);
				?>
			</div>
			<p>
				<?php esc_html_e( 'Thank you for your booking! We have received your request and your booking is currently pending.', 'rahal' ); ?>
			</p>

			<table class="booking-details-table">
				<tr>
					<th><?php esc_html_e( 'Booking Reference', 'rahal' ); ?></th>
					<td><strong style="font-family: monospace; font-size: 15px;"><?php echo esc_html( $booking->reference ); ?></strong></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Tour', 'rahal' ); ?></th>
					<td><?php echo esc_html( $tour_title ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Date', 'rahal' ); ?></th>
					<td><?php echo esc_html( $date_display ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Guests', 'rahal' ); ?></th>
					<td><?php echo esc_html( (string) $booking->guests ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Total Amount', 'rahal' ); ?></th>
					<td><strong><?php echo esc_html( $total_formatted ); ?></strong></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Payment Method', 'rahal' ); ?></th>
					<td><?php echo esc_html( $method_label ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'rahal' ); ?></th>
					<td><span class="badge-status"><?php echo esc_html( ucfirst( $booking->booking_status ) ); ?></span></td>
				</tr>
			</table>

			<?php if ( ! empty( $payment_instructions ) ) : ?>
				<div class="instructions-box">
					<h3><?php esc_html_e( 'Payment Instructions & Hold Period', 'rahal' ); ?></h3>
					<?php if ( $hold_hours > 0 ) : ?>
						<p style="margin-top:0; font-weight: 600;">
							<?php
							printf(
								/* translators: %d: number of hours */
								esc_html__( 'Please complete your payment within %d hours to confirm your reservation.', 'rahal' ),
								(int) $hold_hours
							);
							?>
						</p>
					<?php endif; ?>
					<div><?php echo nl2br( esc_html( $payment_instructions ) ); ?></div>
				</div>
			<?php endif; ?>

			<p style="margin-top: 24px; font-size: 14px; color: #64748b;">
				<?php esc_html_e( 'If you have any questions or need to make changes, please reply to this email or contact us.', 'rahal' ); ?>
			</p>
		</div>
		<div class="email-footer">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>. <?php esc_html_e( 'All rights reserved.', 'rahal' ); ?>
		</div>
	</div>
</body>
</html>


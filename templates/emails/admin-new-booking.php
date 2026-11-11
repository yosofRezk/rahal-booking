<?php
/**
 * قالب إيميل تنبيه الأدمن بحجز جديد.
 *
 * @var object $booking
 * @var string $tour_title
 * @var string $admin_booking_url
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
	<title><?php esc_html_e( 'New Booking Alert', 'rahal' ); ?></title>
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
			background-color: #0f172a;
			color: #ffffff;
			padding: 24px;
			text-align: center;
		}
		.email-header h1 {
			margin: 0;
			font-size: 20px;
			font-weight: 700;
		}
		.email-body {
			padding: 30px 24px;
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
		.btn-manage {
			display: inline-block;
			background-color: #2563eb;
			color: #ffffff !important;
			padding: 12px 24px;
			border-radius: 6px;
			text-decoration: none;
			font-weight: 600;
			font-size: 14px;
			margin-top: 16px;
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
			<h1><?php printf( esc_html__( 'New Booking Alert — %s', 'rahal' ), esc_html( $site_name ) ); ?></h1>
		</div>
		<div class="email-body">
			<p style="font-size: 16px;">
				<?php esc_html_e( 'A new booking has just been submitted on your website. Here are the details:', 'rahal' ); ?>
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
					<th><?php esc_html_e( 'Customer Name', 'rahal' ); ?></th>
					<td><?php echo esc_html( $booking->customer_name ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Email', 'rahal' ); ?></th>
					<td><a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Phone', 'rahal' ); ?></th>
					<td><a href="tel:<?php echo esc_attr( $booking->customer_phone ); ?>"><?php echo esc_html( $booking->customer_phone ); ?></a></td>
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
					<th><?php esc_html_e( 'Payment Status', 'rahal' ); ?></th>
					<td><?php echo esc_html( ucfirst( $booking->payment_status ) ); ?></td>
				</tr>
			</table>

			<?php if ( ! empty( $admin_booking_url ) ) : ?>
				<div style="text-align: center; margin-top: 25px;">
					<a href="<?php echo esc_url( $admin_booking_url ); ?>" class="btn-manage" target="_blank">
						<?php esc_html_e( 'View & Manage Booking in Dashboard', 'rahal' ); ?> &rarr;
					</a>
				</div>
			<?php endif; ?>
		</div>
		<div class="email-footer">
			<?php printf( esc_html__( 'Notification sent automatically from %s', 'rahal' ), esc_html( $site_name ) ); ?>
		</div>
	</div>
</body>
</html>


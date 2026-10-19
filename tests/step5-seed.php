<?php
/**
 * Step 5 — إدراج 4 صفوف تجريبية في جدول الحجوزات.
 *
 * من مجلد public في Local:
 * wp eval-file wp-content/plugins/rahal/tests/step5-seed.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// غيّر الرقم لو Paris Discovery ID مختلف عندك.
$tour_id = 9;

if ( ! function_exists( 'rahl_table_name' ) ) {
	echo "Error: Rahal plugin is not loaded.\n";
	return;
}

global $wpdb;

$table = rahl_table_name();
$now   = gmdate( 'Y-m-d H:i:s' );
$future = gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS );

$base = array(
	'tour_id'               => (int) $tour_id,
	'customer_name'         => 'Test User',
	'customer_email'        => 'test@example.com',
	'customer_phone'        => '01000000000',
	'currency'              => 'USD',
	'price_per_guest_minor' => 20000,
	'subtotal_minor'        => 80000,
	'discount_minor'        => 0,
	'total_minor'           => 80000,
	'payment_status'        => 'unpaid',
	'payment_method'        => 'branch',
	'transaction_id'        => '',
	'tour_title_snapshot'   => 'Paris Discovery',
	'created_at'            => $now,
	'updated_at'            => $now,
);

$rows = array(
	array(
		'reference'       => 'TEST-STEP5-1',
		'tour_date'       => '2026-12-10',
		'guests'          => 4,
		'booking_status'  => 'confirmed',
		'expires_at'      => null,
	),
	array(
		'reference'       => 'TEST-STEP5-2',
		'tour_date'       => '2026-12-10',
		'guests'          => 6,
		'booking_status'  => 'pending',
		'expires_at'      => $future,
	),
	array(
		'reference'       => 'TEST-STEP5-3',
		'tour_date'       => '2026-12-10',
		'guests'          => 3,
		'booking_status'  => 'cancelled',
		'expires_at'      => null,
	),
	array(
		'reference'       => 'TEST-STEP5-4',
		'tour_date'       => '2026-12-20',
		'guests'          => 5,
		'booking_status'  => 'confirmed',
		'expires_at'      => null,
	),
);

$inserted = 0;

foreach ( $rows as $row ) {
	$data = array_merge( $base, $row );
	$ok   = $wpdb->insert( $table, $data );
	if ( false === $ok ) {
		echo 'Insert failed: ' . esc_html( $wpdb->last_error ) . "\n";
		continue;
	}
	++$inserted;
}

echo "Done. Inserted {$inserted} rows for tour_id={$tour_id}.\n";
echo "Next: wp eval-file wp-content/plugins/rahal/tests/step5-checks.php\n";

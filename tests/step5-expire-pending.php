<?php
/**
 * Step 5 — جعل حجز pending (6 guests) منتهي المهلة.
 *
 * wp eval-file wp-content/plugins/rahl/tests/step5-expire-pending.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table = rahl_table_name();

$updated = $wpdb->query(
	$wpdb->prepare(
		"UPDATE {$table} SET expires_at = %s WHERE customer_email = %s AND booking_status = %s AND guests = %d",
		'2020-01-01 00:00:00',
		'test@example.com',
		'pending',
		6
	)
);

if ( false === $updated ) {
	echo 'Update failed: ' . esc_html( $wpdb->last_error ) . "\n";
	return;
}

echo "Set pending 6-guest booking expires_at to past. Rows affected: {$updated}\n";
echo "Run step5-checks.php again — 2026-12-10 should show 16.\n";

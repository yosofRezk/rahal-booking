<?php
/**
 * Step 5 — حذف صفوف الاختبار.
 *
 * wp eval-file wp-content/plugins/rahal/tests/step5-cleanup.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table = rahl_table_name();

$deleted = $wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$table} WHERE customer_email = %s",
		'test@example.com'
	)
);

if ( false === $deleted ) {
	echo 'Delete failed: ' . esc_html( $wpdb->last_error ) . "\n";
	return;
}

echo "Deleted {$deleted} test row(s).\n";

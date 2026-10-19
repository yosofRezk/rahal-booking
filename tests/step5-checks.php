<?php
/**
 * Step 5 — فحص أرقام المقاعد المتاحة.
 *
 * wp eval-file wp-content/plugins/rahal/tests/step5-checks.php
 *
 * @package Rahal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tour_id = 9;

if ( ! function_exists( 'rahl_get_available_seats' ) ) {
	echo "Error: Rahal plugin is not loaded.\n";
	return;
}

$d1 = rahl_get_available_seats( $tour_id, '2026-12-10' );
$d2 = rahl_get_available_seats( $tour_id, '2026-12-20' );

echo "Tour ID: {$tour_id}\n";
echo "Available 2026-12-10: {$d1}  (expected: 10 before expire test, 16 after step5-expire-pending.php)\n";
echo "Available 2026-12-20: {$d2}  (expected: 15)\n";

$ok1 = ( 10 === (int) $d1 || 16 === (int) $d1 );
$ok2 = ( 15 === (int) $d2 );

if ( $ok1 && $ok2 ) {
	echo "PASS (numbers match Step 5 expectations for this stage).\n";
} else {
	echo "FAIL — run step5-cleanup.php then step5-seed.php again (unique reference fix).\n";
}

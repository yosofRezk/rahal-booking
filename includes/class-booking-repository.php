<?php
/**
 * RAHL_Booking_Repository — كل استعلامات جدول الحجوزات.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

class RAHL_Booking_Repository {

    /**
     * يُدخل صف حجز جديد.
     *
     * @param array $row أعمدة الصف (أسماء الأعمدة => قيم).
     * @return int|false معرّف الحجز أو false.
     */
    public static function insert( array $row ) {
        global $wpdb;

        $table = rahl_table_name();
        $done  = $wpdb->insert( $table, $row );

        if ( false === $done ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * يجلب حجزاً بالمعرّف.
     *
     * @param int $id معرّف الحجز.
     * @return object|null
     */
    public static function get( $id ) {
        global $wpdb;

        $id = absint( $id );
        if ( $id <= 0 ) {
            return null;
        }

        $table = rahl_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- اسم الجدول من دالة آمنة.
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * يجلب حجزاً بالرقم المرجعي.
     *
     * @param string $reference الرقم المرجعي.
     * @return object|null
     */
    public static function get_by_reference( $reference ) {
        global $wpdb;

        $reference = sanitize_text_field( $reference );
        if ( '' === $reference ) {
            return null;
        }

        $table = rahl_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE reference = %s",
                $reference
            )
        );
    }

    /**
     * يحدّث أعمدة حجز ويضبط updated_at بتوقيت UTC.
     *
     * @param int   $id   معرّف الحجز.
     * @param array $data الأعمدة المراد تحديثها.
     * @return bool
     */
    public static function update( $id, array $data ) {
        global $wpdb;

        $id = absint( $id );
        if ( $id <= 0 ) {
            return false;
        }

        $data['updated_at'] = gmdate( 'Y-m-d H:i:s' );

        $table = rahl_table_name();

        $updated = $wpdb->update(
            $table,
            $data,
            array( 'id' => $id ),
            null,
            array( '%d' )
        );

        return false !== $updated;
    }

    /**
     * مجموع المقاعد المحجوزة (confirmed أو pending لم تنتهِ مهلته).
     *
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    Y-m-d أو null لصفوف tour_date IS NULL.
     * @return int
     */
    public static function sum_reserved_seats( $tour_id, $date = null ) {
        global $wpdb;

        $tour_id = absint( $tour_id );
        if ( $tour_id <= 0 ) {
            return 0;
        }

        $table = rahl_table_name();

        $status_sql = "( booking_status = 'confirmed' OR ( booking_status = 'pending' AND expires_at > UTC_TIMESTAMP() ) )";

        if ( null !== $date && '' !== $date ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT COALESCE( SUM( guests ), 0 ) FROM {$table}
				WHERE tour_id = %d AND tour_date = %s AND {$status_sql}";

            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    $sql,
                    $tour_id,
                    $date
                )
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT COALESCE( SUM( guests ), 0 ) FROM {$table}
			WHERE tour_id = %d AND tour_date IS NULL AND {$status_sql}";

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                $sql,
                $tour_id
            )
        );
    }
}

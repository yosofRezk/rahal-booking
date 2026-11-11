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

    /**
     * يبحث عن حجز pending مطابق تم إنشاؤه في آخر 60 ثانية (لمنع تكرار الإرسال).
     *
     * @param string      $email   البريد الإلكتروني.
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    تاريخ الرحلة (Y-m-d أو null).
     * @param int         $guests  عدد الأفراد.
     * @return object|null
     */
    public static function find_recent_duplicate( $email, $tour_id, $date, $guests ) {
        global $wpdb;

        $table   = rahl_table_name();
        $email   = sanitize_email( $email );
        $tour_id = absint( $tour_id );
        $guests  = absint( $guests );

        if ( '' === $email || $tour_id <= 0 || $guests <= 0 ) {
            return null;
        }

        if ( null !== $date && '' !== $date ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT * FROM {$table}
                WHERE customer_email = %s
                  AND tour_id = %d
                  AND tour_date = %s
                  AND guests = %d
                  AND booking_status = 'pending'
                  AND created_at >= ( UTC_TIMESTAMP() - INTERVAL 60 SECOND )
                ORDER BY id DESC
                LIMIT 1";

            return $wpdb->get_row(
                $wpdb->prepare(
                    $sql,
                    $email,
                    $tour_id,
                    $date,
                    $guests
                )
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT * FROM {$table}
            WHERE customer_email = %s
              AND tour_id = %d
              AND tour_date IS NULL
              AND guests = %d
              AND booking_status = 'pending'
              AND created_at >= ( UTC_TIMESTAMP() - INTERVAL 60 SECOND )
            ORDER BY id DESC
            LIMIT 1";

        return $wpdb->get_row(
            $wpdb->prepare(
                $sql,
                $email,
                $tour_id,
                $guests
            )
        );
    }

    /**
     * يحسب عدد الحجوزات المعلقة لنفس العميل ونفس الرحلة والتاريخ التي لم تنتهِ مهلتها بعد.
     *
     * @param string      $email   البريد الإلكتروني.
     * @param int         $tour_id معرّف الرحلة.
     * @param string|null $date    تاريخ الرحلة (Y-m-d أو null).
     * @return int
     */
    public static function count_active_pending( $email, $tour_id, $date ) {
        global $wpdb;

        $table   = rahl_table_name();
        $email   = sanitize_email( $email );
        $tour_id = absint( $tour_id );

        if ( '' === $email || $tour_id <= 0 ) {
            return 0;
        }

        if ( null !== $date && '' !== $date ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT COUNT(*) FROM {$table}
                WHERE customer_email = %s
                  AND tour_id = %d
                  AND tour_date = %s
                  AND booking_status = 'pending'
                  AND expires_at > UTC_TIMESTAMP()";

            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    $sql,
                    $email,
                    $tour_id,
                    $date
                )
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT COUNT(*) FROM {$table}
            WHERE customer_email = %s
              AND tour_id = %d
              AND tour_date IS NULL
              AND booking_status = 'pending'
              AND expires_at > UTC_TIMESTAMP()";

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                $sql,
                $email,
                $tour_id
            )
        );
    }

    /**
     * يحذف حجزاً بالمعرّف (تستخدم في شبكة الأمان Rollback).
     *
     * @param int $id معرّف الحجز.
     * @return bool
     */
    public static function delete( $id ) {
        global $wpdb;

        $id = absint( $id );
        if ( $id <= 0 ) {
            return false;
        }

        $table   = rahl_table_name();
        $deleted = $wpdb->delete(
            $table,
            array( 'id' => $id ),
            array( '%d' )
        );

        return false !== $deleted;
    }

    /**
     * يجلب صفوف الحجوزات مع الفلترة والبحث والترتيب والتقسيم لصفحات.
     *
     * @param array $args معايير الاستعلام.
     * @return array
     */
    public static function query( array $args = array() ) {
        global $wpdb;

        $table = rahl_table_name();

        $defaults = array(
            'status'         => '',
            'payment_status' => '',
            'tour_id'        => 0,
            'search'         => '',
            'orderby'        => 'id',
            'order'          => 'DESC',
            'per_page'       => 20,
            'offset'         => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $where  = array( '1=1' );
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'booking_status = %s';
            $params[] = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['payment_status'] ) ) {
            $where[]  = 'payment_status = %s';
            $params[] = sanitize_key( $args['payment_status'] );
        }

        if ( ! empty( $args['tour_id'] ) ) {
            $where[]  = 'tour_id = %d';
            $params[] = absint( $args['tour_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $search_like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[]     = '( reference LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s )';
            $params[]    = $search_like;
            $params[]    = $search_like;
            $params[]    = $search_like;
            $params[]    = $search_like;
        }

        $where_sql = implode( ' AND ', $where );

        // الأعمدة المسموح الترتيب بها
        $allowed_orderby = array( 'id', 'created_at', 'total_minor', 'guests', 'tour_date' );
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
        $order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $per_page = max( 1, absint( $args['per_page'] ) );
        $offset   = max( 0, absint( $args['offset'] ) );

        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    /**
     * يحسب إجمالي عدد الحجوزات المطابقة لمعايير الاستعلام.
     *
     * @param array $args معايير الاستعلام.
     * @return int
     */
    public static function count( array $args = array() ) {
        global $wpdb;

        $table = rahl_table_name();

        $where  = array( '1=1' );
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'booking_status = %s';
            $params[] = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['payment_status'] ) ) {
            $where[]  = 'payment_status = %s';
            $params[] = sanitize_key( $args['payment_status'] );
        }

        if ( ! empty( $args['tour_id'] ) ) {
            $where[]  = 'tour_id = %d';
            $params[] = absint( $args['tour_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $search_like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[]     = '( reference LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s )';
            $params[]    = $search_like;
            $params[]    = $search_like;
            $params[]    = $search_like;
            $params[]    = $search_like;
        }

        $where_sql = implode( ' AND ', $where );
        $sql       = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * يرجّع تعداد الحجوزات لكل حالة (لاستخدامها في فلاتر Views أعلى الجدول).
     *
     * @return array
     */
    public static function count_by_status() {
        global $wpdb;

        $table = rahl_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            "SELECT booking_status, COUNT(*) as count FROM {$table} GROUP BY booking_status"
        );

        $counts = array(
            'all'       => 0,
            'pending'   => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'rejected'  => 0,
            'expired'   => 0,
        );

        if ( $results ) {
            foreach ( $results as $row ) {
                $status = $row->booking_status;
                $num    = (int) $row->count;
                if ( isset( $counts[ $status ] ) ) {
                    $counts[ $status ] = $num;
                }
                $counts['all'] += $num;
            }
        }

        return $counts;
    }
}


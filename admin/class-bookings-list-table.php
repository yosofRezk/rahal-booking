<?php
/**
 * RAHL_Bookings_List_Table — عرض وإدارة جدول الحجوزات في لوحة التحكم.
 *
 * مبني على كلاس ووردبريس القياسي WP_List_Table.
 *
 * @package Rahal
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class RAHL_Bookings_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'booking',
				'plural'   => 'bookings',
				'ajax'     => false,
			)
		);
	}

	/**
	 * تعريف أعمدة الجدول.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'reference'      => __( 'Reference', 'rahal' ),
			'customer'       => __( 'Customer', 'rahal' ),
			'tour_and_date'  => __( 'Tour & Date', 'rahal' ),
			'guests'         => __( 'Guests', 'rahal' ),
			'total'          => __( 'Total', 'rahal' ),
			'payment_status' => __( 'Payment', 'rahal' ),
			'booking_status' => __( 'Status', 'rahal' ),
			'created_at'     => __( 'Date', 'rahal' ),
		);
	}

	/**
	 * الأعمدة القابلة للترتيب (Sortable Columns).
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'reference'  => array( 'id', false ),
			'guests'     => array( 'guests', false ),
			'total'      => array( 'total_minor', false ),
			'created_at' => array( 'created_at', true ), // الافتراضي تنازلي
		);
	}

	/**
	 * فلاتر الحالات في أعلى الجدول (Views).
	 *
	 * @return array
	 */
	protected function get_views() {
		$counts         = RAHL_Booking_Repository::count_by_status();
		$current_status = isset( $_GET['booking_status'] ) ? sanitize_key( $_GET['booking_status'] ) : '';

		$status_labels = array(
			'all'       => __( 'All', 'rahal' ),
			'pending'   => __( 'Pending', 'rahal' ),
			'confirmed' => __( 'Confirmed', 'rahal' ),
			'completed' => __( 'Completed', 'rahal' ),
			'cancelled' => __( 'Cancelled', 'rahal' ),
			'rejected'  => __( 'Rejected', 'rahal' ),
			'expired'   => __( 'Expired', 'rahal' ),
		);

		$views = array();
		$base_url = admin_url( 'admin.php?page=rahl-bookings' );

		foreach ( $status_labels as $key => $label ) {
			$count = isset( $counts[ $key ] ) ? (int) $counts[ $key ] : 0;
			if ( $count === 0 && 'all' !== $key && $current_status !== $key ) {
				continue;
			}

			$url   = ( 'all' === $key ) ? $base_url : add_query_arg( 'booking_status', $key, $base_url );
			$class = ( ( '' === $current_status && 'all' === $key ) || $current_status === $key ) ? 'class="current"' : '';

			$views[ $key ] = sprintf(
				'<a href="%s" %s>%s <span class="count">(%d)</span></a>',
				esc_url( $url ),
				$class,
				esc_html( $label ),
				$count
			);
		}

		return $views;
	}

	/**
	 * عناصر الفلترة الإضافية بين الـ Bulk Actions والجدول.
	 *
	 * @param string $which أعلى أو أسفل الجدول.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$current_payment = isset( $_GET['payment_status'] ) ? sanitize_key( $_GET['payment_status'] ) : '';
		?>
		<div class="alignleft actions">
			<label for="filter-by-payment" class="screen-reader-text"><?php esc_html_e( 'Filter by Payment', 'rahal' ); ?></label>
			<select name="payment_status" id="filter-by-payment">
				<option value=""><?php esc_html_e( 'All Payment Statuses', 'rahal' ); ?></option>
				<option value="unpaid" <?php selected( $current_payment, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'rahal' ); ?></option>
				<option value="paid" <?php selected( $current_payment, 'paid' ); ?>><?php esc_html_e( 'Paid', 'rahal' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'rahal' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
		</div>
		<?php
	}

	/**
	 * تجهيز البيانات، الترتيب، والتقسيم لصفحات.
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$status         = isset( $_GET['booking_status'] ) ? sanitize_key( $_GET['booking_status'] ) : '';
		$payment_status = isset( $_GET['payment_status'] ) ? sanitize_key( $_GET['payment_status'] ) : '';
		$search         = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby        = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'id';
		$order          = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC';

		$query_args = array(
			'status'         => $status,
			'payment_status' => $payment_status,
			'search'         => $search,
			'orderby'        => $orderby,
			'order'          => $order,
			'per_page'       => $per_page,
			'offset'         => ( $current_page - 1 ) * $per_page,
		);

		$total_items = RAHL_Booking_Repository::count( $query_args );
		$this->items = RAHL_Booking_Repository::query( $query_args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * توليد كود الصف <tr> مع إضافة ID لتمكين الـ Focus / Highlight.
	 *
	 * @param object $item
	 */
	public function single_row( $item ) {
		$row_id = 'rahl-booking-row-' . absint( $item->id );
		echo '<tr id="' . esc_attr( $row_id ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * عمود Checkbox.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="booking_ids[]" value="%d" />',
			(int) $item->id
		);
	}

	/**
	 * عمود الرقم المرجعي والإجراءات السريعة (Row Actions).
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_reference( $item ) {
		$ref_title = '<strong><code>' . esc_html( $item->reference ) . '</code></strong>';

		// تجهيز إجراءات الصف بحسب الحالة وصلاحية المستخدم
		$actions = array();
		$base_url = admin_url( 'admin.php?page=rahl-bookings' );

		// 1. Confirm (للحجوزات المعلقة)
		if ( 'pending' === $item->booking_status ) {
			$confirm_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'rahl_confirm',
						'booking_id' => $item->id,
					),
					$base_url
				),
				'rahl_admin_action_' . $item->id
			);
			$actions['confirm'] = sprintf(
				'<a href="%s" style="color:#007017;font-weight:600;">%s</a>',
				esc_url( $confirm_url ),
				esc_html__( 'Confirm', 'rahal' )
			);

			// خيار سريع: Confirm + Mark as Paid معاً
			if ( 'unpaid' === $item->payment_status ) {
				$confirm_pay_url = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'rahl_confirm_and_pay',
							'booking_id' => $item->id,
						),
						$base_url
					),
					'rahl_admin_action_' . $item->id
				);
				$actions['confirm_pay'] = sprintf(
					'<a href="%s" style="color:#007017;">%s</a>',
					esc_url( $confirm_pay_url ),
					esc_html__( 'Confirm & Pay', 'rahal' )
				);
			}

			// Reject
			$reject_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'rahl_reject',
						'booking_id' => $item->id,
					),
					$base_url
				),
				'rahl_admin_action_' . $item->id
			);
			$actions['reject'] = sprintf(
				'<a href="%s" style="color:#d63638;" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $reject_url ),
				esc_js( __( 'Are you sure you want to reject this booking?', 'rahal' ) ),
				esc_html__( 'Reject', 'rahal' )
			);
		}

		// 2. Mark as Paid (للحجوزات غير الملغية وغير المرفوضة وغير المدفوعة)
		if ( 'unpaid' === $item->payment_status && ! in_array( $item->booking_status, array( 'cancelled', 'rejected' ), true ) ) {
			$pay_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'rahl_mark_paid',
						'booking_id' => $item->id,
					),
					$base_url
				),
				'rahl_admin_action_' . $item->id
			);
			$actions['mark_paid'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $pay_url ),
				esc_html__( 'Mark as Paid', 'rahal' )
			);
		}

		// 3. Complete (للحجوزات المؤكدة بعد تاريخ الرحلة)
		if ( 'confirmed' === $item->booking_status ) {
			$can_complete = true;
			if ( ! empty( $item->tour_date ) && $item->tour_date > gmdate( 'Y-m-d' ) ) {
				$can_complete = false;
			}

			if ( $can_complete ) {
				$comp_url = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'rahl_complete',
							'booking_id' => $item->id,
						),
						$base_url
					),
					'rahl_admin_action_' . $item->id
				);
				$actions['complete'] = sprintf(
					'<a href="%s" style="color:#0073aa;">%s</a>',
					esc_url( $comp_url ),
					esc_html__( 'Complete', 'rahal' )
				);
			}
		}

		// 4. Cancel (للحجوزات pending أو confirmed)
		if ( in_array( $item->booking_status, array( 'pending', 'confirmed' ), true ) ) {
			$cancel_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'rahl_cancel',
						'booking_id' => $item->id,
					),
					$base_url
				),
				'rahl_admin_action_' . $item->id
			);
			$actions['cancel'] = sprintf(
				'<a href="%s" style="color:#d63638;" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $cancel_url ),
				esc_js( __( 'Are you sure you want to cancel this booking? Reserved seats will be restored.', 'rahal' ) ),
				esc_html__( 'Cancel', 'rahal' )
			);
		}

		return $ref_title . $this->row_actions( $actions );
	}

	/**
	 * عمود العميل.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_customer( $item ) {
		$html  = '<strong>' . esc_html( $item->customer_name ) . '</strong><br>';
		$html .= '<a href="mailto:' . esc_attr( $item->customer_email ) . '">' . esc_html( $item->customer_email ) . '</a><br>';
		$html .= '<span class="description">' . esc_html( $item->customer_phone ) . '</span>';
		return $html;
	}

	/**
	 * عمود الرحلة وتاريخها.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_tour_and_date( $item ) {
		$title = ! empty( $item->tour_title_snapshot ) ? $item->tour_title_snapshot : RAHL_Tour::get_title( $item->tour_id );
		$html  = '<strong>' . esc_html( $title ) . '</strong>';

		if ( ! empty( $item->tour_date ) ) {
			$html .= '<br><span class="dashicons dashicons-calendar-alt" style="font-size:15px;line-height:1.3;color:#64748b;"></span> ' . esc_html( $item->tour_date );
		}

		return $html;
	}

	/**
	 * عمود الأفراد.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_guests( $item ) {
		return esc_html( (string) $item->guests );
	}

	/**
	 * عمود الإجمالي.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_total( $item ) {
		$formatted = RAHL_Pricing::format( $item->total_minor, $item->currency );
		$method_label = ( 'transfer' === $item->payment_method ) ? __( 'Bank Transfer', 'rahal' ) : __( 'Pay at Branch', 'rahal' );

		return '<strong>' . esc_html( $formatted ) . '</strong><br><small style="color:#64748b;">' . esc_html( $method_label ) . '</small>';
	}

	/**
	 * عمود حالة الدفع.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_payment_status( $item ) {
		if ( 'paid' === $item->payment_status ) {
			return '<span class="rahl-badge rahl-badge-success">' . esc_html__( 'Paid', 'rahal' ) . '</span>';
		}
		return '<span class="rahl-badge rahl-badge-warning">' . esc_html__( 'Unpaid', 'rahal' ) . '</span>';
	}

	/**
	 * عمود حالة الحجز.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_booking_status( $item ) {
		$badges = array(
			'pending'   => 'rahl-badge-warning',
			'confirmed' => 'rahl-badge-success',
			'completed' => 'rahl-badge-info',
			'cancelled' => 'rahl-badge-muted',
			'rejected'  => 'rahl-badge-danger',
			'expired'   => 'rahl-badge-muted',
		);

		$cls = isset( $badges[ $item->booking_status ] ) ? $badges[ $item->booking_status ] : 'rahl-badge-muted';
		return '<span class="rahl-badge ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $item->booking_status ) ) . '</span>';
	}

	/**
	 * عمود تاريخ الإنشاء.
	 *
	 * @param object $item
	 * @return string
	 */
	protected function column_created_at( $item ) {
		return esc_html( gmdate( 'Y-m-d H:i', strtotime( $item->created_at ) ) );
	}

	/**
	 * المخرجات الافتراضية لأي عمود آخر.
	 *
	 * @param object $item
	 * @param string $column_name
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
	}
}

<?php
/**
 * This file contains the informations needed to create a log table inside the admin area.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import the WP_List_Table class.
 */
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Create error logs table.
 */
require_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-logs.php';

/**
 * Create error logs table
 *
 * @since 1.0.0
 */
class WDGPT_Error_Logs_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => __( 'log', 'webdigit-chatbot' ),
				'plural'   => __( 'logs', 'webdigit-chatbot' ),
			)
		);
	}

	/**
	 * Get the default column value.
	 *
	 * @param array  $item The current item.
	 * @param string $column_name The column name.
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Retrieve the column names.
	 */
	public function get_columns() {
		$columns = array(
			'error'      => __( 'Error', 'webdigit-chatbot' ),
			'error_type' => __( 'Error Type', 'webdigit-chatbot' ),
			'error_code' => __( 'Error Code', 'webdigit-chatbot' ),
			'question'   => __( 'Question', 'webdigit-chatbot' ),
			'created_at' => __( 'Created At', 'webdigit-chatbot' ),

		);
		return $columns;
	}

	/**
	 * Retrieve the hidden columns.
	 */
	public function get_hidden_columns() {
		$columns = array(
			'id' => __( 'ID', 'webdigit-chatbot' ),
		);
		return $columns;
	}

	/**
	 * Retrieve the sortable columns.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'error'      => array( 'error', true ),
			'error_type' => array( 'error_type', true ),
			'error_code' => array( 'error_code', true ),
			'question'   => array( 'question', true ),
			'created_at' => array( 'created_at', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Add an extra action on top of the table.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<div class="alignleft actions">
				<form method="post" name="purge_error_logs">
					<?php submit_button( __( 'Purge Logs', 'webdigit-chatbot' ), 'action', 'delete_old_error_logs', false ); ?>
					<select name="months">
						<option value="1" selected>> <?php esc_html_e( '1 Month', 'webdigit-chatbot' ); ?></option>
						<option value="3">> <?php esc_html_e( '3 Months', 'webdigit-chatbot' ); ?></option>
						<option value="6">> <?php esc_html_e( '6 Months', 'webdigit-chatbot' ); ?></option>
						<option value="12">> <?php esc_html_e( '12 Months', 'webdigit-chatbot' ); ?></option>
						<option value="-1"><?php esc_html_e( 'Forever', 'webdigit-chatbot' ); ?></option>
					</select>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Prepare the items for the table to process.
	 *
	 * @return void
	 */
	public function prepare_items() {

		global $wpdb;
		$table_name            = $wpdb->prefix . 'wd_error_logs';
		$per_page              = 20;
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$san_order_by = 'error';
		$san_order    = 'asc';
		$paged        = 0;
		if ( isset( $_REQUEST['wdgpt_error_logs_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wdgpt_error_logs_nonce'] ) ), 'wdgpt_error_logs' ) ) {
			$san_order_by = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'error';
			$san_order    = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
			$paged        = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;
		}

		$total_items = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %1s', $table_name ) );
		$orderby     = ( in_array( $san_order_by, array_keys( $this->get_sortable_columns() ), true ) ) ? $san_order_by : 'error';
		$order       = ( in_array( $san_order, array( 'asc', 'desc' ), true ) ) ? $san_order : 'asc';

		$cache_key = 'wd_error_logs_' . $orderby . '_' . $order . '_' . $per_page . '_' . $paged;
		$items     = wp_cache_get( $cache_key );

		if ( false === $items ) {
			$items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s ORDER BY %1s %1s LIMIT %d OFFSET %d', $table_name, $orderby, $order, $per_page, $paged ), ARRAY_A );
			wp_cache_set( $cache_key, $items );
		}

		$this->items = $items;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

?>
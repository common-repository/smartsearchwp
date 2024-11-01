<?php
/**
 * This file is responsible or creating the logs table.
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
 * Create logs table.
 */
require_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-logs.php';

/**
 * Create logs table
 *
 * @since 1.0.0
 */
class WDGPT_Logs_Table extends WP_List_Table {

	/**
	 * The chat logs instance.
	 *
	 * @var $chat_logs
	 */
	private $chat_logs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $status, $page;
		$this->chat_logs = WDGPT_Logs::get_instance();

		parent::__construct(
			array(
				'singular' => esc_html( __( 'log', 'webdigit-chatbot' ) ),
				'plural'   => esc_html( __( 'logs', 'webdigit-chatbot' ) ),
			)
		);
	}

	/**
	 * Get the default column value.
	 *
	 * @param array  $item The current item.
	 * @param string $column_name The column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Retrieve the column names.
	 *
	 * @param array $item The current item.
	 * @return array
	 */
	public function column_question( $item ) {
		// Add an action to the question that will always be shown, and not need a mouseover.
		$actions = array(
			'view_conversation' => sprintf( '<a href="#" class="view-conversation-link" data-id="%s">%s</a>', $item['id'], esc_html( __( 'View entire conversation', 'webdigit-chatbot' ) ) ),
		);

		// The item to show is the last message where the source is 0.
		$last_message = $this->chat_logs->get_last_message( $item['id'], 0 );
		if ( $last_message ) {
			$question = $last_message->prompt;
			// If the $question has more than 50 characters, we will cut it.
			if ( strlen( $question ) > 50 ) {
				$question = substr( $question, 0, 50 ) . '...';
			}
			return sprintf( '%1$s %2$s', $question, $this->row_actions( $actions, true ) );
		}
	}

	/**
	 * Retrieve the column names.
	 *
	 * @param array $item The current item.
	 * @return array
	 */
	public function column_answer( $item ) {
		// The item to show is the last message where the source is 1.
		$last_message = $this->chat_logs->get_last_message( $item['id'], 1 );
		if ( $last_message ) {
			$answer = $last_message->prompt;
			// If the $answer has more than 50 characters, we will cut it.
			if ( mb_strlen( $answer, 'UTF-8' ) > 50 ) {
				$answer = mb_substr( $answer, 0, 50, 'UTF-8' ) . '...';
			}
			return $answer;
		}
	}

	/**
	 * Retrieve the column names.
	 *
	 * @param array $item The current item.
	 * @return array
	 */
	public function column_post_id( $item ) {
		if ( '' !== $item['post_ids'] ) {
			// post_ids is a comma separated list of post ids, if there is only one, there is no comma.
			$post_ids = explode( ',', $item['post_ids'] );
			$slugs    = array();
			foreach ( $post_ids as $post_id ) {
				$post      = get_post( $post_id );
				$permalink = get_permalink( $post->ID );
				$slug      = ' <a href="' . $permalink . '" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a>';
				$slugs[]   = sprintf(
					'%s %s',
					$post->post_title,
					$slug
				);
			}
			// return an sprintf of the $slugs.
			return implode( ', ', $slugs );
		}
	}

	/**
	 * Retrieve the column names.
	 *
	 * @param array $item The current item.
	 * @return array
	 */
	public function column_created_at( $item ) {
		return sprintf(
			'<span class="date" data-id="' . $item['id'] . '">%s</span>',
			gmdate(
				'Y-m-d H:i:s',
				strtotime( $item['created_at'] )
			)
		) . '</td></tr><tr class="view-conversation-row" data-id=' . $item['id'] . ' style="display:none;"><td class="fixdisplay"></td><td class="fixdisplay" colspan="2">' . $this->details_conversation( $item ) . '</td><td></td></tr>';
	}


	/**
	 * Retrieve the column names.
	 *
	 * @param array $item The current item.
	 * @return array
	 */
	private function details_conversation( $item ) {
		$messages     = $this->chat_logs->get_messages( $item['id'] );
		$conversation = '<div class="conversation">';
		/**
		 * Library to parse markdown to html (ex: **bold** to <strong>bold</strong>).
		 */
		$parsedown = new Parsedown();
		foreach ( $messages as $message ) {
			$role            = '0' === $message->source ? 'user' : 'assistant';
			$conversation   .= '<div class="message ' . $role . '">';
			$conversation   .= '<div class="message-source">';
			$conversation   .= '0' === $message->source ? esc_html( __( 'User', 'webdigit-chatbot' ) ) : esc_html( __( 'Bot', 'webdigit-chatbot' ) );
			$conversation   .= '</div>';
			$conversation   .= '<div class="message-prompt">';
			$title_pattern   = '/##### (.*?)(:|<br>)/';
			$message->prompt = preg_replace( $title_pattern, '<strong>$1</strong>', $message->prompt );
			$title_pattern   = '/#### (.*?)(:|<br>)/';
			$message->prompt = preg_replace( $title_pattern, '<strong>$1</strong>', $message->prompt );
			$title_pattern   = '/### (.*?)(:|<br>)/';
			$message->prompt = preg_replace( $title_pattern, '<strong>$1</strong>', $message->prompt );
			$conversation   .= $parsedown->text( $message->prompt );
			$conversation   .= '</div>';
			$conversation   .= '</div>';
		}
		$conversation .= '</div>';
		return $conversation;
	}


	/**
	 * Retrieve the column names.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'question'   => esc_html( __( 'Latest Question', 'webdigit-chatbot' ) ),
			'answer'     => esc_html( __( 'Answer', 'webdigit-chatbot' ) ),
			'post_id'    => esc_html( __( 'Context used', 'webdigit-chatbot' ) ),
			'created_at' => esc_html( __( 'Created At', 'webdigit-chatbot' ) ),

		);
		return $columns;
	}

	/**
	 * Retrieve the hidden columns.
	 *
	 * @return array
	 */
	private function get_hidden_columns() {
		$columns = array(
			'id' => esc_html( __( 'ID', 'webdigit-chatbot' ) ),
		);
		return $columns;
	}

	/**
	 * Retrieve the sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'created_at' => array( 'created_at', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Retrieve the bulk actions.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
			<div class="alignleft actions">
				<form method="post" name="purge_error_logs">
					<select name="months">
					<option value="1" selected>> <?php esc_html_e( '1 Month', 'webdigit-chatbot' ); ?></option>
						<option value="3">> <?php esc_html_e( '3 Months', 'webdigit-chatbot' ); ?></option>
						<option value="6">> <?php esc_html_e( '6 Months', 'webdigit-chatbot' ); ?></option>
						<option value="12">> <?php esc_html_e( '12 Months', 'webdigit-chatbot' ); ?></option>
						<option value="-1"><?php esc_html_e( 'Forever', 'webdigit-chatbot' ); ?></option>
					</select>
					<?php submit_button( esc_html( __( 'Purge Logs', 'webdigit-chatbot' ) ), 'action', 'delete_old_chat_logs', false ); ?>
				</form>
			</div>
			<?php
		}
	}

	/**
	 * Prepare the items.
	 */
	public function prepare_items() {
		global $wpdb;
		$table_name            = $wpdb->prefix . 'wdgpt_logs';
		$per_page              = 20;
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$san_order_by = 'created_at';
		$san_order    = 'desc';
		if ( isset( $_POST['wdgpt_logs_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wdgpt_logs_nonce'] ) ), 'wdgpt_logs' ) ) {
			$san_order_by = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
			$san_order    = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';
		}

		$cache_key = 'wdgpt_error_logs_count';
		$cache_ttl = 3600; // Cache for 1 hour.

		$total_items = wp_cache_get( $cache_key );
		if ( false === $total_items ) {
			$total_items = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %1s', $table_name ) );
			wp_cache_set( $cache_key, $total_items, '', $cache_ttl );
		}

		$paged       = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] - 1 ) * $per_page ) : 0;
		$orderby     = ( in_array( $san_order_by, array_keys( $this->get_sortable_columns() ), true ) ) ? $san_order_by : 'created_at';
		$order       = ( in_array( $san_order, array( 'asc', 'desc' ), true ) ) ? $san_order : 'desc';
		$this->items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %1s ORDER BY %1s %1s LIMIT %d OFFSET %d', $table_name, $orderby, $order, $per_page, $paged ), ARRAY_A );
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
<?php
/**
 * This file is responsible to manage error logs.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to log errors.
 */
require_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-error-logs-table.php';

/**
 * Function to log errors.
 */
function wdgpt_error_logs() {
	global $wpdb;
	$table = new WDGPT_Error_Logs_Table();
	?>
	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php esc_html_e( 'Error Logs', 'webdigit-chatbot' ); ?>
		</h2>
		<?php
		if ( isset( $_REQUEST['wdgpt_error_logs_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wdgpt_error_logs_nonce'] ) ), 'wdgpt_error_logs' ) ) {
			if ( isset( $_GET['deleted'] ) ) {
				if ( 0 === $_GET['deleted'] ) {
					new WDGPT_Admin_Notices( 2, __( 'Deleted all logs.', 'webdigit-chatbot' ) );
				} elseif ( 1 === $_GET['deleted'] ) {
					$months = isset( $_GET['months'] ) ? intval( $_GET['months'] ) : 0;
					/* translators: %d : number of month  */
					new WDGPT_Admin_Notices( 2, sprintf( esc_html__( 'Deleted logs older than %d months.', 'webdigit-chatbot' ), $months ) );
				}
			}
		}
		$database_updater = new WDGPT_Database_Updater( wdgpt_chatbot()->get_version() );
		$updates          = $database_updater->check_for_updates();
		if ( empty( $updates ) ) {
			$table->prepare_items();
			$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';

			?>
		<form method="post">
			<?php wp_nonce_field( 'wdgpt_error_logs', 'wdgpt_error_logs_nonce' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
			<?php $table->display(); ?>
		</form>
	</div>
			<?php
		}
} ?>
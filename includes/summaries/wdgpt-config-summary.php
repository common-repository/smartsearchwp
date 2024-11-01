<?php
/**
 * This file is responsible to manage summaries.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to manage summaries.
 */
require_once WD_CHATBOT_PATH . 'includes/summaries/class-wdgpt-summaries-table.php';

/**
 * Function to manage summaries.
 */
function wdgpt_generated_summary() {
	$api_key_validator = wdpgt_is_valid_api_key();
	$color             = $api_key_validator['color'];
	if ( 'red' === $color ) {
		new WDGPT_Admin_Notices( 1, __( 'Your OpenAI API key is invalid. Please set a valid API key in the settings page.', 'webdigit-chatbot' ), false );
		return;
	} elseif ( 'orange' === $color ) {
		new WDGPT_Admin_Notices( 1, __( 'Please set your OpenAI API key in the settings page.', 'webdigit-chatbot' ), false );
		return;
	}
	if ( ! wdgpt_is_api_key_set() ) {
		new WDGPT_Admin_Notices( 1, __( 'Please set your OpenAI API key in the settings page.', 'webdigit-chatbot' ), false );
		return;
	}
	global $wpdb;
	$table = new WDGPT_Summaries_Table();
	$page  = '';
	// phpcs:ignore
	$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';

	?>
	<div id="wdgpt-modal-embeddings" class="wdgpt-modal">
			<div class="wdgpt-modal-content">
				<span id="wdgpt-modal-embeddings-close" class="wdgpt-close">&times;</span>
				<p><?php esc_html_e( 'You have an insufficient quota linked to your api key. Please update your plan on the OpenAI website if you want to generate embeddings.', 'webdigit-chatbot' ); ?></p>
			</div>
		</div>
	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php esc_html_e( 'OpenAI Generated Summaries', 'webdigit-chatbot' ); ?>
		</h2>
		<?php
		$database_updater = new WDGPT_Database_Updater( wdgpt_chatbot()->get_version() );
		$updates          = $database_updater->check_for_updates();
		if ( empty( $updates ) ) {
			?>
		<div class="wdgpt-legend">
			<div class="wdgpt-legend-row">
				<div class="wdgpt-legend-color wdgpt-legend-color-green"></div>
				<div class="wdgpt-legend-description"><?php esc_html_e( 'The row is currently active and its embeddings is up to date with the post.', 'webdigit-chatbot' ); ?></div>
			</div>
			<div class="wdgpt-legend-row">
				<div class="wdgpt-legend-color wdgpt-legend-color-yellow"></div>
				<div class="wdgpt-legend-description"><?php esc_html_e( 'The row is currently active but its embeddings are not up to date with the post.', 'webdigit-chatbot' ); ?></div>
			</div>
		</div>

			<?php $table->prepare_items(); ?>
		<form method="get">
			<?php wp_nonce_field( 'wdgpt_summaries', 'wdgpt_summaries_nonce' ); ?>
			<?php $table->views(); ?>
			<input type="hidden" name="page" value="<?php echo esc_html( $page ); ?>" />
			<?php $table->display(); ?>
		</form>
	</div>
			<?php
		}
} ?>
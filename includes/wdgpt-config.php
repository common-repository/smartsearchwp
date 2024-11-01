<?php
/**
 * This file is reponsible for the admin dashboard.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This is required to generate custom tooltips.
 */
require_once WD_CHATBOT_PATH . 'includes/summaries/class-wdgpt-custom-tooltip.php';


use Orhanerday\OpenAi\OpenAi;

/**
 * Checks if the OpenAI API key is valid.
 *
 * @return array An array with the message and the color of the message.
 */
function wdpgt_is_valid_api_key() {
	$openai_api_key = get_option( 'wd_openai_api_key', '' );
	if ( ! $openai_api_key ) {
		return array(
			'message' => __( 'Please enter your api key!', 'webdigit-chatbot' ),
			'color'   => 'orange',
		);
	}
	$openai_client = new OpenAI( $openai_api_key );
	$models        = json_decode( $openai_client->listModels(), true );
	// Verification if the OpenAI API is down.
	if ( isset( $models['error'] ) ) {
		$bad_gateway = strpos( strtolower( $models['error']['message'] ), 'bad gateway' );
		if ( false !== $bad_gateway ) {
			return array(
				'message' => __( 'The OpenAI API currently has issues. Please try again later.', 'webdigit-chatbot' ),
				'color'   => 'orange',
			);
		}
	}

	if ( isset( $models['error'] ) || ! isset( $models['data'] ) ) {

		return array(
			'message' => __( 'Your api key is invalid! If you think this is a mistake, please check your account on the OpenAI platform.', 'webdigit-chatbot' ),
			'color'   => 'red',
		);
	}
	if ( ! $models ) {
		echo 'no models';
		return array(
			'message' => __( 'Your api key is invalid! If you think this is a mistake, please check your settings on the OpenAI platform.', 'webdigit-chatbot' ),
			'color'   => 'red',
		);
	}
	return array(
		'message' => __( 'Your api key is valid!', 'webdigit-chatbot' ),
		'color'   => 'green',
	);
}

/**
 * Get the models that are available with the current api key.
 *
 * @param array $available_models The models that are available.
 */
function wdpgt_get_models( $available_models ) {
	$openai_api_key = get_option( 'wd_openai_api_key', '' );
	if ( ! $openai_api_key ) {
		return array();
	}
	$openai_client = new OpenAI( $openai_api_key );
	$models        = json_decode( $openai_client->listModels(), true );
	if ( isset( $models['error'] ) || ! isset( $models['data'] ) ) {
		return array();
	}
	// Check if the $models['data'] has the $available_models.
	// The $models['data'] is an array of models, where the name is inside the "id" key.
	$models = array_filter(
		$models['data'],
		function ( $model ) use ( $available_models ) {
			return in_array( $model['id'], $available_models, true );
		}
	);
	return $models;
}

/**
 * Show an admin notice to rate the plugin.
 */
function wdgpt_show_admin_notice_rate_us() {
	?>
		<div id="wdgpt-rate-us-notice" class="notice notice-info">
			<p>
				<?php

				esc_html_e(
					'If you like this plugin, please leave us a review to help us grow. Thank you!',
					'webdigit-chatbot'
				);
				?>
			</p>
			<p>
				<a href="https://wordpress.org/support/plugin/smartsearchwp/reviews/#new-post" target="_blank">
					<?php
					esc_html_e(
						'Leave a review',
						'webdigit-chatbot'
					);
					?>
				</a>&nbsp;
				<a href="#" id="wdgpt_rate_us_no_thanks">
				<?php
					esc_html_e(
						'No, thanks',
						'webdigit-chatbot'
					);
				?>
				</a>&nbsp;
				<a href="#" id="wdgpt_rate_us_done">
				<?php
					esc_html_e(
						'Already rated',
						'webdigit-chatbot'
					);
				?>
				</a>&nbsp;
				<a href="#" id="wdgpt_remind_me_later">
				<?php
					esc_html_e(
						'Remind me later',
						'webdigit-chatbot'
					);
				?>
				</a>
			</p>
		</div>
		<?php
}



/**
 * Generate the admin dashboard.
 */
function wdgpt_config_form() {
	$active_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
	$active_tab = $active_tab ? $active_tab : 'wdgpt_settings';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php
		wdgpt_show_admin_notice_rate_us();
		?>

		<h2 class="nav-tab-wrapper">
			<a href="?page=wdgpt&tab=wdgpt_settings" class="nav-tab <?php echo 'wdgpt_settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General Settings', 'webdigit-chatbot' ); ?></a>
			<a href="?page=wdgpt&tab=wdgpt_reporting" class="nav-tab <?php echo 'wdgpt_reporting' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reporting', 'webdigit-chatbot' ); ?></a>
			<?php
				$current_version  = wdgpt_chatbot()->get_version();
				$database_updater = new WDGPT_Database_Updater( $current_version );
				$pending_updates  = $database_updater->check_for_updates();
			if ( ! empty( $pending_updates ) ) {
				?>
						<a href="?page=wdgpt&tab=wdgpt_db_update" class="nav-tab <?php echo 'wdgpt_db_update' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Database Update', 'webdigit-chatbot' ); ?></a>
					<?php
			}
			?>
		</h2>

		<form method="post" enctype="multipart/form-data">
		<?php
		switch ( $active_tab ) {
			case 'wdgpt_settings':
				settings_fields( 'wdgpt_settings' );
				do_settings_sections( 'wdgpt_settings' );
				break;
			case 'wdgpt_reporting':
				settings_fields( 'wdgpt_reporting' );
				do_settings_sections( 'wdgpt_reporting' );
				break;
			case 'wdgpt_db_update':
				settings_fields( 'wdgpt_database_update' );
				do_settings_sections( 'wdgpt_database_update' );
				break;
			default:
				settings_fields( 'wdgpt_settings' );
				do_settings_sections( 'wdgpt_settings' );
				break;
		}
		?>
		</form>
	</div>
	<script>
		function updateImage(event) {
			const file = event.target.files[0];
			const reader = new FileReader();
			reader.onload = function(event) {
			const img = document.getElementById('pluginimg');
			img.src = event.target.result;
			const filenameInput = document.getElementById('wdgpt_image_name');
			filenameInput.value = file.name;
			}
			reader.readAsDataURL(file);
		}
	</script>
	<?php
}

/**
 * Add enctype to form
 */
function wdgpt_post_edit_form_tag() {
	echo ' enctype="multipart/form-data"';
}
add_action( 'post_edit_form_tag', 'wdgpt_post_edit_form_tag' );

?>
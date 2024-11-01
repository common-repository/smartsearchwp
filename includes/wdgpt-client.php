<?php
/**
 * This file is reponsible for creating the chatbot interface.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Orhanerday\OpenAi\OpenAi;

/**
 * Retrieve the active post ids.
 */
function wdgpt_get_active_post_ids() {
	$args = array(
		'post_type'      => 'any',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'   => 'wdgpt_is_active',
				'value' => 'true',
			),
		),
		'fields'         => 'ids',
	);
	return get_posts( $args );
}

/**
 * Checks if the OpenAI API key is set.
 *
 * @return bool True if the API key is set, false otherwise.
 */
function wdgpt_is_api_key_set() {
	$api_key = get_option( 'wd_openai_api_key' );
	return ! empty( $api_key );
}

/**
 * Checks if the OpenAI API is down.
 *
 * @return bool True if the API is down, false otherwise.
 */
function wdgpt_is_api_down() {
	$openai_api_key = get_option( 'wd_openai_api_key', '' );
	$openai_client  = new OpenAI( $openai_api_key );
	$models         = json_decode( $openai_client->listModels(), true );
	// Verification if the OpenAI API is down.
	if ( isset( $models['error'] ) ) {
		$bad_gateway = strpos( strtolower( $models['error']['message'] ), 'bad gateway' );
		if ( $bad_gateway !== false ) {
			return true;
		}
	}
	return false;
}

/**
 * Checks if the chatbot should be displayed.
 *
 * @return bool True if the chatbot should be displayed, false otherwise.
 */
function wdgpt_should_display() {
	$enable_bot                  = get_option( 'wdgpt_enable_chatbot', 'on' );
	$active_posts                = wdgpt_get_active_post_ids();
	$database_update             = new WDGPT_Database_Updater( wdgpt_chatbot()->get_version() );
	$has_pending_database_update = $database_update->should_disable_plugin();
	return 'on' === $enable_bot &&
			count( $active_posts ) > 0 &&
			wdgpt_is_api_key_set() &&
			! $has_pending_database_update &&
			! wdgpt_is_api_down() &&
			ini_get( 'allow_url_fopen' );
}

/**
 * Displays the chatbot footer.
 */
function wdgpt_display_chatbot_footer() {
	if ( wdgpt_should_display() ) {
		wdgpt_chatbot_ui();
	}
}

/**
 * Displays the chatbot inside the footer.
 * Special rule to add dummy messages if the chatbot is not displayed on the front page, such as a preview in the admin panel.
 * 
 * @param bool $is_front True if the chatbot is displayed on the front page, false otherwise.
 * 
 * @return void
 */
function wdgpt_chatbot_ui() {
	$chatbot_name = '' === get_option( 'wdgpt_name', 'Pixel' ) ? 'Pixel' : get_option( 'wdgpt_name', 'Pixel' );
		$image_src    = '';
		$wdgpt_image  = get_option( 'wdgpt_image_name' );
		if ( $wdgpt_image ) {
			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['basedir'] . '/' . $wdgpt_image;
			if ( file_exists( $file_path ) ) {
				$image_src = $upload_dir['baseurl'] . '/' . $wdgpt_image;
			} else {
				$image_src = WD_CHATBOT_URL . '/img/SmartSearchWP-logo.png';
			}
		} else {
			$image_src = WD_CHATBOT_URL . '/img/SmartSearchWP-logo.png';
		}
		?>
		<div id="chat-circle" class="btn btn-raised <?php echo esc_attr('wdgpt-'.get_option('wdgpt_chat_position', 'bottom-right')); ?>">
			<div id="chat-overlay"></div>
			<img id="pluginimg" src="<?php echo esc_attr($image_src); ?>"></img>
			<?php
				if (get_option( 'wdgpt_enable_chatbot_bubble', 'on' ) === 'on' ) {
				?>
				<div class="chat-bubble">
					<div class="text">
						<span class="typing"><? echo esc_attr( get_option('wdgpt_chat_bubble_typing_text_' . get_locale() , __('Hello, may I help you?', 'webdigit-chatbot'))); ?></span>
					</div>
				</div>
			<?php 
				}
			?>
		</div>
		<div id="chatbot-container">
			<div id="chatbot-header">
				<img id="pluginimg" src="<?php echo esc_attr($image_src); ?>"></img>
				<div id="chatbot-title"><?php echo esc_attr($chatbot_name); ?></div>
				<div id="chatbot-resize"><i class="fa-solid fa-expand"></i></div>
				<div id="chatbot-reset"><i class="fa-solid fa-trash-can"></i></div>
				<div id="chatbot-close"><i class="fa fa-times" aria-hidden="true"></i></div>
			</div>
			<div id="chatbot-body">
				<div id="chatbot-messages">
					<div class="chatbot-message assistant">
						<div>
							<img class="chatbot-message-img" src="<?php echo esc_attr($image_src); ?>" alt="Chatbot Image">
							<span class="response assistant"><? echo esc_attr( get_option('wdgpt_greetings_message_' . get_locale() , __('Bonjour, je suis SmartSearchWP, comment puis-je vous aider ?', 'webdigit-chatbot'))); ?></span>
						</div>
					</div>
				</div>
				<div id="chatbot-input-container">
					<button id="wdgpt-speech-to-text" class="chatbot-btn-speech"><i class="fa fa-microphone" aria-hidden="true"></i></button>
					<input type="text" id="chatbot-input" placeholder="<?php esc_html_e( 'Tapez votre message ici...', 'webdigit-chatbot' ); ?>">
					<button id="chatbot-send" class="chatbot-btn-send"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
				</div>
				<div id="chatbot-disclaimer">
					<p><?php esc_html_e( 'Powered by artificial intelligence, the bot can make mistakes. Consider checking important information.', 'webdigit-chatbot' ); ?></p>
				</div>
			</div>
		</div>
		<?php
}

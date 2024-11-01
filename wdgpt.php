<?php
/**
 * Plugin Name: SmartSearchWP
 * Description: A chatbot that helps users navigate your website and find what they're looking for.
 * Plugin URI:  https://www.smartsearchwp.com/
 * Version:     2.4.7
 * Author:      Webdigit
 * Author URI:  https://www.smartsearchwp.com/
 * Text Domain: webdigit-chatbot
 *
 * SmartSearchWP is a powerful tool that uses natural language processing to understand user queries and provide relevant results. With its intuitive interface and customizable settings, it's the perfect solution for any website looking to improve user experience and engagement.
 *
 * @tags chatbot, search, search engine, search results, search widget, search form, search bar, search box, search plugin, chatgpt, openai
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WDGPT_CHATBOT_VERSION', '2.4.7' );


require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/class-wdgpt-chatbot-initializer.php';

/**
 * Initialize WD Chatbot.
 *
 * @return object
 */
function wdgpt_chatbot() {
	static $instance;
	if ( null === $instance || ! ( $instance instanceof WDGPT_Chatbot_Initializer ) ) {
		$instance = WDGPT_Chatbot_Initializer::instance();
	}

	return $instance;
}

wdgpt_chatbot();

register_activation_hook( __FILE__, array( wdgpt_chatbot(), 'activate' ) );
register_deactivation_hook( __FILE__, array( wdgpt_chatbot(), 'deactivate' ) );

/**
 * Show a notice if the plugin needs to update the database.
 */
function wdgpt_admin_notice() {
	$current_version  = wdgpt_chatbot()->get_version();
	$database_updater = new WDGPT_Database_Updater( $current_version );
	$pending_updates  = $database_updater->check_for_updates();
	if ( empty( $pending_updates ) ) {
		return;
	}
	// Check if at least one of the $pending_updates has a level of 1.
	$level_1 = false;
	foreach ( $pending_updates as $update ) {
		if ( 1 === $update['level'] ) {
			$level_1 = true;
			break;
		}
	}
	$notice_level = $level_1 ? 'error' : 'warning';
	?>
	<div class="notice notice-<?php echo esc_attr( $notice_level ); ?> is-dismissible">
		<p><?php esc_html_e( 'The plugin SmartSearchWP needs to update the database. Please click on the link below to go the plugin settings and update the database.', 'webdigit-chatbot' ); ?></p>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wdgpt&tab=wdgpt_db_update' ) ); ?>"><?php esc_html_e( 'Update the database', 'webdigit-chatbot' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'wdgpt_admin_notice' );
?>

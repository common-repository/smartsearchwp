<?php
/**
 * This file is responsible for the database update settings.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Add the database update section to the settings page.
 */
function wdgpt_database_update_section_callback() {
	$database_updater = new WDGPT_Database_Updater( wdgpt_chatbot()->get_version() );
	$updates          = $database_updater->check_for_updates();
	if ( empty( $updates ) ) {
		?>
			<p>
		<?php
			esc_html_e(
				'You are already using the most recent version of the database, you can now use the plugin without any restrictions.',
				'webdigit-chatbot'
			);
	} else {
		?>
		<p>
		<?php
		esc_html_e(
			'After the latest update, the database needs to be updated for the plugin to work properly. You can update the database by interacting with the button below.',
			'webdigit-chatbot'
		);
		?>
		</p>
		<p>
			<?php esc_html_e( 'Please make sure to backup your database before updating it.', 'webdigit-chatbot' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Here are the details of the database update:', 'webdigit-chatbot' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Database version:', 'webdigit-chatbot' ); ?>
			<?php echo esc_html( get_option( 'wdgpt_chatbot_version', '1.0.0' ) ); ?> &#8594;
			<?php echo esc_html( wdgpt_chatbot()->get_version() ); ?>
		</p>
		<p>
			<!-- Retrieve all the updates and their update_notice. -->
			<?php
			foreach ( $updates as $version => $update ) {
				if ( '' !== $update['update_notice'] ) {
					echo esc_html( $update['version'] ) . ' &#8594; ' . esc_html( $update['update_notice'] ) . '<br>';
				}
			}
			?>
		</p>
	
		</p>
		<p>
		<a href="#" id="wdgpt_update_database">
		<?php
		esc_html_e(
			'Update database',
			'webdigit-chatbot'
		);
		?>
		</a>
		</p>
		<p id="wdgpt_update_database_message"></p>
		<?php
	}
}

?>
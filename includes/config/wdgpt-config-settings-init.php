<?php
/**
 * Main settings initialization file.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

add_action( 'admin_init', 'wdgpt_settings_initialization' );

/**
 * Initialize the settings.
 */
function wdgpt_settings_initialization() {

	/**
	 * General settings tab
	 */

	register_setting( 'wdgpt_settings', 'wd_openai_api_key' );
	register_setting( 'wdgpt_settings', 'wdgpt_name' );
	register_setting( 'wdgpt_settings', 'wdgpt_image_name' );
	register_setting( 'wdgpt_settings', 'wd_openai_temperature' );
	register_setting( 'wdgpt_settings', 'wd_openai_max_contexts' );
	register_setting( 'wdgpt_settings', 'wd_openai_similarity_threshold' );
	register_setting( 'wdgpt_settings', 'wd_openai_precision_threshold' );
	register_setting( 'wdgpt_settings', 'wdgpt_model' );
	register_setting( 'wdgpt_settings', 'wdgpt_enable_chatbot' );
	register_setting( 'wdgpt_settings', 'wdgpt_chat_bubble_typing_text_' . get_locale() );
	register_setting( 'wdgpt_settings', 'wdgpt_greetings_message_' . get_locale() );
	register_setting( 'wdgpt_settings', 'wdgpt_enable_chatbot_bubble' );

	add_settings_section(
		'wdgpt_settings_section',
		'',
		'wdgpt_settings_section_callback',
		'wdgpt_settings'
	);

	/**
	 * Reporting tab
	 */

	/**
	 * Settings will be :
	 * Activation of reporting
	 * Mail from which reports are sent
	 * List of mails to send reports to
	 * Schedule of sending reports
	 */

	register_setting( 'wdgpt_reporting', 'wdgpt_reporting_activation' );
	register_setting( 'wdgpt_reporting', 'wdgpt_reporting_mail_from' );
	register_setting( 'wdgpt_reporting', 'wdgpt_reporting_mails' );
	register_setting( 'wdgpt_reporting', 'wdgpt_reporting_schedule' );

	add_settings_section(
		'wdgpt_reporting_section',
		'',
		'wdgpt_reporting_section_callback',
		'wdgpt_reporting'
	);

	/**
	 * Database update tab
	 */

	add_settings_section(
		'wdgpt_database_update_section',
		'',
		'wdgpt_database_update_section_callback',
		'wdgpt_database_update'
	);
}

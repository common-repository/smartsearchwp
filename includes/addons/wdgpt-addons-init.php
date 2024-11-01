<?php
/**
 * Main settings initialization file.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

add_action( 'admin_init', 'wdgpt_addons_initialization' );

/**
 * Initialize the settings.
 */
function wdgpt_addons_initialization() {

	/**
	 * License settings.
	 */

	register_setting( 'wdgpt_license', 'wdgpt_license' );

	add_settings_section(
		'wdgpt_license_section',
		'',
		'wdgpt_license_section_callback',
		'wdgpt_license'
	);

	/**
	 * Addons manager.
	 */

		register_setting( 'wdgpt_addons_manager', 'wdgpt_addons_manager' );

		add_settings_section(
			'wdgpt_addons_manager_section',
			'',
			'wdgpt_addons_manager_section_callback',
			'wdgpt_addons_manager'
		);
}

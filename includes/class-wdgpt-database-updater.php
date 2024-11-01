<?php
/**
 * This file contains the database updater class.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The database updater class.
 */
class WDGPT_Database_Updater {
	/**
	 * The database version.
	 *
	 * @var string
	 */
	private $db_version;

	/**
	 * The WordPress database instance.
	 *
	 * @var $wpdb
	 */
	private $wpdb;
	/**
	 * Constructor.
	 *
	 * @param string $current_version The current version of the plugin.
	 */
	public function __construct( $current_version ) {
		$this->db_version = $current_version;
	}

	/**
	 * Get the updates.
	 */
	public function get_updates() {
		/**
		 * This is the list of versions that need a database upgrade.
		 * The key is the version number and the value is the function to call.
		 * The level is the severity of the update. 1 is the highest, 3 is the lowest.
		 * If the level is 1, the plugin will be disabled until the update is done.
		 * If there is an "ignore_if_previous" key, the update will be ignored if the previous version is the same as the value of this key.
		 */
		$updates = array(
			'1.1.8.2' =>
			array(
				'function'      => 'wdgpt_update_1_1_8_2',
				'update_notice' => __( 'This update will empty the chat logs table, because of a change in the table structure.', 'webdigit-chatbot' ),
				'level'         => 1,
				'version'       => '1.1.8.2',
			),
		);
		return $updates;
	}

	/**
	 * Retrieve the update level for a specific version.
	 *
	 * @param string $version The version number.
	 */
	public function get_update_level( $version ) {
		$updates = $this->get_updates();
		return $updates[ $version ]['level'];
	}

	/**
	 * Check if the plugin should be disabled.
	 */
	public function should_disable_plugin() {
		$pending_updates = $this->check_for_updates();
		return ! empty( $pending_updates );
	}


	/**
	 * Check for updates.
	 */
	public function check_for_updates() {
		/**
		 * Retrieve the previous version of the plugin, stocked in the database.
		 */
		$previous_version = get_option( 'wdgpt_chatbot_version', '1.0.0' );

		/**
		 * If the previous version is different from the current version, we need to update the database.
		 */
		$pending_updates = array();
		if ( version_compare( $previous_version, $this->db_version, '<' ) ) {
			$updates = $this->get_updates();
			foreach ( $updates as $version => $update ) {
				if ( version_compare( $previous_version, $version, '<' ) ) {
					// Check if the update should be ignored for the previous version.
					if ( isset( $update['ignore_if_previous'] ) && $update['ignore_if_previous'] === $previous_version ) {
						continue;
					}
					$pending_updates[] = $update;
				}
			}
		}
		return $pending_updates;
	}

	/**
	 * Update the database.
	 */
	public function update_database() {
		$pending_updates = $this->check_for_updates();
		foreach ( $pending_updates as $update ) {
			$function = $update['function'];
			$function();
		}
	}
}

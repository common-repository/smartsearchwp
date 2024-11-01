<?php
/**
 * This file is responsible for mananing the error logs.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to log errors.
 */
class WDGPT_Error_Logs {
	/**
	 * The error logs instance.
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * The wpdb instance.
	 *
	 * @var $wpdb
	 */
	private $wpdb;

	/**
	 * The table name.
	 *
	 * @var $table_name
	 */
	private $table_name;

	/**
	 * Initialize class.
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'wd_error_logs';
	}

	/**
	 * Get the error logs instance.
	 *
	 * @return WDGPT_Error_Logs
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WDGPT_Error_Logs();
		}
		return self::$instance;
	}

	/**
	 * Create the error logs table.
	 *
	 * @return void
	 */
	public function create_table() {
		$charset_collate = $this->wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            error_type text NOT NULL,
            error_code text NOT NULL,
            error text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Delete all error logs.
	 *
	 * @return void
	 */
	public function purge_logs() {
		$sql = "DELETE FROM $this->table_name";
		$this->wpdb->query( $this->wpdb->prepare( $sql ) );
	}
	/**
	 * Get the logs.
	 *
	 * @param int $days The days.
	 * @return void
	 */
	public function purge_logs_older_than( $days ) {
		$sql = $this->wpdb->prepare(
			"DELETE FROM $this->table_name WHERE created_at > DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		);
		$this->wpdb->query( $sql );
	}
}

<?php
/**
 * This file is responsible to manage messages logs.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to log messages.
 */
class WDGPT_Logs {
	/**
	 * The chat logs instance.
	 *
	 * @var $instance
	 */
	private static $instance = null;

	/**
	 * The WordPress database instance.
	 *
	 * @var $wpdb
	 */
	private $wpdb;

	/**
	 * The name of the table.
	 *
	 * @var $table_name
	 */
	private $table_name;

	/**
	 * The name of the messages table.
	 *
	 * @var $table_name_messages
	 */
	private $table_name_messages;

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb                = $wpdb;
		$this->table_name          = $wpdb->prefix . 'wdgpt_logs';
		$this->table_name_messages = $wpdb->prefix . 'wdgpt_logs_messages';
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return WDGPT_Logs
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WDGPT_Logs();
		}
		return self::$instance;
	}

	/**
	 * Create the table.
	 *
	 * @return void
	 */
	public function create_table() {
		$charset_collate = $this->wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
			unique_id varchar(255) NOT NULL,
            post_ids text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$sql = "CREATE TABLE $this->table_name_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            log_id mediumint(9) NOT NULL,
            prompt text NOT NULL,
            source mediumint(9) NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (log_id) REFERENCES $this->table_name(id) ON DELETE CASCADE
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert log.
	 *
	 * @param array $messages The messages.
	 * @param array $post_id The post id.
	 * @return void
	 */
	public function insert_log( $messages, $post_id ) {
		$now     = current_time( 'mysql' );
		$post_id = implode( ',', $post_id );
		$this->wpdb->insert(
			$this->table_name,
			array(
				'post_ids'   => $post_id,
				'created_at' => $now,
			)
		);
		$log_id = $this->wpdb->insert_id;
		foreach ( $messages as $message ) {
			$this->insert_log_message( $message, $log_id );
		}
	}

	/**
	 * Insert log message.
	 *
	 * @param array $message The message.
	 * @param array $log_id The log id.
	 * @return void
	 */
	private function insert_log_message( $message, $log_id ) {
		switch ( $message['role'] ) {
			case 'user':
				$source = 0;
				break;
			case 'assistant':
				$source = 1;
				break;
		}
		if ( '' !== $message['content'] ) {
			$this->wpdb->insert(
				$this->table_name_messages,
				array(
					'log_id' => $log_id,
					'prompt' => $message['content'],
					'source' => $source,
				)
			);
		}
	}

	/**
	 * Get the last message.
	 *
	 * @param int $log_id The log id.
	 * @param int $source The source.
	 * @return object
	 */
	public function get_last_message( $log_id, $source ) {
		$sql = "SELECT * FROM $this->table_name_messages WHERE log_id = $log_id AND source = $source ORDER BY id DESC LIMIT 1";
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( '%1s', $sql ) );
		return $result[0];
	}

	/**
	 * Get the messages.
	 *
	 * @param int $log_id The log id.
	 * @return array
	 */
	public function get_messages( $log_id ) {
		$sql = "SELECT * FROM $this->table_name_messages WHERE log_id = $log_id ORDER BY id ASC";
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( '%1s', $sql ) );
		return $result;
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

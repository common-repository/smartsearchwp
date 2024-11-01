<?php
/**
 * This file contains all the different update functions for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Update the plugin database to version 1.1.8.2
 */
function wdgpt_update_1_1_8_2() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wdgpt_logs';

	// Check if the column 'unique_id' already exists in the table 'wdgpt_logs'.
	$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM `{$table_name}` LIKE 'unique_id'" );

	// If the column exists, return early.
	if ( ! empty( $column_exists ) ) {
		update_option( 'wdgpt_chatbot_version', '1.1.8.2' );
		return;
	}

	// If the column does not exist, proceed with the update.
	$table_name_messages = $wpdb->prefix . 'wdgpt_logs_messages';
	$wpdb->query( 'TRUNCATE TABLE ' . $table_name_messages );
	$wpdb->query( 'DELETE FROM ' . $table_name );
	$wpdb->query( 'ALTER TABLE ' . $table_name . ' ADD COLUMN unique_id varchar(255) NOT NULL;' );

	// Update the version number in the database.
	update_option( 'wdgpt_chatbot_version', '1.1.8.2' );
}

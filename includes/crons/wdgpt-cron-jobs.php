<?php
/**
 * This file contains all the different crons of the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Dompdf\Dompdf;

add_action( 'wdgpt_reporting_cron_hook', 'wdgpt_reporting_cron' );
/**
 * Schedule the reporting cron.
 */
function wdgpt_reporting_cron() {

	global $wpdb;

	$schedule = get_option( 'wdgpt_reporting_schedule', 'daily' );

	$interval = 'weekly' === $schedule ? '1 WEEK' : '1 DAY';

	$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wdgpt_logs WHERE created_at >= NOW() - INTERVAL $interval" );
	// If there are no logs, we don't send the report.
	if ( empty( $logs ) ) {
		return;
	}
	$dompdf = new Dompdf();
	ob_start();
	$conversation = '
        <style>
        .conversation {
            display: flex;
            flex-direction: column;
            margin: 20px 0;
            border: 1px solid #000; 
            font-size: 12px;
            page-break-before: always;
            padding: 10px;
            border-radius: 5px;
        }

        .conversation.first {
            page-break-before: avoid;
        }

        .message {
            display: flex;
            flex-direction: row;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            background-color: #f1f1f1;
        }

        .message.user {
            margin-left: auto !important;
            background-color: #d1e7dd;
        }

        .message-prompt {
            background-color: #eee;
            border-radius: 5px;
            padding: 10px;
            margin-right: 10px;
        }

        .message-source {
            font-weight: bold;
            margin-right: 10px;
        }

        .message-source::after {
            content: ":";
        }

        .user-message .message-prompt {
            background-color: #0078d7;
            color: #fff;
        }

        .user-message .message-source {
            color: #0078d7;
        }

        .bot-message .message-prompt {
            background-color: #eee;
            color: #000;
        }

        .bot-message .message-source {
            color: #000;
        }
        </style>';

	$conversation .= '<h1>' . esc_html( __( 'SmartSearchWP Chat Logs Report - ', 'webdigit-chatbot' ) ) . date( 'Y-m-d' ) . '</h1>';
	$first         = true;
	/**
	 * Library to parse markdown to html (ex: **bold** to <strong>bold</strong>).
	 */
	$parsedown = new Parsedown();
	foreach ( $logs as $log ) {
		$messages = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wdgpt_logs_messages WHERE log_id = {$log->id}" );

		$conversation .= '<div class="conversation' . ( $first ? ' first' : '' ) . '">';
		foreach ( $messages as $message ) {
			$role          = '0' === $message->source ? 'user' : 'assistant';
			$conversation .= '<div class="message ' . $role . '">';
			$conversation .= '<div class="message-source">';
			$conversation .= '0' === $message->source ? esc_html( __( 'User', 'webdigit-chatbot' ) ) : esc_html( __( 'Bot', 'webdigit-chatbot' ) );
			$conversation .= '</div>';
			$conversation .= '<div class="message-prompt">';
			$conversation .= $parsedown->text( $message->prompt );
			$conversation .= '</div>';
			$conversation .= '</div>';
		}
		$conversation .= '</div>';
		$first         = false;

	}

	$dompdf->loadHtml( $conversation );
	$dompdf->setPaper( 'A4', 'portrait' );
	$dompdf->render();

	$folder_path = WP_CONTENT_DIR . '/uploads/smartsearchwp_reports/';
	if ( ! is_dir( $folder_path ) ) {
		mkdir( $folder_path, 0755, true );
	} else {
		$files = glob( $folder_path . 'smartsearchwp_report_*.pdf' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}

	$current_date     = date( 'Y-m-d' );
	$dompdf_file_name = 'smartsearchwp_report_' . $current_date . '.pdf';

	file_put_contents( $folder_path . $dompdf_file_name, $dompdf->output() );
	$dompdf_file_url = WP_CONTENT_URL . '/uploads/smartsearchwp_reports/' . $dompdf_file_name;

	// Retrieve the targeted mails for the export.
	$targeted_mails = get_option( 'wdgpt_reporting_mails', '' );
	$targeted_mails = explode( ',', $targeted_mails );
	$targeted_mails = array_map( 'trim', $targeted_mails );

	$blog_name = get_bloginfo( 'name' );

	$subject = '[' . $blog_name . '] ' . esc_html( __( 'SmartsearchWP chat logs report', 'webdigit-chatbot' ) ) . ' - ' . date( 'Y-m-d' );

	$message = esc_html( __( 'Please find attached the report of the chat logs from SmartsearchWP.', 'webdigit-chatbot' ) );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);
	add_filter(
		'wp_mail_from',
		function () {
			return get_option( 'wdgpt_reporting_mail_from', get_option( 'admin_email' ) );
		}
	);
	add_filter(
		'wp_mail_from_name',
		function () {
			return get_bloginfo( 'name' );
		}
	);
	foreach ( $targeted_mails as $mail ) {
		$result = wp_mail( $mail, $subject, $message, $headers, $folder_path . $dompdf_file_name );
	}

	remove_filter(
		'wp_mail_from',
		function () {
			return get_option( 'admin_email' );
		}
	);
	remove_filter(
		'wp_mail_from_name',
		function () {
			return get_bloginfo( 'name' );
		}
	);
}

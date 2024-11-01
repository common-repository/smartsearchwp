<?php
/**
 * This file contains all the API requests for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Orhanerday\OpenAi\OpenAi;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'toggle-summary',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_toggle_summary',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'purge-error-logs',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_purge_error_logs',
				'permission_callback' => 'wdgpt_is_authorised_to_do',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'purge-chat-logs',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_purge_chat_logs',
				'permission_callback' => 'wdgpt_is_authorised_to_do',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'retrieve-content/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'wdgpt_retrieve_content',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'save-embeddings',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_save_embeddings',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'temperature',
			array(
				'methods'             => 'GET',
				'callback'            => 'wdgpt_retrieve_temperature',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'retrieve-prompt',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_retrieve_prompt',
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wdgpt/v1',
			'update-database',
			array(
				'methods'             => 'POST',
				'callback'            => 'wdgpt_update_database',
				'permission_callback' => '__return_true',
			)
		);
	}
);

function wdgpt_is_authorised_to_do () {
	$user = wp_get_current_user();
	$allowed_roles = array( 'administrator' );
	return array_intersect( $allowed_roles, $user->roles );
}

/**
 * Updates the database.
 *
 * @return array The result of the operation.
 */
function wdgpt_update_database() {
	try {
		$database_updater = new WDGPT_Database_Updater( wdgpt_chatbot()->get_version() );
		$database_updater->update_database();
		return array(
			'success' => true,
			'message' => __( 'Database updated successfully.', 'webdigit-chatbot' ),
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}

/**
 * Retrieves the prompt from the server.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return string|WP_Error The prompt or an error object.
 */
function wdgpt_retrieve_prompt( $request ) {

	try {
		$params              = json_decode( $request->get_body(), true );
		$question            = sanitize_text_field($params['question']);
		$conversation = array_map(function($conv) {
			return [
				'text' => sanitize_text_field($conv['text']),
				'role' => sanitize_text_field($conv['role']),
				'date' => sanitize_text_field($conv['date'])
			];
		}, $params['conversation']);
		$unique_conversation = sanitize_text_field($params['unique_conversation']);
		$answer_generator    = new WDGPT_Answer_Generator( $question, $conversation );
		$answer_parameters   = $answer_generator->wdgpt_retrieve_answer_parameters();

		header( 'Content-type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		// Check if $answer_parameters is an empty array.
		if ( empty( $answer_parameters ) ) {
			echo 'event: error' . PHP_EOL;
			echo 'data: ' . __( 'Currently, there appears to be an issue. Please try asking me again later.', 'webdigit-chatbot' ) . PHP_EOL;
			ob_flush();
			flush();
			return '0';
		}
		$api_key                = $answer_parameters['api_key'];
		$temperature            = $answer_parameters['temperature'];
		$messages               = json_decode( json_encode( $answer_parameters['messages'], JSON_INVALID_UTF8_SUBSTITUTE ) );
		$max_tokens             = $answer_parameters['max_tokens'];
		$model_type             = $answer_parameters['model_type'];
		$top_summaries_post_ids = $answer_parameters['top_summaries_post_ids'];
		$openai                 = new OpenAi( $api_key );

		$answer = '';

		$chat = json_decode(
			$openai->chat(
				array(
					'model'       => $model_type,
					'messages'    => $messages,
					'temperature' => floatval( $temperature ),
					'max_tokens'  => $max_tokens,
					'stream'      => true,
				),
				function ( $ch, $data ) use ( &$answer, $answer_generator ) {
					$obj = json_decode( $data );
					// Vérifiez si $obj est un objet et s'il a la propriété 'error' et si la propriété 'message' n'est pas vide.
					if ( is_object( $obj ) && property_exists( $obj, 'error' ) && ! empty( $obj->error->message ) ) {
						$answer_generator->wdgpt_insert_error_log_message( $obj->error->message, 0, 'stream_error' );
					} else {
						echo $data;
						$result = explode( 'data: ', $data );
						foreach ( $result as $res ) {
							if ( '[DONE]' !== $res ) {
								$arr = json_decode( $res, true );
								if ( isset( $arr['choices'][0]['delta']['content'] ) ) {
									$answer .= $arr['choices'][0]['delta']['content'];
								}
							}
						}

						// echo PHP_EOL;
						ob_flush();
						flush();
						return strlen( $data );
					}
				}
			)
		);

		$pattern     = '/\[(.*?)\]\((.*?)\)/';
		$replacement = '<a href="$2" target="_blank">$1</a>';

		$transformed_string = preg_replace( $pattern, $replacement, $answer );

		$transformed_string = str_replace( "\n", '<br>', $transformed_string );

		$answer_generator->wdgpt_log_chat( $transformed_string, $top_summaries_post_ids, $unique_conversation );
		return '0';
	} catch ( Exception $e ) {
		return __( 'There is currently an error with the chatbot. Please try again later.', 'webdigit' );
	}
}

/**
 * Retrieves the similarity threshold from the server.
 *
 * @return float|WP_Error The similarity threshold or an error object.
 */
function wdgpt_retrieve_similarity_threshold() {
	try {
		// Get the similarity threshold from the WordPress options.
		$similarity_threshold = get_option( 'wd_openai_similarity_threshold', 0.05 );
		// Return the similarity threshold.
		return $similarity_threshold;
	} catch ( Exception $e ) {
		// Return an error object with the error message and status code.
		return new WP_Error( 'similarity_threshold_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Retrieves the temperature from the server.
 *
 * @return float|WP_Error The temperature or an error object.
 */
function wdgpt_retrieve_temperature() {
	try {
		// Get the temperature from the WordPress options.
		$temperature = get_option( 'wd_openai_temperature', 0.5 );
		// Return the temperature.
		return $temperature;
	} catch ( Exception $e ) {
		// Return an error object with the error message and status code.
		return new WP_Error( 'temperature_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * Retrieves the OpenAI API key from the server.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return string|WP_Error The encrypted API key or an error object.
 * @throws Exception If the API key is not found.
 */
function wdgpt_retrieve_api_key( $request ) {
	try {
		// Get the secret code from the request parameters.
		$params = $request->get_params();
		$key    = $params['key'];
		// Check if the secret code is valid.
		if ( 'U2FsdGVkX1+X' !== $key ) {
			throw new Exception( 'Invalid secret code' );
		}
		// Get the API key from the WordPress options.
		$api_key = get_option( 'wd_openai_api_key' );
		// Check if the API key is found.
		if ( ! $api_key ) {
			throw new Exception( 'API key not found' );
		}
		// Encrypt the API key using the ROT13 cipher.
		$api_key = str_rot13( $api_key );
		// Return the encrypted API key.
		return $api_key;
	} catch ( Exception $e ) {
		// Return an error object with the error message and status code.
		return new WP_Error( 'api_key_error', $e->getMessage(), array( 'status' => 404 ) );
	}
}


/**
 * Saves the embeddings to the server.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return array|WP_Error The result of the operation or an error object.
 */
function wdgpt_save_embeddings( $request ) {

	$params = $request->get_params();
	$post_id = $params['post_id'];

	$answer_generator = new WDGPT_Answer_Generator( '', '' );

	$post_content = get_post( $post_id )->post_content;

    if (class_exists('WDGPT_Pdf') && 'attachment' === get_post_type($post_id)) {
        $pdf_parser = new WDGPT_Pdf();
        $pdf_content = $pdf_parser->get_pdf_content($post_id);
        $post_content = $post_content . ' ' . $pdf_content->content;
    }

	// Retrieve the post type of the post.
	$post_type = get_post_type( $post_id );

	$acf_field_text = '';
	if ( function_exists( 'acf_get_field_groups' ) ) {
		$field_groups = acf_get_field_groups( array(
			'post_type' => $post_type,
		) );

		if ( ! empty ( $field_groups ) ) {
			$acf_fields = get_option( 'wdgpt_custom_type_manager_acf_fields_' . $post_type, '');


			if ( ! empty( $acf_fields) ) {
				$acf_fields = explode( ',', $acf_fields );
				// Remove the possible empty values from the array.
				$acf_fields = array_filter( $acf_fields );
				$acf_fields_array = [];
				// Retrieve the values of the ACF fields.
				foreach ( $acf_fields as $acf_field ) {
					$acf_fields_array[] = $acf_field.':'.get_field( $acf_field, $post_id );
				}
				$acf_field_text = implode( ', ', $acf_fields_array );
			}
		}
	}
	$text = $post_content . ' ' . $acf_field_text;
    $count_tokens = $answer_generator->wdgpt_count_embedings_token($text);
	$splited_content = [];

    if($count_tokens > 8000){
        $split_context = ceil($count_tokens/8000);
		$split_size = strlen( $text ) / $split_context + 10;
		$splited_content = str_split( $text, $split_size );
    }

	if (count($splited_content)) {
		$embeding_splitted = [];
		foreach ( $splited_content as $text ) {
			$generated_embedings = $answer_generator->wdgpt_retrieve_topic_embedding($text);
			if (count($generated_embedings)) {
				$embeding_splitted[] = $generated_embedings;
			}
		}
		$embeddings_array = call_user_func_array('array_merge', $embeding_splitted);
	} else {
		$embeddings_array = $answer_generator->wdgpt_retrieve_topic_embedding($text);
	}

	$result = update_post_meta( $post_id, 'wdgpt_embeddings', $embeddings_array );

	$date = current_time( 'mysql' );

	update_post_meta( $post_id, 'wdgpt_embeddings_last_generation', $date );

	return array(
		'success' => true,
		'date'    => $date,
		'message' => 'Embeddings saved successfully',
	);
}

/**
 * Retrieves the content of a post.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return string|WP_Error The content of the post or an error object.
 */
function wdgpt_retrieve_content( $request ) {
	try {
		$params  = $request->get_params();
		$post_id = $params['post_id'];
		$post    = get_post( $post_id );
		if ( $post ) {
			$content = $post->post_content;
			return rest_ensure_response( $content );
		} else {
			return new WP_Error( 'post_not_found', 'Post not found', array( 'status' => 404 ) );
		}
	} catch ( Exception $e ) {
		return new WP_Error( 'server_error', 'Server error', array( 'status' => 500 ) );
	}
}

/**
 * Purges the chat logs.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return array The result of the operation.
 */
function wdgpt_purge_chat_logs( $request ) {
	global $wpdb;
	try {
		$params = $request->get_params();
		$months = isset( $params['months'] ) ? intval( $params['months'] ) : 0;
		if ( 0 !== $months ) {
			$chat_logs = WDGPT_Logs::get_instance();
			if ( -1 === $months ) {
				$chat_logs->purge_logs();
				return array(
					'success' => true,
					'message' => 'All chat logs purged successfully',
				);
			} else {
				$days = $months * 30;
				$chat_logs->purge_logs_older_than( $days );
				return array(
					'success' => true,
					'message' => 'Chat logs older than ' . $months . ' months purged successfully',
				);
			}
		}
		return array(
			'success' => false,
			'message' => 'Months cannot be 0',
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}

/**
 * Purges the error logs.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return array The result of the operation.
 */
function wdgpt_purge_error_logs( $request ) {
	global $wpdb;
	try {
		$params = $request->get_params();
		$months = isset( $params['months'] ) ? intval( $params['months'] ) : 0;
		if ( 0 !== $months ) {
			$error_logs = WDGPT_Error_Logs::get_instance();
			if ( -1 === $months ) {
				$error_logs->purge_logs();
				return array(
					'success' => true,
					'message' => 'All error logs purged successfully',
				);
			} else {
				$days = $months * 30;
				$error_logs->purge_logs_older_than( $days );
				return array(
					'success' => true,
					'message' => 'Error logs older than ' . $months . ' months purged successfully',
				);
			}
		}
		return array(
			'success' => false,
			'message' => 'Months cannot be 0',
		);
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}

/**
 * Toggles the summary.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return array The result of the operation.
 */
function wdgpt_toggle_summary( $request ) {
	$params = $request->get_params();
	$id     = $params['id'];
	$action = $params['action'];
	$post   = get_post( $id );
	// Retrieve the last_modified date of the post.
	$last_modified = $post->post_modified;

	// Retrieve the post_meta "wdgpt_embeddings_last_generation" of the post.
	$embeddings_last_generation = get_post_meta( $id, 'wdgpt_embeddings_last_generation', true ) ? get_post_meta( $id, 'wdgpt_embeddings_last_generation', true ) : '';

	$embeddings_before_modified = false;

	if ( strtotime( $embeddings_last_generation ) < strtotime( $last_modified ) ) {
		$embeddings_before_modified = true;
	}

	// Verify if the post has been modified since the last generation of embeddings.

	try {
		if ( 'activate' === $action ) {
			$result = update_post_meta( $id, 'wdgpt_is_active', 'true' );
			return array(
				'success' => true,
				'color'   => $embeddings_before_modified ? 'yellow' : 'green',
				'message' => 'Summary activated successfully',
			);
		} elseif ( 'deactivate' === $action ) {
			$result = update_post_meta( $id, 'wdgpt_is_active', 'false' );
			return array(
				'success' => true,
				'color'   => $embeddings_before_modified ? 'yellow' : 'green',
				'message' => 'Summary deactivated successfully',
			);
		} else {
			return array(
				'success' => false,
				'color'   => $embeddings_before_modified ? 'yellow' : 'green',
				'message' => 'Invalid action',
			);
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}

add_action( 'wp_ajax_wdgpt_get_free_license', 'wdgpt_get_free_license' );
/**
 * Retrieves a free license.
 *
 * This function is called when the 'wdgpt_get_free_license' action is triggered via AJAX.
 * It retrieves a free license key from the server and sends the license data as a JSON response.
 *
 * @return void
 */
function wdgpt_get_free_license() {
	check_ajax_referer( 'wdgpt_free_license_nonce', 'security' );
	if ( ! isset( $_POST['email'] ) || ! isset( $_POST['receive_updates'] ) ) {
		wp_send_json_error( 'No email or receive_updates provided' );
	}
	$email           = sanitize_email( wp_unslash( $_POST['email'] ) );
	$receive_updates = sanitize_text_field( wp_unslash( $_POST['receive_updates'] ) );
	$license_data    = get_free_license( $email, $receive_updates );
	wp_send_json_success( $license_data );
}

/**
 * Retrieves a free license.
 *
 * @param string $email The email address.
 * @param string $receive_updates Whether to receive updates.
 * @return string The license key.
 */
function get_free_license( $email, $receive_updates ) {
	$url         = 'https://www.smartsearchwp.com/wp-json/smw/free-license/';
	$url_website = site_url();
	$url_website = str_replace( 'http://', '', $url_website );
	$url_website = str_replace( 'https://', '', $url_website );
	$body        = array(
		'email'           => $email,
		'url_website'     => $url_website,
		'receive_updates' => 'true' === $receive_updates ? 1 : 0,
	);

	$args     = array(
		'body'        => wp_json_encode( $body ),
		'timeout'     => '5',
		'redirection' => '5',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => array(
			'Content-Type' => 'application/json',
		),
		'cookies'     => array(),
	);
	$response = wp_remote_post( $url, $args );
	$body     = wp_remote_retrieve_body( $response );
	$data     = json_decode( $body );

	if ( 0 === strpos( $data, 'free_' ) && $data !== get_option( 'wd_smartsearch_free_license', '' ) ) {
		update_option( 'wd_smartsearch_free_license', $data );
	}

	update_option( 'wd_smartsearch_license_email', $email );
	update_option( 'wd_smartsearch_free_license_receive_updates', 'true' === $receive_updates ? 'on' : '' );
	return $data;
}

add_action( 'wp_ajax_wdgpt_verify_license', 'wdgpt_verify_license_ajax_handler' );
/**
 * Ajax handler for verifying the license.
 *
 * This function is called when the 'wdgpt_verify_license' action is triggered via AJAX.
 * It checks the validity of the license key provided and sends the license data as a JSON response.
 *
 * @return void
 */
function wdgpt_verify_license_ajax_handler() {
	check_ajax_referer( 'wdgpt_verify_license_nonce', 'security' );
	if ( ! isset( $_POST['license_key'] ) ) {
		wp_send_json_error( 'No license key provided' );
	}
	$license_key  = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
	$license_data = WDGPT_License_Manager::instance()->renew_license( $license_key );
	wp_send_json_success( $license_data );
}

add_action( 'wp_ajax_wdgpt_install_addon', 'wdgpt_install_addon' );
/**
 * Installs an addon.
 *
 * @return void
 */
function wdgpt_install_addon() {
	check_ajax_referer( 'wdgpt_install_addon_nonce', 'security' );
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( 'No ID provided' );
	}
	$addons_manager = WDGPT_Addons_Manager::instance();
	$id             = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	$addon          = $addons_manager->retrieve_addon( $id );

	$result = $addons_manager->install_addon( $addon );
	if ( $result ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}

add_action( 'wp_ajax_wdgpt_update_addon', 'wdgpt_update_addon' );
/**
 * Updates an addon.
 *
 * @return void
 */
function wdgpt_update_addon() {
	check_ajax_referer( 'wdgpt_update_addon_nonce', 'security' );
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( 'No ID provided' );
	}
	$addons_manager = WDGPT_Addons_Manager::instance();
	$id             = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	$addon          = $addons_manager->retrieve_addon( $id );
	$result         = $addons_manager->install_addon( $addon, 'update' );
	if ( $result ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}

add_action( 'wp_ajax_wdgpt_uninstall_addon', 'wdgpt_uninstall_addon' );
/**
 * Uninstalls an addon.
 *
 * @return void
 */
function wdgpt_uninstall_addon() {
	check_ajax_referer( 'wdgpt_uninstall_addon_nonce', 'security' );
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( 'No ID provided' );
	}
	$addons_manager = WDGPT_Addons_Manager::instance();
	$id             = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	$addon          = $addons_manager->retrieve_addon( $id );
	$result         = $addons_manager->uninstall_addon( $addon );
	if ( $result ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}

add_action( 'wp_ajax_wdgpt_activate_addon', 'wdgpt_activate_addon' );
/**
 * Activates an addon.
 *
 * @return void
 */
function wdgpt_activate_addon() {
	check_ajax_referer( 'wdgpt_activate_addon_nonce', 'security' );
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( 'No ID provided' );
	}
	$addons_manager = WDGPT_Addons_Manager::instance();
	$id             = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	$addon          = $addons_manager->retrieve_addon( $id );
	$result         = $addons_manager->activate_addon( $addon );
	if ( $result ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}

add_action( 'wp_ajax_wdgpt_deactivate_addon', 'wdgpt_deactivate_addon' );
/**
 * Deactivates an addon.
 *
 * @return void
 */
function wdgpt_deactivate_addon() {
	check_ajax_referer( 'wdgpt_deactivate_addon_nonce', 'security' );
	if ( ! isset( $_POST['id'] ) ) {
		wp_send_json_error( 'No ID provided' );
	}
	$addons_manager = WDGPT_Addons_Manager::instance();
	$id             = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	$addon          = $addons_manager->retrieve_addon( $id );
	$result         = $addons_manager->deactivate_addon( $addon );
	if ( $result ) {
		wp_send_json_success( $result );
	}
	wp_send_json_error( $result );
}

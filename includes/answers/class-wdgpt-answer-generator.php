<?php
/**
 * This file is responsible to generate answers to user questions.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Orhanerday\OpenAi\OpenAi;
use CxRxExO\Tiktoken\EncoderProvider;

/**
 * Class to generate answers to user questions.
 */
class WDGPT_Answer_Generator {

	/**
	 * The API key to use to connect to the server.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * The question to answer.
	 *
	 * @var string
	 */
	private $question;

	/**
	 * The conversation containing the previous messages.
	 *
	 * @var array
	 */
	private $conversation;

	/**
	 * The maximum number of tokens allowed.
	 *
	 * @var int
	 */
	private $total_max_tokens;

	/**
	 * The number of tokens that are safe to use, by adding them to the current context.
	 *
	 * @var int
	 */
	private $token_safe_number;

	/**
	 * Constructor.
	 *
	 * @param string $question The question to answer.
	 * @param array  $conversation The conversation containing the previous messages.
	 */
	public function __construct( $question, $conversation ) {
		$this->api_key      = get_option( 'wd_openai_api_key', '' );
		$this->client       = new OpenAi( get_option( 'wd_openai_api_key', '' ) );
		$this->question     = $question;
		$this->conversation = $conversation;
		$model              = get_option( 'wdgpt_model', 'gpt-3.5-turbo' );
		// If the model is gpt-4, change it to gpt-4o. This is a failsafe in case the user did not update the model.
		if ( 'gpt-4' === $model ) {
			$model = 'gpt-4o';
		}
		/**
		 * Note : token_safe_number cannot be higher than 4000, otherwise it will prevent answers from being generated.
		 * This is an OpenAI limitation, and it is not related to the plugin.
		 */
		if ( 'gpt-4o' === $model ) {
			$this->total_max_tokens  = 120000;
			$this->token_safe_number = 3000;
		} else {
			$this->total_max_tokens  = 16000;
			$this->token_safe_number = 1000;
		}
	}

	/**
	 * Retrieves the answer from the server.
	 *
	 * @return string|WP_Error The answer or an error object.
	 */
	public function wdgpt_retrieve_answer_parameters() {
		if ( empty( $this->api_key ) ) {
			$this->wdgpt_insert_error_log_message( 'The API key is not set.', 500, 'retrieve_prompt' );
			return array();
		}
		$parameters = $this->wdgpt_retrieve_parameters();

		try {
			$embeddings = $this->wdgpt_retrieve_post_embeddings();
			if ( class_exists( 'WDGPT_WooCommerce_OpenAI') ) {
				$openai_embeddings = WDGPT_WooCommerce_OpenAI::instance()->wdgpt_woocommerce_retrieve_post_embeddings();
				$embeddings = array_merge($embeddings, $openai_embeddings);
			}
		} catch ( Exception $e ) {
			$this->wdgpt_insert_error_log_message( $e->getMessage(), $e->getCode(), 'retrieve_post_embeddings' );
			return array();
		}
		try {
			$topic_embedding = $this->wdgpt_retrieve_topic_embedding( $this->question );
			if ( $topic_embedding === '__INSUFFICIENT_QUOTA__' ) {
				return array();
			}
			if ( $topic_embedding === '__CONTEXT_TOO_LONG__' ) {
				return array();
			}
		} catch ( Exception $e ) {
			$this->wdgpt_insert_error_log_message( $e->getMessage(), $e->getCode(), 'retrieve_topic_embedding' );
			return array();
		}
		try {
			$similarities = $this->wdgpt_sorted_similarities( $embeddings, $topic_embedding );
		} catch ( Exception $e ) {
			$this->wdgpt_insert_error_log_message( $e->getMessage(), $e->getCode(), 'sorted_similarities' );
			return array();
		}
		// If the administrator only wants to use products from WooCommerce, remove every single summary that is not a product.
		if ( class_exists( 'WDGPT_WooCommerce_OpenAI' ) && 'on' === get_option( 'wdgpt_woocommerce_only_use_woocommerce_products', '' ) ) {
			// Check if the first item is a product.
			if ( isset( $similarities[0] ) && 'product' === get_post_type( $similarities[0]['post_id'] ) ) {
				// Remove every single summary that is not a product.
				$similarities = array_filter(
					$similarities,
					function ( $similarities ) {
						return 'product' === get_post_type( $similarities['post_id'] );
					}
				);

				$similarities = array_values( $similarities );
			}
		}

		$top_summaries = array();
		if ( $similarities[0]['similarity'] < $parameters['precision'] ) {
			$previous_question = $this->wdgpt_previous_question();
			if ( '' === $previous_question ) {
				return array();
			}
			$combined_question = $previous_question . ' ' . $this->question;
			try {
				$combined_embedding    = $this->wdgpt_retrieve_topic_embedding( $combined_question );
				$combined_similarities = $this->wdgpt_sorted_similarities( $embeddings, $combined_embedding );
			} catch ( Exception $e ) {
				$this->wdgpt_insert_error_log_message( $e->getMessage(), $e->getCode(), 'sorted_similarities' );
				return array();
			}

			if ( $combined_similarities[0]['similarity'] < $parameters['precision'] ) {
				return array();
			}
			if ( $parameters['max_contexts'] > count( $combined_similarities ) ) {
				$parameters['max_contexts'] = count( $combined_similarities );
			}

			$top_similarity = $combined_similarities[0]['similarity'];
			for ( $i = 0; $i < $parameters['max_contexts']; $i++ ) {

				if (
					$combined_similarities[ $i ]['similarity'] >= $parameters['precision'] &&
					( $top_similarity - $combined_similarities[ $i ]['similarity'] ) <= $parameters['similarity']
					) {
						$top_summaries[] = $combined_similarities[ $i ];
				}
			}
		} else {
			if ( $parameters['max_contexts'] > count( $similarities ) ) {
				$parameters['max_contexts'] = count( $similarities );
			}
			$top_similarity = $similarities[0]['similarity'];
			for ( $i = 0; $i < $parameters['max_contexts']; $i++ ) {
				if (
					$similarities[ $i ]['similarity'] >=
					$parameters['precision'] &&
					$top_similarity - $similarities[ $i ]['similarity'] <=
					$parameters['similarity']
					) {
						$top_summaries[] = $similarities[ $i ];
				}
			}
		}

		$top_summaries_content = array();
		$count                 = count( $top_summaries );

		for ( $i = 0; $i < $count; $i++ ) {
			$top_summaries_content[] = array(
				'content'   => preg_replace( '/(<([^>]+)>)/i', '', str_replace( array( "\n", "\r", "\t" ), '', $top_summaries[ $i ]['content'] ) ),
				'permalink' => $top_summaries[ $i ]['permalink'],
				'post_id'   => $top_summaries[ $i ]['post_id'],
			);
		}

		$context_and_prompts = $this->wdgpt_generate_context_prompts( $this->question, $top_summaries_content );

		$tokens = $this->wdgpt_count_tokens( $context_and_prompts );

		$model_number = '';

		if ( count( $top_summaries ) > 1 ) {
			while ( $tokens + $this->token_safe_number > $this->total_max_tokens ) {
				// Remove the last element from the array.
				array_pop( $top_summaries );
				$context_and_prompts = $this->wdgpt_generate_context_prompts( $this->question, $top_summaries );

				$tokens = $this->wdgpt_count_tokens( $context_and_prompts );
				if ( 1 === count( $top_summaries ) ) {
					break;
				}
			}
		}

		// Depending on the size of the context and the questions, there could be an infinite loop.
		// To prevent this, we set a fail_count, which will stop the loop after 15 iterations.
		$fail_count = 0;

		// If the number of tokens exceeds 16k and there is only one summary,
		// regenerate the answer by gradually removing the last 1/4th of the summary
		// until the number of tokens is less than 16k.
		if ( 1 === count( $top_summaries ) ) {
			while ( $tokens + $this->token_safe_number > $this->total_max_tokens ) {
				if ( $fail_count > 15 ) {
					return array();
				}
				$top_summaries[0]['content'] =
				substr(
					$top_summaries[0]['content'],
					0,
					strlen( $top_summaries[0]['content'] ) * 3 / 4
				);
				$context_and_prompts         = $this->wdgpt_generate_context_prompts( $this->question, $top_summaries );
				$tokens                      = $this->wdgpt_count_tokens( $context_and_prompts );
				++$fail_count;
			}
		}

		$top_summaries_post_ids = array_map(
			function ( $summary ) {
				return $summary['post_id'];
			},
			$top_summaries
		);
		// Retrieve the bot model from the options.
		$bot_model  = get_option( 'wdgpt_model', 'gpt-3.5-turbo' );
		if ( 'gpt-4' === $bot_model ) {
			$bot_model = 'gpt-4o';
		}
		$model_type = $bot_model;
		return array(
			'api_key'                => $this->api_key,
			'temperature'            => floatval( $parameters['temperature'] ),
			'messages'               => $context_and_prompts,
			'max_tokens'             => $this->token_safe_number,
			'model_type'             => $model_type,
			'top_summaries_post_ids' => $top_summaries_post_ids,
		);
	}

	/**
	 * Logs the chat in the database.
	 *
	 * @param string $answer The answer.
	 * @param array  $post_ids The post ids.
	 * @param string $unique_conversation The unique conversation id.
	 * @return void
	 */
	public function wdgpt_log_chat( $answer, $post_ids, $unique_conversation ) {
		$messages   = $this->wdgpt_get_last_two_messages();
		$messages[] = array(
			'role'    => 'assistant',
			'content' => $answer,
		);
		$now        = current_time( 'mysql' );
		$post_id    = implode( ',', $post_ids );
		global $wpdb;

        /*
         * Correctif 18-07-24 : Utilisation de la méthode prepare() pour éviter les injections SQL
         */
        //$existing_entry = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}wdgpt_logs WHERE unique_id = '$unique_conversation'" );
        $existing_entry = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wdgpt_logs WHERE unique_id = %s",
            $unique_conversation
        ));

		if ( $existing_entry ) {
			$existing_post_ids = explode( ',', $existing_entry->post_ids );
			$new_post_ids      = array_unique( array_merge( $existing_post_ids, $post_ids ) );
			$wpdb->update(
				$wpdb->prefix . 'wdgpt_logs',
				array(
					'post_ids'   => implode( ',', $new_post_ids ),
					'created_at' => $now,
				),
				array( 'unique_id' => $unique_conversation )
			);
			$log_id = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}wdgpt_logs WHERE unique_id = '$unique_conversation'" );
		} else {
			$wpdb->insert(
				$wpdb->prefix . 'wdgpt_logs',
				array(
					'post_ids'   => $post_id,
					'created_at' => $now,
					'unique_id'  => $unique_conversation,
				)
			);
			$log_id = $wpdb->insert_id;
		}

		foreach ( $messages as $message ) {
			$this->wdgpt_insert_log_message( $message, $log_id );
		}
	}
	/**
	 * Inserts a message in the database.
	 *
	 * @param array $message The message to insert.
	 * @param int   $log_id The log id.
	 * @return void
	 */
	private function wdgpt_insert_log_message( $message, $log_id ) {
		switch ( $message['role'] ) {
			case 'user':
				$source = 0;
				break;
			case 'assistant':
				$source = 1;
				break;
		}
		global $wpdb;
        /*
         * 18-07-24 Correctif failles SQL
         * */

		/*$wpdb->insert(
			$wpdb->prefix . 'wdgpt_logs_messages',
			array(
				'log_id' => $log_id,
				'prompt' => $message['content'],
				'source' => $source,
			)
		);*/


        $wpdb->insert(
            $wpdb->prefix . 'wdgpt_logs_messages',
            array(
                'log_id' => intval($log_id),
                'prompt' => sanitize_text_field($message['content']),
                'source' => intval($source),
            )
        );
	}

	/**
	 * Logs the error in the database.
	 *
	 * @param string $error The error.
	 * @param string $code The error code.
	 * @param string $type The error type.
	 */
	public function wdgpt_insert_error_log_message( $error, $code, $type ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wd_error_logs';
        /*
         * 18-07-24 Correctif failles SQL
         * */
		/*$result     = $wpdb->insert(
			$table_name,
			array(
				'question'   => $this->question,
				'error'      => $error,
				'error_code' => $code,
				'error_type' => $type,
				'created_at' => current_time( 'mysql' ),

			)
		);*/
        $result = $wpdb->insert(
            $table_name,
            array(
                'question'   => sanitize_text_field($this->question),
                'error'      => sanitize_text_field($error),
                'error_code' => intval($code),
                'error_type' => sanitize_text_field($type),
                'created_at' => current_time( 'mysql' ),
            )
        );
	}

	/**
	 * Retrieves the topic embedding from the server.
	 *
	 * @param string $question The question.
	 * @return array|WP_Error The topic embedding or an error object.
	 */
	public function wdgpt_retrieve_topic_embedding( $question ) {
		try {
			$response = json_decode(
				$this->client->embeddings(
					array(
						'model' => 'text-embedding-ada-002',
						'input' => $question,
					)
				)
			);
			if ( isset( $response->error ) && isset( $response->error->type ) ) {
				if ( 'insufficient_quota' === $response->error->type ) {
					$this->wdgpt_insert_error_log_message( $response->error->message, 429, $response->error->type );
					return '__INSUFFICIENT_QUOTA__';
				}
				if ( 'invalid_request_error' === $response->error->type ) {
					$this->wdgpt_insert_error_log_message( $response->error->message, 429, $response->error->type );
					return '__CONTEXT_TOO_LONG__';
				}
			}
			return $response->data[0]->embedding;
		} catch ( Exception $e ) {
			$this->wdgpt_insert_error_log_message( $e->getMessage(), $e->getCode(), 'retrieve_topic_embedding' );
			return new WP_Error( 'server_error', $response, array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieves the tokens from the provided messages.
	 *
	 * @param array $context_and_prompts The context and prompts.
	 * @return array|WP_Error The tokens or an error object.
	 */
	private function wdgpt_count_tokens( $context_and_prompts ) {
		$token_encoder_provider = new EncoderProvider();
		$model =  get_option( 'wdgpt_model', 'gpt-3.5-turbo' );
		if ( 'gpt-4' === $model ) {
			$model = 'gpt-4o';
		}
		$token_encoder          = $token_encoder_provider->getForModel( $model );

		$total_tokens       = 0;
		$tokens_per_message = 3;
		$tokens_per_name    = 1;

		foreach ( $context_and_prompts as $message ) {
			$total_tokens += $tokens_per_message;
			$total_tokens += $tokens_per_name;
			/**
			 * Some posts can have malformed characters, so we need to check the encoding and convert it to UTF-8 if it is not.
			 * This is not an issue from WordPress or the plugin, but from the users who can copy and paste from different sources.
			 * It can also come from plugins that do not handle the encoding properly.
			 */
			if ( ! mb_check_encoding( $message['content'], 'UTF-8' ) ) {
				$message['content'] = mb_convert_encoding( $message['content'], 'UTF-8' );
			}
			$encoded_property = $token_encoder->encode( $message['content'] );
			$total_tokens    += count( $encoded_property );

		}
		$total_tokens += 3;
		return $total_tokens;
	}

    public function wdgpt_count_embedings_token ($text) {
        $token_encoder_provider = new EncoderProvider();
        $encoder = $token_encoder_provider->getForModel('text-embedding-ada-002');
        $tokens = $encoder->encode($text);

        return count($tokens);
    }

	/**
	 * Generates the context and prompts for the question.
	 *
	 * @param string $question The question.
	 * @param array  $contexts_with_embeddings The contexts with their embeddings.
	 * @return array The context and prompts.
	 */
	private function wdgpt_generate_context_prompts( $question, $contexts_with_embeddings ) {

		if ( class_exists( 'WDGPT_WooCommerce_OpenAI' ) ) {
			$contexts_with_embeddings = WDGPT_WooCommerce_OpenAI::instance()->wdgpt_woocommerce_format_products( $contexts_with_embeddings );
		}

		$filtered_contexts = array_filter(
			$contexts_with_embeddings,
			function ( $raw_context ) {
				$product_type = get_post_type( $raw_context['post_id'] );
				return in_array( $product_type, $this->get_post_types(), true );
			},
		);

		$contexts = array_map(
			function ( $raw_context ) {
				return $this->wdgpt_remove_accents( $raw_context['content'] );
			},
			$filtered_contexts
		);
		$contexts = array_map(
			function ( $raw_context ) {
				return $this->wdgpt_replace_double_quotes_with_single_quotes( $raw_context );
			},
			$contexts
		);

		// Escape the ' and " characters.
		$contexts = array_map(
			function ( $raw_context ) {
				return str_replace( array( "'", '"' ), array( "\\'", '\\"' ), $raw_context );
			},
			$contexts
		);

		$context = '';

		$context = array_reduce(
			$contexts,
			function ( $accumulator, $raw_context ) {
				return $accumulator . ' ' . $raw_context;
			},
			$context
		);

		$permalinks = '[URLS]:' . implode(
			',',
			array_map(
				function ( $raw_context ) {
					return $raw_context['permalink'];
				},
				$contexts_with_embeddings
			)
		);

		$prompt = array();

		$base_prompt = 'You are a helpful assistant, providing positive and constructive responses based on the given context.' .
			'Focus on the context to answer questions, and only use information provided within it.' .
			'If a question is unrelated or cannot be answered with the provided context, kindly respond that you need more information.' .
			'When necessary to include a link, please use the provided URLs only. Use markdown formatting to highlight key points in your answer.' .
			'Keep the discussion focused on the relevant topics and features. Please respond in the same language as the question, maintaining a positive and respectful tone.';

		if ( class_exists( 'WDGPT_WooCommerce_OpenAI' ) ) {
			$base_prompt = WDGPT_WooCommerce_OpenAI::instance()->wdgpt_woocommerce_format_base_prompt( $base_prompt, $contexts_with_embeddings );
		}

		$final_prompt = $base_prompt . ' [PROVIDED CONTEXT]: ***' . $context . '***' . $permalinks;

		$prompt[] = array(
			'role'    => 'system',
			'content' => $final_prompt,
		);

		$messages = $this->wdgpt_format_messages();

		// Remove the last two messages from the messages array.
		array_pop( $messages );

		foreach ( $messages as $message ) {
			$prompt[] = array(
				'role'    => $message['role'],
				'content' => $message['content'],
			);
		}

		$prompt[] = array(
			'role'    => 'user',
			'content' => 'Follow the instructions in the System role always.Keep those instructions in context all the time. Here is the question you have to answer: ' . $question,
		);

		return $prompt;
	}

	/**
	 * Formats the messages to be displayed in the chat.
	 *
	 * @return array The formatted messages.
	 */
	private function wdgpt_format_messages() {
		$messages = array();
		foreach ( $this->conversation as $message ) {
			$messages[] = array(
				'role'    => 'user' === $message['role'] ? 'user' : 'assistant',
				'content' => trim( $message['text'] ),
			);
		}
		// If the array has more than 10 elements, return the last 10 elements.
		if ( count( $messages ) > 10 ) {
			return array_slice( $messages, count( $messages ) - 10 );
		}
		return $messages;
	}

	/**
	 * Retrieve the last two messages to be displayed in the chat.
	 *
	 * @return array The last two messages.
	 */
	private function wdgpt_get_last_two_messages() {
		$messages = $this->wdgpt_format_messages();
		// If the array has less than 2 elements, return all elements.
		if ( count( $messages ) < 1 ) {
			return $messages;
		}
		// If the array has more than 2 elements, return the last 2 elements.
		return array_slice( $messages, -1 );
	}

	/**
	 * Removes the accents from a string.
	 *
	 * @param string $str The string to remove the accents from.
	 * @return string The string without accents.
	 */
	private function wdgpt_remove_accents( $str ) {
		$accents     = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖ0òóôõö0ÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
		$accents_out = 'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn';
		return str_replace( str_split( $accents ), str_split( $accents_out ), $str );
	}

	/**
	 * Replaces the double quotes with single quotes.
	 *
	 * @param string $obj The string to replace the double quotes with single quotes.
	 * @return string The string with the double quotes replaced with single quotes.
	 */
	private function wdgpt_replace_double_quotes_with_single_quotes( $obj ) {
		return str_replace( '"', "'", $obj );
	}

	/**
	 * Retrieves the previous question from the conversation.
	 *
	 * @return string The previous question.
	 */
	private function wdgpt_previous_question() {
		if ( empty( $this->conversation ) ) {
			return '';
		}

		$user_messages = array_filter(
			$this->conversation,
			function ( $message ) {
				return 'user' === $message['role'];
			}
		);

		$sorted_messages = array_values( $user_messages );
		usort(
			$sorted_messages,
			function ( $a, $b ) {
				$date_a = new DateTime( $a['date'] );
				$date_b = new DateTime( $b['date'] );
				return $date_a <=> $date_b;
			}
		);

		$user_message_count = count( $sorted_messages );
		if ( $user_message_count > 1 ) {
			return $sorted_messages[1]['text'];
		}

		return '';
	}

	/**
	 * Retrieves the parameters from the server.
	 *
	 * @return array|WP_Error The parameters or an error object.
	 */
	private function wdgpt_retrieve_parameters() {
		try {
			$parameters = array(
				'temperature'  => get_option( 'wd_openai_temperature', 0.5 ),
				'max_contexts' => get_option( 'wd_openai_max_contexts', 3 ),
				'precision'    => get_option( 'wd_openai_precision_threshold', 0.5 ),
				'similarity'   => get_option( 'wd_openai_similarity_threshold', 0.05 ),
			);
			if ( class_exists( 'WDGPT_WooCommerce_OpenAI' ) && 'on' === get_option( 'wdgpt_woocommerce_only_use_woocommerce_products', '' ) ) {
				$parameters['max_contexts'] = get_option( 'wdgpt_woocommerce_override_max_contexts', get_option( 'wd_openai_max_contexts', 3 ) );
			}
			return $parameters;
		} catch ( Exception $e ) {
			return new WP_Error( 'server_error', 'Server error', array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieves the post types.
	 *
	 * @return array The post types.
	 */
	public function get_post_types($default = false) {
		$default_post_types = ['post', 'page'];
        if ( class_exists('WDGPT_Pdf')) {
            $default_post_types[] = 'attachment';
        }

		if ( $default || ! class_exists( 'WDGPT_Custom_Type_Manager_Data' )) {
			return $default_post_types;
		}
		$custom_type_manager_data = WDGPT_Custom_Type_Manager_Data::instance();

		return array_merge( $default_post_types, $custom_type_manager_data->get_post_types() );
	}

    public function get_post_status($default = false) {
        $default_post_status = ['publish'];

        if (class_exists('WDGPT_Pdf')) {
            $default_post_status[] = 'inherit';
        }

        return $default_post_status;
    }

	/**
	 * Retrieves the embeddings from the server.
	 *
	 * @return array|WP_Error The embeddings or an error object.
	 */
	private function wdgpt_retrieve_post_embeddings() {
		try {
			// Retrieve all the post_id where their post meta has the key 'wdgpt_is_active' and it is set to true.
			// Check through the post_metas.
			$post_ids   = get_posts(
				array(
					'post_type'      => $this->get_post_types(),
					'post_status'    => $this->get_post_status(),
					'meta_key'       => 'wdgpt_is_active',
					'meta_value'     => 'true',
					'fields'         => 'ids',
					'posts_per_page' => -1,
				)
			);
			$embeddings = array();
			foreach ( $post_ids as $post_id ) {
				$post_embeddings = get_post_meta( $post_id, 'wdgpt_embeddings', true );
				if ( $post_embeddings ) {
					$embeddings[ $post_id ]['embeddings'] = $post_embeddings;
				}
			}
			// For each $embedding, add the post_content to it.
			foreach ( $embeddings as $post_id => $embedding ) {
				$post                                = get_post( $post_id );

                $embeddings[ $post_id ]['content']   = $post->post_content.' '.$this->add_acf_fields($post_id);

                // Check if the post_type of the post is public, if it is, get the permalink, otherwise, set it to an empty string.
                $is_public_post_type = get_post_type_object( get_post_type( $post_id ) )->public;

              if (class_exists('WDGPT_Pdf') && 'attachment' === get_post_type($post_id)) {
                    $pdf_parser = new WDGPT_Pdf();
                    $pdf_content = $pdf_parser->get_pdf_content($post_id);
                    $embeddings[ $post_id ]['content'] = $post->post_content.' '.$pdf_content->content.' '.$this->add_acf_fields($post_id);
                    // $is_public_post_type = get_post_type_object( get_post_type( $post_id ) )->inherit;
                }

				$embeddings[ $post_id ]['post_id']   = $post_id;

				$embeddings[ $post_id ]['permalink'] = $is_public_post_type ? get_permalink( $post_id ) : '';
			}
			return $embeddings;
		} catch ( Exception $e ) {
			return new WP_Error( 'server_error', 'Server error', array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieves the ACF fields from the server.
	 */
	private function add_acf_fields($post_id) {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return '';
		}
		$post_type = get_post_type( $post_id );
		$field_groups = acf_get_field_groups(array('post_type' => $post_type));
		if ( empty($field_groups) ) {
			return '';
		}

		$fields = [];
		foreach ( $field_groups as $field_group ) {
			$fields[] = acf_get_fields( $field_group['ID'] );
		}
		$option = get_option( 'wdgpt_custom_type_manager_acf_fields_' . $post_type, '' );

		if ( empty($option) ) {
			return '';
		}
		$acf_fields = explode( ',', $option );
		$acf_fields = array_filter( $acf_fields );
		if ( ! empty( $acf_fields) ) {
			// Remove the possible empty values from the array.
			$acf_fields = array_filter( $acf_fields );
			$acf_fields_array = [];
			// Retrieve the values of the ACF fields.
			foreach ( $acf_fields as $acf_field ) {
				$acf_fields_array[] = $acf_field.':'.get_field( $acf_field, $post_id );
			}
			$acf_field_text = implode( ', ', $acf_fields_array );
		}
		return $acf_field_text;

	}

	/**
	 * Calculates the similarities between the embeddings and the topic embedding.
	 *
	 * @param array $embeddings The embeddings.
	 * @param array $topic_embedding The topic embedding.
	 * @return array The embeddings with the similarities.
	 */
	private function wdgpt_calculate_similarities( $embeddings, $topic_embedding ) {
		return array_map(
			function ( $embedding ) use ( $topic_embedding ) {
				$similarity = $this->wdgpt_cosine_similarity(
					$embedding['embeddings'],
					$topic_embedding
				);
				return array_merge( $embedding, array( 'similarity' => $similarity ) );
			},
			array_values( $embeddings )
		);
	}

	/**
	 * Calculates the cosine similarity between two vectors.
	 *
	 * @param array $context The context vector.
	 * @param array $topic The topic vector.
	 * @return float The cosine similarity.
	 */
	private function wdgpt_cosine_similarity( $context, $topic ) {
		$dot_product = 0.0;
		$count       = count( $context );
		for ( $i = 0; $i < $count; $i++ ) {
			$dot_product += $context[ $i ] * $topic[ $i ];
		}
		return $dot_product;
	}

	/**
	 * Sorts the similarities in descending order.
	 *
	 * @param array $embeddings The embeddings.
	 * @param array $topic_embedding The topic embedding.
	 * @return array The embeddings with the similarities sorted in descending order.
	 */
	private function wdgpt_sorted_similarities( $embeddings, $topic_embedding ) {
		$similarities = $this->wdgpt_calculate_similarities( $embeddings, $topic_embedding );
		usort(
			$similarities,
			function ( $a, $b ) {
				return $b['similarity'] <=> $a['similarity'];
			}
		);
		return $similarities;
	}
}

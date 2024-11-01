<?php
/**
 * General settings for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Show a success message if the form was submitted
 *
 * @param string $option_name The name of the option.
 */
function wdgpt_general_settings_save_option( $option_name ) {
	if ( ! isset( $_POST['wdgpt_settings_nonce'] ) ||
	! wp_verify_nonce( sanitize_key( $_POST['wdgpt_settings_nonce'] ), 'wdgpt_settings' ) ) {
		wp_die( 'Security check failed' );
	}

	$opt_name_sanitize = isset( $_POST[ $option_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) : '';
	update_option( $option_name, $opt_name_sanitize );
}

	/**
	 * Save options and show a success message if the form was submitted.
	 */
function wdgpt_general_settings_save_options() {
	wdgpt_general_settings_save_option( 'wd_openai_api_key' );
	wdgpt_general_settings_save_option( 'wdgpt_name' );
	wdgpt_general_settings_save_option( 'wd_openai_temperature' );
	wdgpt_general_settings_save_option( 'wd_openai_max_contexts' );
	wdgpt_general_settings_save_option( 'wd_openai_similarity_threshold' );
	wdgpt_general_settings_save_option( 'wd_openai_precision_threshold' );
	wdgpt_general_settings_save_option( 'wdgpt_model' );
	wdgpt_general_settings_save_option( 'wdgpt_enable_chatbot' );
	wdgpt_general_settings_save_option( 'wdgpt_chat_bubble_typing_text_' . get_locale() );
	wdgpt_general_settings_save_option( 'wdgpt_greetings_message_' . get_locale() );
	wdgpt_general_settings_save_option( 'wdgpt_enable_chatbot_bubble' );
	new WDGPT_Admin_Notices( 2, __( 'Settings saved successfully !', 'webdigit-chatbot' ) );
}

/**
 * Add the general settings section.
 */
function wdgpt_settings_section_callback() {
	$current_version       = wdgpt_chatbot()->get_version();
	$database_updater      = new WDGPT_Database_Updater( $current_version );
	$should_disable_plugin = $database_updater->should_disable_plugin();
	if ( $should_disable_plugin ) {
		?>
			<p>
				<?php
				esc_html_e(
					'The plugin has been disabled because the database is not up to date. Please update the database to enable the plugin.',
					'webdigit-chatbot'
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wdgpt&tab=wdgpt_db_update' ) ); ?>">
				<?php
				esc_html_e(
					'Update the database',
					'webdigit-chatbot'
				);
				?>
				</a>
		<?php
		return;
	}
	if ( isset( $_POST['wdgpt_settings_nonce'] ) &&
		wp_verify_nonce( sanitize_key( $_POST['wdgpt_settings_nonce'] ), 'wdgpt_settings' ) &&
		isset( $_FILES['wdgpt_image'] ) ) {
		$wdgpt_image_name     = isset( $_FILES['wdgpt_image']['name'] ) ? sanitize_file_name( $_FILES['wdgpt_image']['name'] ) : '';
		$wdgpt_image_tmp_name = isset( $_FILES['wdgpt_image']['tmp_name'] ) ? sanitize_file_name( $_FILES['wdgpt_image']['tmp_name'] ) : '';
		$wdgpt_image_type     = '';
		if ( isset( $_FILES['wdgpt_image']['type'] ) ) {
			$wdgpt_image_type = sanitize_mime_type( $_FILES['wdgpt_image']['type'] );
		}

		$wdgpt_image_error = '';
		if ( isset( $_FILES['wdgpt_image']['error'] ) ) {
			$wdgpt_image_error = absint( $_FILES['wdgpt_image']['error'] );
		}

		if ( wdgpt_validate_image( $wdgpt_image_name, $wdgpt_image_tmp_name, $wdgpt_image_type, $wdgpt_image_error ) ) {
			$upload_dir = wp_upload_dir();
			$plugin_dir = $upload_dir['basedir'] . '/wdgpt';

			global $wp_filesystem;
			if ( ! $wp_filesystem ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			if ( ! $wp_filesystem->is_dir( $plugin_dir ) ) {
				$wp_filesystem->mkdir( $plugin_dir, 0755 );
			}

			$file_name     = sanitize_file_name( $_FILES['wdgpt_image']['name'] );
			$tmp_file_name = sanitize_text_field( $_FILES['wdgpt_image']['tmp_name'] );
			$upload        = wp_upload_bits(
				$file_name,
				null,
				file_get_contents( $tmp_file_name )
			);
			if ( ! $upload['error'] ) {
				// Only keep the part after '/uploads/' in the $upload['url'].
				$path = explode( '/uploads/', $upload['url'] );
				update_option( 'wdgpt_image_name', $path[1] );
			}
		}
	}

	if ( isset( $_POST['submit'] ) ) {
		wdgpt_general_settings_save_options();
	}
	?>
		<?php wp_nonce_field( 'wdgpt_settings', 'wdgpt_settings_nonce' ); ?>
				
		<?php
		if ( ini_get( 'allow_url_fopen' ) === '0' ) {
			?>
				<div style="background-color: #f44336; color: white; padding: 5px;">
				<p>
					<?php
					esc_html_e(
						'Your server configuration does not allow the plugin to work properly. Please enable the allow_url_fopen option in your php.ini file.',
						'webdigit-chatbot'
					);
					?>
				</p>
			</div>
			<?php
		}
		?>

		<table class="form-table">
		<tr>
			<th scope="row">
			<?php
			esc_html_e(
				'Enable Chatbot:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<label class="switch">
					<input type="checkbox" id="wdgpt_enable_chatbot" name="wdgpt_enable_chatbot" <?php echo ( get_option( 'wdgpt_enable_chatbot', 'on' ) === 'on' ) ? 'checked' : ''; ?>>
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
				<?php
				esc_html_e(
					'OpenAi API Key:',
					'webdigit-chatbot'
				);
				?>
			</th>
			<td>
				<input type="password" id="wd_openai_api_key_field" name="wd_openai_api_key" value="<?php echo esc_attr( get_option( 'wd_openai_api_key' ) ); ?>" />
				<button type="button" id="wdgpt_validate_api_key_button" class="button button-primary">
					<?php
					esc_html_e(
						'Validate Key',
						'webdigit-chatbot'
					);
					?>
				</button>
				<?php
				$valid_api_key = wdpgt_is_valid_api_key();
				?>
				<p id="wdgpt_api_validation" style="color: <?php echo esc_attr( $valid_api_key['color'] ); ?>;">
					<?php echo esc_attr( $valid_api_key['message'] ); ?>
				</p>
				<p class="description">
					<?php
						esc_html_e(
							'You can get your api key',
							'webdigit-chatbot'
						);
					?>
					<a href="https://platform.openai.com/overview" target="_blank">
					<?php
					esc_html_e(
						'here',
						'webdigit-chatbot'
					);
					?>
					</a>!
				</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">
			<?php
			esc_html_e(
				'ChatGPT Model:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<input type="hidden" id="wdgpt_hidden_model" name="wdgpt_hidden_model" value="<?php echo esc_attr( get_option( 'wdgpt_model', '' ) ); ?>" />
				<select id="wdgpt_model_select" name="wdgpt_model">
					<?php
					/**
					 * If a new model is supported, add it to the $models array.
					 */
					$models = array( 'gpt-3.5-turbo', 'gpt-4o' );

					foreach ( $models as $model ) {
						$option_model = get_option( 'wdgpt_model', 'gpt-3.5-turbo' );
						/**
						 * Note: gpt-4o is a better gpt-4, so we replace gpt-4 with gpt-4o for users.
						 * Conditions for using it are the same as gpt-4, so nothing is needed to change except a better model.
						 */
						if ( 'gpt-4' === $option_model ) {
							$option_model = 'gpt-4o';
						}
						$selected = ( $option_model === $model ) ? 'selected' : '';
						?>
						<option value="<?php echo esc_attr( $model ); ?>" <?php echo esc_attr( $selected ); ?>>
							<?php echo esc_html( $model ); ?>
						</option>
					<?php } ?>
				</select>
				<p>
				<?php
					$available_models      = wdpgt_get_models( $models );
					$selected_model        = get_option( 'wdgpt_model', '' );
					$selected_model_exists = false;
				foreach ( $available_models as $model ) {
					if ( $model['id'] === $selected_model ) {
						$selected_model_exists = true;
						break;
					}
				}
				?>
				<p id="wdgpt_model_error_message" style="color: red";>
					<?php
					if ( ! $selected_model_exists && '' !== $selected_model && '' !== get_option( 'wd_openai_api_key', '' ) ) {
						esc_html_e(
							'The previously selected model is not available anymore with your current api key, which means that the chatbot will not work. Please select a new model. Your previous model was: ',
							'webdigit-chatbot'
						);
						echo esc_html( $selected_model );
					}
					?>
				</p>
				</p>
				<p class="description">
				<?php
					esc_html_e(
						'If you want to use gpt-4o, you must subscribe to chatgpt plus. The model gpt4-o is a better version of gpt-4, with faster speed and lower cost.',
						'webdigit-chatbot'
					);
				?>
				</p>
			</td>
			</tr>
			<tr valign="top">
			<th scope="row">
			<?php
			esc_html_e(
				'Chatbot Name:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td><input type="text" name="wdgpt_name" value="<?php echo esc_attr( get_option( 'wdgpt_name', 'Pixel' ) ); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">
				<?php
				esc_html_e(
					'Chatbot Logo:',
					'webdigit-chatbot'
				);
				?>
				</th>
				<td>
					<div style="display: flex;">
						<?php
						$image_src   = '';
						$wdgpt_image = get_option( 'wdgpt_image_name' );
						if ( $wdgpt_image ) {
							$upload_dir = wp_upload_dir();
							$image_src  = $upload_dir['baseurl'] . '/' . $wdgpt_image;
						} else {
							$image_src = WD_CHATBOT_URL . '/img/SmartSearchWP-logo.png';
						}
						?>
						<img id="pluginimg" src="
						<?php
						echo esc_url(
							$image_src
						);
						?>
						" alt="Chatbot Image" />
						<input type="file" name="wdgpt_image" accept="image/*" onchange="updateImage(event)" />
						<input type="hidden" name="wdgpt_image_name" id="wdgpt_image_name" value="
						<?php
						echo esc_attr(
							$wdgpt_image
						);
						?>
						" />
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Temperature:', 'webdigit-chatbot' ); ?></th>
				<td>
					<input type="range" name="wd_openai_temperature" min="0" max="1" step="0.1" value= "
					<?php
					echo esc_attr(
						get_option( 'wd_openai_temperature', 0.5 ) // default value is 0.5.
					);
					?>
					"
					oninput="temperatureOutput.value = this.value * 10" />
					<output name="temperatureOutput">
					<?php
					echo esc_attr(
						get_option( 'wd_openai_temperature', 0.5 ) * 10 // default value is 0.5 multiplied by 10.
					);
					?>
					</output>
					<p class="description">
						<?php
						esc_html_e(
							'The temperature parameter influences the level of randomness in the output generated by the model. When the temperature is lowered, the model becomes more confident but also tends to repeat similar responses. On the other hand, raising the temperature value increases its creativity but also makes its outputs more unpredictable.',
							'webdigit-chatbot'
						);
						?>
					</p>
				</td>
				
			<tr>
				<th scope="row">
				<?php
				esc_html_e(
					'Max used contexts:',
					'webdigit-chatbot'
				);
				?>
				</th>
					<td>
						<input type="range" name="wd_openai_max_contexts" min="1" max="10" step="1" value= "
						<?php
						echo esc_attr(
							get_option( 'wd_openai_max_contexts', 3 ) // default value is 3.
						);
						?>
						"
						oninput="maxContextsOutput.value = this.value" />
						<output name="maxContextsOutput">
						<?php
						echo esc_attr(
							get_option( 'wd_openai_max_contexts', 3 ) // default value is 3.
						);
						?>
						</output>
						<p class="description">
							<?php
							esc_html_e(
								'The maximum number of contexts that the model can use to generate an answer. The higher this parameter is, the more information the model can use to generate an answer. However, increasing this value also increases the risk of generating irrelevant answers. The default value is 3, which is a good balance between relevance and creativity. If you want to prioritize relevance, you can lower this value to 1. If you want to prioritize creativity, you can increase this value to 10.',
								'webdigit-chatbot'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
					<?php
					esc_html_e(
						'Similarity threshold:',
						'webdigit-chatbot'
					);
					?>
					</th>
					<td>
						<input type="range" name="wd_openai_similarity_threshold" min="0" max="0.1" step="0.01" value=
						"
						<?php
						echo esc_attr(
							get_option( 'wd_openai_similarity_threshold', 0.05 )
						);
						?>
						"
						oninput="similarityThresholdOutput.value = Math.round(this.value) * 100" />
						<output name="similarityThresholdOutput">
						<?php
						echo esc_attr(
							round( get_option( 'wd_openai_similarity_threshold', 0.05 ) * 100 )
						);
						?>
						</output>
						<p class="description">
						<?php
						esc_html_e(
							'This determines the maximum difference allowed between the most likely context to be used, and other contexts. Here is an example: if the similarity threshold is set to 5, the model will only consider contexts that are at most 5% less likely than the most likely context. This parameter is useful to prevent the model from generating irrelevant answers when the most likely context is not relevant. The default value is 5, which is a good balance between relevance and creativity. If you want to prioritize relevance, you can lower this value to 1. If you want to prioritize creativity, you can increase this value to 10.',
							'webdigit-chatbot'
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
			<th scope="row">
				<?php
				esc_html_e(
					'Precision threshold:',
					'webdigit-chatbot'
				);
				?>
			</th>
			<td>
				<input type="range" name="wd_openai_precision_threshold" min="0.3" max="0.6" step="0.1" value=
				"
				<?php
				echo esc_attr(
					get_option( 'wd_openai_precision_threshold', 0.5 )
				);
				?>
				"
				oninput="precisionThresholdOutput.value = this.value * 10" />
				<output name="precisionThresholdOutput">
				<?php
				echo esc_attr(
					get_option( 'wd_openai_precision_threshold', 0.5 ) * 10
				);
				?>
				</output>
				<p class="description">
					<?php
					esc_html_e(
						'The minimum precision required for a context to be considered eligible determines its relevance to the answer. By increasing this value, the system prioritizes more precise and relevant contexts, potentially leading to better results. However, there is a trade-off, as a higher threshold also increases the risk of not generating an answer if the context or the prompt are not sufficiently relevant.',
						'webdigit-chatbot'
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
			<?php
			esc_html_e(
				'Enable Chatbot Bubble:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<label class="switch">
					<input type="checkbox" id="wdgpt_enable_chatbot_bubble" name="wdgpt_enable_chatbot_bubble" <?php echo ( get_option( 'wdgpt_enable_chatbot_bubble', 'on' ) === 'on' ) ? 'checked' : ''; ?>>
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">
			<?php
			esc_html_e(
				'Chatbot Bubble Typing Text:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<input type="text" name="wdgpt_chat_bubble_typing_text_<?php echo get_locale(); ?>" style="width: 100%;" value="<?php echo get_option('wdgpt_chat_bubble_typing_text_' . get_locale(), __('Hello, may I help you?', 'webdigit-chatbot')); ?>" />
				<p class="description">
					<?php
					esc_html_e(
						'This is the text that will be displayed in the chat bubble above the circle when loading a page. If you don\'t set anything in a certain language, the default translation will be used.',
						'webdigit-chatbot'
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
			<?php
			esc_html_e(
				'Greetings Message:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<input type="text" name="wdgpt_greetings_message_<?php echo get_locale(); ?>" style="width: 100%;" value="<?php echo get_option('wdgpt_greetings_message_' . get_locale() , __('Bonjour, je suis SmartSearchWP, comment puis-je vous aider ?', 'webdigit-chatbot')); ?>" />
				<p class="description">
					<?php
					esc_html_e(
						'This is the message that will be displayed when the chatbot will be greeting the user or if the user restarts the conversation. If you don\'t set anything in a certain language, the default translation will be used.',
						'webdigit-chatbot'
					);
					?>
				</p>
			</td>
		</tr>
		</table>
		<td><input type='submit' name='submit' value='
		<?php
		esc_html_e(
			'Save Changes',
			'webdigit-chatbot'
		);
		?>
		' class='button button-primary' /></td>
	<?php
}

/**
 * Validate image type.
 *
 * @param string $file_name The name of the file.
 * @param string $file_tmp_name The temporary name of the file.
 * @param string $file_type The type of the file.
 * @param string $file_error The error of the file.
 */
function wdgpt_validate_image( $file_name, $file_tmp_name, $file_type, $file_error ) {
	if ( ! empty( $file_tmp_name ) && 0 === $file_error ) {
		$allowed_mime_types = array( 'image/jpeg', 'image/png', 'image/gif' );
		if ( in_array( $file_type, $allowed_mime_types, true ) ) {
			$allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif' );
			$file_array         = explode( '.', $file_name );
			$file_extension     = end( $file_array );
			if ( in_array( $file_extension, $allowed_extensions, true ) ) {
				return true;
			}
		}
	}
}


?>
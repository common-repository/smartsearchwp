<?php
/**
 * This file is responsible to manage the license settings.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Callback function for the license section in the wdgpt-config-license-settings.php file.
 * Renders the HTML form for entering and verifying the SmartSearch license key.
 *
 * @return void
 */
function wdgpt_license_section_callback() {
	wp_nonce_field( 'wdgpt_license', 'wdgpt_license_nonce' );
	?>
		<p>
			<?php
				esc_html_e(
					'For anything related to the SmartSearchWP Premium version, please visit',
					'webdigit-chatbot'
				);
			?>
			<a href="https://www.smartsearchwp.com/" target="_blank">
			<?php
					esc_html_e(
						'SmartSearchWP',
						'webdigit-chatbot'
					);
			?>
				</a> 
				<?php
				esc_html_e(
					'and get your license key.',
					'webdigit-chatbot'
				);
				?>
		</p>
		<p>
			<?php
				esc_html_e(
					'Please note that if your license key is no longer valid or you modify the site linked to it, this view might not update correctly until you reverify your license. You can do this by clicking the "Verify your premium license" button below.',
					'webdigit-chatbot'
				);
			?>
		</p>
			<table class="form-table">
				<tr>
					<td colspan="2">
						<?php
							$premium_license_key = WDGPT_License_Manager::instance()->get_license_key( 'premium' );
							$status              = 'no-license';
							$status_icon         = 'times';
							$status_message      = __( 'Your SmartSearchWP Premium license was not found. Please check your license key.', 'webdigit-chatbot' );

						if ( 'active' === $premium_license_key['status'] ) {
							$status         = 'has-license';
							$status_icon    = 'check';
							$status_message = __( 'Your SmartSearchWP Premium license is valid, and has been registered with the current site. You can now use the premium features.', 'webdigit-chatbot' );
						} elseif ( 'expired' === $premium_license_key['status'] ) {
							$status_icon    = 'times';
							$status_message = __( 'Your SmartSearchWP Premium license has expired. Please renew it to continue using the premium features, then verify it again..', 'webdigit-chatbot' );
						}
						?>
						<p id="wd-premium-license-status" class="wd-license-status <?php echo esc_attr( $status ); ?>">
						<i class= "fas fa-<?php echo esc_attr( $status_icon ); ?>"></i>
						<?php
							echo esc_attr( $status_message );
						?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php
							esc_html_e(
								'Premium License:',
								'webdigit-chatbot'
							);
						?>
					</th>
					<td>
						
						<input type="password" name="wd_smartsearch_license" id="license_key" value="<?php echo esc_attr( get_option( 'wd_smartsearch_license' ) ); ?>" />
						<p class="description">
							<?php
								esc_html_e(
									'Enter your SmartSearchWP Premium license key to enable the premium features. You can find your license key in your SmartSearchWP account, after purchasing the premium version of the plugin.',
									'webdigit-chatbot'
								);
							?>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type='submit' name='submit' id="wd-premium-license-submit" value='
						<?php
							esc_html_e(
								'Verify your premium license',
								'webdigit-chatbot'
							);
						?>
						'
						class='button button-primary'/>
					</td>
				</tr>
				<!-- insert a separation line -->
				<tr>
					<td colspan="2">
						<hr>
					</td>
				</tr>
				<div id="free_license_section">
				<tr>
					<td colspan="2">
						<h4>
							<?php
								esc_html_e(
									'Free Addons',
									'webdigit-chatbot'
								);
							?>
						</h4>
						<p>
							<?php
								esc_html_e(
									'You\'ll get access to our free addons by entering your email address below. This will generate a free license key for you.',
									'webdigit-chatbot'
								);
							?>
						</p>
					</td>
				</tr>
				</div>
				<tr>
				<td colspan="2">
					<?php

						$free_license_key = WDGPT_License_Manager::instance()->get_license_key( 'free' );
						$status           = 'no-license';
						$status_icon      = 'times';
						$status_message   = __( 'You currently have no free license key. Get your free license key by entering your email address below.', 'webdigit-chatbot' );
					if ( 'active' === $free_license_key['status'] ) {
						$status         = 'has-license';
						$status_icon    = 'check';
						$status_message = __( 'You currently have a free license key.', 'webdigit-chatbot' );
					}
					?>
					<p id="wd-free-license-status" class="wd-license-status <?php echo esc_attr( $status ); ?>">
					<i class= "fas fa-<?php echo esc_attr( $status_icon ); ?>"></i>
					<?php
						echo esc_attr( $status_message );
					?>
					</p>
				</td>
				</tr>
				<tr>
					<th scope="row">
						<?php
							esc_html_e(
								'Mail:',
								'webdigit-chatbot'
							);
						?>
					</th>
					<td>
						<input type="text" name="wd_smartsearch_license_email" id="wd_smartsearch_license_email" value="<?php echo esc_attr( get_option( 'wd_smartsearch_license_email' ) ); ?>" />
						<p id="wd_smartsearch_license_email_error" style="color: red;" hidden>
							<?php
								esc_html_e(
									'The email is not valid. Please enter a valid email.',
									'webdigit-chatbot'
								);
							?>
						</p>
						<p class="description">
							<?php
								esc_html_e(
									'Enter your email address to get access to our free addons.',
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
								'Receive updates:',
								'webdigit-chatbot'
							);
						?>
							   
					</th>
					<td>
						<label class="switch">
							<input type="checkbox" id="wd_license_updates" name="wd_license_updates" <?php echo ( get_option( 'wd_smartsearch_free_license_receive_updates', '' ) === 'on' ) ? 'checked' : ''; ?>>
							<span class="slider round"></span>
						</label>
						<p class="description">
							<?php
								esc_html_e(
									'Receive updates about our free addons and new features.',
									'webdigit-chatbot'
								);
							?>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<input type='submit' name='submit' id="wd-free-license-submit" value='
						<?php
							esc_html_e(
								'Get your free license',
								'webdigit-chatbot'
							);
						?>
						'
						class='button button-primary'/>
					</td>
				</tr>
			</table>
			
	<?php
}
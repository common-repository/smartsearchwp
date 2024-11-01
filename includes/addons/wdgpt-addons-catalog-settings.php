<?php
/**
 * This file is responsible to manage the addons catalog settings.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Generate a button based on the addon's status.
 *
 * @param string $action The action to perform.
 * @param string $icon The icon to display.
 * @param string $text The text to display.
 * @param string $id The addon's id.
 * @param string $url The url to redirect to.
 *
 * @return void
 */
function wdgpt_addon_generate_button( $action, $icon, $text, $id, $url = '' ) {
	$css_class      = 'button button-primary';
	$url_attribute  = '';
	$data_attribute = '';

	if ( 'free_license' === $action ) {
		$url_attribute = 'href="' . admin_url( 'admin.php' ) . '?page=wdgpt_addons&tab=wdgpt_license#free_license_section"';
	} elseif ( 'url' === $action ) {
		$url_attribute = 'href=' . esc_url( $url ) . ' target="_blank"';
	}

	if ( ! empty( $action ) && ! empty( $id ) ) {
		$data_attribute = 'data-action=' . $action . ' data-id=' . $id . '';
	}

	$icon = esc_attr( $icon );
	$text = esc_html( $text );

	?>
	<a <?php echo esc_attr( $url_attribute ); ?> <?php echo esc_attr( $data_attribute ); ?>
	class="
	<?php
					echo esc_attr( $css_class );
	?>
					">
		<i class="
		<?php
		echo esc_attr( $icon );
		?>
		" aria-hidden="true"></i> 
		<?php
		echo esc_html( $text );
		?>
	</a>
	<?php
}

/**
 * Callback function for the addons manager section in the wdgpt-config-addons-settings.php file.
 *
 * @return void
 */
function wdgpt_addons_manager_section_callback() {
	$addons_manager = WDGPT_Addons_Manager::instance();
	$addons         = $addons_manager->retrieve_addons();
	if ( ! $addons ) {
		?>
			<p>
				<?php
					esc_html_e(
						'An error occured while retrieving the addons.',
						'webdigit-chatbot'
					);
				?>
			</p>
		<?php
		return;
	}
	/**
	 * Handle addon actions display depending on the action type.
	 *
	 * @param string $action_type The action type.
	 * @param string $action_error_message The error message to display.
	 * @param string $action_success_message The success message to display.
	 *
	 * @return void
	 */
	function handle_addon_action( $action_type, $action_error_message, $action_success_message ) {
		if ( isset( $_GET[ $action_type ] ) ) {
			$message_type = '0' === $_GET[ $action_type ] ? 'error' : 'success';
			$message      = '0' === $_GET[ $action_type ] ? $action_error_message : $action_success_message;
			?>
		<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible"><p><?php echo esc_attr( $message ); ?></p></div>
			<?php
		}
	}

	handle_addon_action( 'install', __( 'An error occured while installing the addon.', 'webdigit-chatbot' ), __( 'The addon has been installed and activated successfully.', 'webdigit-chatbot' ) );
	handle_addon_action( 'uninstall', __( 'An error occured while uninstalling the addon.', 'webdigit-chatbot' ), __( 'The addon has been uninstalled successfully.', 'webdigit-chatbot' ) );
	handle_addon_action( 'activate', __( 'An error occured while activating the addon.', 'webdigit-chatbot' ), __( 'The addon has been activated successfully.', 'webdigit-chatbot' ) );
	handle_addon_action( 'deactivate', __( 'An error occured while deactivating the addon.', 'webdigit-chatbot' ), __( 'The addon has been deactivated successfully.', 'webdigit-chatbot' ) );
	handle_addon_action( 'update', __( 'An error occured while updating the addon.', 'webdigit-chatbot' ), __( 'The addon has been updated successfully.', 'webdigit-chatbot' ) );
	$license_status = WDGPT_License_Manager::instance()->get_license_status();

	?>
		<p class="license-status <?php echo esc_attr( $license_status['css_class'] ); ?>"><?php echo esc_attr( $license_status['message'] ); ?></p>
			<p><?php esc_html_e( 'The addons catalog is a collection of premium and free addons that can be installed to extend the functionality of the plugin.', 'webdigit-chatbot' ); ?></p>
		<p><?php esc_html_e( 'Free addons can be installed with a free license, while premium addons require a  premium license to be installed.', 'webdigit-chatbot' ); ?></p>
		<p><?php esc_html_e( 'A premium license is required for premium addons, and also gives access to free addons.', 'webdigit-chatbot' ); ?></p>
		<p><?php esc_html_e( 'Note: you can safely install, uninstall, activate or deactivate addons from this page.', 'webdigit-chatbot' ); ?></p>
	<?php

	$categories = array();

	foreach ( $addons as &$addon ) {
		$categories[]              = $addon['category'];
		$addon['is_plugin_active'] = is_plugin_active( $addon['activation_slug'] );
		$addon['is_installed']     = file_exists( WP_PLUGIN_DIR . '/' . $addon['activation_slug'] );
		if ( $addon['is_plugin_active'] ) {
			$plugin_data             = get_plugin_data( WP_PLUGIN_DIR . '/' . $addon['activation_slug'] );
			$plugin_version          = $plugin_data['Version'];
			$external_addon_version  = $addon['version'];
			$addon['is_out_of_date'] = version_compare( $plugin_version, $external_addon_version, '<' );
		}
	}
	/**
	 * /!\ Do not remove this line, it is used to avoid conflicts with the $addon variable in the loop below
	 */
	unset( $addon );

	$license_capabilities = WDGPT_License_Manager::instance()->get_license_capabilities();
	$installed_plugins    = get_plugins();

	$categories = array_unique( $categories );
	foreach ( $categories as $category ) {
		echo '<h3>' . esc_attr( $category ) . '</h3>';
		?>
		<table class="wdgpt-addons-table">
			<?php
			foreach ( $addons as $addon ) {
				if ( $addon['category'] === $category ) {
					?>
						<tr class="wdgpt-addons-row">
							<td class="wdgpt-addons-cell image-cell">
							<?php
							if ( $addon['url'] ) {
								?>
											<a href="<?php echo esc_attr( $addon['url'] ); ?>" target="_blank">
												<img src="<?php echo esc_attr( $addon['image'] ); ?>" alt="<?php echo esc_attr( $addon['name'] ); ?>" width="64" height="64">
											</a>
									<?php
							} else {
								?>
											<img src="<?php echo esc_attr( $addon['image'] ); ?>" alt="<?php echo esc_attr( $addon['name'] ); ?>" width="64" height="64">
									<?php
							}
							?>
							</td>
							<td class="wdgpt-addons-cell text-cell">
								<h4>
									<span class="version-rectangle"><?php echo ' ' . esc_attr( $addon['version'] ); ?></span>
								<?php
								if ( $addon['url'] ) {
									?>
												<a class="wdgpt-addons-catalog-link" href="<?php echo esc_attr( $addon['url'] ); ?>" target="_blank">
											<?php echo esc_attr( $addon['name'] ); ?>
												</a>
										<?php
								} else {
									echo esc_attr( $addon['name'] );
									?>
										<?php
								}
								?>
								</h4>
								<p><?php echo esc_attr( $addon['description'] ); ?></p>
								<?php
								$addons_manager->show_details( $addon );
								?>
							</td>
							<td class="wdgpt-addons-cell button-cell">
								<?php
								$id = $addon['id'];
								if ( $addon['is_installed'] ) {
									if ( $addon['is_plugin_active'] ) {
										if ( $addon['is_out_of_date'] ) {
											if ( $addon['free'] ) {
												if ( $license_capabilities['free'] ) {
													wdgpt_addon_generate_button( 'update', 'fa fa-arrow-up', __( 'Update', 'webdigit-chatbot' ), $id );
												} else {
													wdgpt_addon_generate_button( 'free_license', 'fas fa-key', __( 'Get Free License', 'webdigit-chatbot' ), $id );
												}
											} elseif ( $license_capabilities['premium'] ) {
													wdgpt_addon_generate_button( 'update', 'fa fa-arrow-up', __( 'Update', 'webdigit-chatbot' ), $id );
											} else {
												wdgpt_addon_generate_button( 'url', 'fas fa-shopping-cart', __( 'Buy', 'webdigit-chatbot' ), $id, 'https://www.smartsearchwp.com/#license_tarification');
											}
										}
										wdgpt_addon_generate_button( 'deactivate', 'fas fa-power-off', __( 'Deactivate', 'webdigit-chatbot' ), $id );
									} else {
										wdgpt_addon_generate_button( 'activate', 'fas fa-power-off', __( 'Activate', 'webdigit-chatbot' ), $id );
										wdgpt_addon_generate_button( 'uninstall', 'fas fa-trash', __( 'Uninstall', 'webdigit-chatbot' ), $id );
									}
								} elseif ( $addon['free'] ) {
									if ( $license_capabilities['free'] ) {
										wdgpt_addon_generate_button( 'install', 'fas fa-download', __( 'Install', 'webdigit-chatbot' ), $id );
									} else {
										wdgpt_addon_generate_button( 'free_license', 'fas fa-key', __( 'Get Free License', 'webdigit-chatbot' ), $id );
									}
								} elseif ( $license_capabilities['premium'] ) {
										wdgpt_addon_generate_button( 'install', 'fas fa-download', __( 'Install', 'webdigit-chatbot' ), $id );
								} else {
									wdgpt_addon_generate_button( 'url', 'fas fa-shopping-cart', __( 'Buy', 'webdigit-chatbot' ), $id, 'https://www.smartsearchwp.com/#license_tarification');
								}
								?>
							</td>
						</tr>
						<?php
				}
			}
			?>
		</table>
		<?php

	}
}
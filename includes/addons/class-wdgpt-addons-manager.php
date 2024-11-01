<?php
/**
 * This file is reponsible to manage addons for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage addons.
 */
class WDGPT_Addons_Manager {

	/**
	 * The addons manager instance.
	 *
	 * @var WDGPT_Addons_Manager
	 */
	private static $instance = null;

	/**
	 * The addons url.
	 *
	 * @var string
	 */
	private $url = 'https://www.smartsearchwp.com/wp-content/addons.json';

	/**
	 * Constructor.
	 */
	private function __construct() {
	}

	/**
	 * Get the instance of the class.
	 *
	 * @return WDGPT_Addons_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Retrieve the addons from the server.
	 *
	 * @return array|bool
	 */
	public function retrieve_addons() {

		$response = wp_remote_get( $this->url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

	/**
	 * Retrieve the specific addon from the server.
	 *
	 * @param string $id The addon's id.
	 *
	 * @return array|bool
	 */
	public function retrieve_addon( $id ) {
		$addons = $this->retrieve_addons();
		foreach ( $addons as $addon ) {
			if ( $addon['id'] === (int)$id ) {
				return $addon;
			}
		}
		return false;
	}

	/**
	 * Show the addon's details.
	 *
	 * @param array $addon The addon's details.
	 *
	 * @return void
	 */
	public function show_details( $addon ) {
		$required_version = $addon['required_version'];
		$current_version  = WDGPT_CHATBOT_VERSION;
		$required_version = version_compare( $current_version, $required_version, '>=' );
		if ( ! $required_version ) {
			?>
			<p class="wdgpt-addons-catalog-warning">
				<i class="fas fa-exclamation-triangle"></i>
				<?php
					/* translators: %s: required version of the plugin */
					printf(
						__( 'This addon will require at least version %s of the plugin to function.', 'webdigit-chatbot' ),
						esc_html( $addon['required_version'] )
					);
				?>
			</p>
			<?php
		}
	}

	/**
	 * Install an addon on the website.
	 *
	 * @param array  $addon The addon to install.
	 * @param string $action The action to perform on the addon.
	 *
	 * @return bool True if the addon was installed, false otherwise.
	 */
	public function install_addon( $addon, $action = 'install' ) {
		$action_query = '';
		switch ( $action ) {
			case 'install':
				$action_query = 'addon_installed';
				break;
			case 'update':
				$action_query = 'addon_updated';
				break;
		}
		$id              = $addon['id'];
		$activation_slug = $addon['activation_slug'];

		$url = site_url();
		$url = str_replace( 'http://', '', $url );
		$url = str_replace( 'https://', '', $url );

		$license_capabilities = WDGPT_License_Manager::instance()->get_license_capabilities();
		try {
			if ( $addon['free'] ) {
				if ( $license_capabilities['premium'] ) {
					$license_key = WDGPT_License_Manager::instance()->get_license_key( 'premium' )['license_key'];
				} else {
					$license_key = WDGPT_License_Manager::instance()->get_license_key( 'free' )['license_key'];
				}
			} else {
				$license_key = WDGPT_License_Manager::instance()->get_license_key( 'premium' )['license_key'];
			}
		} catch ( Exception $e ) {
			return false;
		}

		$source = 'https://www.smartsearchwp.com/wp-json/sswp/addon/download?id=' . $id . '&license=' . $license_key . '&url=' . $url;

		if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );

		if ( 'update' === $action ) {
			delete_plugins( array( $activation_slug ) );
		}
		$result = $upgrader->install( $source );

		if ( ! $result || is_wp_error( $result ) ) {
			return false;
		}
		$result = activate_plugin( $activation_slug );
		if ( is_wp_error( $result ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Activate an addon on the website.
	 *
	 * @param array $addon The addon to activate.
	 *
	 * @return bool True if the addon was activated, false otherwise.
	 */
	public function activate_addon( $addon ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $addon['activation_slug'] ) ) {
			activate_plugin( $addon['activation_slug'] );
			return true;
		}
		return false;
	}

	/**
	 * Deactivate an addon from the website.
	 *
	 * @param array $addon The addon to deactivate.
	 *
	 * @return bool True if the addon was deactivated, false otherwise.
	 */
	public function deactivate_addon( $addon ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $addon['activation_slug'] ) ) {
			deactivate_plugins( $addon['activation_slug'] );
			return true;
		}
		return false;
	}

	/**
	 * Uninstall an addon from the website.
	 *
	 * @param array $addon The addon to uninstall.
	 *
	 * @return bool True if the addon was uninstalled, false otherwise.
	 */
	public function uninstall_addon( $addon ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$this->deactivate_addon( $addon );
		$activation_slug = $addon['activation_slug'];
		$result          = delete_plugins( array( $activation_slug ) );
		if ( ! $result || is_wp_error( $result ) ) {
			return false;
		}
		return true;
	}
}
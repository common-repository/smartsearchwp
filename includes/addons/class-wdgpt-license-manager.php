<?php
/**
 * This file is reponsible to manage the license of the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to manage the license.
 */
class WDGPT_License_Manager {
	/**
	 * The instance of the class.
	 *
	 * @var WDGPT_License_Manager
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
	}

	/**
	 * Retrieve the instance of the class.
	 *
	 * @return WDGPT_License_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new WDGPT_License_manager();
		}

		return self::$instance;
	}

	/**
	 * Retrieve the license key.
	 *
	 * @param string $license_type The license type.
	 *
	 * @return array
	 */
	public function get_license_key( $license_type = '' ) {
		switch ( $license_type ) {
			case 'free':
				return $this->get_free_license();
			case 'premium':
				return $this->get_premium_license();
			default:
				return array(
					$this->get_premium_license(),
					$this->get_free_license(),
				);
		}
	}

	/**
	 * Sidebar menu for the license.
	 *
	 * @return $badge The badge for the license.
	 */
	public function get_license_badge() {
		$premium_license_key = $this->get_license_key( 'premium' );
		$free_license_key    = $this->get_license_key( 'free' );
		$badge               = '';

		if ( 'active' === $premium_license_key['status'] ) {
			$badge = '<span class="license-admin-menu-badge pro">' . __( 'Pro', 'webdigit-chatbot' ) . '</span>';
		} elseif ( 'active' === $free_license_key['status'] ) {
			$badge = '<span class="license-admin-menu-badge free">' . __( 'Free', 'webdigit-chatbot' ) . '</span>';
		}
		return $badge;
	}

	/**
	 * Verify if there is any plugin installed that could need an update.
	 * 
	 * @return string
	 */
	public function get_notifications_number() {
		$notification_number = 0;
		$addons_manager = WDGPT_Addons_Manager::instance();
		$addons = $addons_manager->retrieve_addons();

		foreach ($addons as $addon) {
			$addon_path = WP_PLUGIN_DIR . '/' . $addon['activation_slug'];
			if (is_plugin_active($addon['activation_slug']) && file_exists($addon_path)) {
				$plugin_data = get_plugin_data($addon_path);
				if (version_compare($plugin_data['Version'], $addon['version'], '<')) {
					$notification_number++;
				}
			}
		}

		return $notification_number > 0 ? '<span class="update-plugins count-' . $notification_number . '"><span class="plugin-count">' . $notification_number . '</span></span>' : '';
	}



	/**
	 * Retrieve the license capabilities.
	 *
	 * @return array
	 */
	public function get_license_capabilities() {
		$status = array(
			'free'    => false,
			'premium' => false,
		);

		$premium_license_key = $this->get_license_key( 'premium' );
		$free_license_key    = $this->get_license_key( 'free' );

		if ( 'active' === $premium_license_key['status'] ) {
			$status['premium'] = true;
		}
		if ( 'active' === $free_license_key['status'] ) {
			$status['free'] = true;
		}

		// If the premium license is active, the free license is also active.
		if ( $status['premium'] ) {
			$status['free'] = true;
		}
		return $status;
	}

	/**
	 * Retrieve the license status.
	 *
	 * @return array
	 */
	public function get_license_status() {
		$free_license    = $this->get_license_key( 'free' );
		$premium_license = $this->get_license_key( 'premium' );

		$status = array(
			'free'    => $free_license['status'],
			'premium' => $premium_license['status'],
		);

		$state = $this->determine_license_state( $status );

		return $this->get_license_message( $state );
	}

	/**
	 * Retrieve the license status.
	 *
	 * @param array $status The status of the license.
	 *
	 * @return string
	 */
	private function determine_license_state( $status ) {
		if ( 'expired' === $status['premium'] ) {
			return 'active' === $status['free'] ? 'premium_expired_free_active' : 'premium_expired';
		}

		if ( 'inactive' === $status['premium'] ) {
			return 'active' === $status['free'] ? 'premium_inactive_free_active' : 'premium_inactive';
		}

		if ( 'active' === $status['premium'] ) {
			return 'premium_active';
		}

		if ( 'active' === $status['free'] ) {
			return 'free_active';
		}

		if ( 'inactive' === $status['free'] ) {
			return 'free_inactive';
		}

		return 'unknown';
	}

	/**
	 * Retrieve the license message.
	 *
	 * @param string $state The state of the license.
	 *
	 * @return array
	 */
	private function get_license_message( $state ) {
		$messages = array(
			'premium_expired'              => array(
				'css_class' => 'license-expired',
				'message'   => __( 'Your premium license has expired. Please renew your license to continue having access to new addons.', 'webdigit-chatbot' ),
			),
			'premium_expired_free_active'  => array(
				'css_class' => 'license-expired',
				'message'   => __( 'Your premium license has expired, but your free license is active. You can access free addons.', 'webdigit-chatbot' ),
			),
			'premium_inactive'             => array(
				'css_class' => 'license-warning',
				'message'   => __( 'Your premium license is inactive. Please activate your license to continue having access to new addons.', 'webdigit-chatbot' ),
			),
			'premium_inactive_free_active' => array(
				'css_class' => 'license-valid',
				'message'   => __( 'Your free license is active. You can accesss free addons.', 'webdigit-chatbot' ),
			),
			'premium_active'               => array(
				'css_class' => 'license-valid',
				'message'   => __( 'Your premium license is active. You can download all the available addons.', 'webdigit-chatbot' ),
			),
			'free_active'                  => array(
				'css_class' => 'license-valid',
				'message'   => __( 'Your free license is active. You can accesss free addons.', 'webdigit-chatbot' ),
			),
			'free_inactive'                => array(
				'css_class' => 'license-warning',
				'message'   => __( 'Your free license is inactive. Please activate your license to continue having access to new addons..', 'webdigit-chatbot' ),
			),
			'unknown'                      => array(
				'css_class' => 'license-warning',
				'message'   => __( 'Your license is inactive. Please activate your license to continue having access to new addons.', 'webdigit-chatbot' ),
			),
		);

		return $messages[ $state ] ?? $messages['unknown'];
	}

	/**
	 * Retrieve the free license.
	 *
	 * @return array
	 */
	private function get_free_license() {
		// Retrieve the free license from the options.
		$free_license = get_option( 'wd_smartsearch_free_license', '' );

		// Prepare the default license data.
		$license_data = array(
			'status'       => 'inactive',
			'license_type' => 'free',
			'license_key'  => '',
		);

		// If the free license contains 'free_', update the license data.
		if ( strpos( $free_license, 'free_' ) !== false ) {
			$license_data['status']      = 'active';
			$license_data['license_key'] = $free_license;
		}

		// Return the license data.
		return $license_data;
	}

	/**
	 * Retrieve the premium license.
	 *
	 * @return array
	 */
	private function get_premium_license() {
		// Get the transient value and its expiration time.
		$transient_value = get_transient( 'wdgpt_license_transient' );
		$expiration_time = get_option( '_transient_timeout_wdgpt_license_transient' );

		// Initialize response with default values.
		$response = array(
			'status'       => 'inactive',
			'license_type' => 'premium',
			'license_key'  => '',
		);
		// If transient value exists, check if it is expired.
		if ( $transient_value ) {
			if ( $expiration_time < time() ) {
				// If the transient is expired, renew the license from the server.
				$data = $this->renew_license();
				if ( ! $data->is_valid ) {
					$response['status'] = 'expired';
				} else {
					$response['status']      = 'active';
					$response['license_key'] = $transient_value;
				}
			} else {
				$response['status']      = 'active';
				$response['license_key'] = $transient_value;
			}
		} else {
			$data = $this->renew_license();
			if ( ! $data->is_valid ) {
				$response['status'] = 'expired';
			} else {
				$response['status']      = 'active';
				$response['license_key'] = $data->license_key;
			}
		}

		return $response;
	}

	/**
	 * Renew the license.
	 *
	 * @param string $license_key The license key.
	 *
	 * @return object
	 */
	public function renew_license( $license_key = '' ) {
		$url = 'https://www.smartsearchwp.com/wp-json/smw/license-verification/';

		$url_website = site_url();
		$url_website = str_replace( 'http://', '', $url_website );
		$url_website = str_replace( 'https://', '', $url_website );
		if ( '' === $license_key ) {
			$license_key = get_option( 'wd_smartsearch_license', '' );
		}
		$body = array(
			'license_key' => $license_key,
			'url_website' => $url_website,
		);

		$args = array(
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
		if ( ! $data->is_valid ) {
			return $data;
		}

		$this->save_option( 'wd_smartsearch_license', $license_key );
		$license_data = array(
			'license_key' => $license_key,
			'expiry_date' => $data->expiry_date,
		);
		$this->set_premium_license_transient( $license_data );
		if (!isset($data->license_key)) {
			$data->license_key = $license_key;
		}
		return $data;
	}

	/**
	 * Save the option for the license.
	 *
	 * @param string $option_name The name of the option.
	 * @param string $option_value The value of the option.
	 *
	 * @return void
	 */
	private function save_option( $option_name, $option_value ) {
		$opt_name_sanitize = isset( $option_value ) ? sanitize_text_field( wp_unslash( $option_value ) ) : '';
		update_option( $option_name, $opt_name_sanitize );
	}

	/**
	 * Set the transient for the license.
	 *
	 * @param array $license_data The license data.
	 *
	 * @return void
	 */
	private function set_premium_license_transient( $license_data ) {
		delete_transient( 'wdgpt_license_transient' );
		$expiration_date = $this->convert_string_to_timestamp( $license_data['expiry_date'] );
		$expiration_time = WEEK_IN_SECONDS;
		if ( $expiration_date > time() && $expiration_date - time() < WEEK_IN_SECONDS ) {
			$expiration_time = $expiration_date - time();
		}
		set_transient( 'wdgpt_license_transient', $license_data['license_key'], $expiration_time );
	}
	/**
	 * Retrieve the expiration date in seconds.
	 *
	 * @param string $expiration_date The expiration date.
	 *
	 * @return int
	 */
	private function convert_string_to_timestamp( $expiration_date ) {
		$expiration_date = new DateTime( $expiration_date );
		return $expiration_date->getTimestamp();
	}
}

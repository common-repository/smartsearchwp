<?php
/**
 * This file is responsible for initializing the plugin.
 * It also contains the main class for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WDGPT_Chatbot_Initializer Class Doc Comment
 *
 * @category WDGPT_Chatbot_Initializer
 * @package  Webdigit\Chatbot\WDGPT_Chatbot_Initializer
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://webdigit.be
 */
class WDGPT_Chatbot_Initializer {

	/**
	 * The chatbot instance.
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * The different options for the plugin.
	 *
	 * @var $options
	 */
	public $options = array(
		'wd_openai_api_key'             => '',
		'wdgpt_name'                    => 'Pixel',
		'wd_openai_temperature'         => 0.5,
		'wd_openai_precision_threshold' => 0.05,
		'wd_openai_max_contexts'        => 3,
		'wdgpt_model'                   => 'gpt-3.5-turbo',
	);

	/**
	 * The default values for the plugin.
	 *
	 * @var $defaults
	 */
	public $defaults = array(
		'general' => array(
			'wd_openai_api_key'             => '',
			'wdgpt_name'                    => 'Pixel',
			'wd_openai_temperature'         => 0.5,
			'wd_openai_precision_threshold' => 0.05,
			'wd_openai_max_contexts'        => 3,
			'wdgpt_model'                   => 'gpt-3.5-turbo',
		),
		'version' => WDGPT_CHATBOT_VERSION,
	);



	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->define_constants();

		foreach ( $this->options as $key => $value ) {
			$this->options[ $key ] = get_option( $key );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_footer', 'wdgpt_display_chatbot_footer' );

		add_action( 'plugins_loaded', array( $this, 'wdgpt_chatbot_init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function wdgpt_chatbot_init() {
		// Define the current version of the plugin for external use.
		do_action( 'wdgpt_chatbot_init', WDGPT_CHATBOT_VERSION );
	}


	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->defaults['version'];
	}

	/**
	 * Activate plugin.
	 *
	 * @return void
	 */
	public function activate() {
		$chatbot_logs = WDGPT_Logs::get_instance();
		$chatbot_logs->create_table();
		$error_logs = WDGPT_Error_Logs::get_instance();
		$error_logs->create_table();
		update_option( 'wdgpt_chatbot_version', $this->get_version() );

		/**
		 *
		 * Reactivate all crons that were previously activated.
		 */
		$active_crons = get_option( 'wdgpt_active_crons', '' );
		if ( '' !== $active_crons ) {
			$cron_scheduler = new WDGPT_Cron_Scheduler();
			$cron_scheduler->reactivate_crons( $active_crons );
		}
	}

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public function deactivate() {
		/**
		 *
		 * Deactivate all crons, add option that will contain the previously activated cron to reactivate them when activating the plugin again.
		 */
		$cron_scheduler = new WDGPT_Cron_Scheduler();
		$active_crons   = $cron_scheduler->retrieve_active_crons();
		// Update the option with the active crons, separated by a comma.
		update_option( 'wdgpt_active_crons', implode( ',', $active_crons ) );

		$cron_scheduler->disable_all_crons();
	}

	/**
	 * Setup plugin constants.
	 *
	 * @return void
	 */
	private function define_constants() {
		define( 'WD_CHATBOT_PATH', plugin_dir_path( __FILE__ ) );
		define( 'WD_CHATBOT_URL', plugins_url( '', __FILE__ ) );
		define( 'WD_CHATBOT_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Main plugin instance.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();

			add_action( 'init', array( self::$instance, 'load_textdomain' ) );

			self::$instance->includes();
		}

		return self::$instance;
	}

	/**
	 * Include required files.
	 *
	 * @return void
	 */
	private function includes() {
		include_once WD_CHATBOT_PATH . 'includes/wdgpt-config.php';
		include_once WD_CHATBOT_PATH . 'includes/summaries/wdgpt-config-summary.php';
		include_once WD_CHATBOT_PATH . 'includes/wdgpt-client.php';
		include_once WD_CHATBOT_PATH . 'includes/wdgpt-api-requests.php';
		include_once WD_CHATBOT_PATH . 'includes/logs/wdgpt-config-logs.php';
		include_once WD_CHATBOT_PATH . 'includes/logs/wdgpt-config-error-logs.php';
		include_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-logs.php';
		include_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-error-logs.php';
		include_once WD_CHATBOT_PATH . 'includes/logs/class-wdgpt-admin-notices.php';
		include_once WD_CHATBOT_PATH . 'includes/answers/class-wdgpt-answer-generator.php';
		include_once WD_CHATBOT_PATH . 'includes/answers/class-wdgpt-token-encoding.php';
		include_once WD_CHATBOT_PATH . 'includes/class-wdgpt-database-updater.php';
		include_once WD_CHATBOT_PATH . 'includes/wdgpt-update-functions.php';
		include_once WD_CHATBOT_PATH . 'includes/crons/class-wdgpt-cron-scheduler.php';
		include_once WD_CHATBOT_PATH . 'includes/crons/wdgpt-cron-jobs.php';

		// Settings Tabs.
		include_once WD_CHATBOT_PATH . 'includes/config/wdgpt-config-settings-init.php';
		include_once WD_CHATBOT_PATH . 'includes/config/wdgpt-config-general-settings.php';
		include_once WD_CHATBOT_PATH . 'includes/config/wdgpt-config-reporting-settings.php';
		include_once WD_CHATBOT_PATH . 'includes/config/wdgpt-config-database-update-settings.php';
		// Addons.
		include_once WD_CHATBOT_PATH . 'includes/addons/wdgpt-addons.php';
		include_once WD_CHATBOT_PATH . 'includes/addons/class-wdgpt-addons-manager.php';
		include_once WD_CHATBOT_PATH . 'includes/addons/class-wdgpt-license-manager.php';
		include_once WD_CHATBOT_PATH . 'includes/addons/wdgpt-addons-init.php';
		include_once WD_CHATBOT_PATH . 'includes/addons/wdgpt-addons-license-settings.php';
		include_once WD_CHATBOT_PATH . 'includes/addons/wdgpt-addons-catalog-settings.php';
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wdgpt-chatbot', WD_CHATBOT_URL . '/js/dist/wdgpt.main.bundle.js', array( 'jquery' ), wdgpt_chatbot()->defaults['version'], true );
		wp_enqueue_script( 'wdgpt-style-fontawesome', WD_CHATBOT_URL . '/js/scripts/fontawesome-bafa15e11b.js', array( 'jquery' ), wdgpt_chatbot()->defaults['version'], true );

		$this->add_data_to_front();

		wp_enqueue_style( 'wdgpt-style-front', WD_CHATBOT_URL . '/css/main.css', array(), $this->defaults['version'] );
	}

	/**
	 * Add data to the front.
	 *
	 * @return void
	 */
	public function add_data_to_front() {
		$translations = array(
			'noAnswerFound'           => __( 'NoAnswerFound', 'webdigit-chatbot' ),
			'notUnderstood'           => __( 'Sorry, I didnt understand your question. Can you rephrase it ?', 'webdigit-chatbot' ),
			'notEnoughCharacters'     => __( 'Sorry, your question isnt clear enough. Can you rephrase it ?', 'webdigit-chatbot' ),
			'You'                     => __( 'You', 'webdigit-chatbot' ),
			'regenerateEmbeddings'    => __( 'Regenerate Embeddings', 'webdigit-chatbot' ),
			'activate'                => __( 'Activate', 'webdigit-chatbot' ),
			'deactivate'              => __( 'Deactivate', 'webdigit-chatbot' ),
			'errorInitializing'       => __( 'Error initializing', 'webdigit-chatbot' ),
			'defaultGreetingsMessage' => get_option('wdgpt_greetings_message_' . get_locale() , __('Bonjour, je suis SmartSearchWP, comment puis-je vous aider ?', 'webdigit-chatbot')),
		);

		$chatbot_data = array(
			'botName'  => '' === get_option( 'wdgpt_name', 'Pixel' ) ? 'Pixel' : get_option( 'wdgpt_name', 'Pixel' ),
			'botModel' => get_option( 'wdgpt_model', 'gpt-3.5-turbo' ),
			'botIcon'  => $this->retrieve_bot_icon(),
		);

		wp_localize_script( 'wdgpt-chatbot', 'wdTranslations', $translations );
		wp_localize_script( 'wdgpt-chatbot', 'wdChatbotData', $chatbot_data );
	}

	/**
	 * Retrieve the bot icon.
	 *
	 * @return string
	 */
	public function retrieve_bot_icon() {
		$wdgpt_image       = get_option( 'wdgpt_image_name' );
		$default_image_url = WD_CHATBOT_URL . '/img/SmartSearchWP-logo.png';

		if ( ! $wdgpt_image ) {
			return $default_image_url;
		}

		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/' . $wdgpt_image;

		if ( ! file_exists( $file_path ) ) {
			return $default_image_url;
		}

		return $upload_dir['baseurl'] . '/' . $wdgpt_image;
	}
	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'wdgpt-chatbot', WD_CHATBOT_URL . '/js/dist/wdgpt.admin.bundle.js', array( 'jquery' ), wdgpt_chatbot()->defaults['version'], true );
		wp_enqueue_script( 'wdgpt-license', WD_CHATBOT_URL . '/js/dist/wdgpt.license.bundle.js', array( 'jquery' ), wdgpt_chatbot()->defaults['version'], true );
		wp_enqueue_script( 'wdgpt-style-fontawesome', WD_CHATBOT_URL . '/js/scripts/fontawesome-bafa15e11b.js', array( 'jquery' ), wdgpt_chatbot()->defaults['version'], true );

		$translations = array(
			'regenerateEmbeddings'                     => __( 'Regenerate Embeddings', 'webdigit-chatbot' ),
			'activate'                                 => __( 'Activate', 'webdigit-chatbot' ),
			'deactivate'                               => __( 'Deactivate', 'webdigit-chatbot' ),
			'noApiKeyFound'                            => __( 'Please enter your api key!', 'webdigit-chatbot' ),
			'invalidApiKey'                            => __( 'Your api key is invalid! If you think this is a mistake, please check your account on the OpenAI platform. This can also happen if the OpenAI API is having issues, or is down.', 'webdigit-chatbot' ),
			'validApiKey'                              => __( 'Your api key is valid! Don\'t forget to save your changes.', 'webdigit-chatbot' ),
			'apiModelNotAvailable'                     => __( 'The previously selected model is not available anymore with your current api key, which means that the chatbot will not work. Please select a new model. Your previous model was: ', 'webdigit-chatbot' ),
			'freeLicenseValid'                         => __( 'You currently have a free license key.', 'webdigit-chatbot' ),
			'freeLicenseInvalid'                       => __( 'There was an issue retrieving your free license key. Please try again later.', 'webdigit-chatbot' ),
			'premiumLicenseValid'                      => __( 'Your SmartSearchWP Premium license is valid, and has been registered with the current site. You can now use the premium features.', 'webdigit-chatbot' ),
			'premiumLicenseNotFound'                   => __( 'Your SmartSearchWP Premium license was not found. Please check your license key.', 'webdigit-chatbot' ),
			'premiumLicenseVerifiedWithUrl'            => __( 'Your SmartSearchWP Premium license was already registered with the current site. You can use the premium features.', 'webdigit-chatbot' ),
			'premiumLicenseAlreadyRegisteredWithAnotherUrl' => __( 'Your SmartSearchWP Premium license was already registered with another site. Please unlink it from the other site, or contact support if you think this should not happen.', 'webdigit-chatbot' ),
			'premiumLicenseFailedToRegisterUrl'        => __( 'There was an issue registering your SmartSearchWP Premium license with the current site. Please try again later, or contact support if the problem persists.', 'webdigit-chatbot' ),
			'premiumLicenseFailedToRetrieveExpiryDate' => __( 'There was an issue retrieving the expiry date of your SmartSearchWP Premium license. Please try again later, or contact support if the problem persists.', 'webdigit-chatbot' ),
			'premiumLicenseExpired'                    => __( 'Your SmartSearchWP Premium license has expired. Please renew it to continue using the premium features, then verify it again.', 'webdigit-chatbot' ),
		);

		wp_localize_script( 'wdgpt-chatbot', 'wdAdminTranslations', $translations );
		wp_localize_script(
			'wdgpt-license',
			'wdgpt_ajax_object',
			array(
				'ajax_url'                    => admin_url( 'admin-ajax.php' ),
				'ajax_verify_license_nonce'   => wp_create_nonce( 'wdgpt_verify_license_nonce' ),
				'ajax_free_license_nonce'     => wp_create_nonce( 'wdgpt_free_license_nonce' ),
				'ajax_install_addon_nonce'    => wp_create_nonce( 'wdgpt_install_addon_nonce' ),
				'ajax_update_addon_nonce'     => wp_create_nonce( 'wdgpt_update_addon_nonce' ),
				'ajax_deactivate_addon_nonce' => wp_create_nonce( 'wdgpt_deactivate_addon_nonce' ),
				'ajax_activate_addon_nonce'   => wp_create_nonce( 'wdgpt_activate_addon_nonce' ),
				'ajax_uninstall_addon_nonce'  => wp_create_nonce( 'wdgpt_uninstall_addon_nonce' ),
			)
		);

		wp_enqueue_style( 'wdgpt-style-admin', WD_CHATBOT_URL . '/css/backend.css', array(), $this->defaults['version'] );
	}



	/**
	 * Load textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'webdigit-chatbot', false, dirname( WD_CHATBOT_BASENAME ) . '/languages/' );
	}

	/**
	 * Add entry to admin menu.
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		/**
		 * As the plugin is called SmartSearchWP, we want to display SmartSrchWP in the menu title if there are notifications, and SmartSearchWP if there are none.
		 * This is to avoid having the menu title too long when there are notifications.
		 */
		$notifications_number = WDGPT_License_Manager::instance()->get_notifications_number();
		$menu_title = !empty($notifications_number) ? 'SmartSrchWP' : 'SmartSearchWP';

		add_menu_page(
			'SmartSearchWP',
			$menu_title . ' ' . $notifications_number,
			'edit_others_posts',
			'wdgpt',
			array( $this, 'wdgpt_callback' ),
			'dashicons-format-chat',
			6
		);
		add_submenu_page(
			'wdgpt',
			__( 'Settings', 'webdigit-chatbot' ),
			__( 'Settings', 'webdigit-chatbot' ),
			'edit_others_posts',
			'wdgpt',
			array( $this, 'wdgpt_callback' )
		);
		add_submenu_page(
			'wdgpt',
			__( 'Summary_title', 'webdigit-chatbot' ),
			__( 'Summary_title', 'webdigit-chatbot' ),
			'edit_others_posts',
			'wdgpt_summary',
			array( $this, 'wdgpt_summary_callback' )
		);
		add_submenu_page(
			'wdgpt',
			__( 'Chat Logs', 'webdigit-chatbot' ),
			__( 'Chat Logs', 'webdigit-chatbot' ),
			'edit_others_posts',
			'wdgpt_logs',
			array( $this, 'wdgpt_logs_callback' )
		);

		add_submenu_page(
			'wdgpt',
			__( 'Error Logs', 'webdigit-chatbot' ),
			__( 'Error Logs', 'webdigit-chatbot' ),
			'edit_others_posts',
			'wdgpt_error_logs',
			array( $this, 'wdgpt_error_logs_callback' )
		);

		add_submenu_page(
			'wdgpt',
			__( 'Addons', 'webdigit-chatbot' ),
			'<span style="color:#e9a43e; font-weight: bold;">' . __( 'Addons' ) . '</span>' . WDGPT_License_Manager::instance()->get_license_badge() . ' ' . WDGPT_License_Manager::instance()->get_notifications_number(),
			'edit_others_posts',
			'wdgpt_addons',
			array( $this, 'wdgpt_addons_callback' )
		);
	}

	/**
	 * Callback for admin main menu.
	 */
	public function wdgpt_callback() {
		wdgpt_config_form();
	}

	/**
	 * Callback for admin summary menu.
	 */
	public function wdgpt_summary_callback() {
		wdgpt_generated_summary();
	}

	/**
	 * Callback for admin logs menu.
	 */
	public function wdgpt_logs_callback() {
		wdgpt_chat_logs();
	}

	/**
	 * Callback for admin error logs menu.
	 */
	public function wdgpt_error_logs_callback() {
		wdgpt_error_logs();
	}

	/**
	 * Callback for admin addons menu.
	 */
	public function wdgpt_addons_callback() {
		wdgpt_addons();
	}
}

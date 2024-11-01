<?php
/**
 * This file is reponsible to show messages inside the admin area.
 *
 * @package Webdigit
 */

/**
 * Class to log warnings.
 */
class WDGPT_Admin_Notices {
	/**
	 * Message to be displayed in a warning.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Severity level.
	 *
	 * @var int
	 */
	private $severity;

	/**
	 * Whether the warning is dismissible.
	 *
	 * @var bool
	 */
	private $is_dismissible;

	/**
	 * Initialize class.
	 *
	 * @param int    $severity Severity level.
	 * @param string $message Message to be displayed in a warning.
	 * @param bool   $is_dismissible Whether the warning is dismissible.
	 */
	public function __construct( $severity, $message, $is_dismissible = true ) {
		$this->message        = $message;
		$this->severity       = $severity;
		$this->is_dismissible = $is_dismissible;
		$this->render();
	}

	/**
	 * Get severity class.
	 *
	 * @return string
	 * @since 1.0.0
	 * 0 - info
	 * 1 - error
	 * 2 - success
	 * 3 - warning
	 */
	private function get_severity() {
		switch ( $this->severity ) {
			case 0:
				return 'notice-info';
			case 1:
				return 'notice-error';
			case 2:
				return 'notice-success';
			case 3:
				return 'notice-warning';
			default:
				return 'notice-info';
		}
	}

	/**
	 * Displays warning on the admin screen.
	 *
	 * @return void
	 */
	public function render() {
		$is_dismissible = $this->is_dismissible ? 'is-dismissible' : '';

		// Check if the message contains a link.
		$message = $this->message;
		if ( preg_match( '/\[(.*?)\]\((.*?)\)/', $message, $matches ) ) {
			$text_to_show = $matches[1];
			$link         = $matches[2];
			$message      = str_replace( $matches[0], '<a target ="_blank" href="' . esc_url( $link ) . '">' . esc_html( $text_to_show ) . '</a>', $message );
		}

		printf( '<div class="notice %s %s"><p>%s</p></div>', esc_html( $this->get_severity() ), esc_html( $is_dismissible ), $message );
	}
}

<?php
/**
 * This file is responsible for generating custom tooltips.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to generate custom tooltips.
 */
class WDGPT_Custom_Tooltip {
	/**
	 * The text to be displayed in the tooltip.
	 *
	 * @var $text
	 */
	private $text;

	/**
	 * Constructor.
	 *
	 * @param string $text The text to be displayed in the tooltip.
	 */
	public function __construct( $text ) {
		$this->text = $text;
	}

	/**
	 * Render the tooltip.
	 *
	 * @param bool $red Whether the tooltip should be red.
	 */
	public function render( $red = false ) {
		$red_class = esc_html( $red ) ? 'red' : 'classic';
		?>
		<div class="tooltip-container">
			<i class="fa fa-info-circle"></i>
			<span class="tooltiptext <?php echo esc_html( $red_class ); ?>"><?php echo esc_html( $this->text ); ?></span>
		</div>
		<?php
	}

	/**
	 * Get the HTML of the tooltip.
	 *
	 * @param bool $red Whether the tooltip should be red.
	 * @return string
	 */
	public function get_html( $red = false ) {
		$icon_class = 'fa fa-info-circle';
		$red_class  = esc_html( $red ) ? 'red' : 'classic';
		return '<div class="tooltip-container"><i class="' . esc_html( $icon_class ) . ' ' . esc_html( $red_class ) . '"></i><span class="tooltiptext ' . esc_html( $red_class ) . '">' . esc_html( $this->text ) . '</span></div>';
	}
}
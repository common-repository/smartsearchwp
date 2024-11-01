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
 * Generate the admin dashboard.
 */
function wdgpt_addons() {
	$active_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
	$active_tab = $active_tab ? $active_tab : 'wdgpt_addons_manager';
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<a href="?page=wdgpt_addons&tab=wdgpt_addons_manager" class="nav-tab <?php echo 'wdgpt_addons_manager' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Addons manager', 'webdigit-chatbot' ); ?></a>
			<a href="?page=wdgpt_addons&tab=wdgpt_license" class="nav-tab <?php echo 'wdgpt_license' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'License settings', 'webdigit-chatbot' ); ?></a>
		</h2>

		<form method="post" enctype="multipart/form-data">
		<?php
		switch ( $active_tab ) {
			case 'wdgpt_addons_manager':
				settings_fields( 'wdgpt_addons_manager' );
				do_settings_sections( 'wdgpt_addons_manager' );
				break;
			case 'wdgpt_license':
				settings_fields( 'wdgpt_license' );
				do_settings_sections( 'wdgpt_license' );
				break;
			default:
				settings_fields( 'wdgpt_addons_manager' );
				do_settings_sections( 'wdgpt_addons_manager' );
				break;
		}
		?>
		</form>
	</div>
	<?php
}

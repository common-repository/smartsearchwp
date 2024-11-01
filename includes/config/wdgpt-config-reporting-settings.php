<?php
/**
 * General settings for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/** Show a success message if the form was submitted
 *
 * @param string $option_name The name of the option.
 */
function wdgpt_reporting_settings_save_option( $option_name ) {
	if ( ! isset( $_POST['wdgpt_reporting_nonce'] ) ||
	! wp_verify_nonce( sanitize_key( $_POST['wdgpt_reporting_nonce'] ), 'wdgpt_reporting' ) ) {
		wp_die( 'Security check failed' );
	}

	$opt_name_sanitize = isset( $_POST[ $option_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option_name ] ) ) : '';
	update_option( $option_name, $opt_name_sanitize );
}

/**
 * Save the options.
 */
function wdgpt_reporting_settings_save_options() {
	wdgpt_reporting_settings_save_option( 'wdgpt_reporting_activation' );
	$activation = get_option( 'wdgpt_reporting_activation', '' );
	wdgpt_reporting_settings_save_option( 'wdgpt_reporting_mail_from' );
	wdgpt_reporting_settings_save_option( 'wdgpt_reporting_mails' );
	wdgpt_reporting_settings_save_option( 'wdgpt_reporting_schedule' );

	$cron_scheduler = new WDGPT_Cron_Scheduler();
	if ( 'on' === $activation ) {
		$cron_scheduler->activate_cron( 'wdgpt_reporting_cron_hook' );
	} else {
		$cron_scheduler->disable_cron( 'wdgpt_reporting_cron_hook' );
	}

	new WDGPT_Admin_Notices( 2, __( 'Settings saved successfully !', 'webdigit-chatbot' ) );
}

/**
 * The reporting section callback.
 */
function wdgpt_reporting_section_callback() {
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
	if ( isset( $_POST['submit'] ) ) {
		wdgpt_reporting_settings_save_options();
	}
	?>
	<?php wp_nonce_field( 'wdgpt_reporting', 'wdgpt_reporting_nonce' ); ?>

	<table class="form-table">
	<tr>
	<p>
		<?php
		esc_html_e(
			'This section is dedicated to the reporting settings. Here you can set the mails to send the reports to and the schedule of sending the reports.',
			'webdigit-chatbot'
		);
		?>
	</p>
	<p>
		<?php
		esc_html_e(
			'Please note that, if there has not been any discussion with the chatbot in the previous day or week, no report will be sent. The reports are sent only if there has been a discussion with the chatbot.',
			'webdigit-chatbot'
		);
		?>
	</p>
	</tr>
	<tr>
			<th scope="row">
			<?php
			esc_html_e(
				'Enable reporting:',
				'webdigit-chatbot'
			);
			?>
			</th>
			<td>
				<label class="switch">
					<input type="checkbox" id="wdgpt_reporting_activation" name="wdgpt_reporting_activation" <?php echo ( get_option( 'wdgpt_reporting_activation', '' ) === 'on' ) ? 'checked' : ''; ?>>
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
	<tr>
		<th scope="row">
			<?php
			esc_html_e(
				'Mail from:',
				'webdigit-chatbot'
			);
			?>
		</th>
		<td>
			<input type="text" name="wdgpt_reporting_mail_from" id="wdgpt_reporting_mail_from" value="<?php echo esc_attr( get_option( 'wdgpt_reporting_mail_from', get_option( 'admin_email' ) ) ); ?>" />
			<p id="wdgpt_mail_from_error">
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
				'Please enter the mail from which the reports are sent. It is set by default as the administrator mail, but please use a valid mail from your domain.',
				'webdigit-chatbot'
			);
			?>
		</tr>
	<tr>
		<th scope="row">
			<?php
			esc_html_e(
				'Mails to send the reports to:',
				'webdigit-chatbot'
			);
			?>
		</th>
		<td>
			<input type="text" name="wdgpt_reporting_mails" id="wdgpt_reporting_mails" value="<?php echo esc_attr( get_option( 'wdgpt_reporting_mails', '' ) ); ?>" />
			<p id="wdgpt_mail_error">
			<?php
			esc_html_e(
				'The emails are not valid. Please enter valid emails.',
				'webdigit-chatbot'
			);
			?>
			</p>
			<p class="description">
			<?php
			esc_html_e(
				'Please enter the mails to send the reports to. You can enter multiple mails separated by a comma.',
				'webdigit-chatbot'
			);
			?>
		</td>
	</tr>
	<!-- Create a tr for the schedule of sending the reports. It should be a dropdown with the options: daily, weekly, monthly. -->
	<tr>
		<th scope="row">
			<?php
			esc_html_e(
				'Schedule of sending the reports:',
				'webdigit-chatbot'
			);
			?>
		</th>
		<td>
			<select name="wdgpt_reporting_schedule">
				<option value="daily" <?php selected( get_option( 'wdgpt_reporting_schedule', '' ), 'daily' ); ?>>
				<?php
				esc_html_e(
					'Daily',
					'webdigit-chatbot'
				);
				?>
				</option>
				<option value="weekly" <?php selected( get_option( 'wdgpt_reporting_schedule', '' ), 'weekly' ); ?>>
				<?php
				esc_html_e(
					'Weekly',
					'webdigit-chatbot'
				);
				?>
				</option>
				</option>
			</select>
			<p class="description">
			<?php
			esc_html_e(
				'Please select the schedule of sending the reports.',
				'webdigit-chatbot'
			);
			?>
		</td>
	</tr>
	<?php
	$next_scheduled_timestamp = wp_next_scheduled( 'wdgpt_reporting_cron_hook' );
	if ( $next_scheduled_timestamp ) {
		?>
		<tr>
		<!-- Retrieve the cron schedule in the database and display it here. -->
		<th scope="row">
			<?php
			esc_html_e(
				'Cron schedule:',
				'webdigit-chatbot'
			);
			?>
		</th>
		<td>
			<p>
			<?php
			esc_html_e(
				'The cron schedule is: ',
				'webdigit-chatbot'
			);
			?>
			<?php
			echo esc_attr( date( 'Y-m-d H:i:s', $next_scheduled_timestamp ) );
			?>
			</p>
	
		</tr>
		<?php
	}
	?>
   
  
	</table>
	<p>
		<?php
		esc_html_e(
			'If you deactivate the plugin, the reporting will be put on hold, but will resume when you activate it again.',
			'webdigit-chatbot'
		);
		?>
	</p>
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
?>
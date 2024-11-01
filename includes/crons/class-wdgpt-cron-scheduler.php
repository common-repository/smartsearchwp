<?php
/**
 * This file is reponsible to manage crons for the plugin.
 *
 * @package Webdigit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to schedule crons.
 */
class WDGPT_Cron_Scheduler {

	/**
	 * The cron functions.
	 *
	 * @var array
	 */
	private $cron_functions = array(
		'wdgpt_reporting_cron_hook' =>
		array(
			'cron_start' => 'wdgpt_reporting_next_report_date',
			'schedule'   => 'wdgpt_reporting_schedule',
			'activation' => 'wdgpt_reporting_activation',
			'mails'      => 'wdgpt_reporting_mails',
		),
	);

	/**
	 * Initialize class.
	 */
	public function __construct() {
	}

	/**
	 * Disable all the crons.
	 */
	public function disable_all_crons() {
		foreach ( $this->cron_functions as $cron_hook => $cron_function ) {
			if ( wp_next_scheduled( $cron_hook ) ) {
				wp_clear_scheduled_hook( $cron_hook );
			}
		}
	}

	/**
	 * Retrieve the active crons.
	 *
	 * @return array
	 */
	public function retrieve_active_crons() {
		$active_crons = array();
		foreach ( $this->cron_functions as $cron_hook => $cron_function ) {
			if ( wp_next_scheduled( $cron_hook ) ) {
				$active_crons[] = $cron_hook;
			}
		}
		return $active_crons;
	}

	/**
	 * Activate all the crons.
	 */
	public function activate_all_crons() {
		foreach ( $this->cron_functions as $cron_function ) {
			$this->activate_cron( $cron_function );
		}
	}

	/**
	 * Reactivate all the crons.
	 *
	 * @param array $active_crons The active crons.
	 */
	public function reactivate_crons( $active_crons ) {
		foreach ( $active_crons as $cron_hook ) {
			$this->activate_cron( $cron_hook );
		}
	}

	/**
	 * Activate a cron.
	 *
	 * @param string $cron_function The cron function.
	 */
	public function activate_cron( $cron_function ) {
		$cron_rules = $this->cron_functions[ $cron_function ];
		/**
		 * Retrieve the schedule and the start date from the options.
		 */
		$schedule = get_option( $cron_rules['schedule'], '' );
		/**
		 * If $schedule is weekly, set the time to the next friday at 6PM.
		 * If $schedule is daily, set the time to the next day at 6PM.
		 */
		if ( 'weekly' === $schedule ) {
			$next_friday = strtotime( 'next friday' );
			$cron_start  = strtotime( date( 'Y-m-d', $next_friday ) . ' 18:00:00' );
		} elseif ( date( 'H' ) < 18 ) {
				$cron_start = strtotime( date( 'Y-m-d' ) . ' 18:00:00' );
		} else {
			$next_day   = strtotime( 'tomorrow' );
			$cron_start = strtotime( date( 'Y-m-d', $next_day ) . ' 18:00:00' );
		}

		if ( ! wp_next_scheduled( $cron_function ) ) {
			wp_schedule_event( $cron_start, $schedule, $cron_function );
		} else {
			wp_clear_scheduled_hook( $cron_function );
			wp_schedule_event( $cron_start, $schedule, $cron_function );
		}
	}

	/**
	 * Disable a cron.
	 *
	 * @param string $cron_function The cron function.
	 */
	public function disable_cron( $cron_function ) {
		if ( wp_next_scheduled( $cron_function ) ) {
			wp_clear_scheduled_hook( $cron_function );
		}
	}
}

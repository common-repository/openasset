<?php
/**
 * Scheduling class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

use OpenAsset\API\OpenAssetAPI;
use OpenAsset\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Scheduling
 */
class Scheduling {
	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';
	const OPTION_DATA_KEY     = 'openasset_data';

	/**
	 * OpenAsset API manager.
	 *
	 * @var OpenAssetAPI
	 */
	public $open_asset_api_manager;

	/**
	 * Settings.
	 *
	 * @var settings;
	 */
	public $settings;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->open_asset_api_manager = new OpenAssetAPI();

		$this->settings = new Settings();

		add_filter( 'cron_schedules', array( $this, 'custom_cron_intervals' ) );
		add_action( 'openasset_feed_refresh', array( $this, 'update_open_asset_feed' ) );
		add_action( 'run_openasset_sync', array( $this, 'check_and_update_data' ) );
	}

	/**
	 * Create custom cron intervals.
	 *
	 * @param array $schedules list of cron intervals.
	 *
	 * @return $schedules
	 */
	public function custom_cron_intervals( $schedules ) {
		$schedules['openasset_30'] = array(
			'interval' => 30 * 60, // Convert minutes to seconds.
			'display'  => __( 'Every 30 minutes', 'openasset' ),
		);

		$schedules['openasset_60'] = array(
			'interval' => 60 * 60, // Convert minutes to seconds.
			'display'  => __( 'Every 60 minutes', 'openasset' ),
		);

		$schedules['openasset_8'] = array(
			'interval' => 8 * 60 * 60, // Convert hours to seconds.
			'display'  => __( 'Every 8 hours', 'openasset' ),
		);

		$schedules['openasset_24'] = array(
			'interval' => 24 * 60 * 60, // Convert hours to seconds.
			'display'  => __( 'Every 24 hours', 'openasset' ),
		);
		$schedules['every_minute'] = array(
			'interval' => 60, // In seconds.
			'display'  => __( 'Every Minute', 'openasset' ),
		);
		return $schedules;
	}

	/**
	 * Reschedule OpenAsset Feed refresh.
	 *
	 * @param string $frequency new frequency to set.
	 *
	 * @return bool|WP_Error
	 */
	public function reschedule_open_asset_feed_refresh_frequency( $frequency ) {
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Rescheduling OA feed refresh frequency.' );
		}

		if ( ! is_string( $frequency ) && '' !== $frequency ) {
			$frequency = 'openasset_24';
		}

		$settings = get_option( self::OPTION_SETTINGS_KEY );
		// if ( ! empty( $settings ) && ! empty( $settings['general-settings'] && $frequency === $settings['general-settings']['feed-frequency'] ) ) {
		// 	if ( OPENASSET_ENABLE_LOGGING ) {
		// 		error_log( 'Did not reschedule as the time frequency is the same.' );
		// 	}
		// 	return;
		// }

		// Clear the existing schedule.
		$timestamp = wp_next_scheduled( 'openasset_feed_refresh' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'openasset_feed_refresh' );
		}

		if ( 'none' === $frequency ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( 'Rescheduling OA feed refresh frequency completed. Auto syncing is turned off.' );
			}
			return true;
		}

		$start_time = 30 * 60;
		if ( 'openasset_60' === $frequency ) {
			$start_time = 60 * 60;
		} elseif ( 'openasset_8' === $frequency ) {
			$start_time = 8 * 60 * 60;
		} elseif ( 'openasset_24' === $frequency ) {
			$start_time = 24 * 60 * 60;
		}

		// Schedule the new event with the updated interval.
		$result = wp_schedule_event( time() + $start_time, $frequency, 'openasset_feed_refresh' );
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Rescheduling OA feed refresh frequency completed.' );
		}
		return $result;
	}

	/**
	 * Update OpenAsset feed.
	 *
	 * @return void
	 */
	public function update_open_asset_feed() {
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Updating OpenAsset feed.' );
		}

		$data_fields = array(
			array(
				'type'  => 'fields',
				'url'   => '/Fields?alive=1&deleted=0&limit=0&field_type=employee&orderBy=display_order',
				'field' => 'employee',
			),
			array(
				'type'  => 'fields',
				'url'   => '/Fields?alive=1&deleted=0&limit=0&field_type=project&orderBy=display_order',
				'field' => 'project',
			),
			array(
				'type'  => 'grid-columns',
				'url'   => $this->generate_grid_columns_request_url(),
				'field' => null,
			),
			array(
				'type'  => 'project-keyword-categories',
				'url'   => '/ProjectKeywordCategories?limit=0&orderBy=display_order',
				'field' => null,
			),
		);

		foreach ( $data_fields as $data ) {
			$this->open_asset_api_manager->get_data( $data['type'], $data['url'], $data['field'] );
		}

		update_option( 'openasset_sync_running', 1 );

		if ( ! wp_next_scheduled( 'run_openasset_sync' ) ) {
			wp_schedule_event( time(), 'every_minute', 'run_openasset_sync' );
		}

		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Set openasset_sync_running to true.' );
		}
	}

	/**
	 * Get the OpenAsset feed refresh frequency.
	 *
	 * @return void
	 */
	public function sync_posts() {
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( 'Sync has started.' );
			error_log( 'exact time: ' . gmdate( 'y-m-d h:i:s', time() ) );
		}
		// Update posts.
		$this->settings->handle_posts();
	}

	/**
	 * Check and update data.
	 *
	 * @return void
	 */
	public function check_and_update_data() {
		// Check if the sync is running.
		if ( get_option( 'openasset_sync_running' ) ) {
			$this->sync_posts();
		} else {
			// If the sync should no longer run, clear the scheduled hook.
			$timestamp = wp_next_scheduled( 'run_openasset_sync' );
			wp_unschedule_event( $timestamp, 'run_openasset_sync' );
			update_option( 'openasset_sync_running', 0 );
		}
	}

	/**
	 * Generate request url for gird columns.
	 *
	 * @return string
	 */
	public function generate_grid_columns_request_url() {
		$roles_field_id = $this->open_asset_api_manager->get_roles_field_id();

		if ( $roles_field_id ) {
			return "/Fields/{$roles_field_id}/GridColumns?order_by=display_order";
		} else {
			return '/GridColumns?deleted=0&alive=1&limit=0&orderBy=display_order';
		}

	}

}

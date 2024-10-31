<?php
/**
 * Options API registration class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\API;

use OpenAsset\Admin\Scheduling;
use OpenAsset\Admin\Settings;
use OpenAsset\Core\Options;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OptionsAPI
 */
class OptionsAPI {
	/**
	 * Routes Namespace.
	 *
	 * @var $namespace
	 */
	private $namespace = OPENASSET_SLUG;

	/**
	 * Available routes.
	 *
	 * @var $endpoints
	 */
	private $routes;

	/**
	 * Scheduling.
	 *
	 * @var Scheduling;
	 */
	public $scheduling;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->scheduling = new Scheduling();

		// Define routes.
		$this->define_routes();

		// Register v1 routes.
		add_action( 'rest_api_init', array( $this, 'register_v1_routes' ) );
	}


	/**
	 * Defines REST Routes.
	 *
	 * @return void
	 */
	public function define_routes() {
		$this->routes = array(
			'get-settings'       => array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => 'get_settings',
			),
			'set-settings'       => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'set_settings',
			),
			'delete-settings'    => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'delete_settings',
			),
			'get-data'       => array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => 'get_data',
			),
			'set-data'       => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'set_data',
			),
			'delete-data'    => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'delete_data',
			),
			'run'       => array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => 'update_data',
			),
			'check'     => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'check_credentials',
			),
			'frequency' => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'update_feed_frequency',
			),
			'sort' => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'update_sort_values',
			),
			'sync-status' => array(
				'method'   => WP_REST_Server::READABLE,
				'callback' => 'get_sync_status',
			),
			'stop-sync' => array(
				'method'   => WP_REST_Server::CREATABLE,
				'callback' => 'force_stop_sync',
			),
		);
	}

	/**
	 * Registers v1 REST routes.
	 *
	 * @return void
	 */
	public function register_v1_routes() {
		foreach ( $this->routes as $route => $details ) {
			register_rest_route(
				"{$this->namespace}/v1",
				"/options/{$route}",
				array(
					'methods'             => $details['method'],
					'callback'            => array( $this, $details['callback'] ),
					'permission_callback' => array( $this, 'rest_permission_check' ),
					'args'                => array(),
				)
			);
		}
	}

	/**
	 * Checks if a request has access to update a setting
	 *
	 * @return WP_Error|bool
	 */
	public function rest_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get settings options.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ) {
		$key = $request->get_param( 'key' );

		$options = Options::get_instance();

		if ( ! empty( $key ) ) {
			if ( $options->has( $key, 'settings' ) ) {
				$result = $options->get( $key, false, 'settings' );
			} else {
				return new WP_Error( 'option_error', __( 'Invalid or expired option name.', 'openasset' ) );
			}
		} else {
			$result = $options->get();
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Get data options.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_data( WP_REST_Request $request ) {
		$key = $request->get_param( 'key' );

		$options = Options::get_instance();

		if ( ! empty( $key ) ) {
			if ( $options->has( $key, 'data' ) ) {
				$result = $options->get( $key, false, 'data' );
			} else {
				return new WP_Error( 'option_error', __( 'Invalid or expired option name.', 'openasset' ) );
			}
		} else {
			$result = $options->get( false, false, 'data' );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Set settings options.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function set_settings( WP_REST_Request $request ) {
		$settings = $request->get_param( 'options' );

		$options = Options::get_instance();

		if ( ! empty( $settings ) && is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				$options->set( $key, $value, 'settings' );
			}
		} else {
			return new WP_Error( 'settings_error', __( 'No settings provided.', 'openasset' ) );
		}

		return new WP_REST_Response(
			array(
				'message' => __( 'Settings updated.', 'openasset' ),
			),
			200
		);
	}

	/**
	 * Set data options.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function set_data( WP_REST_Request $request ) {
		$settings = $request->get_param( 'options' );

		$options = Options::get_instance();

		if ( ! empty( $settings ) && is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				$options->set( $key, $value, 'data' );
			}
		} else {
			return new WP_Error( 'data_error', __( 'No data provided.', 'openasset' ) );
		}

		return new WP_REST_Response(
			array(
				'message' => __( 'Data updated.', 'openasset' ),
			),
			200
		);
	}

	/**
	 * Delete settings
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_settings( WP_REST_Request $request ) {
		$key = $request->get_param( 'key' );

		$options = Options::get_instance();

		if ( ! empty( $key ) ) {
			if ( $options->has( $key, 'settings' ) ) {
				$options->delete( $key, 'settings' );
			} else {
				return new WP_Error( 'option_error', __( 'Invalid or expired option name.', 'openasset' ) );
			}
		} else {
			return new WP_Error( 'option_error', __( 'No option key is provided.', 'openasset' ) );
		}

		return new WP_REST_Response(
			array(
				'message' => __( 'Setting deleted.', 'openasset' ),
			),
			200
		);
	}

	/**
	 * Delete data
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_data( WP_REST_Request $request ) {
		$key = $request->get_param( 'key' );

		$options = Options::get_instance();

		if ( ! empty( $key ) ) {
			if ( $options->has( $key, 'data' ) ) {
				$options->delete( $key, 'data' );
			} else {
				return new WP_Error( 'option_error', __( 'Invalid or expired option name.', 'openasset' ) );
			}
		} else {
			return new WP_Error( 'option_error', __( 'No option key is provided.', 'openasset' ) );
		}

		return new WP_REST_Response(
			array(
				'message' => __( 'Data deleted.', 'openasset' ),
			),
			200
		);
	}

	/**
	 * Run the API calls to update the feed.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_data() {
		$updating = get_option( 'openasset_sync_running', 0 );

		if ( $updating == 1 ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Syncing is already running', 'openasset' ),
				),
				200
			);
		}

		// reset error count.
		update_option( 'openasset_sync_error_count', 0 );
		$this->scheduling->update_open_asset_feed();

		return new WP_REST_Response(
			array(
				'message' => __( 'Syncing has started', 'openasset' ),
			),
			200
		);
	}

	/**
	 * Check API Credentials.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function check_credentials( WP_REST_Request $request ) {
		$open_asset_api_manager = new OpenAssetAPI();

		$body = $request->get_body();
		$data = json_decode( $body, true );

		$token_id            = sanitize_text_field( $data['token_id'] );
		$api_token           = sanitize_text_field( $data['api_token'] );
		$client_instance_url = sanitize_text_field( $data['client_instance_url'] );

		if ( str_contains( $client_instance_url, 'www.' ) ) {
			$client_instance_url = str_replace( 'www.', '', $client_instance_url );
		}

		if ( str_contains( $client_instance_url, 'http://' ) ) {
			$client_instance_url = str_replace( 'http://', 'https://', $client_instance_url );
		}

		// If the url does not contain 'https://', prepend it.
		if ( ! str_contains( $client_instance_url, 'https://' ) ) {
			$client_instance_url = 'https://' . $client_instance_url;
		}

		$url = $client_instance_url . 'REST/1/Employees?limit=1';

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'OATU ' . $token_id . ':' . $api_token,
					'User-Agent'    => 'OpenAsset WordPress/' . OPENASSET_VERSION,
				),
				'timeout' => 15, // Increase if needed.
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error making the request' );
		} else {
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$settings = get_option( 'openasset_settings' );

				$settings['check-credentials'] = true;

				// set defaults.
				$settings['general-settings']['enable-logging'] = 'false';

				$settings['general-settings']['feed-frequency'] = 'none';

				$timestamp = wp_next_scheduled( 'openasset_feed_refresh' );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, 'openasset_feed_refresh' );
				}

				$settings['general-settings']['project-name-plural']    = 'Projects';
				$settings['general-settings']['project-name-singular']  = 'Project';
				$settings['general-settings']['project-url-key']        = 'projects';
				$settings['general-settings']['project-show']           = 'no';
				$settings['general-settings']['employee-name-plural']   = 'Employees';
				$settings['general-settings']['employee-name-singular'] = 'Employee';
				$settings['general-settings']['employee-url-key']       = 'employees';
				$settings['general-settings']['employee-show']          = 'no';

				$settings['data-options']['employee-order-by']                      = 'Asc';
				$settings['data-options']['employee-images-tagged-show-on-website'] = 'yes';
				$settings['data-options']['maximum-images-per-employee']            = '0';
				$settings['data-options']['employee-images-sort-by']                = 'created';
				$settings['data-options']['employee-images-order']                  = 'Desc';

				$settings['data-options']['project-order-by']                      = 'Asc';
				$settings['data-options']['project-images-tagged-show-on-website'] = 'yes';
				$settings['data-options']['maximum-images-per-project']            = '4';
				$settings['data-options']['project-images-sort-by']                = 'created';
				$settings['data-options']['project-images-order-by']               = 'Desc';

				$settings['data-options']['file-options'] = array();

				update_option( 'openasset_settings', $settings );

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
						'url'   => $this->scheduling->generate_grid_columns_request_url(),
						'field' => null,
					),
					array(
						'type'  => 'project-keyword-categories',
						'url'   => '/ProjectKeywordCategories?limit=0&orderBy=display_order',
						'field' => null,
					),
				);

				foreach ( $data_fields as $data ) {
					$open_asset_api_manager->get_data( $data['type'], $data['url'], $data['field'] );
				}

				$data_options = get_option( 'openasset_data' );

				$settings = get_option( 'openasset_settings' );

				$settings['data-options']['employee-criteria-fields'] = array();
				$settings['data-options']['project-criteria-fields']  = array();

				foreach ( $data_options['fields']['employee'] as $field ) {
					if ( 'first_name' === $field['rest_code'] ) {
						$settings['data-options']['employee-criteria-fields'][] = $field['id'];
						$settings['data-options']['employee-sort-by']           = $field['id'];
					}
					if ( 'last_name' === $field['rest_code'] ) {
						$settings['data-options']['employee-criteria-fields'][] = $field['id'];
					}
				}
				foreach ( $data_options['fields']['project'] as $field ) {
					if ( 'name' === $field['rest_code'] ) {
						$settings['data-options']['project-criteria-fields'][] = $field['id'];
						$settings['data-options']['project-sort-by']           = $field['id'];
					}
				}

				update_option( 'openasset_settings', $settings );

				wp_send_json_success();
			} elseif ( 402 === wp_remote_retrieve_response_code( $response ) ) {
				wp_send_json_error( 'Licence Required' );
			} elseif ( 401 === wp_remote_retrieve_response_code( $response ) ) {
				wp_send_json_error( 'Invalid Token ID' );
			} else {
				wp_send_json_error( 'Invalid credentials' );
			}
		}
	}

	/**
	 * Update feed frequency.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_feed_frequency( WP_REST_Request $request ) {
		$body = $request->get_body();
		$data = json_decode( $body, true );

		$frequency = $data['frequency'];

		$response   = $this->scheduling->reschedule_open_asset_feed_refresh_frequency( $frequency );

		if ( $response ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Updated feed frequency.', 'openasset' ),
				),
				200
			);
		} else {
			wp_send_json_error( $response );
		}
	}

	/**
	 * Update sort values.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_sort_values( WP_REST_Request $request ) {
		$body = $request->get_body();
		$data = json_decode( $body, true );

		$settings = new Settings();
		$response = $settings->add_sort_value_to_post( $data['sort-by'] );

		if ( $response ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Updated sort values.', 'openasset' ),
				),
				200
			);
		} else {
			wp_send_json_error( $response );
		}
	}

	/**
	 * Get Sync Status.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_sync_status() {
		return new WP_REST_Response(
			array(
				'projectsSynced'  => get_option( 'openasset_last_project_updated', -1 ) + 1,
				'employeesSynced' => get_option( 'openasset_last_employee_updated', -1 ) + 1,
				'totalProjects'   => get_option( 'openasset_total_project_count', 0 ),
				'totalEmployees'  => get_option( 'openasset_total_employee_count', 0 ),
				'syncRunning'     => get_option( 'openasset_sync_running', 0 ),
			),
			200
		);
	}

	/**
	 * Force Stop Sync.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function force_stop_sync() {
		$updating = update_option( 'openasset_sync_running', 0 );
		return new WP_REST_Response(
			array(
				'message' => __( 'Sync is stopping.', 'openasset' ),
			),
			200
		);
	}
}

<?php
/**
 * Options API registration class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\API;

use OpenAsset\Core\Options;
use OpenAsset\Core\Helpers;
use WP_Error;
use OpenAsset\API\OptionsAPI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OpenAssetAPI
 */
class OpenAssetAPI {
	/**
	 * API url head.
	 *
	 * @var string $url_head
	 */
	private $url_head;

	/**
	 * API auth for header.
	 *
	 * @var string $auth
	 */
	private $auth;

	/**
	 * API header args.
	 *
	 * @var array $header_args
	 */
	private $header_args;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$settings = get_option( 'openasset_settings' );

		if ( empty( $settings ) || empty( $settings['general-settings'] ) ) {
			return;
		}

		$helpers = new Helpers();

		$token_id            = isset( $settings['general-settings']['your-real-token-id'] ) ? $settings['general-settings']['your-real-token-id'] : '';
		$api_token           = isset( $settings['general-settings']['your-real-api-token'] ) ? $settings['general-settings']['your-real-api-token'] : '';
		$api_token           = $helpers->decrypt_password( $api_token, OPENASSET_ENCRYPTION_KEY );
		$client_instance_url = isset( $settings['general-settings']['client-instance-url'] ) ? $settings['general-settings']['client-instance-url'] : '';

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

		$this->url_head = $client_instance_url . 'REST/1';
		$this->auth     = 'OATU ' . $token_id . ':' . $api_token;

		$this->header_args = array(
			'headers' => array(
				'timeout'       => 30,
				'Authorization' => $this->auth,
				'User-Agent'    => 'OpenAsset WordPress/' . OPENASSET_VERSION,
			),
		);

	}

	/**
	 * Get data for specific type.
	 *
	 * @param string $type Type of data to get.
	 * @param string $url  URL to get data from.
	 * @param string $field Field to get data from.
	 *
	 * @return void
	 */
	public function get_data( $type, $url, $field = null ) {
		$complete_url = $this->url_head . $url;

		$data = get_option( 'openasset_data' );

		if ( empty( $data ) ) {
			$data = array();
		}

		if ( empty( $data[ $type ] ) ) {
			$data[ $type ] = array();
		}

		try {

			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$response_body = json_decode( $response['body'], true );

				if ( null === $field ) {
					$data[ $type ] = $response_body;
				} else {
					$data[ $type ][ $field ] = $response_body;
				}

				update_option( 'openasset_data', $data );
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
			}
		}
	}

	/**
	 * Return data for specific API URL.
	 *
	 * @param string $url  URL to get data from.
	 *
	 * @return array
	 */
	public function return_data( $url ) {
		$complete_url       = $this->url_head . $url;
		$error_count_option = 'openasset_sync_error_count'; // Option name to store the error count.

		try {
			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( 'Requesting URL: ' . $complete_url );
				error_log( 'Response Code: ' . wp_remote_retrieve_response_code( $response ) );
			}

			if ( is_wp_error( $response ) ) {
				if ( OPENASSET_ENABLE_LOGGING ) {
					error_log( 'Error: ' . $response->get_error_message() );
				}
				// Increment the error count.
				$error_count = get_option( $error_count_option, 0 ) + 1;
				update_option( $error_count_option, $error_count );

				// Check if this is the third consecutive error.
				if ( $error_count >= 10 ) {
					$this->terminate_sync( 'Sync terminated due to repeated API errors.' );
					// Optionally add an admin notice.
					return null;
				}
			} else {
				// Reset the error count if a successful response is received.
				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					update_option( $error_count_option, 0 );
					$response_body = json_decode( $response['body'], true );
					return $response_body;
				}
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
				error_log( 'Error fetching data from OpenAsset. Sync terminated.' );
			}
			$this->terminate_sync( 'Sync terminated due to an exception.' );
			return null;
		}
	}

	/**
	 * Terminate sync.
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function terminate_sync( $message ) {
		if ( OPENASSET_ENABLE_LOGGING ) {
			error_log( $message );
		}
		update_option( 'openasset_sync_running', 2 );
	}

	/**
	 * Get roles field id.
	 *
	 * @return int|null
	 */
	public function get_roles_field_id() {
		$query_params = array(
			'alive'      => 1,
			'deleted'    => 0,
			'limit'      => 0,
			'field_type' => 'employee2project',
			'rest_code'  => 'roles',
		);

		$query_string = http_build_query( $query_params );

		$url = "/Fields?{$query_string}";

		$complete_url = $this->url_head . $url;

		try {

			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$response_body = json_decode( $response['body'], true );
				return $response_body[0]['id'];
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
			}

			return null;
		}
	}

	/**
	 * Get data for specific image.
	 *
	 * @param int $id ID of image.
	 *
	 * @return array
	 */
	public function get_individual_file_data( $id ) {
		$url          = '/Files?access_level=1&limit=0&sizes[limit]=0&id=' . $id;
		$complete_url = $this->url_head . $url;

		try {

			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$body = json_decode( $response['body'], true );
				return $body[0];
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
			}
		}
	}

	/**
	 * Get hero image data of project or employee.
	 *
	 * @param int $hero_id ID of image.
	 *
	 * @return array
	 */
	public function get_hero_image_data( $hero_id ) {
		$settings = get_option( 'openasset_settings' );

		$file_options = $settings['data-options']['file-options'] ? $settings['data-options']['file-options'] : array();
		$file_options = implode( ',', $file_options );

		$query_params = array(
			'access_level'   => 1,
			'is_vr'          => 0,
			'contains_audio' => 0,
			'contains_video' => 0,
			'displayFields'  => 'id,md5_at_upload,' . $file_options,
			'remoteFields'   => $file_options,
			'sizes' => array(
				'displayFields' => 'id,md5_at_upload,width,height,http_root,http_relative_path',
				'file_format'   => array( 'png', 'jpg', 'gif' ),
				'width'         => '<=2000',
				'height'        => '<=2000',
				'limit'         => 0,
				'filesize'      => '<=3145728',
			),
			'id'             => $hero_id,
		);

		$query_string = http_build_query( $query_params );

		$url = "/Files?{$query_string}";

		$complete_url = $this->url_head . $url;

		try {

			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$body = json_decode( $response['body'], true );
				if ( is_array( $body ) && isset( $body[0] ) ) {
					return $body[0];
				} else {
					if ( OPENASSET_ENABLE_LOGGING ) {
						error_log( 'No hero image returned from server.' );
					}
					return array();
				}
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
			}
		}
	}

	/**
	 * Get all image data of project or employee.
	 *
	 * @param string $post_type Type of post.
	 * @param int    $openasset_id ID of project or employee.
	 * @param int    $image_limit Limit of images to get.
	 * @param string $show_on_website Whether to show on website or not.
	 * @return array
	 */
	public function get_all_image_data( $post_type, $openasset_id, $image_limit, $show_on_website ) {
		$settings = get_option( 'openasset_settings' );

		$show_on_website = ( 'yes' === $show_on_website || true === $show_on_website ) ? '1' : '';

		$id_field = ( 'project' === $post_type ) ? 'project_id' : 'object_id';

		$project_display_order = ( 'project' === $post_type ) ? ',project_display_order' : '';

		$sort_by = isset( $settings['data-options'][ $post_type . '-images-sort-by' ] ) ? $settings['data-options'][ $post_type . '-images-sort-by' ] : 'created';

		$order_by = isset( $settings['data-options'][ $post_type . '-images-order-by' ] ) ? $settings['data-options'][ $post_type . '-images-order-by' ] : 'Desc';

		$file_options = isset( $settings['data-options']['file-options'] ) ? $settings['data-options']['file-options'] : array();
		$file_options = implode( ',', $file_options );

		++$image_limit;

		$query_params = array(
			'access_level'   => 1,
			'is_vr'          => 0,
			'contains_audio' => 0,
			'contains_video' => 0,
			'remoteFields'   => $file_options,
			'sizes' => array(
				'limit'         => 0,
				'file_format'   => array( 'jpg', 'gif', 'png' ),
				'height'        => '<=2000',
				'filesize'      => '<=3145728',
				'displayFields' => 'id,width,height,http_root,http_relative_path',
				'width'         => '<=2000',
			),
			'displayFields'  => "id,{$file_options},md5_at_upload,created,uploaded,updated,rank{$project_display_order}",
			$id_field        => $openasset_id,
			'limit'          => $image_limit,
			'orderBy'        => "{$sort_by}{$order_by}",
		);

		if ( $show_on_website ) {
			$query_params['show_on_website'] = $show_on_website;
		}

		$query_string = http_build_query( $query_params );
		$complete_url = $this->url_head . '/Files?' . $query_string;

		try {
			$response = wp_remote_get( $complete_url, $this->header_args );

			if ( ( ! is_wp_error( $response ) ) && ( 200 === wp_remote_retrieve_response_code( $response ) ) ) {
				$body = json_decode( $response['body'], true );
				return $body;
			}
		} catch ( \Exception $ex ) {
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( $ex->getMessage() );
			}
		}
	}
}

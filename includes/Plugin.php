<?php
/**
 * Primary class file for the OpenAsset.
 *
 * @package OpenAsset
 */

namespace OpenAsset;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OpenAsset\Core\Options;
use OpenAsset\Core\Helpers;
use OpenAsset\API\OptionsAPI;
use OpenAsset\API\OpenAssetAPI;
use OpenAsset\Admin\RegisterAdmin;
use OpenAsset\Admin\Setup;

/**
 * Class Plugin
 */
class Plugin {
	/**
	 * Options manager.
	 *
	 * @var Options
	 */
	public $options_manager;

	/**
	 * Helpers functions.
	 *
	 * @var helpers
	 */
	public $helpers;

	/**
	 * Options API manager.
	 *
	 * @var OptionsAPI
	 */
	public $options_api_manager;

	/**
	 * OpenAsset API manager.
	 *
	 * @var OpenAssetAPI
	 */
	public $open_asset_api_manager;


	/**
	 * Admin Manager.
	 *
	 * @var RegisterAdmin;
	 */
	public $admin_manager;

	/**
	 * Setup.
	 *
	 * @var Setup;
	 */
	public $setup;


	/**
	 * Constructor.
	 */
	public function __construct() {
		// Get options manager instance.
		$this->options_manager = Options::get_instance();

		// Get helpers instance.
		$this->helpers = new Helpers();

		// Register APIs.
		$this->options_api_manager    = new OptionsAPI();
		$this->open_asset_api_manager = new OpenAssetAPI();

		// Register Admin.
		$this->admin_manager = new RegisterAdmin();

		// Setup Hooks.
		$this->setup = new Setup();

		$this->register_hooks();

		$this->define_error_logging();
	}

	/**
	 * Registers core hooks.
	 */
	public function register_hooks() {
		/**
		 * Add "Dashboard" link to plugins page.
		 */
		add_filter(
			'plugin_action_links_' . OPENASSET_FOLDER . '/openasset.php',
			array( $this, 'action_links' )
		);
		add_filter( 'http_request_args', array( $this, 'increase_http_request_timeout' ) );
	}

	/**
	 * Increase HTTP request timeout.
	 *
	 * @param array $args HTTP request arguments.
	 * @return array
	 */
	public function increase_http_request_timeout( $args ) {
		$args['timeout'] = 30;  // Timeout in seconds.
		return $args;
	}

	/**
	 * Registers plugin action links.
	 *
	 * @param array $actions A list of actions for the plugin.
	 * @return array
	 */
	public function action_links( $actions ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=openasset#/dashboard' ) ) . '">' . __( 'Dashboard', 'openasset' ) . '</a>';
		array_unshift( $actions, $settings_link );

		return $actions;
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @access public
	 * @static
	 */
	public static function plugin_activation() {
		$settings = get_option( 'openasset_settings' );

		$general_settings = array();
		$data_options     = array();

		if ( isset( $settings['general-settings'] ) && ! empty( $settings['general-settings'] ) ) {
			return;
		}

		update_option(
			'openasset_settings',
			array(
				'general-settings' => $general_settings,
				'data-options'     => $data_options,
			),
			true
		);
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @access public
	 * @static
	 */
	public static function plugin_deactivation() {
	}

	/**
	 * Defines error logging.
	 */
	public function define_error_logging() {
		$settings = get_option( 'openasset_settings' );

		if ( isset( $settings['general-settings']['enable-logging'] ) && ! empty( $settings['general-settings']['enable-logging'] ) ) {
			define( 'OPENASSET_ENABLE_LOGGING', 'true' === $settings['general-settings']['enable-logging'] ? true : false );
		} else {
			define( 'OPENASSET_ENABLE_LOGGING', false );
		}
	}
}

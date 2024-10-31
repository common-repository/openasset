<?php
/**
 * Admin registration class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\Jetpack\Assets;

/**
 * Class RegisterAdmin
 */
class RegisterAdmin {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Register Admin Dashboard.
		add_action( 'admin_menu', array( $this, 'register_admin_dashboard' ) );

		// registerfrontend scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts_and_styles' ) );
	}

	/**
	 * Register admin dashboard.
	 */
	public function register_admin_dashboard() {
		$primary_slug = 'openasset';

		$dashboard_page_suffix = add_menu_page(
			_x( 'OpenAsset Dashboard', 'Page title', 'openasset' ),
			_x( 'OpenAsset', 'Menu title', 'openasset' ),
			'manage_options',
			$primary_slug,
			array( $this, 'plugin_dashboard_page' ),
			'dashicons-admin-generic',
			30
		);

		// Register dashboard hooks.
		add_action( 'load-' . $dashboard_page_suffix, array( $this, 'dashboard_admin_init' ) );

		$settings = get_option( 'openasset_settings' );

		// Register dashboard submenu nav item.
		if ( isset( $settings['check-credentials'] ) && $settings['check-credentials'] ) {
			add_submenu_page( $primary_slug, 'OpenAsset Dashboard', '', 'manage_options', $primary_slug . '#/settings', '__return_null' );
		} else {
			add_submenu_page( $primary_slug, 'OpenAsset Dashboard', 'Dashboard', 'manage_options', $primary_slug . '#/dashboard', '__return_null' );
		}

		// Remove duplicate menu hack.
		// Note: It needs to go after the above add_submenu_page call.
		remove_submenu_page( $primary_slug, $primary_slug );

		// Register settings submenu nav item.
		if ( isset( $settings['check-credentials'] ) && $settings['check-credentials'] ) {
			add_submenu_page( $primary_slug, 'OpenAsset Dashboard', 'General Settings', 'manage_options', $primary_slug . '#/settings', '__return_null' );
		}
		if ( isset( $settings['check-credentials'] ) && $settings['check-credentials'] ) {
			add_submenu_page( $primary_slug, 'OpenAsset Dashboard', 'Data Options', 'manage_options', $primary_slug . '#/data-options', '__return_null' );
		}
	}

	/**
	 * Initialize the Dashboard admin resources.
	 */
	public function dashboard_admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dashboard_admin_scripts' ) );
	}

	/**
	 * Enqueue plugin admin scripts and styles.
	 */
	public function enqueue_dashboard_admin_scripts() {
		Assets::register_script(
			'openasset-dashboard',
			'build/dashboard/index.js',
			OPENASSET_ROOT_FILE,
			array(
				'in_footer'  => true,
				'textdomain' => 'openasset',
			)
		);

		// Localize the script with your data.
		wp_localize_script(
			'openasset-dashboard',
			'pluginData',
			array(
				'pluginUrl'            => plugin_dir_url( OPENASSET_ROOT_FILE ),
				'openassetSyncRunning' => get_option( 'openasset_sync_running', 0 ), // Default to 0 if not set.
				'openassetProjectsSynced' => get_option( 'openasset_last_project_updated', -1 ) + 1, // Default to 0 if not set.
				'openassetEmployeesSynced' => get_option( 'openasset_last_employee_updated', -1 ) + 1, // Default to 0 if not set.
				'openassetTotalProjects' => get_option( 'openasset_total_project_count', 0 ), // Default to 0 if not set.
				'openassetTotalEmployees' => get_option( 'openasset_total_employee_count', 0 ), // Default to 0 if not set.
			)
		);

		// Enqueue app script.
		Assets::enqueue_script( 'openasset-dashboard' );
		// Initial JS state.
		wp_add_inline_script( 'openasset-dashboard', $this->render_dashboard_initial_state(), 'before' );

		wp_enqueue_style(
			'openasset-admin-style',
			plugin_dir_url( OPENASSET_ROOT_FILE ) . '/src/dashboard/styles/index.css',
			array(),
			OPENASSET_VERSION
		);
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_frontend_scripts_and_styles() {
		// Enqueue Select2 CSS.
		wp_enqueue_style( 'select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
		
		// Enqueue Select2 JS.
		wp_enqueue_script( 'select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );


		wp_enqueue_style(
			'openasset-frontend-lightbox-css',
			plugin_dir_url( OPENASSET_ROOT_FILE ) . 'assets/css/lightbox.min.css',
			array(),
			OPENASSET_VERSION
		);

		wp_enqueue_script(
			'openasset-frontend-lightbox-js',
			plugin_dir_url( OPENASSET_ROOT_FILE ) . 'assets/js/lightbox.min.js',
			array( 'jquery' ), // Setting jQuery as a dependency.
			OPENASSET_VERSION,
			true // Load the script in the footer for better performance.
		);
		wp_enqueue_style(
			'openasset-frontend',
			plugin_dir_url( OPENASSET_ROOT_FILE ) . 'build/frontend/index.css',
			array(),
			OPENASSET_VERSION
		);
		wp_enqueue_script(
			'openasset-frontend-js',
			plugin_dir_url( OPENASSET_ROOT_FILE ) . 'build/frontend/index.js',
			array( 'jquery', 'select2-js' ), // Setting jQuery as a dependency.
			OPENASSET_VERSION,
			true // Load the script in the footer for better performance.
		);
	}

	/**
	 * Render the initial state into a JavaScript variable.
	 *
	 * @return string
	 */
	public function render_dashboard_initial_state() {
		return 'var openAssetPluginState=JSON.parse(decodeURIComponent("' . rawurlencode( wp_json_encode( $this->initial_dashboard_state() ) ) . '"));';
	}

	/**
	 * Get the initial state data for hydrating the React UI.
	 *
	 * @return array
	 */
	public function initial_dashboard_state() {
		// Add check credentials value to JS.
		$settings = get_option( 'openasset_settings' );

		return array(
			'apiRoute'         => OPENASSET_SLUG . '/v1',
			'assetsURL'        => OPENASSET_URL . '/assets',
			// You can also replace this changelog URL to something else so that it loads from one source and stays up-to-date always.
			'changelogURL'     => OPENASSET_URL . '/changelog.json?ver=' . filemtime( OPENASSET_DIR . '/changelog.json' ),
			'version'          => OPENASSET_VERSION,
			'checkCredentials' => isset( $settings['check-credentials'] ) ? $settings['check-credentials'] : false,
			'redirectTo'       => admin_url( 'admin.php?page=openasset#/settings' ),
		);
	}

	/**
	 * Plugin Dashboard page.
	 */
	public function plugin_dashboard_page() {
		?>
			<div id="openasset-dashboard-root"></div>
		<?php
	}
}

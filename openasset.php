<?php
/**
 * OpenAsset
 *
 * @package           OpenAsset
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       OpenAsset
 * Description:       Power your AEC company website Project Portfolio and Team pages from your centralized data in OpenAsset.
 * Version:           1.1.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            OpenAsset
 * Author URI:        https://openasset.com/
 * Text Domain:       openasset
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OPENASSET_VERSION', '1.0.0' );
define( 'OPENASSET_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENASSET_ROOT_FILE', __FILE__ );
define( 'OPENASSET_ROOT_FILE_RELATIVE_PATH', plugin_basename( __FILE__ ) );
define( 'OPENASSET_SLUG', 'openasset' );
define( 'OPENASSET_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
define( 'OPENASSET_URL', plugins_url( '', __FILE__ ) );

// chck if OPENASSET_ENCRYPTION_KEY is defined.
if ( ! defined( 'OPENASSET_ENCRYPTION_KEY' ) ) {
	define( 'OPENASSET_ENCRYPTION_KEY', 'OPENASSET-Encryption-2024' );
}

// OpenAsset Autoloader.
$openasset_autoloader = OPENASSET_DIR . 'vendor/autoload_packages.php';
if ( is_readable( $openasset_autoloader ) ) {
	require_once $openasset_autoloader;
} else { // Something very unexpected. Error out gently with an admin_notice and exit loading.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			__( 'Error loading autoloader file for OpenAsset plugin', 'openasset' )
		);
	}

	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				printf(
					wp_kses(
						/* translators: Placeholder is a link to a support document. */
						__( 'Your installation of OpenAsset is incomplete. If you installed OpenAsset from GitHub, please refer to <a href="%1$s" target="_blank" rel="noopener noreferrer">this document</a> to set up your development environment. OpenAsset must have Composer dependencies installed and built via the build command.', 'openasset' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					),
					'https://openasset.com/'
				);
				?>
			</p>
		</div>
			<?php
		}
	);

	return;
}

// Redirect to plugin onboarding when the plugin is activated.
add_action( 'activated_plugin', 'openasset_activation' );

/**
 * Redirects to plugin onboarding when the plugin is activated
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 */
function openasset_activation( $plugin ) {
	global $wpdb;
	// Change old 'project' and 'employee' post types to new 'oa-project' and 'oa-employee' post types.

	// Update 'project' post type to 'openasset-project' for posts that have 'openasset_id' as postmeta key with an integer value.
	$wpdb->query(
		"UPDATE {$wpdb->posts}
		SET post_type = 'oa-project'
		WHERE post_type = 'project'
		AND ID IN (
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = 'openasset_id'
			AND meta_value REGEXP '^[0-9]+$'
		)"
	);

	// Update 'employee' post type to 'openasset-employee' for posts that have 'openasset_id' as postmeta key with an integer value.
	$wpdb->query(
		"UPDATE {$wpdb->posts}
		SET post_type = 'oa-employee'
		WHERE post_type = 'employee'
		AND ID IN (
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = 'openasset_id'
			AND meta_value REGEXP '^[0-9]+$'
		)"
	);

	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules();
	if (
		OPENASSET_ROOT_FILE_RELATIVE_PATH === $plugin && class_exists( '\Automattic\Jetpack\Plugins_Installer' ) &&
		\Automattic\Jetpack\Plugins_Installer::is_current_request_activating_plugin_from_plugins_screen( OPENASSET_ROOT_FILE_RELATIVE_PATH )
	) {
		wp_safe_redirect( esc_url( admin_url( 'admin.php?page=openasset#/dashboard' ) ) );
		exit;
	}
}

register_activation_hook( __FILE__, array( '\OpenAsset\Plugin', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( '\OpenAsset\Plugin', 'plugin_deactivation' ) );

/**
 * Enqueue init admin styles.
 */
function openasset_init_enqueue_admin_styles() {
	wp_enqueue_style(
		'openasset-init-admin-style',
		plugin_dir_url( OPENASSET_ROOT_FILE ) . '/src/dashboard/styles/init.css',
		array(),
		OPENASSET_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'openasset_init_enqueue_admin_styles' );

// Main plugin class.
if ( class_exists( \OpenAsset\Plugin::class ) ) {
	new \OpenAsset\Plugin();
}

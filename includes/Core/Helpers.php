<?php
/**
 * Plugin Helpers class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Core;

/**
 * Plugin helpers custom functions.
 */
class Helpers {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'template_include', array( $this, 'load_custom_templates' ) );
	}

	/**
	 * Load custom templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public function load_custom_templates( $template ) {
		if ( is_post_type_archive( 'oa-employee' ) ) {
			$template = $this->get_template( 'archive-oa-employee.php' );
		} elseif ( is_singular( 'oa-employee' ) ) {
			$template = $this->get_template( 'single-oa-employee.php' );
		} elseif ( is_post_type_archive( 'oa-project' ) ) {
			$template = $this->get_template( 'archive-oa-project.php' );
		} elseif ( is_singular( 'oa-project' ) ) {
			$template = $this->get_template( 'single-oa-project.php' );
		}
		return $template;
	}

	/**
	 * Get the custom template if it exists.
	 *
	 * @param string $template_name The name of the template.
	 * @param string $type 'template' or 'template-part'.
	 * @return string Full path to the template file.
	 */
	public function get_template( $template_name, $type = 'template' ) {
		$theme_file = locate_template( 'openasset/' . $template_name );

		// Check if theme has the custom template or template part.
		if ( $theme_file && is_file( $theme_file ) ) {
			return $theme_file;
		} else {
			$directory = ( 'template' === $type ) ? 'templates' : '';
			return OPENASSET_DIR . $directory . '/' . $template_name;
		}
	}

	/**
	 * Get template part. Tries to get theme override template first, then falls back to plugin.
	 *
	 * @param string      $slug The base slug of the template (e.g. 'template-parts/content').
	 * @param string|null $name The name to create a more specific template (e.g. 'single').
	 */
	public function get_template_part( $slug, $name = null ) {
		// If the theme provides this template part, use WordPress's core function.
		if ( locate_template( "{$slug}-{$name}.php" ) ) {
			get_template_part( $slug, $name );
			return;
		}

		// Construct the plugin template part path.
		$plugin_template_name = $name ? "{$slug}-{$name}.php" : "{$slug}.php";

		$template = $this->get_template( $plugin_template_name, 'template-part' );

		// Include the template part if it exists.
		if ( file_exists( $template ) ) {
			require $template;
		}
	}

	/**
	 * Convert date format to readable date.
	 *
	 * @param string $input_date The date format return from OpenAsset.
	 *
	 * @return string
	 */
	public function convert_to_readable_date( $input_date ) {
		// Create a DateTime object from the input date.
		$datetime = \DateTime::createFromFormat( 'YmdHis', $input_date );

		// Check if the date is valid.
		if ( ! $datetime ) {
			return 'Not stated';
		}

		// Format the date.
		return $datetime->format( 'F jS Y' );
	}

	/**
	 * Sorts an array of arrays by a property.
	 *
	 * @param array  $files      Array of objects to sort.
	 * @param string $sort_by    Property to sort by.
	 * @param string $sort_order Order to sort by.
	 *
	 * @return array
	 */
	public function sort_images( $files, $sort_by, $sort_order ) {
		// To sort by 'created' property in ascending order.
		if ( 'created' === $sort_by ) {
			if ( 'asc' === $sort_order ) {
				usort(
					$files,
					function( $a, $b ) {
						return strcmp( $a['created'], $b['created'] );
					}
				);
			} else {
				usort(
					$files,
					function( $a, $b ) {
						return strcmp( $b['created'], $a['created'] );
					}
				);
			}
		} elseif ( 'updated' === $sort_by ) {
			if ( 'asc' === $sort_order ) {
				usort(
					$files,
					function( $a, $b ) {
						return strcmp( $a['updated'], $b['updated'] );
					}
				);
			} else {
				usort(
					$files,
					function( $a, $b ) {
						return strcmp( $b['updated'], $a['updated'] );
					}
				);
			}
		} elseif ( 'project_display_order' === $sort_by ) {
			if ( 'asc' === $sort_order ) {
				usort(
					$files,
					function( $a, $b ) {
						return $a['project_display_order'] - $b['project_display_order'];
					}
				);
			} else {
				usort(
					$files,
					function( $a, $b ) {
						return $b['project_display_order'] - $a['project_display_order'];
					}
				);
			}
		} elseif ( 'id' === $sort_by ) {
			if ( 'asc' === $sort_order ) {
				usort(
					$files,
					function( $a, $b ) {
						return $a['id'] - $b['id'];
					}
				);
			} else {
				usort(
					$files,
					function( $a, $b ) {
						return $b['id'] - $a['id'];
					}
				);
			}
		}
		return $files;
	}

	/**
	 * Encrypt password.
	 *
	 * @param string $password Password to encrypt.
	 * @return string | bool
	 */
	public function encrypt_password( $password ) {
		$iv        = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encrypted = openssl_encrypt( $password, 'aes-256-cbc', OPENASSET_ENCRYPTION_KEY, 0, $iv );

		return base64_encode( $encrypted . '::' . $iv );
	}

	/**
	 * Decrypt password.
	 *
	 * @param string $encrypted_password Password to decrypt.
	 * @return string | bool
	 */
	public function decrypt_password( $encrypted_password ) {
		$parts = explode( '::', base64_decode( $encrypted_password ), 2 );

		// Check if both parts (encrypted data and IV) exist.
		if ( 2 === count( $parts ) ) {
			list( $encrypted_data, $iv ) = $parts;
			return openssl_decrypt( $encrypted_data, 'aes-256-cbc', OPENASSET_ENCRYPTION_KEY, 0, $iv );
		} else {
			// Handle the error, maybe log it or return false.
			if ( OPENASSET_ENABLE_LOGGING ) {
				error_log( 'Decryption error: Incorrect format.' );
			}
			return false;
		}
	}

}

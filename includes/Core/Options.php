<?php
/**
 * Plugin Options class.
 *
 * @package OpenAsset
 */

namespace OpenAsset\Core;

use OpenAsset\Core\Helpers;

/**
 * Plugin options registration and management.
 */
class Options {
	/**
	 * Class instance.
	 *
	 * @var $instace
	 */
	protected static $instance;

	/**
	 * Options key.
	 */
	const OPTION_SETTINGS_KEY = 'openasset_settings';
	const OPTION_DATA_KEY     = 'openasset_data';

	/**
	 * Get an instance of class.
	 *
	 * @return Options
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Checks whether or not a value is set for the given option.
	 *
	 * @param string $option Option name.
	 * @param string $type Option type.
	 * @return bool True if value set, false otherwise.
	 */
	public function has( $option, $type = 'settings' ) {
		if ( 'settings' === $type ) {
			$settings = get_option( self::OPTION_SETTINGS_KEY );
			return isset( $settings[ $option ] );
		}
		if ( 'data' === $type ) {
			$data = get_option( self::OPTION_DATA_KEY );
			return isset( $data[ $option ] );
		}
	}

	/**
	 * Get a single option, or all if no key is provided.
	 *
	 * @param string|array|bool $key Option key.
	 * @param mixed             $default Default value.
	 * @param string            $type Option type.
	 * @return array|string|bool
	 */
	public function get( $key = false, $default = false, $type = 'settings' ) {
		if ( 'settings' === $type ) {
			$options = get_option( self::OPTION_SETTINGS_KEY );
		} else {
			$options = get_option( self::OPTION_DATA_KEY );
		}

		if ( ! $options || ! is_array( $options ) ) {
			$options = array();
		}

		if ( false !== $key ) {
			return isset( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		return $options;
	}

	/**
	 * Update a single option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Option value.
	 * @param string $type Option type.
	 * @return void
	 */
	public function set( $key, $value, $type = 'settings' ) {
		if ( 'settings' === $type ) {
			$options = $this->get();
			// encrypt api-token.
			if ( isset( $value['your-real-api-token'] ) && '' !== $value['your-real-api-token'] ) {
				$helpers   = new Helpers();
				$decrypted = $helpers->decrypt_password( $value['your-real-api-token'], OPENASSET_ENCRYPTION_KEY );
				if ( ! $decrypted ) {
					$encrypted_token              = $helpers->encrypt_password( $value['your-real-api-token'] );
					$value['your-real-api-token'] = $encrypted_token;
				}
			}
			$options[ $key ] = $value;
			update_option( self::OPTION_SETTINGS_KEY, $options );
		} else {
			$options = $this->get( false, false, 'data' );
			// encrypt api-token.
			if ( isset( $value['your-real-api-token'] ) && '' !== $value['your-real-api-token'] ) {
				$helpers   = new Helpers();
				$decrypted = $helpers->decrypt_password( $value['your-real-api-token'] );
				if ( ! $decrypted ) {
					$encrypted_token              = $helpers->encrypt_password( $value['your-real-api-token'], OPENASSET_ENCRYPTION_KEY );
					$value['your-real-api-token'] = $encrypted_token;
				}
			}
			$options[ $key ] = $value;
			update_option( self::OPTION_DATA_KEY, $options );
		}
	}

	/**
	 * Delete a single option.
	 *
	 * @param string $key Option key.
	 * @param string $type Option type.
	 * @return void
	 */
	public function delete( $key, $type = 'settings' ) {
		if ( 'settings' === $type ) {
			$options = $this->get();
			if ( ! isset( $options[ $key ] ) ) {
				return;
			}

			unset( $options[ $key ] );
			update_option( self::OPTION_SETTINGS_KEY, $options );
		} else {
			$options = $this->get( false, false, 'data' );
			if ( ! isset( $options[ $key ] ) ) {
				return;
			}

			unset( $options[ $key ] );
			update_option( self::OPTION_DATA_KEY, $options );
		}
	}
}

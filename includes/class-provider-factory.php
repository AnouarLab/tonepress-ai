<?php
/**
 * TonePress AI
 *
 * @package           TonePress AI
 * @author            AnouarLab <https://anouarlab.fr>
 * @copyright         2026 AnouarLab
 * @license           GPL-2.0-or-later
 */

/**
 * Provider Factory class.
 *
 * Factory pattern for creating AI provider instances.
 * Follows Single Responsibility and Open/Closed principles.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Provider_Factory
 *
 * Creates and manages AI provider instances.
 */
class Provider_Factory {

	/**
	 * Registered provider classes.
	 *
	 * @var array
	 */
	private static $providers = array(
		'openai'  => 'ACE\\Provider_OpenAI',
		'claude'  => 'ACE\\Provider_Claude',
		'gemini'  => 'ACE\\Provider_Gemini',
	);

	/**
	 * Cached provider instances.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Create a provider instance.
	 *
	 * @param string $provider_id Provider identifier.
	 * @param string $api_key     Optional API key override.
	 * @return AI_Provider|WP_Error Provider instance or error.
	 */
	public static function create( $provider_id, $api_key = null ) {
		if ( ! isset( self::$providers[ $provider_id ] ) ) {
			return new \WP_Error(
				'invalid_provider',
				sprintf(
					/* translators: %s: Provider ID */
					__( 'Unknown AI provider: %s', 'tonepress-ai' ),
					$provider_id
				)
			);
		}

		$class = self::$providers[ $provider_id ];

		if ( ! class_exists( $class ) ) {
			return new \WP_Error(
				'provider_not_loaded',
				sprintf(
					/* translators: %s: Provider class */
					__( 'Provider class not found: %s', 'tonepress-ai' ),
					$class
				)
			);
		}

		return new $class( $api_key );
	}

	/**
	 * Get or create a singleton provider instance.
	 *
	 * @param string $provider_id Provider identifier.
	 * @return AI_Provider|WP_Error Provider instance or error.
	 */
	public static function get( $provider_id ) {
		if ( ! isset( self::$instances[ $provider_id ] ) ) {
			$provider = self::create( $provider_id );
			
			if ( is_wp_error( $provider ) ) {
				return $provider;
			}

			self::$instances[ $provider_id ] = $provider;
		}

		return self::$instances[ $provider_id ];
	}

	/**
	 * Get the currently active provider.
	 *
	 * @return AI_Provider|WP_Error Active provider or error.
	 */
	public static function get_active() {
		$active_provider = get_option( 'ace_ai_provider', 'openai' );
		return self::get( $active_provider );
	}

	/**
	 * Get all registered provider IDs.
	 *
	 * @return array Provider IDs.
	 */
	public static function get_provider_ids() {
		return array_keys( self::$providers );
	}

	/**
	 * Get all providers info.
	 *
	 * @return array Provider information array.
	 */
	public static function get_all_providers_info() {
		$info = array();

		foreach ( self::$providers as $id => $class ) {
			$provider = self::get( $id );
			
			if ( ! is_wp_error( $provider ) ) {
				$info[ $id ] = $provider->get_info();
			}
		}

		return $info;
	}

	/**
	 * Get configured providers only.
	 *
	 * @return array Configured provider instances.
	 */
	public static function get_configured_providers() {
		$configured = array();

		foreach ( self::$providers as $id => $class ) {
			$provider = self::get( $id );
			
			if ( ! is_wp_error( $provider ) && $provider->is_configured() ) {
				$configured[ $id ] = $provider;
			}
		}

		return $configured;
	}

	/**
	 * Register a new provider.
	 *
	 * @param string $provider_id Provider identifier.
	 * @param string $class_name  Fully qualified class name.
	 */
	public static function register( $provider_id, $class_name ) {
		self::$providers[ $provider_id ] = $class_name;
	}
}

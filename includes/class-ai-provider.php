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
 * Abstract AI Provider base class.
 *
 * Defines the contract for all AI provider implementations.
 * Follows SOLID principles - Open/Closed principle for extensibility.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Abstract Class AI_Provider
 *
 * Base class for all AI content generation providers.
 */
abstract class AI_Provider {

	/**
	 * Provider unique identifier.
	 *
	 * @var string
	 */
	protected $provider_id;

	/**
	 * Provider display name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * API key for the provider.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Default model for this provider.
	 *
	 * @var string
	 */
	protected $default_model;

	/**
	 * API endpoint URL.
	 *
	 * @var string
	 */
	protected $api_endpoint;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Encrypted API key.
	 */
	public function __construct( $api_key = null ) {
		if ( null === $api_key ) {
			$api_key = $this->get_stored_api_key();
		}

		$this->api_key = Security::decrypt_api_key( $api_key );
	}

	/**
	 * Get the provider ID.
	 *
	 * @return string Provider identifier.
	 */
	public function get_id() {
		return $this->provider_id;
	}

	/**
	 * Get the provider name.
	 *
	 * @return string Provider display name.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get available models for this provider.
	 *
	 * @return array Associative array of model_id => model_name.
	 */
	abstract public function get_available_models();

	/**
	 * Get the stored API key option name.
	 *
	 * @return string Option name for API key.
	 */
	abstract protected function get_api_key_option();

	/**
	 * Get stored API key from options.
	 *
	 * @return string Encrypted API key.
	 */
	protected function get_stored_api_key() {
		return get_option( $this->get_api_key_option(), '' );
	}

	/**
	 * Generate content using the AI provider.
	 *
	 * @param string $system_prompt System prompt for context.
	 * @param string $user_prompt   User prompt with specific request.
	 * @param array  $options       Generation options (model, temperature, max_tokens).
	 * @return array|WP_Error Response with content and usage, or error.
	 */
	abstract public function generate_content( $system_prompt, $user_prompt, $options = array() );

	/**
	 * Test the API connection.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	abstract public function test_connection();

	/**
	 * Estimate cost for tokens used.
	 *
	 * @param int    $tokens Token count.
	 * @param string $model  Model used.
	 * @return float Estimated cost in USD.
	 */
	abstract public function estimate_cost( $tokens, $model = null );

	/**
	 * Check if provider is configured (has API key).
	 *
	 * @return bool True if configured.
	 */
	public function is_configured() {
		return ! empty( $this->api_key );
	}

	/**
	 * Make HTTP request to provider API.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $body     Request body.
	 * @param array  $headers  Additional headers.
	 * @return array|WP_Error Response or error.
	 */
	protected function make_request( $endpoint, $body, $headers = array() ) {
		$default_headers = array(
			'Content-Type' => 'application/json',
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array_merge( $default_headers, $headers ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'API request failed: %s', 'ai-content-engine' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return array(
			'code' => $response_code,
			'body' => json_decode( $response_body, true ),
			'raw'  => $response_body,
		);
	}

	/**
	 * Get provider info for display.
	 *
	 * @return array Provider information.
	 */
	public function get_info() {
		return array(
			'id'          => $this->provider_id,
			'name'        => $this->name,
			'models'      => $this->get_available_models(),
			'configured'  => $this->is_configured(),
			'endpoint'    => $this->api_endpoint,
		);
	}
}

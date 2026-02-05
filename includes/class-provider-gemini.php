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
 * Google Gemini Provider implementation.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Provider_Gemini
 *
 * Google Gemini API implementation.
 */
class Provider_Gemini extends AI_Provider {

	/**
	 * Constructor.
	 *
	 * @param string $api_key Optional API key.
	 */
	public function __construct( $api_key = null ) {
		$this->provider_id  = 'gemini';
		$this->name         = 'Google Gemini';
		$this->api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models';
		$this->default_model = 'gemini-1.5-flash';

		parent::__construct( $api_key );
	}

	/**
	 * Get API key option name.
	 *
	 * @return string Option name.
	 */
	protected function get_api_key_option() {
		return 'ace_gemini_api_key';
	}

	/**
	 * Get available models.
	 *
	 * @return array Models.
	 */
	public function get_available_models() {
		return array(
			'gemini-1.5-flash'   => 'Gemini 1.5 Flash (Fast & Free Tier)',
			'gemini-1.5-pro'     => 'Gemini 1.5 Pro (Most Capable)',
			'gemini-pro'         => 'Gemini Pro (Stable)',
		);
	}

	/**
	 * Generate content.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_prompt   User prompt.
	 * @param array  $options       Options.
	 * @return array|WP_Error Response or error.
	 */
	public function generate_content( $system_prompt, $user_prompt, $options = array() ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'not_configured', __( 'Gemini API key not configured.', 'ai-content-engine' ) );
		}

		$model       = $options['model'] ?? get_option( 'ace_gemini_model', $this->default_model );
		$temperature = $options['temperature'] ?? 0.7;
		$max_tokens  = $options['max_tokens'] ?? 4096;

		$endpoint = sprintf(
			'%s/%s:generateContent?key=%s',
			$this->api_endpoint,
			$model,
			$this->api_key
		);

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array( 'text' => $system_prompt . "\n\n" . $user_prompt ),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => (float) $temperature,
				'maxOutputTokens' => (int) $max_tokens,
				'responseMimeType' => 'application/json',
			),
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_request_failed', $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$error_msg = $body['error']['message'] ?? __( 'Unknown Gemini API error', 'ai-content-engine' );
			return new \WP_Error( 'api_error', $error_msg );
		}

		$content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
		
		// Estimate tokens (Gemini doesn't always return token count)
		$prompt_tokens = isset( $body['usageMetadata']['promptTokenCount'] ) 
			? $body['usageMetadata']['promptTokenCount'] 
			: (int) ( strlen( $system_prompt . $user_prompt ) / 4 );
		
		$completion_tokens = isset( $body['usageMetadata']['candidatesTokenCount'] )
			? $body['usageMetadata']['candidatesTokenCount']
			: (int) ( strlen( $content ) / 4 );

		$usage = array(
			'prompt_tokens'     => $prompt_tokens,
			'completion_tokens' => $completion_tokens,
			'total_tokens'      => $prompt_tokens + $completion_tokens,
		);

		return array(
			'content'  => $content,
			'usage'    => $usage,
			'model'    => $model,
			'provider' => $this->provider_id,
		);
	}

	/**
	 * Test connection.
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'not_configured', __( 'API key not set.', 'ai-content-engine' ) );
		}

		$endpoint = sprintf(
			'%s?key=%s',
			$this->api_endpoint,
			$this->api_key
		);

		$response = wp_remote_get( $endpoint, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return 200 === $code ? true : new \WP_Error( 'api_error', __( 'Invalid API key.', 'ai-content-engine' ) );
	}

	/**
	 * Estimate cost.
	 *
	 * @param int    $tokens Total tokens.
	 * @param string $model  Model used.
	 * @return float Cost in USD.
	 */
	public function estimate_cost( $tokens, $model = null ) {
		$model = $model ?? $this->default_model;

		// Gemini Flash is free tier, Pro has costs
		$rates = array(
			'gemini-1.5-flash' => 0.000075,  // Very cheap
			'gemini-1.5-pro'   => 0.00125,
			'gemini-pro'       => 0.00025,
		);

		$rate = $rates[ $model ] ?? 0.00025;
		return ( $tokens / 1000 ) * $rate;
	}
}

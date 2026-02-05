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
 * Claude (Anthropic) Provider implementation.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Provider_Claude
 *
 * Anthropic Claude API implementation.
 */
class Provider_Claude extends AI_Provider {

	/**
	 * Constructor.
	 *
	 * @param string $api_key Optional API key.
	 */
	public function __construct( $api_key = null ) {
		$this->provider_id  = 'claude';
		$this->name         = 'Anthropic Claude';
		$this->api_endpoint = 'https://api.anthropic.com/v1/messages';
		$this->default_model = 'claude-3-haiku-20240307';

		parent::__construct( $api_key );
	}

	/**
	 * Get API key option name.
	 *
	 * @return string Option name.
	 */
	protected function get_api_key_option() {
		return 'ace_claude_api_key';
	}

	/**
	 * Get available models.
	 *
	 * @return array Models.
	 */
	public function get_available_models() {
		return array(
			'claude-3-haiku-20240307'  => 'Claude 3 Haiku (Fast & Affordable)',
			'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Balanced)',
			'claude-3-opus-20240229'   => 'Claude 3 Opus (Most Capable)',
			'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Latest)',
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
			return new \WP_Error( 'not_configured', __( 'Claude API key not configured.', 'tonepress-ai' ) );
		}

		$model       = $options['model'] ?? get_option( 'ace_claude_model', $this->default_model );
		$temperature = $options['temperature'] ?? 0.7;
		$max_tokens  = $options['max_tokens'] ?? 4096;

		$body = array(
			'model'      => $model,
			'max_tokens' => (int) $max_tokens,
			'system'     => $system_prompt,
			'messages'   => array(
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
		);

		// Only add temperature if not 1.0 (Claude default)
		if ( $temperature != 1.0 ) {
			$body['temperature'] = (float) $temperature;
		}

		$response = $this->make_request(
			$this->api_endpoint,
			$body,
			array(
				'x-api-key'         => $this->api_key,
				'anthropic-version' => '2023-06-01',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response['code'] ) {
			$error_msg = $response['body']['error']['message'] ?? __( 'Unknown Claude API error', 'tonepress-ai' );
			return new \WP_Error( 'api_error', $error_msg );
		}

		$content = $response['body']['content'][0]['text'] ?? '';
		
		// Build usage data similar to OpenAI format
		$usage = array(
			'prompt_tokens'     => $response['body']['usage']['input_tokens'] ?? 0,
			'completion_tokens' => $response['body']['usage']['output_tokens'] ?? 0,
			'total_tokens'      => ( $response['body']['usage']['input_tokens'] ?? 0 ) + ( $response['body']['usage']['output_tokens'] ?? 0 ),
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
			return new \WP_Error( 'not_configured', __( 'API key not set.', 'tonepress-ai' ) );
		}

		// Make a minimal request to test
		$body = array(
			'model'      => 'claude-3-haiku-20240307',
			'max_tokens' => 10,
			'messages'   => array(
				array( 'role' => 'user', 'content' => 'Hi' ),
			),
		);

		$response = $this->make_request(
			$this->api_endpoint,
			$body,
			array(
				'x-api-key'         => $this->api_key,
				'anthropic-version' => '2023-06-01',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return 200 === $response['code'] ? true : new \WP_Error( 'api_error', __( 'Invalid API key.', 'tonepress-ai' ) );
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

		// Approximate rates per 1K tokens (input + output averaged)
		$rates = array(
			'claude-3-haiku-20240307'    => 0.00025,
			'claude-3-sonnet-20240229'   => 0.003,
			'claude-3-opus-20240229'     => 0.015,
			'claude-3-5-sonnet-20241022' => 0.003,
		);

		$rate = $rates[ $model ] ?? 0.003;
		return ( $tokens / 1000 ) * $rate;
	}
}

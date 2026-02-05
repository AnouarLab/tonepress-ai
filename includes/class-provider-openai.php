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
 * OpenAI Provider implementation.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Provider_OpenAI
 *
 * OpenAI API implementation (GPT-3.5, GPT-4, GPT-4 Turbo).
 */
class Provider_OpenAI extends AI_Provider {

	/**
	 * Constructor.
	 *
	 * @param string $api_key Optional API key.
	 */
	public function __construct( $api_key = null ) {
		$this->provider_id  = 'openai';
		$this->name         = 'OpenAI';
		$this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
		$this->default_model = 'gpt-3.5-turbo';

		parent::__construct( $api_key );
	}

	/**
	 * Get API key option name.
	 *
	 * @return string Option name.
	 */
	protected function get_api_key_option() {
		return 'ace_openai_api_key';
	}

	/**
	 * Get available models.
	 *
	 * @return array Models.
	 */
	public function get_available_models() {
		return array(
			'gpt-3.5-turbo'     => 'GPT-3.5 Turbo (Fast, Affordable)',
			'gpt-4'             => 'GPT-4 (Most Capable)',
			'gpt-4-turbo'       => 'GPT-4 Turbo (Faster GPT-4)',
			'gpt-4o'            => 'GPT-4o (Optimized)',
			'gpt-4o-mini'       => 'GPT-4o Mini (Fast & Cheap)',
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
			return new \WP_Error( 'not_configured', __( 'OpenAI API key not configured.', 'ai-content-engine' ) );
		}

		$model       = $options['model'] ?? get_option( 'ace_openai_model', $this->default_model );
		$temperature = $options['temperature'] ?? 0.7;
		$max_tokens  = $options['max_tokens'] ?? 3000;

		$body = array(
			'model'       => $model,
			'messages'    => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user', 'content' => $user_prompt ),
			),
			'temperature' => (float) $temperature,
			'max_tokens'  => (int) $max_tokens,
		);

		// Enable JSON mode for most models - gpt-3.5-turbo-0125+ and all gpt-4 variants support it
		$json_supported_models = array( 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4', 'gpt-3.5-turbo', 'gpt-3.5-turbo-1106', 'gpt-4-1106-preview' );
		if ( in_array( $model, $json_supported_models, true ) || strpos( $model, 'gpt-4' ) !== false ) {
			$body['response_format'] = array( 'type' => 'json_object' );
		}

		$response = $this->make_request(
			$this->api_endpoint,
			$body,
			array( 'Authorization' => 'Bearer ' . $this->api_key )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response['code'] ) {
			$error_msg = $response['body']['error']['message'] ?? __( 'Unknown API error', 'ai-content-engine' );
			return new \WP_Error( 'api_error', $error_msg );
		}

		$content = $response['body']['choices'][0]['message']['content'] ?? '';
		$usage   = $response['body']['usage'] ?? array();

		return array(
			'content' => $content,
			'usage'   => $usage,
			'model'   => $model,
			'provider' => $this->provider_id,
		);
	}

	/**
	 * Generate content with function calling (tools).
	 *
	 * @param array  $messages    Conversation messages.
	 * @param array  $tools       Tool definitions.
	 * @param array  $options     Options.
	 * @return array|WP_Error Response with tool calls or content.
	 */
	public function generate_with_tools( $messages, $tools, $options = array() ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'not_configured', __( 'OpenAI API key not configured.', 'ai-content-engine' ) );
		}

		$model       = $options['model'] ?? get_option( 'ace_openai_model', $this->default_model );
		$temperature = $options['temperature'] ?? 0.7;
		$max_tokens  = $options['max_tokens'] ?? 4000;

		$body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => (float) $temperature,
			'max_tokens'  => (int) $max_tokens,
			'tools'       => $tools,
			'tool_choice' => 'auto',
		);

		$response = $this->make_request(
			$this->api_endpoint,
			$body,
			array( 'Authorization' => 'Bearer ' . $this->api_key )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== $response['code'] ) {
			$error_msg = $response['body']['error']['message'] ?? __( 'Unknown API error', 'ai-content-engine' );
			return new \WP_Error( 'api_error', $error_msg );
		}

		$choice = $response['body']['choices'][0]['message'] ?? array();
		$usage  = $response['body']['usage'] ?? array();

		// Check if there are tool calls
		$tool_calls = $choice['tool_calls'] ?? null;
		
		return array(
			'content'     => $choice['content'] ?? '',
			'tool_calls'  => $tool_calls,
			'usage'       => $usage,
			'model'       => $model,
			'provider'    => $this->provider_id,
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

		$response = $this->make_request(
			'https://api.openai.com/v1/models',
			array(),
			array( 'Authorization' => 'Bearer ' . $this->api_key )
		);

		// Use GET for models endpoint
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $this->api_key ),
				'timeout' => 30,
			)
		);

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

		$rates = array(
			'gpt-3.5-turbo' => 0.002,
			'gpt-4'         => 0.03,
			'gpt-4-turbo'   => 0.01,
			'gpt-4o'        => 0.005,
			'gpt-4o-mini'   => 0.00015,
		);

		$rate = $rates[ $model ] ?? 0.002;
		return ( $tokens / 1000 ) * $rate;
	}
}

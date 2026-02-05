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
 * Chat Handler for Chat Builder.
 *
 * Main orchestrator for chat operations.
 *
 * @package AI_Content_Engine
 * @since 2.2.0
 */

namespace ACE\Chat;

use ACE\AI_Provider;

/**
 * Class Chat_Handler
 *
 * Orchestrates chat operations using Intent_Classifier, Prompt_Manager, and Session_Manager.
 */
class Chat_Handler {

	/**
	 * Intent Classifier instance.
	 *
	 * @var Intent_Classifier
	 */
	private $classifier;

	/**
	 * Prompt Manager instance.
	 *
	 * @var Prompt_Manager
	 */
	private $prompts;

	/**
	 * Session Manager instance.
	 *
	 * @var Session_Manager
	 */
	private $sessions;

	/**
	 * AI Provider instance.
	 *
	 * @var AI_Provider|\WP_Error
	 */
	private $ai_provider;

	/**
	 * Constructor.
	 *
	 * @param AI_Provider|\WP_Error $ai_provider AI provider instance.
	 */
	public function __construct( $ai_provider ) {
		$this->ai_provider = $ai_provider;
		$this->classifier = new Intent_Classifier();
		$this->prompts = new Prompt_Manager();
		$this->sessions = new Session_Manager();

		// Share provider with classifier.
		if ( ! is_wp_error( $ai_provider ) ) {
			$this->classifier->set_provider( $ai_provider );
		}
	}

	/**
	 * Start a new chat session.
	 *
	 * @param string $topic        Topic/prompt.
	 * @param string $model        Model ID.
	 * @param string $keywords     Keywords.
	 * @param bool   $professional Professional mode.
	 * @return array Session data.
	 */
	public function start_session( $topic, $model = '', $keywords = '', $professional = false ) {
		$session_id = $this->sessions->create( array(
			'topic'        => $topic,
			'model'        => $model,
			'keywords'     => $keywords,
			'professional' => $professional,
		) );

		$session = $this->sessions->get( $session_id );
		$conversation = array();
		$current_content = '';
		$initial_response = '';

		// Add user message.
		if ( ! empty( $topic ) ) {
			$conversation[] = array(
				'role'      => 'user',
				'content'   => $topic,
				'timestamp' => current_time( 'mysql' ),
			);

			// Classify intent and get AI response.
			$intent = $this->classifier->classify( $topic );
			$ai_result = $this->get_ai_response(
				$conversation,
				'',
				$model,
				$intent,
				$this->sessions->get_requirements( $session )
			);

			if ( ! is_wp_error( $ai_result ) ) {
				$initial_response = $ai_result['message'];
				$current_content = $ai_result['content'] ?? '';

				if ( $professional && ! empty( $current_content ) ) {
					$polished = $this->polish_content( $current_content, $model );
					if ( ! empty( $polished['content'] ) ) {
						$current_content = $polished['content'];
						$initial_response = $polished['message'] ?: $initial_response;
					}
				}

				$conversation[] = array(
					'role'        => 'assistant',
					'content'     => $initial_response,
					'timestamp'   => current_time( 'mysql' ),
					'has_content' => ! empty( $current_content ),
				);
			}
		}

		// Save to database.
		$this->sessions->update( $session_id, array(
			'conversation'    => $conversation,
			'current_content' => $current_content,
		) );

		if ( ! empty( $current_content ) ) {
			$this->sessions->save_version( $session_id, $current_content );
		}

		return array(
			'session_id'  => $session_id,
			'message'     => $initial_response,
			'content'     => $current_content,
			'has_content' => ! empty( $current_content ),
			'word_count'  => str_word_count( wp_strip_all_tags( $current_content ) ),
		);
	}

	/**
	 * Send a message to a session.
	 *
	 * @param string $session_id   Session ID.
	 * @param string $message      User message.
	 * @param string $context      Optional context.
	 * @param string $model        Model override.
	 * @param bool   $professional Professional mode.
	 * @return array|\WP_Error Response data.
	 */
	public function send_message( $session_id, $message, $context = '', $model = '', $professional = false ) {
		$session = $this->sessions->get( $session_id );
		if ( ! $session ) {
			return new \WP_Error( 'invalid_session', 'Session not found' );
		}

		$conversation = json_decode( $session->conversation, true ) ?: array();
		$current_content = $session->current_content;

		// Add user message.
		$conversation[] = array(
			'role'      => 'user',
			'content'   => $message,
			'timestamp' => current_time( 'mysql' ),
		);

		// Classify intent.
		$intent = $this->classifier->classify( $message );
		$requirements = $this->sessions->get_requirements( $session );

		// Get AI response.
		$ai_result = $this->get_ai_response(
			$conversation,
			$current_content,
			$model,
			$intent,
			$requirements
		);

		if ( is_wp_error( $ai_result ) ) {
			return $ai_result;
		}

		$ai_message = $ai_result['message'];
		$new_content = $ai_result['content'] ?? '';

		// Polish if professional mode.
		if ( $professional && ! empty( $new_content ) ) {
			$polished = $this->polish_content( $new_content, $model );
			if ( ! empty( $polished['content'] ) ) {
				$new_content = $polished['content'];
				$ai_message = $polished['message'] ?: $ai_message;
			}
		}

		// Update content if provided.
		if ( ! empty( $new_content ) ) {
			$current_content = $new_content;
			$this->sessions->save_version( $session_id, $current_content );
		}

		// Add AI response.
		$conversation[] = array(
			'role'        => 'assistant',
			'content'     => $ai_message,
			'timestamp'   => current_time( 'mysql' ),
			'has_content' => ! empty( $new_content ),
		);

		// Update session.
		$this->sessions->update( $session_id, array(
			'conversation'    => $conversation,
			'current_content' => $current_content,
		) );

		// Update requirements if provided.
		if ( ! empty( $ai_result['requirements_update'] ) ) {
			$this->sessions->update_requirements( $session_id, $ai_result['requirements_update'] );
		}

		return array(
			'message'     => $ai_message,
			'content'     => $current_content,
			'has_content' => ! empty( $new_content ),
			'word_count'  => str_word_count( wp_strip_all_tags( $current_content ) ),
			'intent'      => $intent,
		);
	}

	/**
	 * Get AI response using function calling.
	 *
	 * @param array  $messages     Conversation messages.
	 * @param string $content      Current content.
	 * @param string $model        Model ID.
	 * @param string $intent       Deprecated - not used with function calling.
	 * @param array  $requirements Session requirements.
	 * @return array|\WP_Error Response.
	 */
	private function get_ai_response( $messages, $content, $model, $intent, $requirements ) {
		if ( is_wp_error( $this->ai_provider ) ) {
			return $this->ai_provider;
		}

		// Check if provider supports function calling.
		if ( ! method_exists( $this->ai_provider, 'generate_with_tools' ) ) {
			// Fallback to old method.
			return $this->get_ai_response_legacy( $messages, $content, $model, $intent, $requirements );
		}

		$has_content = ! empty( $content );

		// Build messages for API.
		$api_messages = array(
			array(
				'role'    => 'system',
				'content' => Action_Handler::get_system_prompt( $has_content ),
			),
		);

		// Add current content context if exists.
		if ( $has_content ) {
			$api_messages[] = array(
				'role'    => 'system',
				'content' => "CURRENT ARTICLE:\n{$content}\n\nWhen making edits, return the COMPLETE updated article.",
			);
		}

		// Add conversation history (last 10 messages).
		$recent = array_slice( $messages, -10 );
		foreach ( $recent as $msg ) {
			if ( in_array( $msg['role'], array( 'user', 'assistant' ), true ) ) {
				$api_messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}

		// Get tool definition.
		$tools = array( Action_Handler::get_tool_definition() );

		try {
			// Call OpenAI with function calling.
			$response = $this->ai_provider->generate_with_tools(
				$api_messages,
				$tools,
				array(
					'max_tokens'  => 4000,
					'temperature' => 0.7,
					'model'       => $model,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Parse tool call response.
			$action_data = Action_Handler::parse_tool_call( $response['tool_calls'] ?? null );
			
			if ( ! $action_data ) {
				// No tool call - return plain response.
				return array(
					'message' => $response['content'] ?? 'I processed your request.',
					'content' => '',
				);
			}

			$action = $action_data['action'];
			$ai_message = $action_data['response'];
			$content_html = $action_data['content_html'] ?? '';

			// Single-call: content comes directly from the function call
			return array(
				'message' => $ai_message,
				'content' => $content_html,
				'action'  => $action,
				'target'  => $action_data['target'],
			);

		} catch ( \Exception $e ) {
			return new \WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Legacy AI response (for non-OpenAI providers).
	 */
	private function get_ai_response_legacy( $messages, $content, $model, $intent, $requirements ) {
		$phase = $this->classifier->get_phase( $intent );

		switch ( $phase ) {
			case 'conversation':
				$system_prompt = $this->prompts->get_conversation_prompt( $requirements );
				break;
			case 'finalize':
				$system_prompt = $this->prompts->get_finalize_prompt( $requirements );
				break;
			default:
				$system_prompt = $this->prompts->get_system_prompt();
		}

		$context = '';
		if ( ! empty( $content ) ) {
			$context .= "=== CURRENT ARTICLE ===\n{$content}\n=== END ===\n\n";
		}

		$context .= "=== CONVERSATION ===\n";
		$recent = array_slice( $messages, -10 );
		foreach ( $recent as $msg ) {
			if ( $msg['role'] !== 'system' ) {
				$role = $msg['role'] === 'user' ? 'User' : 'Assistant';
				$context .= "{$role}: {$msg['content']}\n\n";
			}
		}
		$context .= "=== END ===\n\n";

		$full_prompt = $context . $this->prompts->get_phase_instruction( $phase );

		try {
			$response = $this->ai_provider->generate_content(
				$system_prompt,
				$full_prompt,
				array(
					'max_tokens'  => 4000,
					'temperature' => 0.7,
					'model'       => $model,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$content_string = is_array( $response ) ? ( $response['content'] ?? '' ) : $response;
			return $this->parse_response( $content_string, $this->classifier->expects_content( $intent ) );

		} catch ( \Exception $e ) {
			return new \WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Parse AI response.
	 *
	 * @param string $response        Raw response.
	 * @param bool   $expects_content Whether content is expected.
	 * @return array Parsed response.
	 */
	private function parse_response( $response, $expects_content = true ) {
		$response = trim( $response );

		// Try JSON.
		if ( preg_match( '/\{[\s\S]*\}/m', $response, $matches ) ) {
			$json = json_decode( $matches[0], true );
			if ( $json && ! empty( $json['message'] ) ) {
				return array(
					'message'             => $json['message'],
					'content'             => $expects_content ? ( $json['content_html'] ?? '' ) : '',
					'requirements_update' => $json['requirements_update'] ?? null,
				);
			}
		}

		// Check for HTML.
		if ( $expects_content && preg_match( '/<h[1-6]|<p>|<article/i', $response ) ) {
			preg_match( '/(<(?:article|div|h[1-6]|p)[\s\S]*)/i', $response, $matches );
			return array(
				'message' => 'Content created/updated.',
				'content' => $matches[1] ?? $response,
			);
		}

		return array(
			'message' => $response,
			'content' => '',
		);
	}

	/**
	 * Polish content.
	 *
	 * @param string $content Content.
	 * @param string $model   Model.
	 * @return array Polished content.
	 */
	private function polish_content( $content, $model = '' ) {
		if ( is_wp_error( $this->ai_provider ) ) {
			return array( 'content' => $content, 'message' => '' );
		}

		try {
			$response = $this->ai_provider->generate_content(
				$this->prompts->get_polish_prompt(),
				"Polish this article:\n\n{$content}",
				array( 'max_tokens' => 4000, 'model' => $model )
			);

			if ( is_wp_error( $response ) ) {
				return array( 'content' => $content, 'message' => '' );
			}

			$parsed = $this->parse_response( $response['content'] ?? '', true );
			return array(
				'content' => $parsed['content'] ?: $content,
				'message' => $parsed['message'] ?? '',
			);
		} catch ( \Exception $e ) {
			return array( 'content' => $content, 'message' => '' );
		}
	}

	/**
	 * Get Session Manager instance.
	 *
	 * @return Session_Manager
	 */
	public function get_session_manager() {
		return $this->sessions;
	}
}

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
 * Chat Builder - Conversational Article Builder with Live Preview
 *
 * @package AI_Content_Engine
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chat Builder class for conversational article generation.
 */
class Chat_Builder {

	/**
	 * AI provider instance.
	 *
	 * @var ACE\AI_Provider
	 */
	private $ai_provider;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;
	/**
	 * Versions table name.
	 *
	 * @var string
	 */
	private $versions_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ace_chat_sessions';
		$this->versions_table = $wpdb->prefix . 'ace_chat_versions';
		
		$this->ai_provider = \ACE\Provider_Factory::get_active();

		if ( ! $this->table_exists() || ! $this->versions_table_exists() ) {
			self::create_table();
		}
	}

	/**
	 * Create database table on activation.
	 */
	public static function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ace_chat_sessions';
		$versions_table  = $wpdb->prefix . 'ace_chat_versions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			title VARCHAR(255) DEFAULT '',
			current_content LONGTEXT,
			conversation LONGTEXT,
			meta_data LONGTEXT,
			status VARCHAR(20) DEFAULT 'active',
			pinned TINYINT(1) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY session_id (session_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY pinned (pinned)
		) $charset_collate;";

		$versions_sql = "CREATE TABLE $versions_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(64) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			content LONGTEXT NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		dbDelta( $versions_sql );
	}

	/**
	 * Start a new chat session.
	 *
	 * @param string $topic Initial topic or brief.
	 * @return array Session data with initial AI response.
	 */
	public function start_session( $topic = '', $model = '', $keywords = '', $professional = false ) {
		global $wpdb;

		$session_id = wp_generate_uuid4();
		$user_id    = get_current_user_id();

		// Initial system context.
		$system_prompt = $this->get_system_prompt();

		// Initial conversation.
		$conversation = array(
			array(
				'role'      => 'system',
				'content'   => $system_prompt,
				'timestamp' => current_time( 'mysql' ),
			),
		);

		// If topic provided, add initial user message.
		$initial_response = '';
		$current_content  = '';

		if ( ! empty( $topic ) ) {
			$conversation[] = array(
				'role'      => 'user',
				'content'   => $topic,
				'timestamp' => current_time( 'mysql' ),
			);

			// Get AI response.
			$ai_result = $this->get_ai_response(
				$conversation,
				'',
				$model,
				$this->message_requests_content( $topic )
			);
			
			if ( ! is_wp_error( $ai_result ) ) {
				$initial_response = $ai_result['message'];
				$current_content  = $ai_result['content'] ?? '';
				$seo_meta         = $ai_result['meta'] ?? array();

				if ( $professional && ! empty( $current_content ) ) {
					$polished = $this->polish_content_html( $current_content, $model );
					if ( ! is_wp_error( $polished ) && ! empty( $polished['content'] ) ) {
						$current_content  = $polished['content'];
						$initial_response = $polished['message'] ?: $initial_response;
					}
				}
				
				$conversation[] = array(
					'role'      => 'assistant',
					'content'   => $initial_response,
					'timestamp' => current_time( 'mysql' ),
					'has_content' => ! empty( $current_content ),
				);
			}
		}

		// Save to database.
		$wpdb->insert(
			$this->table_name,
			array(
				'session_id'      => $session_id,
				'user_id'         => $user_id,
				'title'           => $this->generate_title( $topic ),
				'current_content' => $current_content,
				'conversation'    => wp_json_encode( $conversation ),
				'meta_data'       => wp_json_encode(
					array(
						'topic'    => $topic,
						'model'    => $model,
						'keywords' => $keywords,
						'professional' => (bool) $professional,
						'seo_meta' => $seo_meta,
						'requirements' => array(
							'topic' => $topic,
							'tone' => 'professional',
							'length' => 'medium',
							'blocks' => array(),
							'notes' => array(),
						),
					)
				),
				'status'          => 'active',
				'pinned'          => 0,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( ! empty( $current_content ) ) {
			$this->save_version( $session_id, $current_content );
		}

		return array(
			'session_id'    => $session_id,
			'title'         => $this->generate_title( $topic ),
			'conversation'  => $conversation,
			'content'       => $current_content,
			'initial_message' => $initial_response,
		);
	}

	/**
	 * Send a message and get AI response.
	 *
	 * @param string $session_id Session ID.
	 * @param string $message User message.
	 * @return array|WP_Error Response data or error.
	 */
	public function send_message( $session_id, $message, $model = '', $keywords = '', $professional = false ) {
		global $wpdb;

		// Get session.
		$session = $this->get_session( $session_id );
		if ( ! $session ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$conversation = json_decode( $session->conversation, true ) ?: array();
		$current_content = $session->current_content;

		// Add user message.
		$conversation[] = array(
			'role'      => 'user',
			'content'   => $message,
			'timestamp' => current_time( 'mysql' ),
		);

		$context_message = $message;

		// Build messages for API.
		$api_messages = $this->build_api_messages( $conversation, $context_message );

		// Get AI response - pass current content for context.
		// Classify user intent
		$intent = $this->classify_intent( $message );
		$expects_content = $this->intent_expects_content( $intent );
		
		// Get AI response with intent-based routing
		$ai_result = $this->get_ai_response(
			$api_messages,
			$current_content,
			$model,
			$expects_content,
			$intent,
			array(
				'requirements' => json_decode( $session->meta_data, true )['requirements'] ?? array(),
			)
		);

		if ( is_wp_error( $ai_result ) ) {
			return $ai_result;
		}

		$ai_message   = $ai_result['message'];
		$new_content  = $ai_result['content'] ?? '';
		$seo_meta     = $ai_result['meta'] ?? array();

		if ( $professional && ! empty( $new_content ) ) {
			$polished = $this->polish_content_html( $new_content, $model );
			if ( ! is_wp_error( $polished ) && ! empty( $polished['content'] ) ) {
				$new_content = $polished['content'];
				if ( ! empty( $polished['message'] ) ) {
					$ai_message = $polished['message'];
				}
			}
		}

		// Update content if new content provided.
		if ( ! empty( $new_content ) ) {
			$current_content = $new_content;
			$this->save_version( $session_id, $current_content );
		}

		// Add AI response to conversation.
		$conversation[] = array(
			'role'        => 'assistant',
			'content'     => $ai_message,
			'timestamp'   => current_time( 'mysql' ),
			'has_content' => ! empty( $new_content ),
		);

		$update_data = array(
			'conversation'    => wp_json_encode( $conversation ),
			'current_content' => $current_content,
			'title'           => $this->maybe_update_title( $session->title, $conversation ),
		);
		$update_format = array( '%s', '%s', '%s' );

		$meta = json_decode( $session->meta_data, true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		if ( ! empty( $model ) ) {
			$meta['model'] = $model;
		}
		if ( ! empty( $keywords ) ) {
			$meta['keywords'] = $keywords;
		}
		if ( ! empty( $seo_meta ) ) {
			$meta['seo_meta'] = $seo_meta;
		}
		$meta['professional'] = (bool) $professional;
		$update_data['meta_data'] = wp_json_encode( $meta );
		$update_format[] = '%s';

		$wpdb->update(
			$this->table_name,
			$update_data,
			array( 'session_id' => $session_id ),
			$update_format,
			array( '%s' )
		);

		return array(
			'message'     => $ai_message,
			'content'     => $current_content,
			'has_content' => ! empty( $new_content ),
			'word_count'  => str_word_count( wp_strip_all_tags( $current_content ) ),
		);
	}

	/**
	 * Get AI response using function calling (single-call approach).
	 *
	 * @param array  $messages        Conversation messages.
	 * @param string $current_content Current article content.
	 * @param string $model           Model to use.
	 * @param bool   $expects_content Deprecated - function calling handles this.
	 * @param string $intent          Deprecated - function calling handles this.
	 * @param array  $session_data    Session data.
	 * @return array|WP_Error AI response data.
	 */
	private function get_ai_response( $messages, $current_content = '', $model = '', $expects_content = true, $intent = '', $session_data = array() ) {
		if ( is_wp_error( $this->ai_provider ) ) {
			return $this->ai_provider;
		}

		$model = $this->resolve_model( $model );

		// Check if provider supports function calling
		if ( method_exists( $this->ai_provider, 'generate_with_tools' ) ) {
			return $this->get_ai_response_function_calling( $messages, $current_content, $model );
		}

		// Fallback to legacy for non-OpenAI providers
		return $this->get_ai_response_legacy( $messages, $current_content, $model, $expects_content, $intent, $session_data );
	}

	/**
	 * Get AI response using OpenAI function calling (single-call).
	 */
	private function get_ai_response_function_calling( $messages, $current_content, $model ) {
		$has_content = ! empty( $current_content );

		// Build messages for API
		$api_messages = array(
			array(
				'role'    => 'system',
				'content' => \ACE\Chat\Action_Handler::get_system_prompt( $has_content ),
			),
		);

		// Add current content context if exists
		if ( $has_content ) {
			$api_messages[] = array(
				'role'    => 'system',
				'content' => "CURRENT ARTICLE:\n{$current_content}\n\nFor edit actions, return the COMPLETE updated article in content_html.",
			);
		}

		// Add conversation history (last 10 messages)
		$recent = array_slice( $messages, -10 );
		foreach ( $recent as $msg ) {
			if ( in_array( $msg['role'], array( 'user', 'assistant' ), true ) ) {
				$api_messages[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}

		// Get tool definition
		$tools = array( \ACE\Chat\Action_Handler::get_tool_definition() );

		// Determine max_tokens based on model
		$max_tokens = 4000; // Safe default
		if ( strpos( $model, 'gpt-4o' ) !== false ) {
			$max_tokens = 8000; // GPT-4o and GPT-4o-mini support 16K
		} elseif ( strpos( $model, 'gpt-4' ) !== false && strpos( $model, 'turbo' ) === false ) {
			$max_tokens = 6000; // GPT-4 supports 8K
		}

		try {
			// Call OpenAI with function calling
			$response = $this->ai_provider->generate_with_tools(
				$api_messages,
				$tools,
				array(
					'max_tokens'  => $max_tokens,
					'temperature' => 0.7,
					'model'       => $model,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Parse tool call response
			$action_data = \ACE\Chat\Action_Handler::parse_tool_call( $response['tool_calls'] ?? null );

			if ( ! $action_data ) {
				// No tool call - return plain response
				return array(
					'message' => $response['content'] ?? 'I processed your request.',
					'content' => '',
				);
			}

			return array(
				'message' => $action_data['response'],
				'content' => $action_data['content_html'] ?? '',
				'action'  => $action_data['action'],
			);

		} catch ( Exception $e ) {
			return new WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Legacy AI response (for non-OpenAI providers).
	 */
	private function get_ai_response_legacy( $messages, $current_content, $model, $expects_content, $intent, $session_data ) {
		$phase = $this->get_phase_for_intent( $intent );
		$requirements = $this->get_session_requirements( $session_data );

		switch ( $phase ) {
			case 'conversation':
				$system_prompt = $this->get_conversation_prompt( $requirements );
				break;
			case 'finalize':
				$system_prompt = $this->get_finalize_prompt( $requirements, 'html' );
				break;
			default:
				$system_prompt = $this->get_system_prompt();
		}

		$context = '';
		if ( ! empty( $current_content ) ) {
			$context .= "=== CURRENT ARTICLE ===\n{$current_content}\n=== END ===\n\n";
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

		$full_prompt = $context . $this->get_phase_instruction( $phase );

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
			return $this->parse_ai_response( $content_string, $expects_content );

		} catch ( Exception $e ) {
			return new WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Parse AI response to extract message and content.
	 *
	 * @param string $response Raw AI response.
	 * @return array Parsed message and content.
	 */
	private function parse_ai_response( $response, $expects_content = true ) {
		// Try to parse as JSON first.
		$decoded = json_decode( $response, true );

		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			$content = $decoded['content_html'] ?? $decoded['content'] ?? '';
			if ( ! $expects_content ) {
				$content = '';
			}
			return array(
				'message' => $decoded['message'] ?? $decoded['response'] ?? 'Content updated.',
				'content' => $content,
				'meta'    => $decoded['meta'] ?? array(),
			);
		}

		// Check if response contains HTML content markers.
		if ( $expects_content && preg_match( '/<h[1-6]|<p>|<article/i', $response ) ) {
			// Extract HTML content.
			preg_match( '/(<(?:article|div|h[1-6]|p)[\s\S]*)/i', $response, $matches );
			
			return array(
				'message' => "I've created/updated the article. Check the preview!",
				'content' => $matches[1] ?? $response,
				'meta'    => array(),
			);
		}

		// Plain text response.
		return array(
			'message' => $response,
			'content' => '',
			'meta'    => array(),
		);
	}

	/**
	 * Classify user intent using LLM (works in any language).
	 *
	 * @param string $message User message.
	 * @return string Intent: generate, add, remove, replace, tone, length, question, other
	 */
	private function classify_intent( $message ) {
		// Use cache to avoid redundant classification calls
		$cache_key = 'ace_intent_' . md5( $message );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Quick local check for very short greetings (any language patterns)
		$message_lower = strtolower( trim( $message ) );
		
		// Quick check for finalize patterns first - most important to catch
		$finalize_patterns = array(
			'go', 'done', 'ok', 'start', 'generate', 'create', 'write',
			'let\'s go', 'go ahead', 'do it', 'make it', 'build it',
			'go generating', 'start generating', 'generate it', 'create it',
			'yes go', 'yes start', 'yes generate', 'yes create',
			'yep', 'yeah', 'sure', 'proceed', 'continue',
			// French
			'allons-y', 'vas-y', 'créer', 'génère',
			// Arabic
			'يلا', 'ابدأ',
		);
		
		foreach ( $finalize_patterns as $pattern ) {
			if ( strpos( $message_lower, $pattern ) !== false ) {
				// Cache and return finalize
				set_transient( $cache_key, 'finalize', 5 * MINUTE_IN_SECONDS );
				return 'finalize';
			}
		}
		
		// Short messages with "yes" are likely confirmation to finalize
		if ( strlen( $message_lower ) < 20 && preg_match( '/^(yes|yep|yeah|ok|okay|sure|right|correct|exactly|perfect)/', $message_lower ) ) {
			set_transient( $cache_key, 'finalize', 5 * MINUTE_IN_SECONDS );
			return 'finalize';
		}
		
		if ( strlen( $message_lower ) < 15 ) {
			// Common greetings in various languages
			$greetings = array( 'hi', 'hello', 'hey', 'bonjour', 'salut', 'hola', 'مرحبا', 'hallo', 'ciao' );
			foreach ( $greetings as $greeting ) {
				if ( strpos( $message_lower, $greeting ) === 0 ) {
					return 'question';
				}
			}
		}

		// Use LLM to classify intent
		if ( is_wp_error( $this->ai_provider ) ) {
			// Fallback to assuming content generation if no AI available
			return 'generate';
		}

		$intent_prompt = $this->get_intent_classification_prompt( $message );
		
		try {
			$result = $this->ai_provider->generate_content( $intent_prompt, '', array(
				'max_tokens' => 20,
				'temperature' => 0,
			) );

			if ( is_wp_error( $result ) ) {
				return 'generate'; // Default fallback
			}

			$intent = strtolower( trim( $result['content'] ?? 'generate' ) );
			
			// Normalize to valid intents
			$valid_intents = array( 'generate', 'add', 'remove', 'replace', 'tone', 'length', 'question', 'other' );
			if ( ! in_array( $intent, $valid_intents, true ) ) {
				$intent = 'generate';
			}

			// Cache for 5 minutes
			set_transient( $cache_key, $intent, 5 * MINUTE_IN_SECONDS );

			return $intent;
		} catch ( \Exception $e ) {
			return 'generate';
		}
	}

	/**
	 * Get the prompt for intent classification.
	 *
	 * @param string $message User message to classify.
	 * @return string Classification prompt.
	 */
	private function get_intent_classification_prompt( $message ) {
		$word_count = str_word_count( $message );
		$is_long_text = $word_count > 200;
		
		return <<<PROMPT
Classify this user message into ONE category. Reply with ONLY the category name, nothing else.

Categories:
- conversation: Discussing topic ideas, asking for suggestions, general chat about what to write
- request_block: Asking to add FAQ, pros/cons, table, callout, key takeaways to the plan
- finalize: User is ready to generate (says "go", "create it", "generate", "done", "let's do it")
- import: User is pasting/sharing existing article text to be formatted (usually long text with no question)
- edit_add: Add new section/paragraph to existing content
- edit_remove: Delete/remove content from existing article
- edit_replace: Change/swap/replace specific content
- edit_tone: Change writing style (formal, casual, professional)
- edit_length: Make content shorter or longer
- question: Asking how something works, greeting, general help question

Context: Message has {$word_count} words. Long text (>200 words) without questions is likely "import".

User message: "{$message}"

Category:
PROMPT;
	}

	/**
	 * Determine if intent expects content changes/generation.
	 *
	 * @param string $intent The classified intent.
	 * @return bool
	 */
	private function intent_expects_content( $intent ) {
		$content_intents = array( 'finalize', 'import', 'edit_add', 'edit_remove', 'edit_replace', 'edit_tone', 'edit_length' );
		return in_array( $intent, $content_intents, true );
	}

	/**
	 * Get the phase for a given intent.
	 *
	 * @param string $intent The classified intent.
	 * @return string Phase: 'conversation', 'finalize', 'import', 'edit'
	 */
	private function get_phase_for_intent( $intent ) {
		switch ( $intent ) {
			case 'conversation':
			case 'request_block':
			case 'question':
				return 'conversation';
			case 'finalize':
				return 'finalize';
			case 'import':
				return 'import';
			case 'edit_add':
			case 'edit_remove':
			case 'edit_replace':
			case 'edit_tone':
			case 'edit_length':
				return 'edit';
			default:
				return 'conversation';
		}
	}

	/**
	 * Get session requirements from session data.
	 *
	 * @param array $session_data Session data.
	 * @return array Requirements array.
	 */
	private function get_session_requirements( $session_data ) {
		return $session_data['requirements'] ?? array(
			'topic' => '',
			'tone' => 'professional',
			'length' => 'medium',
			'blocks' => array(),
			'notes' => array(),
		);
	}

	/**
	 * Update session requirements.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $updates    Updates to apply.
	 * @return bool
	 */
	private function update_session_requirements( $session_id, $updates ) {
		$session = $this->get_session( $session_id );
		if ( ! $session ) {
			return false;
		}

		$requirements = $this->get_session_requirements( $session );
		$requirements = array_merge( $requirements, $updates );
		
		return $this->update_session( $session_id, array( 'requirements' => $requirements ) );
	}

	/**
	 * Get the instruction text for a given phase.
	 *
	 * @param string $phase The current phase.
	 * @return string Instruction text.
	 */
	private function get_phase_instruction( $phase ) {
		switch ( $phase ) {
			case 'conversation':
				return "Continue the conversation naturally. Help the user plan their article. Ask clarifying questions. Suggest content blocks they might want. Remember: do NOT generate a full article yet - just have a planning discussion. Respond with valid JSON with 'message' field.";
			
			case 'finalize':
				return "The user is ready! Generate the COMPLETE article now based on all the requirements collected during conversation. Return valid JSON with 'message', 'meta' (title, description, keywords), and 'content_html' containing the full HTML article.";
			
			case 'import':
				return "The user has shared existing content to format. Convert it to well-structured HTML while preserving their original text. Add any requested content blocks. Return valid JSON with 'message', 'meta' (title, description, keywords), and 'content_html'.";
			
			case 'edit':
			default:
				return "Based on the conversation above and the current article content, respond to the user's edit request. Always include the COMPLETE updated article in 'content_html' as valid HTML, and include 'meta' (title, description, keywords) when possible.";
		}
	}

	/**
	 * Legacy function - now uses intent classification.
	 *
	 * @param string $message User message.
	 * @return bool
	 */
	private function message_requests_content( $message ) {
		$intent = $this->classify_intent( $message );
		return $this->intent_expects_content( $intent );
	}

	/**
	 * Get system prompt for chat.
	 *
	 * @return string System prompt.
	 */
	private function get_system_prompt() {
		return <<<PROMPT
You are an AI article writing assistant. Your PRIMARY job is to WRITE COMPLETE ARTICLES when asked.

CRITICAL RULE:
When a user says "write", "create", "draft", "generate", or asks for a "blog", "article", or "post" about ANY topic - you MUST immediately generate a COMPLETE, FULL-LENGTH article. Do NOT just respond with suggestions or ask clarifying questions. WRITE THE ARTICLE.

RESPONSE FORMAT (MANDATORY):
You MUST respond with valid JSON in this exact format:
{
  "message": "Brief explanation of what you created/updated",
  "meta": {
    "title": "SEO title",
    "description": "Meta description (150-160 chars)",
    "keywords": ["keyword1", "keyword2"]
  },
  "content_html": "<h1>Article Title</h1><p>Full article content in HTML...</p>"
}

CONTENT REQUIREMENTS:
- content_html MUST contain a complete article (minimum 1000 words)
- Use proper HTML tags: h1 (title), h2 (sections), h3 (subsections), p, ul, ol, li, strong, em
- NO markdown - pure HTML only
- Include: introduction, multiple sections with h2 headings, conclusion
- Make it engaging, informative, and SEO-friendly

SPECIAL CONTENT BLOCKS (use when appropriate):

1. FAQ Accordion (for Q&A sections):
<div class="ace-faq">
  <details><summary>Question here?</summary><p>Answer here.</p></details>
  <details><summary>Another question?</summary><p>Another answer.</p></details>
</div>

2. Callout Boxes (for important info, tips, warnings):
<div class="ace-callout ace-callout-info"><strong>Note:</strong> Important information.</div>
<div class="ace-callout ace-callout-warning"><strong>Warning:</strong> Be careful about this.</div>
<div class="ace-callout ace-callout-tip"><strong>Tip:</strong> Helpful suggestion.</div>

3. Key Takeaways (summary box at end of article):
<div class="ace-takeaways">
<strong>Key Takeaways</strong>
<ul><li>Main point 1</li><li>Main point 2</li><li>Main point 3</li></ul>
</div>

4. Table of Contents (after introduction):
<nav class="ace-toc">
<strong>In This Article</strong>
<ul><li><a href="#section1">Section 1</a></li><li><a href="#section2">Section 2</a></li></ul>
</nav>

5. Pros/Cons Comparison:
<div class="ace-proscons">
<div class="ace-pros"><strong>✓ Pros</strong><ul><li>Advantage 1</li><li>Advantage 2</li></ul></div>
<div class="ace-cons"><strong>✗ Cons</strong><ul><li>Disadvantage 1</li><li>Disadvantage 2</li></ul></div>
</div>

6. Stat Highlight (for impressive numbers):
<div class="ace-stat"><span class="ace-stat-number">89%</span><span class="ace-stat-label">of businesses report this</span></div>

7. Image Placeholder (suggest where images should go):
<figure class="ace-image-placeholder"><div class="ace-placeholder-icon">🖼️</div><figcaption>Suggested: Infographic comparing tax rates</figcaption></figure>

ARTICLE STRUCTURE:
- Start with engaging h1 title
- Brief introduction paragraph
- Add Table of Contents for long articles
- Multiple h2 sections with detailed content
- Include FAQ section when relevant
- End with Key Takeaways box
- Use callouts for important points throughout

EDITING EXISTING CONTENT:

When the user wants to MODIFY existing content, follow these rules:

1. REMOVE requests ("remove the section about X", "delete the FAQ"):
   - Remove ONLY the specified section/element
   - Keep ALL other content exactly as-is
   - Return the complete updated article

2. REPLACE requests ("replace X with Y", "change the intro to..."):
   - Find and replace the specified content
   - Keep surrounding content unchanged
   - Maintain document structure

3. ADD/INSERT requests ("add a FAQ", "insert pros/cons after section 2"):
   - Keep ALL existing content intact
   - Insert new content at the appropriate location
   - Use proper heading hierarchy

4. TONE adjustments ("make it conversational", "more professional"):
   - Rewrite with the requested tone/style
   - Keep the same facts, structure, and sections
   - Preserve all formatting and special blocks

5. LENGTH adjustments ("make it shorter", "expand the intro"):
   - Shorten: Remove redundancy, keep key points
   - Lengthen: Add more detail, examples, depth
   - Apply to specified section or whole article

6. REORDER requests ("move section A before B"):
   - Reorganize sections as requested
   - Update Table of Contents if present
   - Maintain logical transitions

CRITICAL: For ALL operations, return the COMPLETE updated article in content_html, not just the changed portion.

REMEMBER: When user wants content, ALWAYS return JSON with content_html containing the COMPLETE article with rich formatting. Use the special blocks to make articles visually engaging.
PROMPT;
	}

	/**
	 * Get system prompt for conversation phase (planning/discussing).
	 *
	 * @param array $requirements Current session requirements.
	 * @return string System prompt.
	 */
	private function get_conversation_prompt( $requirements = array() ) {
		$blocks_list = ! empty( $requirements['blocks'] ) ? implode( ', ', $requirements['blocks'] ) : 'none yet';
		$notes_list = ! empty( $requirements['notes'] ) ? implode( '; ', $requirements['notes'] ) : 'none yet';
		
		return <<<PROMPT
You are a helpful content planning assistant. You're having a conversation to understand what the user wants to write about.

YOUR ROLE IN THIS PHASE:
- Ask clarifying questions about their topic
- Suggest angles, approaches, and content ideas
- Help them decide what to include
- Remember their preferences for the final article

DO NOT generate a full article yet. Just have a helpful planning conversation.

CURRENT REQUIREMENTS COLLECTED:
- Topic: {$requirements['topic']}
- Tone: {$requirements['tone']}
- Length: {$requirements['length']}
- Special blocks requested: {$blocks_list}
- Additional notes: {$notes_list}

AVAILABLE BLOCKS (mention these as options):
- FAQ section (collapsible Q&A)
- Key Takeaways box
- Pros/Cons comparison
- Table of Contents
- Callout boxes (tips, warnings, notes)
- Statistics highlights
- Comparison tables

You MUST respond with valid JSON in this format:
{
  "message": "Your conversational response here",
  "requirements_update": {
    "topic": "Updated topic if mentioned",
    "tone": "formal|casual|professional|friendly",
    "blocks": ["faq", "takeaways", "proscons"],
    "notes": ["any specific requests"]
  }
}

When user is ready to generate (says "go", "create it", "done"), acknowledge and prepare for generation.
PROMPT;
	}

	/**
	 * Get system prompt for finalize phase (content generation).
	 *
	 * @param array  $requirements Session requirements.
	 * @param string $format       Output format: 'markdown' or 'html'.
	 * @return string System prompt.
	 */
	private function get_finalize_prompt( $requirements, $format = 'markdown' ) {
		$blocks_json = wp_json_encode( $requirements['blocks'] ?? array() );
		$notes_json = wp_json_encode( $requirements['notes'] ?? array() );
		
		$format_instruction = $format === 'html' 
			? 'Output in pure HTML with proper tags (h1, h2, p, ul, li, etc.).'
			: 'Output in clean Markdown format.';
		
		return <<<PROMPT
You are a professional content writer. Generate a complete, high-quality article based on these requirements:

ARTICLE REQUIREMENTS:
- Topic: {$requirements['topic']}
- Tone: {$requirements['tone']}
- Length: {$requirements['length']} (short=500 words, medium=1000 words, long=2000+ words)
- Include these blocks: {$blocks_json}
- Special notes: {$notes_json}

{$format_instruction}

REQUIRED BLOCKS TO INCLUDE:
PROMPT;
	}

	/**
	 * Get system prompt for import phase (converting existing text).
	 *
	 * @param array $requested_blocks Blocks to add.
	 * @return string System prompt.
	 */
	private function get_import_prompt( $requested_blocks = array() ) {
		$blocks_json = wp_json_encode( $requested_blocks );
		
		return <<<PROMPT
You are converting existing article text into properly formatted HTML with rich content blocks.

YOUR TASK:
1. Preserve ALL the original content and meaning
2. Structure it with proper HTML (h1 for title, h2 for sections, p for paragraphs)
3. Add the requested content blocks: {$blocks_json}

AVAILABLE BLOCKS:
- ace-faq: Add <div class="ace-faq"><details><summary>Q?</summary><p>A</p></details></div>
- ace-takeaways: Add <div class="ace-takeaways"><strong>Key Takeaways</strong><ul>...</ul></div>
- ace-proscons: Add comparison box
- ace-callout: Add <div class="ace-callout ace-callout-tip">...</div>
- ace-toc: Add table of contents

RESPONSE FORMAT:
{
  "message": "Brief description of formatting applied",
  "meta": {
    "title": "SEO title",
    "description": "Meta description",
    "keywords": ["keyword1", "keyword2"]
  },
  "content_html": "<h1>Title</h1>..."
}

Keep the author's voice and style. Only improve structure and add requested blocks.
PROMPT;
	}

	/**
	 * Get system prompt for professional polish.
	 *
	 * @return string
	 */
	private function get_polish_prompt() {
		return <<<PROMPT
You are an expert editor. Your job is to polish HTML articles to be professional and publication-ready.

RULES:
- Return ONLY valid JSON with "message" and "content_html"
- Preserve the original meaning and facts
- Improve clarity, flow, and conciseness
- Fix grammar, tone, and structure
- Keep all content in HTML (no markdown)
- Do NOT remove important sections

RESPONSE FORMAT:
{
  "message": "Brief note about improvements",
  "content_html": "<h1>...</h1><p>Polished HTML...</p>"
}
PROMPT;
	}

	/**
	 * Polish HTML content for professional output.
	 *
	 * @param string $content HTML content.
	 * @param string $model   Model ID.
	 * @return array|\WP_Error
	 */
	private function polish_content_html( $content, $model = '' ) {
		if ( is_wp_error( $this->ai_provider ) ) {
			return $this->ai_provider;
		}

		$model = $this->resolve_model( $model );

		$user_prompt = "Polish the following HTML article for professional publication. Return JSON exactly matching the required schema.\n\nCONTENT:\n" . $content;

		try {
			$response = $this->ai_provider->generate_content(
				$this->get_polish_prompt(),
				$user_prompt,
				array(
					'max_tokens'  => 4000,
					'temperature' => 0.3,
					'model'       => $model,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$content_string = is_array( $response ) ? ( $response['content'] ?? '' ) : $response;
			return $this->parse_ai_response( $content_string, true );
		} catch ( Exception $e ) {
			return new WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Build API messages from conversation.
	 *
	 * @param array  $conversation Full conversation.
	 * @param string $latest_message Latest user message with context.
	 * @return array API-formatted messages.
	 */
	private function build_api_messages( $conversation, $latest_message ) {
		$messages = array();

		// Add last few exchanges for context (limit to prevent token overflow).
		$recent = array_slice( $conversation, -8 );
		
		foreach ( $recent as $msg ) {
			$messages[] = array(
				'role'    => $msg['role'],
				'content' => $msg['content'],
			);
		}

		if ( ! empty( $latest_message ) ) {
			$replaced = false;
			for ( $i = count( $messages ) - 1; $i >= 0; $i-- ) {
				if ( 'user' === $messages[ $i ]['role'] ) {
					$messages[ $i ]['content'] = $latest_message;
					$replaced = true;
					break;
				}
			}

			if ( ! $replaced ) {
				$messages[] = array(
					'role'    => 'user',
					'content' => $latest_message,
				);
			}
		}

		return $messages;
	}

	/**
	 * Resolve model from request or session metadata.
	 *
	 * @param string $model Model ID.
	 * @return string
	 */
	private function resolve_model( $model ) {
		if ( ! empty( $model ) ) {
			return $model;
		}

		$provider_id = get_option( 'ace_ai_provider', 'openai' );
		switch ( $provider_id ) {
			case 'claude':
				return get_option( 'ace_claude_model', 'claude-3-haiku-20240307' );
			case 'gemini':
				return get_option( 'ace_gemini_model', 'gemini-1.5-flash' );
			case 'openai':
			default:
				return get_option( 'ace_openai_model', 'gpt-3.5-turbo' );
		}
	}

	/**
	 * Get a session by ID.
	 *
	 * @param string $session_id Session ID.
	 * @return object|null Session object.
	 */
	public function get_session( $session_id ) {
		global $wpdb;
		
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE session_id = %s",
				$session_id
			)
		);
	}

	/**
	 * Get all sessions for current user.
	 *
	 * @param int $limit Number of sessions to return.
	 * @return array Session list.
	 */
	public function get_user_sessions( $limit = 20 ) {
		global $wpdb;
		
		// Check if table exists.
		if ( ! $this->table_exists() ) {
			return array();
		}
		
		$user_id = get_current_user_id();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT session_id, title, status, pinned, created_at, updated_at 
				FROM {$this->table_name} 
				WHERE user_id = %d 
				ORDER BY pinned DESC, updated_at DESC 
				LIMIT %d",
				$user_id,
				$limit
			)
		) ?: array();
	}

	/**
	 * Save session as WordPress draft.
	 *
	 * @param string $session_id Session ID.
	 * @return int|WP_Error Post ID or error.
	 */
	public function save_as_draft( $session_id ) {
		$session = $this->get_session( $session_id );
		
		if ( ! $session ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		if ( empty( $session->current_content ) ) {
			return new WP_Error( 'no_content', 'No content to save.' );
		}

		// Extract title from content or use session title.
		$title = $session->title;
		if ( preg_match( '/<h1[^>]*>(.+?)<\/h1>/i', $session->current_content, $matches ) ) {
			$title = wp_strip_all_tags( $matches[1] );
		}

		// Create post.
		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $session->current_content,
				'post_status'  => 'draft',
				'post_type'    => 'post',
				'post_author'  => get_current_user_id(),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Add meta.
		update_post_meta( $post_id, '_ace_generated', true );
		update_post_meta( $post_id, '_ace_chat_session', $session_id );
		update_post_meta( $post_id, '_ace_word_count', str_word_count( wp_strip_all_tags( $session->current_content ) ) );

		$meta = json_decode( $session->meta_data, true );
		if ( is_array( $meta ) && ! empty( $meta['seo_meta'] ) ) {
			$seo = new \ACE\SEO_Integrations();
			$seo->init();
			$seo->save_seo_meta( $post_id, $meta['seo_meta'] );
		}

		// Update session status.
		$this->update_session_status( $session_id, 'completed' );

		return $post_id;
	}

	/**
	 * Publish session as WordPress post.
	 *
	 * @param string $session_id Session ID.
	 * @return int|WP_Error Post ID or error.
	 */
	public function publish( $session_id ) {
		$post_id = $this->save_as_draft( $session_id );
		
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Update to published.
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		return $post_id;
	}

	/**
	 * Delete a session.
	 *
	 * @param string $session_id Session ID.
	 * @return bool Success.
	 */
	public function delete_session( $session_id ) {
		global $wpdb;

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$deleted = $wpdb->delete(
			$this->table_name,
			array( 'session_id' => $session_id ),
			array( '%s' )
		);

		$wpdb->delete(
			$this->versions_table,
			array( 'session_id' => $session_id ),
			array( '%s' )
		);

		return false !== $deleted;
	}

	/**
	 * Update a session title.
	 *
	 * @param string $session_id Session ID.
	 * @param string $title New title.
	 * @return bool|WP_Error Success or error.
	 */
	public function update_session_title( $session_id, $title ) {
		global $wpdb;

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$title = sanitize_text_field( $title );
		if ( empty( $title ) ) {
			return new WP_Error( 'invalid_title', 'Title cannot be empty.' );
		}

		return $wpdb->update(
			$this->table_name,
			array( 'title' => $title ),
			array( 'session_id' => $session_id ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Duplicate a session.
	 *
	 * @param string $session_id Session ID.
	 * @return array|WP_Error New session data or error.
	 */
	public function duplicate_session( $session_id ) {
		global $wpdb;

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$new_session_id = wp_generate_uuid4();
		$new_title      = sprintf( 'Copy of %s', $session->title );

		$wpdb->insert(
			$this->table_name,
			array(
				'session_id'      => $new_session_id,
				'user_id'         => get_current_user_id(),
				'title'           => $new_title,
				'current_content' => $session->current_content,
				'conversation'    => $session->conversation,
				'meta_data'       => $session->meta_data,
				'status'          => 'active',
				'pinned'          => 0,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( ! empty( $session->current_content ) ) {
			$this->save_version( $new_session_id, $session->current_content );
		}

		return array(
			'session_id' => $new_session_id,
			'title'      => $new_title,
		);
	}

	/**
	 * Set pinned status for a session.
	 *
	 * @param string $session_id Session ID.
	 * @param bool   $pinned Whether to pin the session.
	 * @return bool|WP_Error Success or error.
	 */
	public function set_pinned( $session_id, $pinned ) {
		global $wpdb;

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		return $wpdb->update(
			$this->table_name,
			array( 'pinned' => $pinned ? 1 : 0 ),
			array( 'session_id' => $session_id ),
			array( '%d' ),
			array( '%s' )
		);
	}

	/**
	 * Get versions for a session.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $limit Number of versions.
	 * @return array|WP_Error Version list or error.
	 */
	public function get_versions( $session_id, $limit = 20 ) {
		global $wpdb;

		if ( ! $this->versions_table_exists() ) {
			return array();
		}

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$versions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, content, created_at FROM {$this->versions_table} WHERE session_id = %s ORDER BY id DESC LIMIT %d",
				$session_id,
				$limit
			)
		);

		$data = array();
		foreach ( $versions as $version ) {
			$data[] = array(
				'id'         => (int) $version->id,
				'content'    => $version->content,
				'created_at' => $version->created_at,
			);
		}

		return $data;
	}

	/**
	 * Restore a version as current content.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $version_id Version ID.
	 * @return array|WP_Error Restored data or error.
	 */
	public function restore_version( $session_id, $version_id ) {
		global $wpdb;

		$session = $this->get_session( $session_id );
		if ( ! $session || (int) $session->user_id !== get_current_user_id() ) {
			return new WP_Error( 'invalid_session', 'Session not found.' );
		}

		$version = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, content FROM {$this->versions_table} WHERE id = %d AND session_id = %s",
				$version_id,
				$session_id
			)
		);

		if ( ! $version ) {
			return new WP_Error( 'invalid_version', 'Version not found.' );
		}

		$wpdb->update(
			$this->table_name,
			array( 'current_content' => $version->content ),
			array( 'session_id' => $session_id ),
			array( '%s' ),
			array( '%s' )
		);

		$this->save_version( $session_id, $version->content );

		return array(
			'content' => $version->content,
		);
	}

	/**
	 * Save a content version.
	 *
	 * @param string $session_id Session ID.
	 * @param string $content HTML content.
	 * @return void
	 */
	private function save_version( $session_id, $content ) {
		global $wpdb;

		if ( ! $this->versions_table_exists() ) {
			return;
		}

		if ( empty( $content ) ) {
			return;
		}

		$wpdb->insert(
			$this->versions_table,
			array(
				'session_id' => $session_id,
				'user_id'    => get_current_user_id(),
				'content'    => $content,
			),
			array( '%s', '%d', '%s' )
		);
	}

	/**
	 * Check if the versions table exists.
	 *
	 * @return bool True if table exists.
	 */
	private function versions_table_exists() {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$this->versions_table
			)
		);

		return $result === $this->versions_table;
	}

	/**
	 * Update session status.
	 *
	 * @param string $session_id Session ID.
	 * @param string $status New status.
	 * @return bool Success.
	 */
	public function update_session_status( $session_id, $status ) {
		global $wpdb;
		
		return $wpdb->update(
			$this->table_name,
			array( 'status' => $status ),
			array( 'session_id' => $session_id ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Generate title from topic.
	 *
	 * @param string $topic Topic string.
	 * @return string Generated title.
	 */
	private function generate_title( $topic ) {
		if ( empty( $topic ) ) {
			return 'New Chat - ' . current_time( 'M j, g:i a' );
		}

		// Truncate topic for title.
		$title = wp_trim_words( $topic, 8, '...' );
		return $title;
	}

	/**
	 * Maybe update title based on content.
	 *
	 * @param string $current_title Current title.
	 * @param array  $conversation Conversation array.
	 * @return string Updated title.
	 */
	private function maybe_update_title( $current_title, $conversation ) {
		// Only update if it's a default title.
		if ( strpos( $current_title, 'New Chat -' ) === false ) {
			return $current_title;
		}

		// Get first substantial user message.
		foreach ( $conversation as $msg ) {
			if ( $msg['role'] === 'user' && strlen( $msg['content'] ) > 10 ) {
				return wp_trim_words( $msg['content'], 8, '...' );
			}
		}

		return $current_title;
	}

	/**
	 * Get session statistics.
	 *
	 * @return array Statistics.
	 */
	public function get_stats() {
		global $wpdb;
		
		// Check if table exists.
		if ( ! $this->table_exists() ) {
			return array(
				'total'     => 0,
				'completed' => 0,
				'active'    => 0,
			);
		}
		
		$user_id = get_current_user_id();

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(*) as total_sessions,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
					SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
				FROM {$this->table_name} 
				WHERE user_id = %d",
				$user_id
			)
		);

		if ( ! $stats ) {
			return array(
				'total'     => 0,
				'completed' => 0,
				'active'    => 0,
			);
		}

		return array(
			'total'     => (int) $stats->total_sessions,
			'completed' => (int) $stats->completed,
			'active'    => (int) $stats->active,
		);
	}

	/**
	 * Check if the chat sessions table exists.
	 *
	 * @return bool True if table exists.
	 */
	private function table_exists() {
		global $wpdb;
		
		$table_name = $this->table_name;
		$result = $wpdb->get_var( $wpdb->prepare( 
			"SHOW TABLES LIKE %s", 
			$table_name 
		) );
		
		return $result === $table_name;
	}
}

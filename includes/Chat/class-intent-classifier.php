<?php
/**
 * Intent Classifier for Chat Builder.
 *
 * Uses LLM to classify user intent in any language.
 *
 * @package AI_Content_Engine
 * @since 2.2.0
 */

namespace ACE\Chat;

use ACE\Provider_Factory;

/**
 * Class Intent_Classifier
 *
 * Classifies user messages into actionable intents using LLM.
 */
class Intent_Classifier {

	/**
	 * Valid intent types.
	 *
	 * @var array
	 */
	const INTENTS = array(
		'conversation',   // Planning/discussing
		'request_block',  // Adding FAQ, pros/cons, etc.
		'finalize',       // Ready to generate
		'import',         // Pasting existing content
		'edit_add',       // Add to existing
		'edit_remove',    // Remove from existing
		'edit_replace',   // Replace content
		'edit_tone',      // Change tone
		'edit_length',    // Shorten/expand
		'translate',      // Translate content
		'summarize',      // Summarize content
		'regenerate',     // Try again
		'question',       // Asking a question
	);

	/**
	 * AI provider instance.
	 *
	 * @var \ACE\AI_Provider|null
	 */
	private $ai_provider;

	/**
	 * Constructor.
	 *
	 * @param string $provider_id Provider ID (openai, claude, gemini).
	 */
	public function __construct( $provider_id = '' ) {
		if ( ! empty( $provider_id ) ) {
			$this->ai_provider = Provider_Factory::get( $provider_id );
		}
	}

	/**
	 * Set AI provider.
	 *
	 * @param \ACE\AI_Provider $provider AI provider instance.
	 */
	public function set_provider( $provider ) {
		$this->ai_provider = $provider;
	}

	/**
	 * Classify user intent using LLM (works in any language).
	 *
	 * @param string $message User message.
	 * @return string Intent type.
	 */
	public function classify( $message ) {
		// Use cache to avoid redundant classification calls.
		$cache_key = 'ace_intent_' . md5( $message );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Quick local check for very short greetings.
		$intent = $this->quick_classify( $message );
		if ( $intent ) {
			return $intent;
		}

		// Use LLM to classify intent.
		if ( ! $this->ai_provider || is_wp_error( $this->ai_provider ) ) {
			return 'generate';
		}

		$prompt = $this->get_classification_prompt( $message );

		try {
			$result = $this->ai_provider->generate_content( $prompt, '', array(
				'max_tokens'  => 20,
				'temperature' => 0,
			) );

			if ( is_wp_error( $result ) ) {
				return 'conversation';
			}

			$intent = strtolower( trim( $result['content'] ?? 'conversation' ) );

			// Normalize to valid intents.
			if ( ! in_array( $intent, self::INTENTS, true ) ) {
				$intent = 'conversation';
			}

			// Cache for 5 minutes.
			set_transient( $cache_key, $intent, 5 * MINUTE_IN_SECONDS );

			return $intent;
		} catch ( \Exception $e ) {
			return 'conversation';
		}
	}

	/**
	 * Quick classification for common patterns (no API call needed).
	 *
	 * @param string $message User message.
	 * @return string|null Intent or null if LLM needed.
	 */
	private function quick_classify( $message ) {
		$message_lower = strtolower( trim( $message ) );
		$word_count = str_word_count( $message );

		// Very short greetings.
		if ( strlen( $message_lower ) < 15 ) {
			$greetings = array( 'hi', 'hello', 'hey', 'bonjour', 'salut', 'hola', 'مرحبا', 'hallo', 'ciao' );
			foreach ( $greetings as $greeting ) {
				if ( strpos( $message_lower, $greeting ) === 0 ) {
					return 'question';
				}
			}
		}

		// Finalize patterns (common in all languages).
		$finalize_patterns = array( 'go', 'done', 'ok', 'let\'s go', 'generate', 'create it', 'start', 'يلا', 'allons-y' );
		if ( in_array( $message_lower, $finalize_patterns, true ) ) {
			return 'finalize';
		}

		// Long text without questions is likely import.
		if ( $word_count > 200 && strpos( $message, '?' ) === false ) {
			return 'import';
		}

		return null; // Need LLM classification.
	}

	/**
	 * Get the prompt for intent classification.
	 *
	 * @param string $message User message to classify.
	 * @return string Classification prompt.
	 */
	private function get_classification_prompt( $message ) {
		$word_count = str_word_count( $message );

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
- translate: Translate content to another language
- summarize: Create a summary of the content
- regenerate: Try again, redo, regenerate the content
- question: Asking how something works, greeting, general help question

Context: Message has {$word_count} words. Long text (>200 words) without questions is likely "import".

User message: "{$message}"

Category:
PROMPT;
	}

	/**
	 * Get the phase for a given intent.
	 *
	 * @param string $intent The classified intent.
	 * @return string Phase: 'conversation', 'finalize', 'import', 'edit'
	 */
	public function get_phase( $intent ) {
		switch ( $intent ) {
			case 'conversation':
			case 'request_block':
			case 'question':
				return 'conversation';
			case 'finalize':
				return 'finalize';
			case 'import':
				return 'import';
			case 'translate':
				return 'translate';
			case 'summarize':
				return 'summarize';
			case 'regenerate':
				return 'regenerate';
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
	 * Check if intent expects content generation/modification.
	 *
	 * @param string $intent The classified intent.
	 * @return bool
	 */
	public function expects_content( $intent ) {
		$content_intents = array(
			'finalize',
			'import',
			'translate',
			'summarize',
			'regenerate',
			'edit_add',
			'edit_remove',
			'edit_replace',
			'edit_tone',
			'edit_length',
		);
		return in_array( $intent, $content_intents, true );
	}
}

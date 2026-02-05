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
 * Action Handler for Chat Builder.
 *
 * Executes actions returned by OpenAI function calling.
 *
 * @package AI_Content_Engine
 * @since 2.2.0
 */

namespace ACE\Chat;

/**
 * Class Action_Handler
 *
 * Handles execution of actions from function calling.
 */
class Action_Handler {

	/**
	 * Tool definition for OpenAI function calling.
	 * Single-call approach: classify action AND generate content in one call.
	 *
	 * @return array Tool definition.
	 */
	public static function get_tool_definition() {
		return array(
			'type' => 'function',
			'function' => array(
				'name' => 'handle_user_request',
				'description' => 'Process user request and generate/edit article content. ALWAYS use this function.',
				'parameters' => array(
					'type' => 'object',
					'properties' => array(
						'action' => array(
							'type' => 'string',
							'enum' => array( 'generate', 'regenerate', 'add', 'remove', 'replace', 'reorder', 'format', 'tone', 'length', 'translate', 'summarize', 'import', 'chat' ),
							'description' => 'Action: generate (new), regenerate (redo), add (element), remove, replace, reorder (move sections), format (bullets/headers), tone, length, translate, summarize, import (format pasted content), chat',
						),
						'target' => array(
							'type' => 'string',
							'description' => 'What to modify: table, faq, section, intro, conclusion, pros_cons, takeaways, callout, toc, image, heading, paragraph',
						),
						'response' => array(
							'type' => 'string',
							'description' => 'Brief message to user explaining what you did',
						),
						'content_html' => array(
							'type' => 'string',
							'description' => 'For content actions: the COMPLETE article HTML. Include ALL content (existing + changes). Use h1, h2, p, ul, li tags. For chat action: leave empty.',
						),
					),
					'required' => array( 'action', 'response' ),
				),
			),
		);
	}

	/**
	 * Get system prompt for function calling.
	 *
	 * @param bool $has_content Whether article content exists.
	 * @return string System prompt.
	 */
	public static function get_system_prompt( $has_content = false ) {
		$content_context = $has_content 
			? "An article already exists. For edit actions, return the COMPLETE updated article in content_html."
			: "No article exists yet. Generate content when user requests it.";

		$prompt = "You are an AI article writing assistant. Use handle_user_request for EVERY response.\n\n";
		$prompt .= "{$content_context}\n\n";
		$prompt .= "ACTIONS (use the most appropriate one):\n";
		$prompt .= "- generate: Create a new article from scratch\n";
		$prompt .= "- regenerate: Redo/recreate the current article differently\n";
		$prompt .= "- add: Add new element (table, faq, section, image, callout, pros_cons, etc.)\n";
		$prompt .= "- remove: Delete/remove an element or section\n";
		$prompt .= "- replace: Change specific content (swap X with Y)\n";
		$prompt .= "- reorder: Move sections around, reorganize structure\n";
		$prompt .= "- format: Add formatting (bullets, headers, styles)\n";
		$prompt .= "- tone: Adjust writing style (casual, formal, professional)\n";
		$prompt .= "- length: Make shorter or longer (condense, expand)\n";
		$prompt .= "- translate: Translate to another language\n";
		$prompt .= "- summarize: Create a summary of the content\n";
		$prompt .= "- import: Format/structure pasted content from user\n";
		$prompt .= "- chat: Just respond conversationally (NO content_html needed)\n\n";
		$prompt .= "CONTENT FORMAT (content_html):\n";
		$prompt .= "- Use h1 for title, h2 for sections, p for paragraphs\n";
		$prompt .= "- Tables: <table><thead>...</thead><tbody>...</tbody></table>\n";
		$prompt .= "- FAQ: <div class=\"ace-faq\"><details><summary>Q?</summary><p>A</p></details></div>\n";
		$prompt .= "- Callout: <div class=\"ace-callout ace-callout-info\">...</div>\n";
		$prompt .= "- Pros/Cons: <div class=\"ace-proscons\"><div class=\"ace-pros\">...</div><div class=\"ace-cons\">...</div></div>\n";
		$prompt .= "- Image placeholder: <figure class=\"ace-image-placeholder\"><div>Image: description</div><figcaption>Caption</figcaption></figure>\n\n";
		$prompt .= "CRITICAL RULES:\n";
		$prompt .= "1. For ALL content actions: include content_html with COMPLETE article\n";
		$prompt .= "2. When user says \"add\", \"just do it\", \"go\", \"now\" - DO IT immediately, don't ask questions\n";
		$prompt .= "3. For edits: include ALL existing content plus your changes\n";
		$prompt .= "4. response = brief message explaining what you did\n";
		$prompt .= "5. content_html = full HTML article (empty ONLY for chat action)";
		
		return $prompt;
	}

	/**
	 * Parse tool call response.
	 *
	 * @param array $tool_calls Tool calls from OpenAI response.
	 * @return array|null Parsed action data or null.
	 */
	public static function parse_tool_call( $tool_calls ) {
		if ( empty( $tool_calls ) ) {
			return null;
		}

		$call = $tool_calls[0];
		if ( $call['function']['name'] !== 'handle_user_request' ) {
			return null;
		}

		$args = json_decode( $call['function']['arguments'], true );
		if ( ! $args ) {
			return null;
		}

		return array(
			'action'       => $args['action'] ?? 'chat',
			'target'       => $args['target'] ?? '',
			'response'     => $args['response'] ?? '',
			'content_html' => $args['content_html'] ?? '',
		);
	}

	/**
	 * Check if action expects content generation.
	 *
	 * @param string $action The action type.
	 * @return bool
	 */
	public static function expects_content( $action ) {
		$content_actions = array( 'generate', 'regenerate', 'add', 'remove', 'replace', 'reorder', 'format', 'tone', 'length', 'translate', 'summarize', 'import' );
		return in_array( $action, $content_actions, true );
	}

	/**
	 * Get content generation prompt for action.
	 *
	 * @param array  $action_data    Parsed action data.
	 * @param string $current_content Current article content.
	 * @param array  $requirements   Session requirements.
	 * @return string Prompt for content generation.
	 */
	public static function get_content_prompt( $action_data, $current_content = '', $requirements = array() ) {
		$action  = $action_data['action'];
		$target  = $action_data['target'];
		$details = $action_data['details'];
		$topic   = $requirements['topic'] ?? 'the requested topic';

		switch ( $action ) {
			case 'generate':
			case 'finalize':
				return self::get_generate_prompt( $topic, $requirements );
			
			case 'add':
				return self::get_add_prompt( $target, $details, $current_content );
			
			case 'remove':
				return self::get_remove_prompt( $target, $current_content );
			
			case 'replace':
				return self::get_replace_prompt( $target, $details, $current_content );
			
			case 'tone':
				return self::get_tone_prompt( $details, $current_content );
			
			case 'length':
				return self::get_length_prompt( $details, $current_content );
			
			case 'translate':
				return self::get_translate_prompt( $details, $current_content );
			
			case 'summarize':
				return self::get_summarize_prompt( $current_content );
			
			default:
				return '';
		}
	}

	/**
	 * Get prompt for generating new content.
	 */
	private static function get_generate_prompt( $topic, $requirements ) {
		$blocks = ! empty( $requirements['blocks'] ) ? implode( ', ', $requirements['blocks'] ) : 'appropriate blocks';
		$tone = $requirements['tone'] ?? 'professional';
		$length = $requirements['length'] ?? 'medium';

		$prompt = "Generate a complete, high-quality article about: {$topic}\n\n";
		$prompt .= "Requirements:\n";
		$prompt .= "- Tone: {$tone}\n";
		$prompt .= "- Length: {$length} (short=500, medium=1000, long=2000+ words)\n";
		$prompt .= "- Include: {$blocks}\n\n";
		$prompt .= "Return ONLY valid HTML. Use h1 for title, h2 for sections, proper HTML formatting.\n";
		$prompt .= "Include special content blocks where appropriate (FAQ, callouts, pros/cons, etc.).";
		
		return $prompt;
	}

	/**
	 * Get prompt for adding content.
	 */
	private static function get_add_prompt( $target, $details, $current_content ) {
		$prompt = "Add a {$target} to this article.\n";
		$prompt .= "Details: {$details}\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE updated article with the new {$target} added at an appropriate location.\n";
		$prompt .= "Use proper HTML formatting for the new element.";
		
		return $prompt;
	}

	/**
	 * Get prompt for removing content.
	 */
	private static function get_remove_prompt( $target, $current_content ) {
		$prompt = "Remove the {$target} from this article.\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE updated article WITHOUT the {$target}.\n";
		$prompt .= "Keep all other content intact.";
		
		return $prompt;
	}

	/**
	 * Get prompt for replacing content.
	 */
	private static function get_replace_prompt( $target, $details, $current_content ) {
		$prompt = "Replace/change: {$target}\n";
		$prompt .= "With/To: {$details}\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE updated article with the replacement made.\n";
		$prompt .= "Keep all other content intact.";
		
		return $prompt;
	}

	/**
	 * Get prompt for tone adjustment.
	 */
	private static function get_tone_prompt( $details, $current_content ) {
		$prompt = "Rewrite this article with a {$details} tone.\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE rewritten article.\n";
		$prompt .= "Keep the same structure, facts, and HTML formatting.\n";
		$prompt .= "Only change the writing style to be {$details}.";
		
		return $prompt;
	}

	/**
	 * Get prompt for length adjustment.
	 */
	private static function get_length_prompt( $details, $current_content ) {
		$instruction = strpos( strtolower( $details ), 'short' ) !== false || strpos( strtolower( $details ), 'condense' ) !== false
			? 'Make the article shorter by removing redundancy and condensing content.'
			: 'Make the article longer by adding more detail, examples, and depth.';

		$prompt = "{$instruction}\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE adjusted article.\n";
		$prompt .= "Keep the HTML structure intact.";
		
		return $prompt;
	}

	/**
	 * Get prompt for translation.
	 */
	private static function get_translate_prompt( $language, $current_content ) {
		$prompt = "Translate this article to {$language}.\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return the COMPLETE translated article.\n";
		$prompt .= "Keep ALL HTML structure and class names intact.\n";
		$prompt .= "Only translate the visible text content.";
		
		return $prompt;
	}

	/**
	 * Get prompt for summarization.
	 */
	private static function get_summarize_prompt( $current_content ) {
		$prompt = "Create a summary of this article (about 20-30% of original length).\n\n";
		$prompt .= "CURRENT ARTICLE:\n";
		$prompt .= "{$current_content}\n\n";
		$prompt .= "Return a summary with:\n";
		$prompt .= "- Key points preserved\n";
		$prompt .= "- Main arguments intact\n";
		$prompt .= "- Proper HTML formatting";
		
		return $prompt;
	}
}

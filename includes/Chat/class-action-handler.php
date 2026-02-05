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

		return <<<PROMPT
You are an AI article writing assistant. Use handle_user_request for EVERY response.

{$content_context}

ACTIONS (use the most appropriate one):
- generate: Create a new article from scratch
- regenerate: Redo/recreate the current article differently
- add: Add new element (table, faq, section, image, callout, pros_cons, etc.)
- remove: Delete/remove an element or section
- replace: Change specific content (swap X with Y)
- reorder: Move sections around, reorganize structure
- format: Add formatting (bullets, headers, styles)
- tone: Adjust writing style (casual, formal, professional)
- length: Make shorter or longer (condense, expand)
- translate: Translate to another language
- summarize: Create a summary of the content
- import: Format/structure pasted content from user
- chat: Just respond conversationally (NO content_html needed)

CONTENT FORMAT (content_html):
- Use h1 for title, h2 for sections, p for paragraphs
- Tables: <table><thead>...</thead><tbody>...</tbody></table>
- FAQ: <div class="ace-faq"><details><summary>Q?</summary><p>A</p></details></div>
- Callout: <div class="ace-callout ace-callout-info">...</div>
- Pros/Cons: <div class="ace-proscons"><div class="ace-pros">...</div><div class="ace-cons">...</div></div>
- Image placeholder: <figure class="ace-image-placeholder"><div>Image: description</div><figcaption>Caption</figcaption></figure>

CRITICAL RULES:
1. For ALL content actions: include content_html with COMPLETE article
2. When user says "add", "just do it", "go", "now" - DO IT immediately, don't ask questions
3. For edits: include ALL existing content plus your changes
4. response = brief message explaining what you did
5. content_html = full HTML article (empty ONLY for chat action)
PROMPT;
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

		return <<<PROMPT
Generate a complete, high-quality article about: {$topic}

Requirements:
- Tone: {$tone}
- Length: {$length} (short=500, medium=1000, long=2000+ words)
- Include: {$blocks}

Return ONLY valid HTML. Use h1 for title, h2 for sections, proper HTML formatting.
Include special content blocks where appropriate (FAQ, callouts, pros/cons, etc.).
PROMPT;
	}

	/**
	 * Get prompt for adding content.
	 */
	private static function get_add_prompt( $target, $details, $current_content ) {
		return <<<PROMPT
Add a {$target} to this article.
Details: {$details}

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE updated article with the new {$target} added at an appropriate location.
Use proper HTML formatting for the new element.
PROMPT;
	}

	/**
	 * Get prompt for removing content.
	 */
	private static function get_remove_prompt( $target, $current_content ) {
		return <<<PROMPT
Remove the {$target} from this article.

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE updated article WITHOUT the {$target}.
Keep all other content intact.
PROMPT;
	}

	/**
	 * Get prompt for replacing content.
	 */
	private static function get_replace_prompt( $target, $details, $current_content ) {
		return <<<PROMPT
Replace/change: {$target}
With/To: {$details}

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE updated article with the replacement made.
Keep all other content intact.
PROMPT;
	}

	/**
	 * Get prompt for tone adjustment.
	 */
	private static function get_tone_prompt( $details, $current_content ) {
		return <<<PROMPT
Rewrite this article with a {$details} tone.

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE rewritten article.
Keep the same structure, facts, and HTML formatting.
Only change the writing style to be {$details}.
PROMPT;
	}

	/**
	 * Get prompt for length adjustment.
	 */
	private static function get_length_prompt( $details, $current_content ) {
		$instruction = strpos( strtolower( $details ), 'short' ) !== false || strpos( strtolower( $details ), 'condense' ) !== false
			? 'Make the article shorter by removing redundancy and condensing content.'
			: 'Make the article longer by adding more detail, examples, and depth.';

		return <<<PROMPT
{$instruction}

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE adjusted article.
Keep the HTML structure intact.
PROMPT;
	}

	/**
	 * Get prompt for translation.
	 */
	private static function get_translate_prompt( $language, $current_content ) {
		return <<<PROMPT
Translate this article to {$language}.

CURRENT ARTICLE:
{$current_content}

Return the COMPLETE translated article.
Keep ALL HTML structure and class names intact.
Only translate the visible text content.
PROMPT;
	}

	/**
	 * Get prompt for summarization.
	 */
	private static function get_summarize_prompt( $current_content ) {
		return <<<PROMPT
Create a summary of this article (about 20-30% of original length).

CURRENT ARTICLE:
{$current_content}

Return a summary with:
- Key points preserved
- Main arguments intact
- Proper HTML formatting
PROMPT;
	}
}

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
 * Prompt Manager for Chat Builder.
 *
 * Manages all AI prompts for different phases and operations.
 *
 * @package AI_Content_Engine
 * @since 2.2.0
 */

namespace ACE\Chat;

/**
 * Class Prompt_Manager
 *
 * Centralized management of all AI prompts.
 */
class Prompt_Manager {

	/**
	 * Get system prompt for content generation (edit mode).
	 *
	 * @return string System prompt.
	 */
	public function get_system_prompt() {
		$prompt = "You are an AI article writing assistant. Your PRIMARY job is to WRITE and EDIT articles.\n\n";
		$prompt .= "RESPONSE FORMAT (MANDATORY):\n";
		$prompt .= "You MUST respond with valid JSON in this exact format:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Brief explanation of what you created/updated\",\n";
		$prompt .= "  \"content_html\": \"<h1>Article Title</h1><p>Full article content in HTML...</p>\"\n";
		$prompt .= "}\n\n";
		$prompt .= "CONTENT REQUIREMENTS:\n";
		$prompt .= "- content_html MUST contain a complete article (minimum 1000 words)\n";
		$prompt .= "- Use proper HTML tags: h1 (title), h2 (sections), h3 (subsections), p, ul, ol, li, strong, em\n";
		$prompt .= "- NO markdown - pure HTML only\n";
		$prompt .= "- Include: introduction, multiple sections with h2 headings, conclusion\n";
		$prompt .= "- Make it engaging, informative, and SEO-friendly\n\n";
		$prompt .= "SPECIAL CONTENT BLOCKS:\n\n";
		$prompt .= "1. FAQ Accordion:\n";
		$prompt .= "<div class=\"ace-faq\">\n";
		$prompt .= "  <details><summary>Question here?</summary><p>Answer here.</p></details>\n";
		$prompt .= "</div>\n\n";
		$prompt .= "2. Callout Boxes:\n";
		$prompt .= "<div class=\"ace-callout ace-callout-info\"><strong>Note:</strong> Important information.</div>\n";
		$prompt .= "<div class=\"ace-callout ace-callout-warning\"><strong>Warning:</strong> Be careful.</div>\n";
		$prompt .= "<div class=\"ace-callout ace-callout-tip\"><strong>Tip:</strong> Helpful suggestion.</div>\n\n";
		$prompt .= "3. Key Takeaways:\n";
		$prompt .= "<div class=\"ace-takeaways\">\n";
		$prompt .= "<strong>Key Takeaways</strong>\n";
		$prompt .= "<ul><li>Main point 1</li><li>Main point 2</li></ul>\n";
		$prompt .= "</div>\n\n";
		$prompt .= "4. Table of Contents:\n";
		$prompt .= "<nav class=\"ace-toc\">\n";
		$prompt .= "<strong>In This Article</strong>\n";
		$prompt .= "<ul><li><a href=\"#section1\">Section 1</a></li></ul>\n";
		$prompt .= "</nav>\n\n";
		$prompt .= "5. Pros/Cons:\n";
		$prompt .= "<div class=\"ace-proscons\">\n";
		$prompt .= "<div class=\"ace-pros\"><strong>✓ Pros</strong><ul><li>Advantage</li></ul></div>\n";
		$prompt .= "<div class=\"ace-cons\"><strong>✗ Cons</strong><ul><li>Disadvantage</li></ul></div>\n";
		$prompt .= "</div>\n\n";
		$prompt .= "6. Stat Highlight:\n";
		$prompt .= "<div class=\"ace-stat\"><span class=\"ace-stat-number\">89%</span><span class=\"ace-stat-label\">description</span></div>\n\n";
		$prompt .= "EDITING RULES:\n";
		$prompt .= "- REMOVE: Remove ONLY the specified section, keep rest intact\n";
		$prompt .= "- REPLACE: Find and swap specified content\n";
		$prompt .= "- ADD: Keep existing content, insert at appropriate location\n";
		$prompt .= "- TONE: Rewrite with requested style, preserve structure\n";
		$prompt .= "- LENGTH: Shorten removes redundancy, lengthen adds detail\n\n";
		$prompt .= "CRITICAL: Always return COMPLETE article in content_html.";
		
		return $prompt;
	}

	/**
	 * Get prompt for conversation phase.
	 *
	 * @param array $requirements Current session requirements.
	 * @return string System prompt.
	 */
	public function get_conversation_prompt( $requirements = array() ) {
		$topic = $requirements['topic'] ?? '';
		$tone = $requirements['tone'] ?? 'professional';
		$length = $requirements['length'] ?? 'medium';
		$blocks_list = ! empty( $requirements['blocks'] ) ? implode( ', ', $requirements['blocks'] ) : 'none yet';
		$notes_list = ! empty( $requirements['notes'] ) ? implode( '; ', $requirements['notes'] ) : 'none yet';

		$prompt = "You are a helpful content planning assistant. Have a conversation to understand what the user wants to write.\n\n";
		$prompt .= "YOUR ROLE:\n";
		$prompt .= "- Ask clarifying questions about their topic\n";
		$prompt .= "- Suggest angles, approaches, and content ideas\n";
		$prompt .= "- Help them decide what to include\n";
		$prompt .= "- Remember their preferences\n\n";
		$prompt .= "DO NOT generate a full article yet. Just have a planning conversation.\n\n";
		$prompt .= "CURRENT REQUIREMENTS:\n";
		$prompt .= "- Topic: {$topic}\n";
		$prompt .= "- Tone: {$tone}\n";
		$prompt .= "- Length: {$length}\n";
		$prompt .= "- Blocks requested: {$blocks_list}\n";
		$prompt .= "- Notes: {$notes_list}\n\n";
		$prompt .= "AVAILABLE BLOCKS (suggest these):\n";
		$prompt .= "- FAQ section (collapsible Q&A)\n";
		$prompt .= "- Key Takeaways box\n";
		$prompt .= "- Pros/Cons comparison\n";
		$prompt .= "- Table of Contents\n";
		$prompt .= "- Callout boxes (tips, warnings)\n";
		$prompt .= "- Statistics highlights\n";
		$prompt .= "- Comparison tables\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Your conversational response here\",\n";
		$prompt .= "  \"requirements_update\": {\n";
		$prompt .= "    \"topic\": \"Updated topic if mentioned\",\n";
		$prompt .= "    \"tone\": \"formal|casual|professional|friendly\",\n";
		$prompt .= "    \"blocks\": [\"faq\", \"takeaways\", \"proscons\"],\n";
		$prompt .= "    \"notes\": [\"specific requests\"]\n";
		$prompt .= "  }\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get prompt for finalize phase.
	 *
	 * @param array  $requirements Session requirements.
	 * @param string $format       Output format: 'markdown' or 'html'.
	 * @return string System prompt.
	 */
	public function get_finalize_prompt( $requirements, $format = 'html' ) {
		$topic = $requirements['topic'] ?? 'the requested topic';
		$tone = $requirements['tone'] ?? 'professional';
		$length = $requirements['length'] ?? 'medium';
		$blocks = wp_json_encode( $requirements['blocks'] ?? array() );
		$notes = wp_json_encode( $requirements['notes'] ?? array() );

		$format_instruction = $format === 'html'
			? 'Output in pure HTML with proper tags (h1, h2, p, ul, li, etc.).'
			: 'Output in clean Markdown format.';

		$prompt = "Generate a complete, high-quality article based on these requirements:\n\n";
		$prompt .= "REQUIREMENTS:\n";
		$prompt .= "- Topic: {$topic}\n";
		$prompt .= "- Tone: {$tone}\n";
		$prompt .= "- Length: {$length} (short=500, medium=1000, long=2000+ words)\n";
		$prompt .= "- Include blocks: {$blocks}\n";
		$prompt .= "- Special notes: {$notes}\n\n";
		$prompt .= "{$format_instruction}\n\n";
		$prompt .= "Use the special content blocks (FAQ, callouts, takeaways, etc.) where appropriate.\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Brief note about the article created\",\n";
		$prompt .= "  \"content_html\": \"<h1>Title</h1>...\"\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get prompt for import phase.
	 *
	 * @param array $requested_blocks Blocks to add.
	 * @return string System prompt.
	 */
	public function get_import_prompt( $requested_blocks = array() ) {
		$blocks = wp_json_encode( $requested_blocks );

		$prompt = "Convert existing article text into properly formatted HTML with rich content blocks.\n\n";
		$prompt .= "TASKS:\n";
		$prompt .= "1. Preserve ALL original content and meaning\n";
		$prompt .= "2. Structure with proper HTML (h1 title, h2 sections, p paragraphs)\n";
		$prompt .= "3. Add requested content blocks: {$blocks}\n\n";
		$prompt .= "Keep the author's voice and style. Only improve structure and add requested blocks.\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Brief description of formatting applied\",\n";
		$prompt .= "  \"content_html\": \"<h1>Title</h1>...\"\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get prompt for translation.
	 *
	 * @param string $target_language Target language.
	 * @return string System prompt.
	 */
	public function get_translate_prompt( $target_language ) {
		$prompt = "Translate the article content to {$target_language}.\n\n";
		$prompt .= "RULES:\n";
		$prompt .= "- Preserve ALL HTML structure and tags\n";
		$prompt .= "- Keep all class names unchanged (ace-faq, ace-callout, etc.)\n";
		$prompt .= "- Translate only the visible text content\n";
		$prompt .= "- Maintain the same tone and style\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Translated to {$target_language}\",\n";
		$prompt .= "  \"content_html\": \"<h1>Translated Title</h1>...\"\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get prompt for summarization.
	 *
	 * @return string System prompt.
	 */
	public function get_summarize_prompt() {
		$prompt = "Create a concise summary of the article.\n\n";
		$prompt .= "REQUIREMENTS:\n";
		$prompt .= "- Keep the most important points\n";
		$prompt .= "- Reduce to approximately 20-30% of original length\n";
		$prompt .= "- Maintain key takeaways and main arguments\n";
		$prompt .= "- Keep proper HTML structure\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Created summary\",\n";
		$prompt .= "  \"content_html\": \"<h1>Title</h1><p>Summary content...</p>\"\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get prompt for professional polish.
	 *
	 * @return string System prompt.
	 */
	public function get_polish_prompt() {
		$prompt = "Polish this HTML article to be professional and publication-ready.\n\n";
		$prompt .= "RULES:\n";
		$prompt .= "- Preserve original meaning and facts\n";
		$prompt .= "- Improve clarity, flow, and conciseness\n";
		$prompt .= "- Fix grammar, tone, and structure\n";
		$prompt .= "- Keep all content in HTML (no markdown)\n";
		$prompt .= "- Do NOT remove important sections\n\n";
		$prompt .= "RESPONSE FORMAT:\n";
		$prompt .= "{\n";
		$prompt .= "  \"message\": \"Brief note about improvements\",\n";
		$prompt .= "  \"content_html\": \"...\"\n";
		$prompt .= "}";
		
		return $prompt;
	}

	/**
	 * Get instruction text for a phase.
	 *
	 * @param string $phase The current phase.
	 * @return string Instruction text.
	 */
	public function get_phase_instruction( $phase ) {
		switch ( $phase ) {
			case 'conversation':
				return 'Continue the conversation naturally. Help plan the article. Ask clarifying questions. Do NOT generate a full article yet.';

			case 'finalize':
				return 'The user is ready! Generate the COMPLETE article now. Return valid JSON with message and content_html.';

			case 'import':
				return 'Convert the shared content to well-structured HTML. Add requested blocks. Return JSON with message and content_html.';

			case 'translate':
				return 'Translate the content while preserving HTML structure. Return JSON with message and content_html.';

			case 'summarize':
				return 'Create a concise summary preserving key points. Return JSON with message and content_html.';

			case 'regenerate':
				return 'Regenerate the content with improvements. Return JSON with message and content_html.';

			case 'edit':
			default:
				return 'Respond to the edit request. Include COMPLETE updated article in content_html.';
		}
	}
}

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
		return <<<PROMPT
You are an AI article writing assistant. Your PRIMARY job is to WRITE and EDIT articles.

RESPONSE FORMAT (MANDATORY):
You MUST respond with valid JSON in this exact format:
{
  "message": "Brief explanation of what you created/updated",
  "content_html": "<h1>Article Title</h1><p>Full article content in HTML...</p>"
}

CONTENT REQUIREMENTS:
- content_html MUST contain a complete article (minimum 1000 words)
- Use proper HTML tags: h1 (title), h2 (sections), h3 (subsections), p, ul, ol, li, strong, em
- NO markdown - pure HTML only
- Include: introduction, multiple sections with h2 headings, conclusion
- Make it engaging, informative, and SEO-friendly

SPECIAL CONTENT BLOCKS:

1. FAQ Accordion:
<div class="ace-faq">
  <details><summary>Question here?</summary><p>Answer here.</p></details>
</div>

2. Callout Boxes:
<div class="ace-callout ace-callout-info"><strong>Note:</strong> Important information.</div>
<div class="ace-callout ace-callout-warning"><strong>Warning:</strong> Be careful.</div>
<div class="ace-callout ace-callout-tip"><strong>Tip:</strong> Helpful suggestion.</div>

3. Key Takeaways:
<div class="ace-takeaways">
<strong>Key Takeaways</strong>
<ul><li>Main point 1</li><li>Main point 2</li></ul>
</div>

4. Table of Contents:
<nav class="ace-toc">
<strong>In This Article</strong>
<ul><li><a href="#section1">Section 1</a></li></ul>
</nav>

5. Pros/Cons:
<div class="ace-proscons">
<div class="ace-pros"><strong>✓ Pros</strong><ul><li>Advantage</li></ul></div>
<div class="ace-cons"><strong>✗ Cons</strong><ul><li>Disadvantage</li></ul></div>
</div>

6. Stat Highlight:
<div class="ace-stat"><span class="ace-stat-number">89%</span><span class="ace-stat-label">description</span></div>

EDITING RULES:
- REMOVE: Remove ONLY the specified section, keep rest intact
- REPLACE: Find and swap specified content
- ADD: Keep existing content, insert at appropriate location
- TONE: Rewrite with requested style, preserve structure
- LENGTH: Shorten removes redundancy, lengthen adds detail

CRITICAL: Always return COMPLETE article in content_html.
PROMPT;
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

		return <<<PROMPT
You are a helpful content planning assistant. Have a conversation to understand what the user wants to write.

YOUR ROLE:
- Ask clarifying questions about their topic
- Suggest angles, approaches, and content ideas
- Help them decide what to include
- Remember their preferences

DO NOT generate a full article yet. Just have a planning conversation.

CURRENT REQUIREMENTS:
- Topic: {$topic}
- Tone: {$tone}
- Length: {$length}
- Blocks requested: {$blocks_list}
- Notes: {$notes_list}

AVAILABLE BLOCKS (suggest these):
- FAQ section (collapsible Q&A)
- Key Takeaways box
- Pros/Cons comparison
- Table of Contents
- Callout boxes (tips, warnings)
- Statistics highlights
- Comparison tables

RESPONSE FORMAT:
{
  "message": "Your conversational response here",
  "requirements_update": {
    "topic": "Updated topic if mentioned",
    "tone": "formal|casual|professional|friendly",
    "blocks": ["faq", "takeaways", "proscons"],
    "notes": ["specific requests"]
  }
}
PROMPT;
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

		return <<<PROMPT
Generate a complete, high-quality article based on these requirements:

REQUIREMENTS:
- Topic: {$topic}
- Tone: {$tone}
- Length: {$length} (short=500, medium=1000, long=2000+ words)
- Include blocks: {$blocks}
- Special notes: {$notes}

{$format_instruction}

Use the special content blocks (FAQ, callouts, takeaways, etc.) where appropriate.

RESPONSE FORMAT:
{
  "message": "Brief note about the article created",
  "content_html": "<h1>Title</h1>..."
}
PROMPT;
	}

	/**
	 * Get prompt for import phase.
	 *
	 * @param array $requested_blocks Blocks to add.
	 * @return string System prompt.
	 */
	public function get_import_prompt( $requested_blocks = array() ) {
		$blocks = wp_json_encode( $requested_blocks );

		return <<<PROMPT
Convert existing article text into properly formatted HTML with rich content blocks.

TASKS:
1. Preserve ALL original content and meaning
2. Structure with proper HTML (h1 title, h2 sections, p paragraphs)
3. Add requested content blocks: {$blocks}

Keep the author's voice and style. Only improve structure and add requested blocks.

RESPONSE FORMAT:
{
  "message": "Brief description of formatting applied",
  "content_html": "<h1>Title</h1>..."
}
PROMPT;
	}

	/**
	 * Get prompt for translation.
	 *
	 * @param string $target_language Target language.
	 * @return string System prompt.
	 */
	public function get_translate_prompt( $target_language ) {
		return <<<PROMPT
Translate the article content to {$target_language}.

RULES:
- Preserve ALL HTML structure and tags
- Keep all class names unchanged (ace-faq, ace-callout, etc.)
- Translate only the visible text content
- Maintain the same tone and style

RESPONSE FORMAT:
{
  "message": "Translated to {$target_language}",
  "content_html": "<h1>Translated Title</h1>..."
}
PROMPT;
	}

	/**
	 * Get prompt for summarization.
	 *
	 * @return string System prompt.
	 */
	public function get_summarize_prompt() {
		return <<<PROMPT
Create a concise summary of the article.

REQUIREMENTS:
- Keep the most important points
- Reduce to approximately 20-30% of original length
- Maintain key takeaways and main arguments
- Keep proper HTML structure

RESPONSE FORMAT:
{
  "message": "Created summary",
  "content_html": "<h1>Title</h1><p>Summary content...</p>"
}
PROMPT;
	}

	/**
	 * Get prompt for professional polish.
	 *
	 * @return string System prompt.
	 */
	public function get_polish_prompt() {
		return <<<PROMPT
Polish this HTML article to be professional and publication-ready.

RULES:
- Preserve original meaning and facts
- Improve clarity, flow, and conciseness
- Fix grammar, tone, and structure
- Keep all content in HTML (no markdown)
- Do NOT remove important sections

RESPONSE FORMAT:
{
  "message": "Brief note about improvements",
  "content_html": "..."
}
PROMPT;
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

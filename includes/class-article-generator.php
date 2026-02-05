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
 * Article Generator class - main content orchestration.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Article_Generator
 *
 * Orchestrates the entire article generation process from prompts to WordPress posts.
 */
class Article_Generator {



	/**
	 * Content parser instance.
	 *
	 * @var Content_Parser
	 */
	private $content_parser;

	/**
	 * Chart renderer instance.
	 *
	 * @var Chart_Renderer
	 */
	private $chart_renderer;

	/**
	 * SEO integrations instance.
	 *
	 * @var SEO_Integrations
	 */
	private $seo;
	
	/**
	 * Image generator instance.
	 *
	 * @var Image_Generator
	 */
	private $image_generator;

	/**
	 * AI provider instance.
	 *
	 * @var AI_Provider
	 */
	private $ai_provider;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->ai_provider     = Provider_Factory::get_active();
		$this->content_parser  = new Content_Parser();
		$this->chart_renderer  = new Chart_Renderer();
		$this->seo             = new SEO_Integrations();
		$this->seo->init();
		$this->image_generator = new Image_Generator();
	}

	/**
	 * Generate and create a blog article.
	 *
	 * @param string $topic   Article topic.
	 * @param array  $options Article generation options.
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	public function generate_article( $topic, $options = array() ) {
		// 1. Validate inputs.
		if ( empty( $topic ) ) {
			return new \WP_Error( 'empty_topic', __( 'Article topic cannot be empty.', 'tonepress-ai' ) );
		}

		// 2. Check rate limit.
		$rate_check = Cache_Manager::check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// 3. Sanitize topic and options.
		$topic   = Security::sanitize_user_prompt( $topic );
		$options = $this->sanitize_options( $options );
		
		// 3b. Apply content template if specified.
		if ( ! empty( $options['template_id'] ) && 'default' !== $options['template_id'] ) {
			$options = Template_Manager::apply_template( $options, $options['template_id'] );
		}

		// 4. Check cache.
		$cache_key = Cache_Manager::generate_cache_key( $topic, $options );
		$cached    = Cache_Manager::get_cached_article( $cache_key );

		if ( $cached && isset( $cached['post_id'] ) ) {
			// Return cached post ID.
			return $cached['post_id'];
		}

		// 5. Build prompts.
		$system_prompt = $this->build_system_prompt();
		$user_prompt   = $this->build_user_prompt( $topic, $options );

		// 6. Call AI Provider API.
		$api_options = array(
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 3000,
		);
		
		$result = $this->ai_provider->generate_content( $system_prompt, $user_prompt, $api_options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// 7. Parse JSON response.
		$article_data = json_decode( $result['content'], true );

		if ( null === $article_data ) {
			return new \WP_Error(
				'invalid_json',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse AI response as JSON: %s', 'tonepress-ai' ),
					json_last_error_msg()
				)
			);
		}

		// 8. Validate JSON structure.
		$validation = $this->content_parser->validate_json_structure( $article_data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// 9. Process content (inject tables and charts).
		$chart_ids = array();
		if ( ! empty( $article_data['charts'] ) ) {
			foreach ( $article_data['charts'] as $chart ) {
				$chart_ids[] = $chart['id'];
			}
		}

		$processed_content = $this->content_parser->parse_and_inject(
			$article_data['content_html'],
			$article_data['tables'] ?? array(),
			$chart_ids
		);

		// 10. Create WordPress post with Quick Win options.
		$post_id = $this->create_post(
			$article_data['title'],
			$processed_content,
			$article_data['meta']['description'] ?? '',
			$options
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// 11. Save SEO metadata.
		$this->seo->save_seo_meta( $post_id, $article_data['meta'] );

		// 12. Save chart data.
		if ( ! empty( $article_data['charts'] ) ) {
			$this->chart_renderer->save_charts( $post_id, $article_data['charts'] );
		}

		// 13. Save generation metadata.
		$this->save_generation_meta( $post_id, $result['usage'], $options );
		
		// 13b. Generate and attach images if requested.
		if ( ! empty( $options['generate_featured_image'] ) ) {
			$featured_image_id = $this->image_generator->generate_featured_image(
				$article_data['title'],
				$topic,
				array( 'size' => '1792x1024', 'quality' => 'standard' )
			);
			
			if ( ! is_wp_error( $featured_image_id ) ) {
				set_post_thumbnail( $post_id, $featured_image_id );
			}
		}
		
		if ( ! empty( $options['generate_inline_images'] ) ) {
			$inline_images = $this->image_generator->generate_inline_images(
				$processed_content,
				array(),
				array( 'max_inline_images' => 3, 'size' => '1024x1024' )
			);
			
			if ( ! empty( $inline_images ) ) {
				// Re-inject images into content.
				$processed_content = $this->image_generator->inject_images_into_content(
					$processed_content,
					$inline_images
				);
				
				// Update post content with images.
				wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => $processed_content,
					)
				);
			}
		}

		// 14. Increment rate limit.
		Cache_Manager::increment_rate_limit();

		// 15. Cache the result.
		Cache_Manager::cache_article(
			$cache_key,
			array(
				'post_id'      => $post_id,
				'article_data' => $article_data,
				'usage'        => $result['usage'],
			)
		);

		// Allow post-processing.
		do_action( 'ace_after_article_generated', $post_id, $article_data, $options );

		return $post_id;
	}

	/**
	 * Build the system prompt (immutable, defines AI behavior).
	 *
	 * @return string System prompt.
	 */
	private function build_system_prompt() {
		$prompt = "You are a professional blog article writer. You generate high-quality, SEO-optimized articles.\n\n";
		$prompt .= "STRICT OUTPUT RULES:\n";
		$prompt .= "1. Return ONLY valid JSON matching the exact schema provided\n";
		$prompt .= "2. Use ONLY HTML tags in content_html (h2, h3, h4, p, ul, ol, li, strong, em, a)\n";
		$prompt .= "3. ABSOLUTELY NO MARKDOWN - never use ##, **, __, ``` or any markdown syntax\n";
		$prompt .= "4. Headings must be pure HTML: <h2>Title</h2> NOT ## Title or <h2>Title</h2> mixed\n";
		$prompt .= "5. NO explanations, NO comments, NO text outside the JSON object\n";
		$prompt .= "6. Generate content that reads naturally and professionally\n";
		$prompt .= "7. Include proper HTML structure with semantic headings\n";
		$prompt .= "8. Ensure all HTML is properly closed and valid\n";
		$prompt .= "9. Do NOT mix markdown with HTML - use ONLY pure HTML tags\n\n";
		$prompt .= "CRITICAL: All headings must be formatted as: <h2>Heading Text</h2>\n";
		$prompt .= "WRONG: ## Heading</h2>\n";
		$prompt .= "WRONG: <h2>## Heading</h2>\n";
		$prompt .= "CORRECT: <h2>Heading Text</h2>\n\n";
		$prompt .= "JSON SCHEMA (YOU MUST MATCH THIS EXACTLY):\n";
		$prompt .= "{\n";
		$prompt .= "  \"title\": \"string\",\n";
		$prompt .= "  \"meta\": {\n";
		$prompt .= "    \"description\": \"string (150-160 chars)\",\n";
		$prompt .= "    \"keywords\": [\"string\", \"string\", \"...\"]\n";
		$prompt .= "  },\n";
		$prompt .= "  \"content_html\": \"string (full HTML article body)\",\n";
		$prompt .= "  \"tables\": [\n";
		$prompt .= "    {\n";
		$prompt .= "      \"title\": \"string\",\n";
		$prompt .= "      \"html\": \"string (complete <table> HTML)\"\n";
		$prompt .= "    }\n";
		$prompt .= "  ],\n";
		$prompt .= "  \"charts\": [\n";
		$prompt .= "    {\n";
		$prompt .= "      \"id\": \"string (unique, e.g., 'chart1')\",\n";
		$prompt .= "      \"type\": \"bar|line|pie|doughnut\",\n";
		$prompt .= "      \"labels\": [\"string\"],\n";
		$prompt .= "      \"datasets\": [\n";
		$prompt .= "        {\n";
		$prompt .= "          \"label\": \"string\",\n";
		$prompt .= "          \"data\": [number],\n";
		$prompt .= "          \"backgroundColor\": \"string (optional)\",\n";
		$prompt .= "          \"borderColor\": \"string (optional)\"\n";
		$prompt .= "        }\n";
		$prompt .= "      ]\n";
		$prompt .= "    }\n";
		$prompt .= "  ]\n";
		$prompt .= "}\n\n";
		$prompt .= "IMPORTANT: \n";
		$prompt .= "- Ensure all JSON is valid\n";
		$prompt .= "- Escape quotes in strings properly\n";
		$prompt .= "- Use double quotes for JSON keys and strings\n";
		$prompt .= "- content_html should contain ONLY the article body (no <html>, <body>, or <head> tags)";

		// Inject company context if enabled.
		$company_context = $this->build_company_context();
		if ( ! empty( $company_context ) ) {
			$prompt .= $company_context;
		}

		return apply_filters( 'ace_system_prompt', $prompt );
	}

	/**
	 * Build company context string from settings.
	 *
	 * @return string Company context for prompt injection.
	 */
	private function build_company_context() {
		// Check if company context is enabled.
		if ( ! get_option( 'ace_enable_company_context', true ) ) {
			return '';
		}

		$context_parts = array();
		
		// Company name.
		$company_name = get_option( 'ace_company_name', '' );
		if ( ! empty( $company_name ) ) {
			$context_parts[] = "Company: {$company_name}";
		}
		
		// Industry.
		$industry = get_option( 'ace_company_industry', '' );
		if ( ! empty( $industry ) ) {
			$context_parts[] = "Industry: {$industry}";
		}
		
		// Description.
		$description = get_option( 'ace_company_description', '' );
		if ( ! empty( $description ) ) {
			$context_parts[] = "About: {$description}";
		}
		
		// Target audience.
		$audience = get_option( 'ace_target_audience', '' );
		if ( ! empty( $audience ) ) {
			$context_parts[] = "Target Audience: {$audience}";
		}
		
		// Key products.
		$products = get_option( 'ace_key_products', '' );
		if ( ! empty( $products ) ) {
			$context_parts[] = "Key Products/Services: {$products}";
		}
		
		// Brand values.
		$values = get_option( 'ace_brand_values', '' );
		if ( ! empty( $values ) ) {
			$context_parts[] = "Brand Values: {$values}";
		}
		
		// Brand voice.
		$voice = get_option( 'ace_brand_voice', '' );
		if ( ! empty( $voice ) ) {
			$context_parts[] = "Brand Voice: {$voice}";
		}
		
		if ( empty( $context_parts ) ) {
			return '';
		}
		
		return "\n\nCOMPANY CONTEXT:\n" . implode( "\n", $context_parts ) . "\n\nPlease ensure the article aligns with the above company context and brand voice.";
	}

	/**
	 * Build the user prompt (dynamic, based on user input).
	 *
	 * @param string $topic   Article topic.
	 * @param array  $options Article options.
	 * @return string User prompt.
	 */
	private function build_user_prompt( $topic, $options ) {
		// Extract options with defaults.
		$length         = $options['length'] ?? 'medium';
		$tone           = $options['tone'] ?? 'professional';
		$keywords       = $options['keywords'] ?? array();
		$include_tables = $options['include_tables'] ?? false;
		$include_charts = $options['include_charts'] ?? false;

		// Quick Win: Custom word count override.
		if ( ! empty( $options['word_count'] ) ) {
			$word_count = $options['word_count'] . ' words';
		} else {
			// Convert length to word count guidance.
			$word_count_map = array(
				'short'  => '800-1200 words',
				'medium' => '1200-1800 words',
				'long'   => '1800-2500+ words',
			);
			$word_count = $word_count_map[ $length ] ?? $word_count_map['medium'];
		}

		// Format keywords.
		$keywords_string = is_array( $keywords ) ? implode( ', ', $keywords ) : $keywords;

		// Build the prompt.
		$prompt = "Write a comprehensive article on the following topic:\n\n";
		$prompt .= "TOPIC: {$topic}\n";

		if ( ! empty( $keywords_string ) ) {
			$prompt .= "TARGET KEYWORDS: {$keywords_string}\n";
		}

		$prompt .= "ARTICLE LENGTH: {$word_count} (MINIMUM - do not write less than this)\n";
		$prompt .= "WRITING TONE: {$tone}\n";

		if ( $include_tables ) {
			$prompt .= "INCLUDE TABLES: Yes - Include 1-3 relevant data tables with real comparative data\n";
		}

		if ( $include_charts ) {
			$prompt .= "INCLUDE CHARTS: Yes - Include 1-3 relevant charts with actual data that visualizes key points\n";
		}

		$prompt .= "\nREQUIREMENTS:\n";
		$prompt .= "- Start with an engaging introduction\n";
		$prompt .= "- Use clear headings (H2, H3, H4) to organize sections\n";
		$prompt .= "- Write in a {$tone} tone\n";
		
		// Language instruction.
		$language = $options['language'] ?? 'English';
		if ( 'English' !== $language ) {
			$prompt .= "- IMPORTANT: Write the ENTIRE article in {$language} language\n";
		}

		if ( ! empty( $keywords_string ) ) {
			$prompt .= "- Naturally incorporate target keywords without keyword stuffing\n";
		}

		$prompt .= "- Include specific examples and data where applicable\n";
		$prompt .= "- End with a strong conclusion or call-to-action\n";
		$prompt .= "- Ensure content is factual, valuable, and SEO-friendly\n";
		$prompt .= "- Make the article informative and engaging for readers\n";
		$prompt .= "- CRITICAL: The article MUST be at least {$word_count} - do NOT produce shorter content\n\n";
		
		// Quick Win: Custom instructions.
		if ( ! empty( $options['custom_instructions'] ) ) {
			$prompt .= "ADDITIONAL INSTRUCTIONS:\n";
			$prompt .= $options['custom_instructions'] . "\n\n";
		}
		
		$prompt .= "Generate a FULL LENGTH article ({$word_count}) now as valid JSON matching the schema exactly.";

		return apply_filters( 'ace_user_prompt', $prompt, $topic, $options );
	}

	/**
	 * Sanitize article generation options.
	 *
	 * @param array $options Raw options.
	 * @return array Sanitized options.
	 */
	private function sanitize_options( $options ) {
		$sanitized = array();

		// Length: short, medium, long.
		$valid_lengths              = array( 'short', 'medium', 'long' );
		$sanitized['length']        = in_array( $options['length'] ?? '', $valid_lengths, true ) ? $options['length'] : 'medium';

		// Tone.
		$valid_tones              = array( 'professional', 'conversational', 'authoritative', 'friendly', 'academic' );
		$sanitized['tone']        = in_array( $options['tone'] ?? '', $valid_tones, true ) ? $options['tone'] : 'professional';

		// Keywords.
		$sanitized['keywords'] = Security::sanitize_keywords( $options['keywords'] ?? array() );

		// Boolean options.
		$sanitized['include_tables'] = ! empty( $options['include_tables'] );
		$sanitized['include_charts'] = ! empty( $options['include_charts'] );
		$sanitized['generate_featured_image'] = ! empty( $options['generate_featured_image'] );
		$sanitized['generate_inline_images']  = ! empty( $options['generate_inline_images'] );

		// Post status.
		$valid_statuses              = array( 'draft', 'publish', 'future' );
		$sanitized['post_status']    = in_array( $options['post_status'] ?? '', $valid_statuses, true ) ? $options['post_status'] : 'draft';

		// Post date (for scheduling).
		if ( ! empty( $options['post_date'] ) ) {
			$sanitized['post_date'] = sanitize_text_field( $options['post_date'] );
		}
		
		// Quick Win: Advanced options.
		$sanitized['word_count']          = ! empty( $options['word_count'] ) ? absint( $options['word_count'] ) : null;
		$sanitized['temperature']         = isset( $options['temperature'] ) ? floatval( $options['temperature'] ) : 0.7;
		$sanitized['max_tokens']          = isset( $options['max_tokens'] ) ? absint( $options['max_tokens'] ) : 3000;
		$sanitized['post_type']           = ! empty( $options['post_type'] ) ? sanitize_key( $options['post_type'] ) : 'post';
		$sanitized['categories']          = ! empty( $options['categories'] ) ? array_map( 'absint', (array) $options['categories'] ) : array();
		$sanitized['auto_tags']           = ! empty( $options['auto_tags'] );
		$sanitized['custom_instructions'] = ! empty( $options['custom_instructions'] ) ? sanitize_textarea_field( $options['custom_instructions'] ) : '';
		$sanitized['template_id']         = ! empty( $options['template_id'] ) ? sanitize_key( $options['template_id'] ) : 'default';

		return $sanitized;
	}

	/**
	 * Create a WordPress post.
	 *
	 * @param string $title       Post title.
	 * @param string $content     Post content (HTML).
	 * @param string $excerpt     Post excerpt (from meta description).
	 * @param array  $options     Post creation options.
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	private function create_post( $title, $content, $excerpt = '', $options = array() ) {
		$post_data = array(
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => $content, // Already sanitized by content_parser.
			'post_excerpt' => sanitize_text_field( $excerpt ), // Quick Win: Auto excerpt.
			'post_status'  => $options['post_status'] ?? 'draft',
			'post_author'  => get_current_user_id(),
			'post_type'    => $options['post_type'] ?? 'post', // Quick Win: Custom post type.
		);

		// Set post date for scheduling.
		if ( 'future' === $post_data['post_status'] && ! empty( $options['post_date'] ) ) {
			$post_data['post_date']     = $options['post_date'];
			$post_data['post_date_gmt'] = get_gmt_from_date( $options['post_date'] );
		}

		// Allow filtering of post data.
		$post_data = apply_filters( 'ace_post_data', $post_data, $options );

		// Insert the post.
		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		
		// Quick Win: Assign categories.
		if ( ! empty( $options['categories'] ) && 'post' === $post_data['post_type'] ) {
			wp_set_post_categories( $post_id, $options['categories'] );
		}
		
		// Quick Win: Auto-generate tags.
		if ( ! empty( $options['auto_tags'] ) && ! empty( $options['keywords'] ) ) {
			$tags = is_array( $options['keywords'] ) ? $options['keywords'] : explode( ',', $options['keywords'] );
			$tags = array_map( 'trim', $tags );
			wp_set_post_tags( $post_id, $tags, false );
		}

		return $post_id;
	}

	/**
	 * Save generation metadata to post meta.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $usage   Token usage data.
	 * @param array $options Generation options.
	 */
	private function save_generation_meta( $post_id, $usage, $options ) {
		update_post_meta( $post_id, '_ace_generated', true );
		update_post_meta( $post_id, '_ace_generated_date', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_ace_token_usage', $usage );
		update_post_meta( $post_id, '_ace_generation_options', $options );
		update_post_meta( $post_id, '_ace_model', get_option( 'ace_openai_model', 'gpt-3.5-turbo' ) );

		// Calculate estimated cost.
		$cost = $this->ai_provider->estimate_cost( $usage['total_tokens'] ?? 0 );
		update_post_meta( $post_id, '_ace_estimated_cost', $cost );

		// Calculate reading time (average 200 words per minute).
		$content = get_post_field( 'post_content', $post_id );
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		$reading_time = max( 1, ceil( $word_count / 200 ) ); // At least 1 minute
		update_post_meta( $post_id, '_ace_reading_time', $reading_time );
		update_post_meta( $post_id, '_ace_word_count', $word_count );
	}

	/**
	 * Generate article data without creating a post (for Gutenberg blocks).
	 *
	 * @param string $topic   Article topic.
	 * @param array  $options Article generation options.
	 * @return array|\WP_Error Article data on success, WP_Error on failure.
	 */
	public function generate_article_data( $topic, $options = array() ) {
		// 1. Validate inputs.
		if ( empty( $topic ) ) {
			return new \WP_Error( 'empty_topic', __( 'Article topic cannot be empty.', 'tonepress-ai' ) );
		}

		// 2. Check rate limit.
		$rate_check = Cache_Manager::check_rate_limit();
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// 3. Sanitize topic and options.
		$topic   = Security::sanitize_user_prompt( $topic );
		$options = $this->sanitize_options( $options );

		// 4. Build prompts.
		$system_prompt = $this->build_system_prompt();
		$user_prompt   = $this->build_user_prompt( $topic, $options );

		// 5. Call AI Provider API.
		$api_options = array(
			'temperature' => $options['temperature'] ?? 0.7,
			'max_tokens'  => $options['max_tokens'] ?? 3000,
		);
		
		$result = $this->ai_provider->generate_content( $system_prompt, $user_prompt, $api_options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// 6. Parse JSON response.
		$article_data = json_decode( $result['content'], true );

		if ( null === $article_data ) {
			return new \WP_Error(
				'invalid_json',
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Failed to parse AI response as JSON: %s', 'tonepress-ai' ),
					json_last_error_msg()
				)
			);
		}

		// 7. Validate JSON structure.
		$validation = $this->content_parser->validate_json_structure( $article_data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// 8. Process content (inject tables and charts).
		$chart_ids = array();
		if ( ! empty( $article_data['charts'] ) ) {
			foreach ( $article_data['charts'] as $chart ) {
				$chart_ids[] = $chart['id'];
			}
		}

		$processed_content = $this->content_parser->parse_and_inject(
			$article_data['content_html'],
			$article_data['tables'] ?? array(),
			$chart_ids
		);

		//  9. Increment rate limit.
		Cache_Manager::increment_rate_limit();

		// Return data without creating post.
		return array(
			'title'        => $article_data['title'],
			'content_html' => $processed_content,
			'meta'         => $article_data['meta'] ?? array(),
			'tables'       => $article_data['tables'] ?? array(),
			'charts'       => $article_data['charts'] ?? array(),
			'usage'        => $result['usage'] ?? array(),
		);
	}
}

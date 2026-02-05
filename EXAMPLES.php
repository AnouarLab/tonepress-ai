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
 * Example usage and code snippets for AI Content Engine.
 *
 * This file demonstrates how to use the plugin programmatically
 * and extend its functionality with hooks and filters.
 *
 * @package AI_Content_Engine
 */

// ============================================================================
// BASIC USAGE
// ============================================================================

/**
 * Example 1: Generate an article programmatically.
 */
function my_generate_article() {
	if ( ! class_exists( 'ACE\Article_Generator' ) ) {
		return new WP_Error( 'plugin_not_active', 'AI Content Engine plugin is not active.' );
	}

	$generator = new ACE\Article_Generator();

	$topic = 'The Future of Artificial Intelligence in Healthcare';
	$options = array(
		'length'         => 'long',
		'tone'           => 'authoritative',
		'keywords'       => array( 'AI', 'healthcare', 'machine learning', 'medical diagnosis' ),
		'include_tables' => true,
		'include_charts' => true,
		'post_status'    => 'draft',
	);

	$post_id = $generator->generate_article( $topic, $options );

	if ( is_wp_error( $post_id ) ) {
		error_log( 'Article generation failed: ' . $post_id->get_error_message() );
		return $post_id;
	}

	error_log( 'Article created with ID: ' . $post_id );
	return $post_id;
}

// ============================================================================
// HOOKS & FILTERS
// ============================================================================

/**
 * Example 2: Modify the system prompt to add custom instructions.
 */
add_filter( 'ace_system_prompt', function( $prompt ) {
	$custom_instructions = "\nADDITIONAL REQUIREMENTS:\n";
	$custom_instructions .= "- Always include a 'Key Takeaways' section at the end\n";
	$custom_instructions .= "- Use bullet points for better readability\n";
	$custom_instructions .= "- Include relevant statistics when possible\n";

	return $prompt . $custom_instructions;
} );

/**
 * Example 3: Modify the user prompt to add industry-specific context.
 */
add_filter( 'ace_user_prompt', function( $prompt, $topic, $options ) {
	// Add context for technology topics.
	if ( strpos( strtolower( $topic ), 'technology' ) !== false || 
		 strpos( strtolower( $topic ), 'software' ) !== false ) {
		$prompt .= "\n\nCONTEXT: This article is for a technical audience with intermediate to advanced knowledge.";
		$prompt .= "\nInclude code examples or technical diagrams where relevant.";
	}

	return $prompt;
}, 10, 3 );

/**
 * Example 4: Customize OpenAI API request parameters.
 */
add_filter( 'ace_openai_request_payload', function( $payload ) {
	// Adjust temperature for more creative output.
	$payload['temperature'] = 0.8;

	// Increase max tokens for longer articles.
	$payload['max_tokens'] = 4000;

	return $payload;
} );

/**
 * Example 5: Automatically categorize generated articles.
 */
add_action( 'ace_after_article_generated', function( $post_id, $article_data, $options ) {
	// Auto-assign categories based on keywords.
	$keywords = $options['keywords'] ?? array();

	if ( in_array( 'technology', $keywords, true ) ) {
		wp_set_post_categories( $post_id, array( 5 ) ); // Technology category ID.
	}

	if ( in_array( 'business', $keywords, true ) ) {
		wp_set_post_categories( $post_id, array( 7 ) ); // Business category ID.
	}
}, 10, 3 );

/**
 * Example 6: Send notification when article is generated.
 */
add_action( 'ace_after_article_generated', function( $post_id, $article_data, $options ) {
	$post = get_post( $post_id );
	$usage = get_post_meta( $post_id, '_ace_token_usage', true );

	// Send email to admin.
	$to = get_option( 'admin_email' );
	$subject = 'New AI-Generated Article: ' . $post->post_title;
	$message = sprintf(
		"A new article has been generated:\n\nTitle: %s\nTokens Used: %d\nEdit: %s",
		$post->post_title,
		$usage['total_tokens'] ?? 0,
		get_edit_post_link( $post_id, 'raw' )
	);

	wp_mail( $to, $subject, $message );
}, 10, 3 );

/**
 * Example 7: Modify post data before creation.
 */
add_filter( 'ace_post_data', function( $post_data ) {
	// Auto-assign author.
	$post_data['post_author'] = 2; // Specific author ID.

	// Add default category.
	$post_data['post_category'] = array( 1, 5 ); // Uncategorized + custom category.

	// Add default tags.
	$post_data['tags_input'] = array( 'AI', 'Content', 'Automation' );

	return $post_data;
} );

// ============================================================================
// ADVANCED USAGE
// ============================================================================

/**
 * Example 8: Bulk generate articles from a CSV file.
 */
function ace_bulk_generate_from_csv( $csv_file_path ) {
	if ( ! file_exists( $csv_file_path ) ) {
		return new WP_Error( 'file_not_found', 'CSV file not found.' );
	}

	$generator = new ACE\Article_Generator();
	$handle = fopen( $csv_file_path, 'r' );
	$results = array();

	// Skip header row.
	fgetcsv( $handle );

	while ( ( $row = fgetcsv( $handle ) ) !== false ) {
		list( $topic, $keywords, $length, $tone ) = $row;

		$options = array(
			'length'         => $length,
			'tone'           => $tone,
			'keywords'       => explode( ';', $keywords ),
			'include_tables' => true,
			'include_charts' => false,
			'post_status'    => 'draft',
		);

		$post_id = $generator->generate_article( $topic, $options );

		if ( is_wp_error( $post_id ) ) {
			$results[] = array(
				'topic'  => $topic,
				'status' => 'failed',
				'error'  => $post_id->get_error_message(),
			);
		} else {
			$results[] = array(
				'topic'   => $topic,
				'status'  => 'success',
				'post_id' => $post_id,
			);
		}

		// Pause to respect rate limits.
		sleep( 10 );
	}

	fclose( $handle );
	return $results;
}

/**
 * Example 9: Schedule daily article generation.
 */
add_action( 'init', function() {
	if ( ! wp_next_scheduled( 'ace_daily_article_generation' ) ) {
		wp_schedule_event( time(), 'daily', 'ace_daily_article_generation' );
	}
} );

add_action( 'ace_daily_article_generation', function() {
	$generator = new ACE\Article_Generator();

	// Array of topics to rotate through.
	$topics = array(
		'Latest Trends in Digital Marketing',
		'How to Improve Website Performance',
		'Best Practices for SEO in 2024',
		'The Future of Remote Work',
	);

	// Pick a random topic.
	$topic = $topics[ array_rand( $topics ) ];

	$options = array(
		'length'         => 'medium',
		'tone'           => 'professional',
		'keywords'       => array(),
		'include_tables' => true,
		'include_charts' => false,
		'post_status'    => 'future',
		'post_date'      => date( 'Y-m-d H:i:s', strtotime( '+1 day 09:00:00' ) ),
	);

	$generator->generate_article( $topic, $options );
} );

/**
 * Example 10: Create a custom admin page for batch generation.
 */
add_action( 'admin_menu', function() {
	add_submenu_page(
		'tools.php',
		'Bulk AI Generation',
		'Bulk AI Generation',
		'manage_options',
		'ace-bulk-generation',
		'ace_render_bulk_generation_page'
	);
} );

function ace_render_bulk_generation_page() {
	?>
	<div class="wrap">
		<h1>Bulk Article Generation</h1>
		<form method="post" action="">
			<?php wp_nonce_field( 'ace_bulk_generation' ); ?>
			<table class="form-table">
				<tr>
					<th><label for="topics">Topics (one per line)</label></th>
					<td>
						<textarea name="topics" id="topics" rows="10" class="large-text"></textarea>
						<p class="description">Enter one topic per line</p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" name="ace_bulk_generate" class="button button-primary">
					Generate Articles
				</button>
			</p>
		</form>
	</div>
	<?php

	if ( isset( $_POST['ace_bulk_generate'] ) ) {
		check_admin_referer( 'ace_bulk_generation' );

		$topics = sanitize_textarea_field( wp_unslash( $_POST['topics'] ) );
		$topics = array_filter( explode( "\n", $topics ) );

		$generator = new ACE\Article_Generator();

		foreach ( $topics as $topic ) {
			$topic = trim( $topic );
			if ( empty( $topic ) ) {
				continue;
			}

			$post_id = $generator->generate_article(
				$topic,
				array(
					'length'      => 'medium',
					'tone'        => 'professional',
					'post_status' => 'draft',
				)
			);

			if ( is_wp_error( $post_id ) ) {
				echo '<p style="color: red;">Failed: ' . esc_html( $topic ) . ' - ' . esc_html( $post_id->get_error_message() ) . '</p>';
			} else {
				echo '<p style="color: green;">Success: ' . esc_html( $topic ) . ' (Post ID: ' . esc_html( $post_id ) . ')</p>';
			}
		}
	}
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Example 11: Get usage statistics.
 */
function ace_get_usage_stats() {
	global $wpdb;

	$stats = array();

	// Total generated articles.
	$stats['total_articles'] = $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ace_generated' AND meta_value = '1'"
	);

	// Total tokens used.
	$stats['total_tokens'] = 0;
	$token_data = $wpdb->get_results(
		"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_ace_token_usage'"
	);

	foreach ( $token_data as $row ) {
		$usage = maybe_unserialize( $row->meta_value );
		if ( isset( $usage['total_tokens'] ) ) {
			$stats['total_tokens'] += (int) $usage['total_tokens'];
		}
	}

	// Total estimated cost.
	$stats['total_cost'] = 0;
	$cost_data = $wpdb->get_results(
		"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_ace_estimated_cost'"
	);

	foreach ( $cost_data as $row ) {
		$stats['total_cost'] += (float) $row->meta_value;
	}

	return $stats;
}

/**
 * Example 12: Clear all cached articles.
 */
function ace_clear_all_cache() {
	$cleared = ACE\Cache_Manager::clear_all_cache();
	return $cleared;
}

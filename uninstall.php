<?php
/**
 * Uninstall script for AI Content Engine.
 * 
 * This file is executed when the plugin is deleted via WordPress admin.
 * It cleans up plugin settings and cached data while preserving generated posts.
 *
 * @package AI_Content_Engine
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 * 
 * What gets deleted:
 * - All plugin settings from wp_options
 * - Cached articles (transients)
 * - Rate limit data
 * 
 * What stays:
 * - All generated posts and their content
 * - Post metadata (SEO, charts, generation info)
 * - Categories and tags
 */

global $wpdb;

// 1. Remove plugin settings from wp_options.
$options_to_delete = array(
	'ace_openai_api_key',
	'ace_openai_model',
	'ace_default_length',
	'ace_default_tone',
	'ace_enable_cache',
	'ace_enable_rate_limit',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// 2. Clear all cached articles (transients).
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE %s 
		OR option_name LIKE %s",
		'_transient_ace_article_%',
		'_transient_timeout_ace_article_%'
	)
);

// 3. Clear all rate limit data (transients).
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE %s 
		OR option_name LIKE %s",
		'_transient_ace_rate_limit_%',
		'_transient_timeout_ace_rate_limit_%'
	)
);

// Optional: Uncomment the following section if you want to remove post metadata as well.
// WARNING: This removes tracking data but keeps the posts themselves.
/*
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} 
	WHERE meta_key IN (
		'_ace_generated',
		'_ace_generated_date',
		'_ace_token_usage',
		'_ace_generation_options',
		'_ace_model',
		'_ace_estimated_cost',
		'_ace_charts'
	)"
);
*/

// Optional: Uncomment the following section if you want to DELETE all AI-generated posts.
// WARNING: This is DESTRUCTIVE and will permanently delete all posts created by the plugin!
/*
$post_ids = $wpdb->get_col(
	"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ace_generated' AND meta_value = '1'"
);

if ( ! empty( $post_ids ) ) {
	foreach ( $post_ids as $post_id ) {
		// Force delete (skip trash)
		wp_delete_post( $post_id, true );
	}
}
*/

// Clear any remaining transients.
wp_cache_flush();

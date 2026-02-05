<?php
/**
 * Cache Manager class for caching articles and rate limiting.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Cache_Manager
 *
 * Handles caching of generated articles and rate limiting for API requests.
 */
class Cache_Manager {

	/**
	 * Cache key prefix for articles.
	 */
	const ARTICLE_CACHE_PREFIX = 'ace_article_';

	/**
	 * Cache key prefix for rate limiting.
	 */
	const RATE_LIMIT_PREFIX = 'ace_rate_limit_';

	/**
	 * Get a cached article by cache key.
	 *
	 * @param string $cache_key The cache key (hashed from topic + options).
	 * @return mixed|false The cached article data, or false if not found.
	 */
	public static function get_cached_article( $cache_key ) {
		if ( ! self::is_cache_enabled() ) {
			return false;
		}

		$key = self::ARTICLE_CACHE_PREFIX . md5( $cache_key );
		return get_transient( $key );
	}

	/**
	 * Cache an article.
	 *
	 * @param string $cache_key     The cache key.
	 * @param array  $article_data  The article data to cache.
	 * @param int    $expiration    Cache expiration in seconds (default: 24 hours).
	 * @return bool Whether the cache was set successfully.
	 */
	public static function cache_article( $cache_key, $article_data, $expiration = null ) {
		if ( ! self::is_cache_enabled() ) {
			return false;
		}

		if ( null === $expiration ) {
			$expiration = ACE_CACHE_EXPIRATION;
		}

		$key = self::ARTICLE_CACHE_PREFIX . md5( $cache_key );
		return set_transient( $key, $article_data, $expiration );
	}

	/**
	 * Clear a specific cached article.
	 *
	 * @param string $cache_key The cache key.
	 * @return bool Whether the cache was deleted.
	 */
	public static function clear_cached_article( $cache_key ) {
		$key = self::ARTICLE_CACHE_PREFIX . md5( $cache_key );
		return delete_transient( $key );
	}

	/**
	 * Clear all cached articles.
	 *
	 * @return int Number of cache entries deleted.
	 */
	public static function clear_all_cache() {
		global $wpdb;

		$count = 0;

		// Delete transients.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::ARTICLE_CACHE_PREFIX . '%',
				'_transient_timeout_' . self::ARTICLE_CACHE_PREFIX . '%'
			)
		);

		if ( $deleted ) {
			$count = $deleted / 2; // Each transient has 2 entries (value + timeout).
		}

		return (int) $count;
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @return bool Whether caching is enabled.
	 */
	private static function is_cache_enabled() {
		return '1' === get_option( 'ace_enable_cache', '1' );
	}

	/**
	 * Check if rate limiting is enabled.
	 *
	 * @return bool Whether rate limiting is enabled.
	 */
	private static function is_rate_limit_enabled() {
		return '1' === get_option( 'ace_enable_rate_limit', '1' );
	}

	/**
	 * Check if a user has exceeded the rate limit.
	 *
	 * @param int $user_id User ID (default: current user).
	 * @return bool|\WP_Error True if within limit, WP_Error if exceeded.
	 */
	public static function check_rate_limit( $user_id = null ) {
		if ( ! self::is_rate_limit_enabled() ) {
			return true;
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Admins bypass rate limiting.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$key   = self::RATE_LIMIT_PREFIX . $user_id;
		$count = (int) get_transient( $key );

		if ( $count >= ACE_MAX_REQUESTS_PER_HOUR ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: Maximum requests per hour */
					__( 'Rate limit exceeded. You can generate a maximum of %d articles per hour. Please try again later.', 'ai-content-engine' ),
					ACE_MAX_REQUESTS_PER_HOUR
				)
			);
		}

		return true;
	}

	/**
	 * Increment the rate limit counter for a user.
	 *
	 * @param int $user_id User ID (default: current user).
	 */
	public static function increment_rate_limit( $user_id = null ) {
		if ( ! self::is_rate_limit_enabled() ) {
			return;
		}

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$key   = self::RATE_LIMIT_PREFIX . $user_id;
		$count = (int) get_transient( $key );

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	/**
	 * Get the current rate limit count for a user.
	 *
	 * @param int $user_id User ID (default: current user).
	 * @return int The current count.
	 */
	public static function get_rate_limit_count( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$key = self::RATE_LIMIT_PREFIX . $user_id;
		return (int) get_transient( $key );
	}

	/**
	 * Reset the rate limit for a user.
	 *
	 * @param int $user_id User ID (default: current user).
	 * @return bool Whether the rate limit was reset.
	 */
	public static function reset_rate_limit( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		$key = self::RATE_LIMIT_PREFIX . $user_id;
		return delete_transient( $key );
	}

	/**
	 * Generate a cache key from topic and options.
	 *
	 * @param string $topic   Article topic.
	 * @param array  $options Article generation options.
	 * @return string The cache key.
	 */
	public static function generate_cache_key( $topic, $options ) {
		// Create a deterministic key from topic and key options.
		$cache_parts = array(
			'topic'    => $topic,
			'length'   => $options['length'] ?? 'medium',
			'tone'     => $options['tone'] ?? 'professional',
			'keywords' => isset( $options['keywords'] ) ? implode( ',', (array) $options['keywords'] ) : '',
			'tables'   => $options['include_tables'] ?? false,
			'charts'   => $options['include_charts'] ?? false,
		);

		return md5( wp_json_encode( $cache_parts ) );
	}
}

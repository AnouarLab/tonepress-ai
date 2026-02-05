<?php
/**
 * Reading Time class for displaying estimated reading time.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Reading_Time
 *
 * Handles reading time calculation and display.
 */
class Reading_Time {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		// Frontend display filter.
		add_filter( 'the_content', array( __CLASS__, 'display_reading_time' ), 5 );
		
		// Shortcode.
		add_shortcode( 'ace_reading_time', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Calculate reading time for content.
	 *
	 * @param string $content Content to calculate.
	 * @param int    $wpm Words per minute (default 200).
	 * @return int Reading time in minutes.
	 */
	public static function calculate( $content, $wpm = 200 ) {
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		return max( 1, ceil( $word_count / $wpm ) );
	}

	/**
	 * Get reading time for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int|null Reading time or null if not AI-generated.
	 */
	public static function get_reading_time( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$reading_time = get_post_meta( $post_id, '_ace_reading_time', true );
		
		if ( ! $reading_time ) {
			// Calculate if not stored.
			$content = get_post_field( 'post_content', $post_id );
			$reading_time = self::calculate( $content );
		}

		return (int) $reading_time;
	}

	/**
	 * Get word count for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Word count.
	 */
	public static function get_word_count( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$word_count = get_post_meta( $post_id, '_ace_word_count', true );
		
		if ( ! $word_count ) {
			$content = get_post_field( 'post_content', $post_id );
			$word_count = str_word_count( wp_strip_all_tags( $content ) );
		}

		return (int) $word_count;
	}

	/**
	 * Display reading time before content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public static function display_reading_time( $content ) {
		// Only on single posts and if enabled.
		if ( ! is_singular() || ! is_main_query() ) {
			return $content;
		}

		// Check if display is enabled.
		$display_enabled = get_option( 'ace_display_reading_time', true );
		if ( ! $display_enabled ) {
			return $content;
		}

		// Only for AI-generated posts (optional: can show for all).
		$is_ace_post = get_post_meta( get_the_ID(), '_ace_generated', true );
		$show_for_all = apply_filters( 'ace_reading_time_show_for_all', false );
		
		if ( ! $is_ace_post && ! $show_for_all ) {
			return $content;
		}

		$reading_time = self::get_reading_time();
		$word_count = self::get_word_count();

		$reading_time_html = sprintf(
			'<div class="ace-reading-time" style="margin-bottom: 20px; padding: 12px 16px; background: #f8f9fa; border-left: 4px solid #2271b1; border-radius: 4px;">
				<span class="dashicons dashicons-clock" style="color: #2271b1; margin-right: 8px;"></span>
				<strong>%s</strong> &bull; %s
			</div>',
			sprintf(
				/* translators: %d: Number of minutes */
				_n( '%d min read', '%d min read', $reading_time, 'ai-content-engine' ),
				$reading_time
			),
			sprintf(
				/* translators: %s: Number of words */
				__( '%s words', 'ai-content-engine' ),
				number_format( $word_count )
			)
		);

		return apply_filters( 'ace_reading_time_html', $reading_time_html, $reading_time, $word_count ) . $content;
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Reading time display.
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'     => get_the_ID(),
				'show_words'  => 'yes',
				'icon'        => 'yes',
			),
			$atts
		);

		$reading_time = self::get_reading_time( $atts['post_id'] );
		$word_count = self::get_word_count( $atts['post_id'] );

		$icon = ( 'yes' === $atts['icon'] ) ? '<span class="dashicons dashicons-clock"></span> ' : '';
		$words = ( 'yes' === $atts['show_words'] ) 
			? sprintf( ' &bull; %s words', number_format( $word_count ) )
			: '';

		return sprintf(
			'<span class="ace-reading-time-inline">%s%d min read%s</span>',
			$icon,
			$reading_time,
			$words
		);
	}
}

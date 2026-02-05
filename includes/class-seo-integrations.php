<?php
/**
 * SEO Integrations class for Yoast SEO and RankMath.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class SEO_Integrations
 *
 * Handles integration with popular SEO plugins and fallback meta storage.
 */
class SEO_Integrations {

	/**
	 * Active SEO plugin.
	 *
	 * @var string|null
	 */
	private $active_plugin = null;

	/**
	 * Initialize SEO integrations.
	 */
	public function init() {
		$this->detect_seo_plugin();
	}

	/**
	 * Detect which SEO plugin is active.
	 */
	private function detect_seo_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			$this->active_plugin = 'yoast';
		} elseif ( class_exists( 'RankMath' ) ) {
			$this->active_plugin = 'rankmath';
		}
	}

	/**
	 * Save SEO metadata for a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta    SEO metadata (title, description, keywords).
	 * @return bool Whether the metadata was saved successfully.
	 */
	public function save_seo_meta( $post_id, $meta ) {
		if ( empty( $meta ) ) {
			return false;
		}

		$title       = $meta['title'] ?? '';
		$description = $meta['description'] ?? '';
		$keywords    = $meta['keywords'] ?? array();

		// Convert keywords array to string.
		if ( is_array( $keywords ) ) {
			$keywords_string = implode( ', ', $keywords );
		} else {
			$keywords_string = $keywords;
		}

		// Save using the appropriate SEO plugin or fallback.
		switch ( $this->active_plugin ) {
			case 'yoast':
				$this->save_yoast_meta( $post_id, $title, $description, $keywords_string );
				break;

			case 'rankmath':
				$this->save_rankmath_meta( $post_id, $title, $description, $keywords_string );
				break;

			default:
				$this->save_fallback_meta( $post_id, $title, $description, $keywords_string );
				break;
		}

		return true;
	}

	/**
	 * Save metadata for Yoast SEO.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $title       SEO title.
	 * @param string $description Meta description.
	 * @param string $keywords    Focus keywords.
	 */
	private function save_yoast_meta( $post_id, $title, $description, $keywords ) {
		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $title ) );
		}

		if ( ! empty( $description ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $description ) );
		}

		if ( ! empty( $keywords ) ) {
			// Yoast uses the first keyword as the focus keyword.
			$keywords_array = array_map( 'trim', explode( ',', $keywords ) );
			$focus_keyword  = $keywords_array[0] ?? '';

			if ( ! empty( $focus_keyword ) ) {
				update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $focus_keyword ) );
			}

			// Save all keywords as well.
			update_post_meta( $post_id, '_yoast_wpseo_metakeywords', sanitize_text_field( $keywords ) );
		}
	}

	/**
	 * Save metadata for RankMath.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $title       SEO title.
	 * @param string $description Meta description.
	 * @param string $keywords    Focus keywords.
	 */
	private function save_rankmath_meta( $post_id, $title, $description, $keywords ) {
		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, 'rank_math_title', sanitize_text_field( $title ) );
		}

		if ( ! empty( $description ) ) {
			update_post_meta( $post_id, 'rank_math_description', sanitize_textarea_field( $description ) );
		}

		if ( ! empty( $keywords ) ) {
			// RankMath uses the first keyword as the focus keyword.
			$keywords_array = array_map( 'trim', explode( ',', $keywords ) );
			$focus_keyword  = $keywords_array[0] ?? '';

			if ( ! empty( $focus_keyword ) ) {
				update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $focus_keyword ) );
			}
		}
	}

	/**
	 * Save metadata as custom post meta (fallback when no SEO plugin is active).
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $title       SEO title.
	 * @param string $description Meta description.
	 * @param string $keywords    Keywords.
	 */
	private function save_fallback_meta( $post_id, $title, $description, $keywords ) {
		if ( ! empty( $title ) ) {
			update_post_meta( $post_id, '_ace_meta_title', sanitize_text_field( $title ) );
		}

		if ( ! empty( $description ) ) {
			update_post_meta( $post_id, '_ace_meta_description', sanitize_textarea_field( $description ) );
		}

		if ( ! empty( $keywords ) ) {
			update_post_meta( $post_id, '_ace_meta_keywords', sanitize_text_field( $keywords ) );
		}

		// Also add a filter to inject this meta into the head.
		add_action( 'wp_head', array( $this, 'output_fallback_meta' ) );
	}

	/**
	 * Output fallback meta tags in the head.
	 */
	public function output_fallback_meta() {
		if ( ! is_single() ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		$title       = get_post_meta( $post->ID, '_ace_meta_title', true );
		$description = get_post_meta( $post->ID, '_ace_meta_description', true );
		$keywords    = get_post_meta( $post->ID, '_ace_meta_keywords', true );

		if ( ! empty( $title ) ) {
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
		}

		if ( ! empty( $description ) ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
		}

		if ( ! empty( $keywords ) ) {
			echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '" />' . "\n";
		}
	}

	/**
	 * Get the currently active SEO plugin.
	 *
	 * @return string|null 'yoast', 'rankmath', or null.
	 */
	public function get_active_plugin() {
		return $this->active_plugin;
	}

	/**
	 * Check if an SEO plugin is active.
	 *
	 * @return bool Whether an SEO plugin is active.
	 */
	public function has_seo_plugin() {
		return null !== $this->active_plugin;
	}
}

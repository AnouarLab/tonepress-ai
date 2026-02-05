<?php
/**
 * Content Template class for managing reusable article templates.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Content_Template
 *
 * Manages content generation templates for faster, consistent article creation.
 */
class Content_Template {

	/**
	 * Option key for storing templates.
	 */
	const OPTION_KEY = 'ace_content_templates';

	/**
	 * Get all templates.
	 *
	 * @return array Array of templates.
	 */
	public static function get_all() {
		$templates = get_option( self::OPTION_KEY, array() );
		
		// Ensure it's an array.
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}
		
		return $templates;
	}

	/**
	 * Get single template by ID.
	 *
	 * @param string $id Template ID.
	 * @return array|null Template data or null if not found.
	 */
	public static function get( $id ) {
		$templates = self::get_all();
		return $templates[ $id ] ?? null;
	}

	/**
	 * Save or update a template.
	 *
	 * @param array $template Template data.
	 * @return string Template ID.
	 */
	public static function save( $template ) {
		$templates = self::get_all();

		// Generate ID if new template.
		if ( empty( $template['id'] ) ) {
			$template['id'] = 'template-' . time() . '-' . wp_rand( 1000, 9999 );
		}

		// Add metadata.
		if ( empty( $template['created'] ) ) {
			$template['created'] = current_time( 'mysql' );
		}
		$template['updated'] = current_time( 'mysql' );

		// Sanitize template data.
		$template = self::sanitize_template( $template );

		// Save template.
		$templates[ $template['id'] ] = $template;
		update_option( self::OPTION_KEY, $templates );

		return $template['id'];
	}

	/**
	 * Delete a template.
	 *
	 * @param string $id Template ID.
	 * @return bool True on success, false if template doesn't exist.
	 */
	public static function delete( $id ) {
		$templates = self::get_all();

		if ( ! isset( $templates[ $id ] ) ) {
			return false;
		}

		unset( $templates[ $id ] );
		update_option( self::OPTION_KEY, $templates );

		return true;
	}

	/**
	 * Apply template to generation options.
	 *
	 * @param string $id            Template ID.
	 * @param string $specific_topic Optional. Override topic with specific one.
	 * @return array|null Generation options or null if template not found.
	 */
	public static function apply( $id, $specific_topic = '' ) {
		$template = self::get( $id );

		if ( ! $template ) {
			return null;
		}

		return array(
			'topic'               => $specific_topic ?: $template['topic_pattern'],
			'keywords'            => $template['keywords'] ?? '',
			'tone'                => $template['tone'] ?? 'professional',
			'length'              => $template['length'] ?? 'medium',
			'custom_instructions' => $template['custom_instructions'] ?? '',
		);
	}

	/**
	 * Sanitize template data.
	 *
	 * @param array $template Template data.
	 * @return array Sanitized template.
	 */
	private static function sanitize_template( $template ) {
		return array(
			'id'                  => sanitize_key( $template['id'] ?? '' ),
			'name'                => sanitize_text_field( $template['name'] ?? '' ),
			'description'         => sanitize_textarea_field( $template['description'] ?? '' ),
			'topic_pattern'       => sanitize_text_field( $template['topic_pattern'] ?? '' ),
			'keywords'            => sanitize_textarea_field( $template['keywords'] ?? '' ),
			'tone'                => sanitize_text_field( $template['tone'] ?? 'professional' ),
			'length'              => sanitize_text_field( $template['length'] ?? 'medium' ),
			'custom_instructions' => sanitize_textarea_field( $template['custom_instructions'] ?? '' ),
			'created'             => $template['created'] ?? current_time( 'mysql' ),
			'updated'             => current_time( 'mysql' ),
		);
	}

	/**
	 * Get template count.
	 *
	 * @return int Number of templates.
	 */
	public static function count() {
		$templates = self::get_all();
		return count( $templates );
	}

	/**
	 * Create default templates on first install.
	 *
	 * @return void
	 */
	public static function create_defaults() {
		// Only create if no templates exist.
		if ( self::count() > 0 ) {
			return;
		}

		$defaults = array(
			array(
				'name'                => 'How-To Guide',
				'description'         => 'Step-by-step tutorial format',
				'topic_pattern'       => 'How to [achieve result]',
				'keywords'            => 'tutorial, guide, step-by-step',
				'tone'                => 'helpful',
				'length'              => 'medium',
				'custom_instructions' => "Create a comprehensive how-to guide with:\n- Clear requirements section\n- Numbered steps (5-7)\n- Examples for each step\n- Common mistakes section\n- Final tips",
			),
			array(
				'name'                => 'Listicle Article',
				'description'         => 'Numbered list format',
				'topic_pattern'       => '[Number] Ways to [benefit]',
				'keywords'            => 'tips, best practices, list',
				'tone'                => 'engaging',
				'length'              => 'medium',
				'custom_instructions' => "Create an engaging listicle with:\n- Catchy numbered list (7-10 items)\n- 2-3 paragraphs per item\n- Real examples\n- Data/statistics for credibility",
			),
			array(
				'name'                => 'Product Feature',
				'description'         => 'Highlight specific product feature',
				'topic_pattern'       => 'How [Feature] Solves [Problem]',
				'keywords'            => 'feature, solution, benefit',
				'tone'                => 'professional',
				'length'              => 'short',
				'custom_instructions' => "Focus on:\n- Problem identification\n- Feature explanation\n- Benefits and use cases\n- Customer examples\n- Clear CTA",
			),
		);

		foreach ( $defaults as $template ) {
			self::save( $template );
		}
	}
}

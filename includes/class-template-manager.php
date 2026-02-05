<?php
/**
 * Template Manager class for content templates.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Template_Manager
 *
 * Manages article templates for consistent content generation.
 */
class Template_Manager {

	/**
	 * Get all available templates.
	 *
	 * @return array Array of templates.
	 */
	public static function get_templates() {
		$templates = array(
			'default'     => self::get_default_template(),
			'how_to'      => self::get_how_to_template(),
			'review'      => self::get_review_template(),
			'comparison'  => self::get_comparison_template(),
			'listicle'    => self::get_listicle_template(),
		);

		// Allow custom templates via filter.
		return apply_filters( 'ace_content_templates', $templates );
	}

	/**
	 * Get template by ID.
	 *
	 * @param string $template_id Template ID.
	 * @return array|null Template data or null if not found.
	 */
	public static function get_template( $template_id ) {
		$templates = self::get_templates();
		return $templates[ $template_id ] ?? null;
	}

	/**
	 * Default template (current behavior).
	 *
	 * @return array Template configuration.
	 */
	private static function get_default_template() {
		return array(
			'id'          => 'default',
			'name'        => __( 'Default Article', 'ai-content-engine' ),
			'description' => __( 'Standard blog article format', 'ai-content-engine' ),
			'prompt_additions' => '',
			'required_sections' => array(),
		);
	}

	/**
	 * How-To Guide template.
	 *
	 * @return array Template configuration.
	 */
	private static function get_how_to_template() {
		return array(
			'id'          => 'how_to',
			'name'        => __( 'How-To Guide', 'ai-content-engine' ),
			'description' => __( 'Step-by-step instructional article', 'ai-content-engine' ),
			'prompt_additions' => "\n\nSTRUCTURE REQUIREMENTS:\n" .
				"- Start with a brief introduction explaining what the reader will learn\n" .
				"- Include a 'What You'll Need' or 'Prerequisites' section\n" .
				"- Present steps in numbered format with clear headings (Step 1, Step 2, etc.)\n" .
				"- Each step should have a descriptive heading and detailed explanation\n" .
				"- Include tips, warnings, or notes where relevant\n" .
				"- End with a 'Conclusion' or 'What's Next' section\n" .
				"- Add a FAQ section addressing common questions",
			'required_sections' => array( 'introduction', 'steps', 'conclusion' ),
			'include_tables' => true,
			'include_charts' => false,
		);
	}

	/**
	 * Product Review template.
	 *
	 * @return array Template configuration.
	 */
	private static function get_review_template() {
		return array(
			'id'          => 'review',
			'name'        => __( 'Product Review', 'ai-content-engine' ),
			'description' => __( 'Comprehensive product evaluation', 'ai-content-engine' ),
			'prompt_additions' => "\n\nSTRUCTURE REQUIREMENTS:\n" .
				"- Start with a brief overview and rating (out of 5 or 10)\n" .
				"- Include 'Key Specifications' section with bullet points\n" .
				"- 'Pros and Cons' section with balanced lists\n" .
				"- Detailed 'Features and Performance' section\n" .
				"- 'Price and Value' analysis\n" .
				"- 'Comparison with Alternatives' (include comparison table)\n" .
				"- Final 'Verdict' or 'Recommendation' section\n" .
				"- Who this product is best for",
			'required_sections' => array( 'overview', 'pros_cons', 'features', 'verdict' ),
			'include_tables' => true,
			'include_charts' => true,
		);
	}

	/**
	 * Comparison Article template.
	 *
	 * @return array Template configuration.
	 */
	private static function get_comparison_template() {
		return array(
			'id'          => 'comparison',
			'name'        => __( 'Comparison Article', 'ai-content-engine' ),
			'description' => __( 'Side-by-side comparison of options', 'ai-content-engine' ),
			'prompt_additions' => "\n\nSTRUCTURE REQUIREMENTS:\n" .
				"- Introduction explaining what's being compared and why\n" .
				"- Quick comparison table showing key differences at a glance\n" .
				"- Individual sections for each option being compared\n" .
				"- 'Key Differences' section highlighting main distinctions\n" .
				"- 'Similarities' section if applicable\n" .
				"- 'Which One Should You Choose' with use case recommendations\n" .
				"- Include detailed comparison table with features/specs\n" .
				"- Use charts to visualize price, performance, or other metrics",
			'required_sections' => array( 'introduction', 'comparison_table', 'analysis', 'recommendation' ),
			'include_tables' => true,
			'include_charts' => true,
		);
	}

	/**
	 * Listicle template.
	 *
	 * @return array Template configuration.
	 */
	private static function get_listicle_template() {
		return array(
			'id'          => 'listicle',
			'name'        => __( 'Listicle (Top 10, Best Of)', 'ai-content-engine' ),
			'description' => __( 'Numbered list article format', 'ai-content-engine' ),
			'prompt_additions' => "\n\nSTRUCTURE REQUIREMENTS:\n" .
				"- Engaging introduction explaining the list topic\n" .
				"- Each item should have:\n" .
				"  * Clear numbered heading (e.g., '1. Item Name')\n" .
				"  * Brief description or explanation\n" .
				"  * Why it made the list\n" .
				"  * Key highlights or features\n" .
				"- Items should be ordered logically (best to worst, chronological, etc.)\n" .
				"- Include a summary table listing all items for quick reference\n" .
				"- Conclusion tying the list together\n" .
				"- Suggested list sizes: 5, 7, 10, or 15 items",
			'required_sections' => array( 'introduction', 'list_items', 'conclusion' ),
			'include_tables' => true,
			'include_charts' => false,
		);
	}

	/**
	 * Apply template to generation options.
	 *
	 * @param array  $options     Current generation options.
	 * @param string $template_id Template ID to apply.
	 * @return array Modified options.
	 */
	public static function apply_template( $options, $template_id ) {
		$template = self::get_template( $template_id );

		if ( ! $template ) {
			return $options;
		}

		// Merge template requirements into custom instructions.
		if ( ! empty( $template['prompt_additions'] ) ) {
			$existing_instructions = $options['custom_instructions'] ?? '';
			$options['custom_instructions'] = $existing_instructions . $template['prompt_additions'];
		}

		// Override include flags if template specifies.
		if ( isset( $template['include_tables'] ) ) {
			$options['include_tables'] = $template['include_tables'];
		}

		if ( isset( $template['include_charts'] ) ) {
			$options['include_charts'] = $template['include_charts'];
		}

		// Store template ID in options.
		$options['template_id'] = $template_id;

		return $options;
	}

	/**
	 * Get custom templates created by users.
	 *
	 * @return array Array of custom templates.
	 */
	public static function get_custom_templates() {
		$custom_templates = get_option( 'ace_custom_templates', array() );
		return is_array( $custom_templates ) ? $custom_templates : array();
	}

	/**
	 * Save a custom template.
	 *
	 * @param array $template Template data.
	 * @return bool Whether the template was saved successfully.
	 */
	public static function save_custom_template( $template ) {
		$custom_templates = self::get_custom_templates();

		// Generate unique ID if not provided.
		if ( empty( $template['id'] ) ) {
			$template['id'] = 'custom_' . uniqid();
		}

		// Sanitize template data.
		$template = array(
			'id'                => sanitize_key( $template['id'] ),
			'name'              => sanitize_text_field( $template['name'] ?? 'Custom Template' ),
			'description'       => sanitize_textarea_field( $template['description'] ?? '' ),
			'prompt_additions'  => sanitize_textarea_field( $template['prompt_additions'] ?? '' ),
			'include_tables'    => ! empty( $template['include_tables'] ),
			'include_charts'    => ! empty( $template['include_charts'] ),
		);

		$custom_templates[ $template['id'] ] = $template;

		return update_option( 'ace_custom_templates', $custom_templates );
	}

	/**
	 * Delete a custom template.
	 *
	 * @param string $template_id Template ID to delete.
	 * @return bool Whether the template was deleted successfully.
	 */
	public static function delete_custom_template( $template_id ) {
		$custom_templates = self::get_custom_templates();

		if ( isset( $custom_templates[ $template_id ] ) ) {
			unset( $custom_templates[ $template_id ] );
			return update_option( 'ace_custom_templates', $custom_templates );
		}

		return false;
	}
}

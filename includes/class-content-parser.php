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
 * Content Parser class for parsing and injecting AI-generated content.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Content_Parser
 *
 * Handles parsing, sanitization, and injection of tables and charts into HTML content.
 */
class Content_Parser {

	/**
	 * Parse and inject tables and charts into content.
	 *
	 * @param string $content_html Base HTML content from AI.
	 * @param array  $tables       Array of table data with 'title' and 'html'.
	 * @param array  $chart_ids    Array of chart IDs to inject placeholders for.
	 * @return string Final parsed and sanitized HTML.
	 */
	public function parse_and_inject( $content_html, $tables = array(), $chart_ids = array() ) {
		// 1. Sanitize the base HTML content.
		$content = Security::sanitize_ai_content( $content_html );

		// 2. Inject tables if provided.
		if ( ! empty( $tables ) ) {
			$content = $this->inject_tables( $content, $tables );
		}

		// 3. Inject chart placeholders if provided.
		if ( ! empty( $chart_ids ) ) {
			$content = $this->inject_chart_placeholders( $content, $chart_ids );
		}

		// 4. Final validation and cleanup.
		$content = $this->cleanup_html( $content );

		return $content;
	}

	/**
	 * Inject tables into content at logical positions.
	 *
	 * @param string $content HTML content.
	 * @param array  $tables  Array of tables with 'title' and 'html'.
	 * @return string Content with tables injected.
	 */
	private function inject_tables( $content, $tables ) {
		// Find logical injection points (after H2 or H3 headings).
		$injection_points = $this->find_injection_points( $content, 'h2|h3' );

		if ( empty( $injection_points ) ) {
			// Fallback: inject after the first paragraph.
			$injection_points = $this->find_injection_points( $content, 'p' );
		}

		foreach ( $tables as $index => $table_data ) {
			// Sanitize table HTML.
			$table_html = Security::sanitize_ai_content( $table_data['html'] ?? '' );

			if ( empty( $table_html ) ) {
				continue;
			}

			// Wrap table in a container.
			$table_wrapper = sprintf(
				'<div class="ai-table-wrapper">%s%s</div>',
				! empty( $table_data['title'] ) ? '<p class="ai-table-title"><strong>' . esc_html( $table_data['title'] ) . '</strong></p>' : '',
				$table_html
			);

			// Find injection point for this table.
			$point_index = $index % count( $injection_points );
			$injection_point = $injection_points[ $point_index ] ?? null;

			if ( $injection_point ) {
				// Insert table after the injection point.
				$content = substr_replace( $content, $table_wrapper, $injection_point, 0 );

				// Adjust remaining injection points (shift by the length of inserted content).
				$shift = strlen( $table_wrapper );
				foreach ( $injection_points as $key => $point ) {
					if ( $point > $injection_point ) {
						$injection_points[ $key ] += $shift;
					}
				}
			} else {
				// Fallback: append at the end.
				$content .= "\n\n" . $table_wrapper;
			}
		}

		return $content;
	}

	/**
	 * Inject chart placeholders into content.
	 *
	 * @param string $content   HTML content.
	 * @param array  $chart_ids Array of chart IDs.
	 * @return string Content with chart placeholders injected.
	 */
	private function inject_chart_placeholders( $content, $chart_ids ) {
		// Find logical injection points (after H2 or H3 headings).
		$injection_points = $this->find_injection_points( $content, 'h2|h3' );

		if ( empty( $injection_points ) ) {
			// Fallback: inject after paragraphs.
			$injection_points = $this->find_injection_points( $content, 'p' );
		}

		foreach ( $chart_ids as $index => $chart_id ) {
			// Create canvas element for Chart.js.
			$chart_placeholder = sprintf(
				'<div class="ai-chart-wrapper"><canvas data-chart-id="%s" width="600" height="400"></canvas></div>',
				esc_attr( $chart_id )
			);

			// Find injection point for this chart.
			$point_index = $index % count( $injection_points );
			$injection_point = $injection_points[ $point_index ] ?? null;

			if ( $injection_point ) {
				// Insert chart after the injection point.
				$content = substr_replace( $content, $chart_placeholder, $injection_point, 0 );

				// Adjust remaining injection points.
				$shift = strlen( $chart_placeholder );
				foreach ( $injection_points as $key => $point ) {
					if ( $point > $injection_point ) {
						$injection_points[ $key ] += $shift;
					}
				}
			} else {
				// Fallback: append at the end.
				$content .= "\n\n" . $chart_placeholder;
			}
		}

		return $content;
	}

	/**
	 * Find injection points in HTML content.
	 *
	 * @param string $content HTML content.
	 * @param string $tag     Tag pattern (e.g., 'h2|h3' or 'p').
	 * @return array Array of character positions where content can be injected.
	 */
	private function find_injection_points( $content, $tag ) {
		$points = array();

		// Use regex to find all closing tags of the specified type.
		$pattern = sprintf( '#</%s>#i', $tag );
		preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[0] as $match ) {
				// Position right after the closing tag.
				$points[] = $match[1] + strlen( $match[0] );
			}
		}

		return $points;
	}

	/**
	 * Clean up HTML to ensure valid structure.
	 *
	 * @param string $content HTML content.
	 * @return string Cleaned HTML.
	 */
	private function cleanup_html( $content ) {
		// Remove multiple consecutive line breaks.
		$content = preg_replace( '/(\n\s*){3,}/', "\n\n", $content );

		// Ensure proper spacing around block elements.
		$content = preg_replace( '/(<\/(?:div|table|p|h[1-6])>)(\s*)(<(?:div|table|p|h[1-6]))/i', '$1' . "\n\n" . '$3', $content );

		// Trim whitespace.
		$content = trim( $content );

		return $content;
	}

	/**
	 * Validate AI-generated JSON structure.
	 *
	 * @param array $data Decoded JSON data.
	 * @return bool|\WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_json_structure( $data ) {
		// Required fields.
		$required_fields = array( 'title', 'meta', 'content_html' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				return new \WP_Error(
					'invalid_json',
					sprintf(
						/* translators: %s: Missing field name */
						__( 'Invalid JSON structure: Missing required field "%s".', 'ai-content-engine' ),
						$field
					)
				);
			}
		}

		// Validate meta structure.
		if ( ! isset( $data['meta']['description'] ) || ! isset( $data['meta']['keywords'] ) ) {
			return new \WP_Error(
				'invalid_json',
				__( 'Invalid JSON structure: Meta must contain "description" and "keywords".', 'ai-content-engine' )
			);
		}

		// Validate tables structure (if present).
		if ( isset( $data['tables'] ) && is_array( $data['tables'] ) ) {
			foreach ( $data['tables'] as $index => $table ) {
				if ( ! isset( $table['html'] ) ) {
					return new \WP_Error(
						'invalid_json',
						sprintf(
							/* translators: %d: Table index */
							__( 'Invalid JSON structure: Table %d is missing "html" field.', 'ai-content-engine' ),
							$index
						)
					);
				}
			}
		}

		// Validate charts structure (if present).
		if ( isset( $data['charts'] ) && is_array( $data['charts'] ) ) {
			foreach ( $data['charts'] as $index => $chart ) {
				$required_chart_fields = array( 'id', 'type', 'labels', 'datasets' );
				foreach ( $required_chart_fields as $field ) {
					if ( ! isset( $chart[ $field ] ) ) {
						return new \WP_Error(
							'invalid_json',
							sprintf(
								/* translators: 1: Chart index, 2: Missing field name */
								__( 'Invalid JSON structure: Chart %1$d is missing "%2$s" field.', 'ai-content-engine' ),
								$index,
								$field
							)
						);
					}
				}
			}
		}

		return true;
	}
}

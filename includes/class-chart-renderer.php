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
 * Chart Renderer class for Chart.js integration.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Chart_Renderer
 *
 * Handles Chart.js integration and chart rendering on the frontend.
 */
class Chart_Renderer {

	/**
	 * Initialize the chart renderer.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_chart_scripts' ) );
	}

	/**
	 * Save chart data to post meta.
	 *
	 * @param int   $post_id     Post ID.
	 * @param array $charts_data Array of chart data.
	 * @return bool Whether the meta was saved successfully.
	 */
	public function save_charts( $post_id, $charts_data ) {
		if ( empty( $charts_data ) ) {
			delete_post_meta( $post_id, '_ai_charts' );
			return false;
		}

		return update_post_meta( $post_id, '_ai_charts', $charts_data );
	}

	/**
	 * Get chart data from post meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array|false Chart data array or false if none.
	 */
	public function get_charts( $post_id ) {
		$charts = get_post_meta( $post_id, '_ai_charts', true );
		return ! empty( $charts ) ? $charts : false;
	}

	/**
	 * Check if a post has charts.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether the post has charts.
	 */
	public function has_charts( $post_id ) {
		$charts = $this->get_charts( $post_id );
		return ! empty( $charts );
	}

	/**
	 * Enqueue Chart.js scripts on posts that have charts.
	 */
	public function enqueue_chart_scripts() {
		if ( ! is_single() ) {
			return;
		}

		global $post;
		if ( ! $post || ! $this->has_charts( $post->ID ) ) {
			return;
		}

		// Enqueue Chart.js library.
		wp_enqueue_script(
			'chartjs',
			ACE_PLUGIN_URL . 'assets/js/chart.min.js',
			array(),
			'4.4.0',
			true
		);

		// Enqueue our chart renderer script.
		wp_enqueue_script(
			'ace-chart-renderer',
			ACE_PLUGIN_URL . 'assets/js/chart-renderer.js',
			array( 'chartjs' ),
			ACE_VERSION,
			true
		);

		// Pass chart data to JavaScript.
		$charts = $this->get_charts( $post->ID );
		wp_localize_script(
			'ace-chart-renderer',
			'aceChartData',
			$charts
		);

		// Enqueue chart styles.
		wp_enqueue_style(
			'ace-charts',
			ACE_PLUGIN_URL . 'assets/css/charts.css',
			array(),
			ACE_VERSION
		);
	}

	/**
	 * Validate chart data structure.
	 *
	 * @param array $chart Chart data.
	 * @return bool|\WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_chart_data( $chart ) {
		$required_fields = array( 'id', 'type', 'labels', 'datasets' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $chart[ $field ] ) ) {
				return new \WP_Error(
					'invalid_chart_data',
					sprintf(
						/* translators: %s: Missing field name */
						__( 'Chart data missing required field: %s', 'tonepress-ai' ),
						$field
					)
				);
			}
		}

		// Validate chart type.
		$valid_types = array( 'bar', 'line', 'pie', 'doughnut', 'radar', 'polarArea' );
		if ( ! in_array( $chart['type'], $valid_types, true ) ) {
			return new \WP_Error(
				'invalid_chart_type',
				sprintf(
					/* translators: %s: Invalid chart type */
					__( 'Invalid chart type: %s. Must be one of: bar, line, pie, doughnut, radar, polarArea.', 'tonepress-ai' ),
					$chart['type']
				)
			);
		}

		// Validate datasets structure.
		if ( ! is_array( $chart['datasets'] ) || empty( $chart['datasets'] ) ) {
			return new \WP_Error(
				'invalid_chart_datasets',
				__( 'Chart must have at least one dataset.', 'tonepress-ai' )
			);
		}

		foreach ( $chart['datasets'] as $dataset ) {
			if ( ! isset( $dataset['data'] ) || ! is_array( $dataset['data'] ) ) {
				return new \WP_Error(
					'invalid_dataset',
					__( 'Each dataset must have a "data" array.', 'tonepress-ai' )
				);
			}
		}

		return true;
	}

	/**
	 * Generate default chart colors for datasets.
	 *
	 * @param int $count Number of colors needed.
	 * @return array Array of color strings.
	 */
	public function generate_chart_colors( $count ) {
		$default_colors = array(
			'rgba(59, 130, 246, 0.8)',  // Blue.
			'rgba(16, 185, 129, 0.8)',  // Green.
			'rgba(245, 158, 11, 0.8)',  // Amber.
			'rgba(239, 68, 68, 0.8)',   // Red.
			'rgba(168, 85, 247, 0.8)',  // Purple.
			'rgba(236, 72, 153, 0.8)',  // Pink.
			'rgba(20, 184, 166, 0.8)',  // Teal.
			'rgba(251, 146, 60, 0.8)',  // Orange.
		);

		$colors = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$colors[] = $default_colors[ $i % count( $default_colors ) ];
		}

		return $colors;
	}
}

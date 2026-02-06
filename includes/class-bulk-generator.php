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
 * Bulk Generator class for CSV-based batch article generation.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Bulk_Generator
 *
 * Handles bulk article generation from CSV files with queue management.
 */
class Bulk_Generator {

	/**
	 * Queue option name.
	 */
	const QUEUE_OPTION = 'ace_bulk_queue';

	/**
	 * Queue status option name.
	 */
	const STATUS_OPTION = 'ace_bulk_status';

	/**
	 * Article generator instance.
	 *
	 * @var Article_Generator
	 */
	private $generator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->generator = new Article_Generator();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Schedule cron for queue processing.
		add_action( 'ace_process_bulk_queue', array( $this, 'process_queue_item' ) );
		
		// Ensure cron event is scheduled.
		if ( ! wp_next_scheduled( 'ace_process_bulk_queue' ) ) {
			wp_schedule_event( time(), 'ace_bulk_interval', 'ace_process_bulk_queue' );
		}
		
		// Add custom cron interval.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
	}

	/**
	 * Add custom cron interval for bulk processing.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public function add_cron_interval( $schedules ) {
		// Process one item every 2 minutes to respect rate limits.
		$schedules['ace_bulk_interval'] = array(
			'interval' => 120, // 2 minutes
			'display'  => __( 'Every 2 Minutes (ACE Bulk)', 'tonepress-ai' ),
		);
		return $schedules;
	}

	/**
	 * Parse CSV file and create queue.
	 *
	 * @param string $file_path Path to CSV file.
	 * @return array|WP_Error Queue ID and item count on success, WP_Error on failure.
	 */
	public function parse_csv( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error( 'file_not_found', __( 'CSV file not found.', 'tonepress-ai' ) );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return new \WP_Error( 'file_read_error', __( 'Could not read CSV file.', 'tonepress-ai' ) );
		}

		// Read header row.
		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return new \WP_Error( 'invalid_csv', __( 'Invalid CSV format.', 'tonepress-ai' ) );
		}

		// Validate required columns.
		$required_columns = array( 'topic' );
		foreach ( $required_columns as $column ) {
			if ( ! in_array( $column, $header, true ) ) {
				fclose( $handle );
				return new \WP_Error(
					'missing_column',
					sprintf(
						/* translators: %s: Column name */
						__( 'Missing required column: %s', 'tonepress-ai' ),
						$column
					)
				);
			}
		}

		// Parse rows.
		$items = array();
		$row_number = 1;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_number++;
			
			// Skip empty rows.
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Create associative array from row.
			$item = array_combine( $header, $row );
			
			if ( empty( $item['topic'] ) ) {
				continue; // Skip rows without topic.
			}

			// Build options from CSV columns.
			$options = array(
				'length'         => $item['length'] ?? 'medium',
				'tone'           => $item['tone'] ?? 'professional',
				'keywords'       => $item['keywords'] ?? '',
				'include_tables' => ! empty( $item['include_tables'] ) && 'yes' === strtolower( $item['include_tables'] ),
				'include_charts' => ! empty( $item['include_charts'] ) && 'yes' === strtolower( $item['include_charts'] ),
				'post_status'    => $item['post_status'] ?? 'draft',
				'post_type'      => $item['post_type'] ?? 'post',
				'template_id'    => $item['template_id'] ?? 'default',
			);

			// Optional columns.
			if ( ! empty( $item['word_count'] ) ) {
				$options['word_count'] = (int) $item['word_count'];
			}

			if ( ! empty( $item['custom_instructions'] ) ) {
				$options['custom_instructions'] = $item['custom_instructions'];
			}

			if ( ! empty( $item['categories'] ) ) {
				$options['categories'] = array_map( 'intval', explode( ',', $item['categories'] ) );
			}

			$items[] = array(
				'topic'   => $item['topic'],
				'options' => $options,
				'row'     => $row_number,
			);
		}

		fclose( $handle );

		if ( empty( $items ) ) {
			return new \WP_Error( 'no_valid_rows', __( 'No valid rows found in CSV.', 'tonepress-ai' ) );
		}

		// Create queue.
		$queue_id = $this->create_queue( $items );

		return array(
			'queue_id'   => $queue_id,
			'item_count' => count( $items ),
		);
	}

	/**
	 * Create a new queue.
	 *
	 * @param array $items Queue items.
	 * @return string Queue ID.
	 */
	private function create_queue( $items ) {
		$queue_id = 'bulk_' . time() . '_' . wp_rand( 1000, 9999 );

		$queue_data = array(
			'id'         => $queue_id,
			'created'    => current_time( 'mysql' ),
			'created_by' => get_current_user_id(),
			'total'      => count( $items ),
			'completed'  => 0,
			'failed'     => 0,
			'status'     => 'pending', // pending, processing, paused, completed
			'items'      => $items,
			'results'    => array(),
		);

		// Save queue.
		$queues = get_option( self::QUEUE_OPTION, array() );
		$queues[ $queue_id ] = $queue_data;
		update_option( self::QUEUE_OPTION, $queues );

		return $queue_id;
	}

	/**
	 * Get queue by ID.
	 *
	 * @param string $queue_id Queue ID.
	 * @return array|null Queue data or null if not found.
	 */
	public function get_queue( $queue_id ) {
		$queues = get_option( self::QUEUE_OPTION, array() );
		return $queues[ $queue_id ] ?? null;
	}

	/**
	 * Get all queues.
	 *
	 * @return array All queues.
	 */
	public function get_all_queues() {
		return get_option( self::QUEUE_OPTION, array() );
	}

	/**
	 * Update queue status.
	 *
	 * @param string $queue_id Queue ID.
	 * @param string $status New status.
	 * @return bool Success.
	 */
	public function update_queue_status( $queue_id, $status ) {
		$queues = get_option( self::QUEUE_OPTION, array() );
		
		if ( ! isset( $queues[ $queue_id ] ) ) {
			return false;
		}

		$queues[ $queue_id ]['status'] = $status;
		return update_option( self::QUEUE_OPTION, $queues );
	}

	/**
	 * Process next item in active queues.
	 */
	public function process_queue_item() {
		$queues = get_option( self::QUEUE_OPTION, array() );

		foreach ( $queues as $queue_id => $queue ) {
			// Only process pending or processing queues.
			if ( ! in_array( $queue['status'], array( 'pending', 'processing' ), true ) ) {
				continue;
			}

			// Find next unprocessed item.
			foreach ( $queue['items'] as $index => $item ) {
				if ( isset( $queue['results'][ $index ] ) ) {
					continue; // Already processed.
				}

				// Mark queue as processing.
				$this->update_queue_status( $queue_id, 'processing' );

				// Generate article.
				$result = $this->generator->generate_article( $item['topic'], $item['options'] );

				// Save result.
				$queues = get_option( self::QUEUE_OPTION, array() );
				$queues[ $queue_id ]['results'][ $index ] = array(
					'topic'     => $item['topic'],
					'row'       => $item['row'],
					'success'   => ! is_wp_error( $result ),
					'post_id'   => is_wp_error( $result ) ? null : $result,
					'error'     => is_wp_error( $result ) ? $result->get_error_message() : null,
					'timestamp' => current_time( 'mysql' ),
				);

				if ( is_wp_error( $result ) ) {
					$queues[ $queue_id ]['failed']++;
				} else {
					$queues[ $queue_id ]['completed']++;
				}

				// Check if queue is complete.
				if ( $queues[ $queue_id ]['completed'] + $queues[ $queue_id ]['failed'] >= $queue['total'] ) {
					$queues[ $queue_id ]['status'] = 'completed';
					$queues[ $queue_id ]['finished'] = current_time( 'mysql' );
					
					// Send completion email.
					$this->send_completion_email( $queue_id, $queues[ $queue_id ] );
				}

				update_option( self::QUEUE_OPTION, $queues );

				// Process only one item per cron run to respect rate limits.
				return;
			}
		}
	}

	/**
	 * Pause a queue.
	 *
	 * @param string $queue_id Queue ID.
	 * @return bool Success.
	 */
	public function pause_queue( $queue_id ) {
		return $this->update_queue_status( $queue_id, 'paused' );
	}

	/**
	 * Resume a paused queue.
	 *
	 * @param string $queue_id Queue ID.
	 * @return bool Success.
	 */
	public function resume_queue( $queue_id ) {
		return $this->update_queue_status( $queue_id, 'processing' );
	}

	/**
	 * Delete a queue.
	 *
	 * @param string $queue_id Queue ID.
	 * @return bool Success.
	 */
	public function delete_queue( $queue_id ) {
		$queues = get_option( self::QUEUE_OPTION, array() );
		
		if ( ! isset( $queues[ $queue_id ] ) ) {
			return false;
		}

		unset( $queues[ $queue_id ] );
		return update_option( self::QUEUE_OPTION, $queues );
	}

	/**
	 * Export queue results to CSV.
	 *
	 * @param string $queue_id Queue ID.
	 * @return string|WP_Error CSV content or error.
	 */
	public function export_results( $queue_id ) {
		$queue = $this->get_queue( $queue_id );

		if ( ! $queue ) {
			return new \WP_Error( 'queue_not_found', __( 'Queue not found.', 'tonepress-ai' ) );
		}

		// Build CSV.
		$csv = "Row,Topic,Status,Post ID,Edit Link,Error\n";

		foreach ( $queue['results'] as $index => $result ) {
			$status = $result['success'] ? 'Success' : 'Failed';
			$post_id = $result['post_id'] ?? '';
			$edit_link = $result['post_id'] ? get_edit_post_link( $result['post_id'], 'raw' ) : '';
			$error = $result['error'] ?? '';

			$csv .= sprintf(
				"%d,\"%s\",\"%s\",%s,\"%s\",\"%s\"\n",
				$result['row'],
				str_replace( '"', '""', $result['topic'] ),
				$status,
				$post_id,
				$edit_link,
				str_replace( '"', '""', $error )
			);
		}

		return $csv;
	}

	/**
	 * Send completion email to user.
	 *
	 * @param string $queue_id Queue ID.
	 * @param array  $queue Queue data.
	 */
	private function send_completion_email( $queue_id, $queue ) {
		$user = get_user_by( 'id', $queue['created_by'] );
		
		if ( ! $user ) {
			return;
		}

		$subject = sprintf(
			/* translators: %d: Number of articles */
			__( 'Bulk Generation Complete: %d Articles Generated', 'tonepress-ai' ),
			$queue['completed']
		);

		$message = sprintf(
			__( "Your bulk article generation is complete!\n\nTotal: %d\nSuccessful: %d\nFailed: %d\n\nView results: %s", 'tonepress-ai' ),
			$queue['total'],
			$queue['completed'],
			$queue['failed'],
			admin_url( 'tools.php?page=tonepress-ai&tab=bulk&queue=' . $queue_id )
		);

		wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Get sample CSV template.
	 *
	 * @return string CSV template content.
	 */
	public static function get_csv_template() {
		$csv = "topic,keywords,length,tone,template_id,post_status,post_type,include_tables,include_charts,word_count,custom_instructions,categories\n";
		$csv .= '"How to Start a Blog","blogging,WordPress,tutorial",long,friendly,how_to,draft,post,yes,no,2000,"Include examples for beginners","1,5"\n';
		$csv .= '"Best WordPress Plugins 2024","plugins,WordPress,tools",medium,professional,listicle,draft,post,yes,yes,,,"2"\n';
		$csv .= '"iPhone 15 Pro Review","iPhone,Apple,smartphone",long,authoritative,review,draft,post,yes,yes,,"Focus on camera quality","3"\n';

		return $csv;
	}
}

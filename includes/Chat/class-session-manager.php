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
 * Session Manager for Chat Builder.
 *
 * Handles session CRUD operations and requirements tracking.
 *
 * @package AI_Content_Engine
 * @since 2.2.0
 */

namespace ACE\Chat;

/**
 * Class Session_Manager
 *
 * Manages chat sessions, versions, and requirements.
 */
class Session_Manager {

	/**
	 * Table name for sessions.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Versions table name.
	 *
	 * @var string
	 */
	private $versions_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'ace_chat_sessions';
		$this->versions_table = $wpdb->prefix . 'ace_chat_versions';
	}

	/**
	 * Create a new session.
	 *
	 * @param array $args Session arguments.
	 * @return string Session ID.
	 */
	public function create( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'topic'        => '',
			'model'        => '',
			'keywords'     => '',
			'professional' => false,
			'user_id'      => get_current_user_id(),
		);
		$args = wp_parse_args( $args, $defaults );

		$session_id = wp_generate_uuid4();

		$meta_data = array(
			'topic'        => $args['topic'],
			'model'        => $args['model'],
			'keywords'     => $args['keywords'],
			'professional' => (bool) $args['professional'],
			'requirements' => array(
				'topic'  => $args['topic'],
				'tone'   => 'professional',
				'length' => 'medium',
				'blocks' => array(),
				'notes'  => array(),
			),
		);

		$wpdb->insert(
			$this->table_name,
			array(
				'session_id'      => $session_id,
				'user_id'         => $args['user_id'],
				'title'           => $this->generate_title( $args['topic'] ),
				'current_content' => '',
				'conversation'    => wp_json_encode( array() ),
				'meta_data'       => wp_json_encode( $meta_data ),
				'status'          => 'active',
				'pinned'          => 0,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $session_id;
	}

	/**
	 * Get a session by ID.
	 *
	 * @param string $session_id Session ID.
	 * @return object|null Session object or null.
	 */
	public function get( $session_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE session_id = %s",
				$session_id
			)
		);
	}

	/**
	 * Update a session.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $data       Data to update.
	 * @return bool Success.
	 */
	public function update( $session_id, $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );

		$format = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = wp_json_encode( $value );
			}
			$format[] = is_int( $value ) ? '%d' : '%s';
		}

		return $wpdb->update(
			$this->table_name,
			$data,
			array( 'session_id' => $session_id ),
			$format,
			array( '%s' )
		) !== false;
	}

	/**
	 * Delete a session.
	 *
	 * @param string $session_id Session ID.
	 * @return bool Success.
	 */
	public function delete( $session_id ) {
		global $wpdb;

		// Delete versions first.
		$wpdb->delete(
			$this->versions_table,
			array( 'session_id' => $session_id ),
			array( '%s' )
		);

		return $wpdb->delete(
			$this->table_name,
			array( 'session_id' => $session_id ),
			array( '%s' )
		) !== false;
	}

	/**
	 * Get sessions for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Query arguments.
	 * @return array Sessions.
	 */
	public function get_user_sessions( $user_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status' => 'active',
			'limit'  => 50,
			'offset' => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} 
				WHERE user_id = %d AND status = %s 
				ORDER BY pinned DESC, updated_at DESC 
				LIMIT %d OFFSET %d",
				$user_id,
				$args['status'],
				$args['limit'],
				$args['offset']
			)
		);
	}

	/**
	 * Get session requirements.
	 *
	 * @param object|array $session Session object or data array.
	 * @return array Requirements.
	 */
	public function get_requirements( $session ) {
		$meta = is_object( $session ) 
			? json_decode( $session->meta_data, true ) 
			: ( $session['meta_data'] ?? array() );

		return $meta['requirements'] ?? array(
			'topic'  => '',
			'tone'   => 'professional',
			'length' => 'medium',
			'blocks' => array(),
			'notes'  => array(),
		);
	}

	/**
	 * Update session requirements.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $updates    Requirement updates.
	 * @return bool Success.
	 */
	public function update_requirements( $session_id, $updates ) {
		$session = $this->get( $session_id );
		if ( ! $session ) {
			return false;
		}

		$meta = json_decode( $session->meta_data, true ) ?: array();
		$requirements = $meta['requirements'] ?? array();

		// Merge updates.
		foreach ( $updates as $key => $value ) {
			if ( $key === 'blocks' || $key === 'notes' ) {
				// Append to arrays.
				$requirements[ $key ] = array_unique(
					array_merge( $requirements[ $key ] ?? array(), (array) $value )
				);
			} else {
				$requirements[ $key ] = $value;
			}
		}

		$meta['requirements'] = $requirements;

		return $this->update( $session_id, array( 'meta_data' => $meta ) );
	}

	/**
	 * Add a message to session conversation.
	 *
	 * @param string $session_id Session ID.
	 * @param string $role       Role: 'user' or 'assistant'.
	 * @param string $content    Message content.
	 * @param array  $extra      Extra data.
	 * @return bool Success.
	 */
	public function add_message( $session_id, $role, $content, $extra = array() ) {
		$session = $this->get( $session_id );
		if ( ! $session ) {
			return false;
		}

		$conversation = json_decode( $session->conversation, true ) ?: array();
		$conversation[] = array_merge(
			array(
				'role'      => $role,
				'content'   => $content,
				'timestamp' => current_time( 'mysql' ),
			),
			$extra
		);

		return $this->update( $session_id, array(
			'conversation' => $conversation,
		) );
	}

	/**
	 * Save a content version.
	 *
	 * @param string $session_id Session ID.
	 * @param string $content    Content to save.
	 * @return int|false Version ID or false.
	 */
	public function save_version( $session_id, $content ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->versions_table,
			array(
				'session_id' => $session_id,
				'content'    => $content,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get versions for a session.
	 *
	 * @param string $session_id Session ID.
	 * @param int    $limit      Max versions.
	 * @return array Versions.
	 */
	public function get_versions( $session_id, $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->versions_table} 
				WHERE session_id = %s 
				ORDER BY created_at DESC 
				LIMIT %d",
				$session_id,
				$limit
			)
		);
	}

	/**
	 * Generate a title from topic.
	 *
	 * @param string $topic Topic.
	 * @return string Title.
	 */
	private function generate_title( $topic ) {
		if ( empty( $topic ) ) {
			return __( 'New Chat', 'ai-content-engine' );
		}

		$title = wp_trim_words( $topic, 6, '...' );
		return ucfirst( $title );
	}

	/**
	 * Update session title based on conversation.
	 *
	 * @param string $session_id  Session ID.
	 * @param string $old_title   Current title.
	 * @param array  $conversation Conversation array.
	 * @return string Updated title.
	 */
	public function maybe_update_title( $session_id, $old_title, $conversation ) {
		if ( $old_title !== __( 'New Chat', 'ai-content-engine' ) ) {
			return $old_title;
		}

		// Find first user message.
		foreach ( $conversation as $msg ) {
			if ( $msg['role'] === 'user' && ! empty( $msg['content'] ) ) {
				return wp_trim_words( $msg['content'], 6, '...' );
			}
		}

		return $old_title;
	}
}

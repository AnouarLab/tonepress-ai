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
 * Onboarding Wizard for AI Content Engine
 *
 * Guides new users through initial setup with a 7-step wizard.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Onboarding Wizard Class
 */
class Onboarding_Wizard {
	/**
	 * Wizard steps
	 *
	 * @var array
	 */
	private $steps = array(
		'welcome'      => 'Welcome',
		'provider'     => 'AI Provider Setup',
		'company'      => 'Company Profile',
		'preferences'  => 'Content Preferences',
		'first_article' => 'First Article',
		'templates'    => 'Templates',
		'complete'     => 'Complete',
	);

	/**
	 * Current step
	 *
	 * @var string
	 */
	private $current_step = '';

	/**
	 * Initialize the wizard
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_wizard' ) );
		add_action( 'admin_menu', array( $this, 'register_wizard_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// AJAX handlers for wizard steps.
		add_action( 'wp_ajax_ace_wizard_save_step', array( $this, 'ajax_save_step' ) );
		add_action( 'wp_ajax_ace_wizard_test_api', array( $this, 'ajax_test_api' ) );
		add_action( 'wp_ajax_ace_wizard_generate_sample', array( $this, 'ajax_generate_sample' ) );
	}

	/**
	 * Maybe redirect to wizard on first activation
	 */
	public function maybe_redirect_to_wizard() {
		// Check if we should redirect.
		if ( ! get_transient( 'ace_activation_redirect' ) ) {
			return;
		}

		// Delete the transient.
		delete_transient( 'ace_activation_redirect' );

		// Check if wizard already completed.
		if ( get_option( 'ace_wizard_completed' ) ) {
			return;
		}

		// Don't redirect if doing bulk activation.
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Redirect to wizard.
		wp_safe_redirect( admin_url( 'admin.php?page=ace-wizard' ) );
		exit;
	}

	/**
	 * Register wizard admin page (hidden from menu)
	 */
	public function register_wizard_page() {
		add_submenu_page(
			null, // No parent = hidden from menu.
			__( 'AI Content Engine - Setup Wizard', 'ai-content-engine' ),
			__( 'Setup Wizard', 'ai-content-engine' ),
			'manage_options',
			'ace-wizard',
			array( $this, 'render_wizard' )
		);
	}

	/**
	 * Enqueue wizard assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'admin_page_ace-wizard' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ace-wizard',
			ACE_PLUGIN_URL . 'assets/css/wizard.css',
			array(),
			ACE_VERSION
		);

		wp_enqueue_script(
			'ace-wizard',
			ACE_PLUGIN_URL . 'assets/js/wizard.js',
			array( 'jquery' ),
			ACE_VERSION,
			true
		);

		wp_localize_script(
			'ace-wizard',
			'aceWizard',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'ace_wizard_nonce' ),
				'steps'       => $this->steps,
				'current_step' => $this->get_current_step(),
			)
		);
	}

	/**
	 * Get current wizard step
	 *
	 * @return string
	 */
	private function get_current_step() {
		if ( ! empty( $this->current_step ) ) {
			return $this->current_step;
		}

		// Get from query string or saved state.
		$saved_step = get_option( 'ace_wizard_current_step', 'welcome' );
		$query_step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->current_step = ! empty( $query_step ) && isset( $this->steps[ $query_step ] ) ? $query_step : $saved_step;

		return $this->current_step;
	}

	/**
	 * Render wizard page
	 */
	public function render_wizard() {
		$current_step = $this->get_current_step();
		$step_number  = array_search( $current_step, array_keys( $this->steps ), true ) + 1;
		$total_steps  = count( $this->steps );

		include ACE_PLUGIN_DIR . 'templates/wizard/wizard-layout.php';
	}

	/**
	 * AJAX: Save wizard step data
	 */
	public function ajax_save_step() {
		check_ajax_referer( 'ace_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$step = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : '';
		$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $step ) || ! isset( $this->steps[ $step ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid step.', 'ai-content-engine' ) ) );
		}

		// Save step data.
		$this->save_step_data( $step, $data );

		// Move to next step.
		$next_step = $this->get_next_step( $step );

		// Update current step.
		update_option( 'ace_wizard_current_step', $next_step );

		// If completed, mark wizard as done.
		if ( 'complete' === $next_step ) {
			update_option( 'ace_wizard_completed', time() );
		}

		wp_send_json_success(
			array(
				'next_step' => $next_step,
				'redirect'  => admin_url( 'admin.php?page=ace-wizard&step=' . $next_step ),
			)
		);
	}

	/**
	 * Save step data
	 *
	 * @param string $step Step name.
	 * @param array  $data Step data.
	 */
	private function save_step_data( $step, $data ) {
		switch ( $step ) {
			case 'provider':
				// Save AI provider settings.
				if ( isset( $data['provider'] ) ) {
					update_option( 'ace_ai_provider', sanitize_text_field( $data['provider'] ) );
				}
				if ( isset( $data['api_key'] ) ) {
					$provider = sanitize_text_field( $data['provider'] );
					update_option( 'ace_' . $provider . '_api_key', sanitize_text_field( $data['api_key'] ) );
				}
				break;

			case 'company':
				// Save company profile.
				$company_data = array(
					'name'         => isset( $data['company_name'] ) ? sanitize_text_field( $data['company_name'] ) : '',
					'industry'     => isset( $data['industry'] ) ? sanitize_text_field( $data['industry'] ) : '',
					'audience'     => isset( $data['target_audience'] ) ? sanitize_textarea_field( $data['target_audience'] ) : '',
					'brand_voice'  => isset( $data['brand_voice'] ) ? sanitize_textarea_field( $data['brand_voice'] ) : '',
				);
				update_option( 'ace_company_context', $company_data );
				break;

			case 'preferences':
				// Save content preferences.
				if ( isset( $data['default_length'] ) ) {
					update_option( 'ace_default_length', sanitize_text_field( $data['default_length'] ) );
				}
				if ( isset( $data['auto_featured_image'] ) ) {
					update_option( 'ace_auto_featured_image', (bool) $data['auto_featured_image'] );
				}
				if ( isset( $data['auto_seo'] ) ) {
					update_option( 'ace_auto_seo', (bool) $data['auto_seo'] );
				}
				break;

			case 'templates':
				// Save selected starter templates.
				if ( isset( $data['templates'] ) && is_array( $data['templates'] ) ) {
					update_option( 'ace_starter_templates', array_map( 'sanitize_key', $data['templates'] ) );
				}
				break;
		}

		// Save raw wizard data for reference.
		$wizard_data = get_option( 'ace_wizard_data', array() );
		$wizard_data[ $step ] = $data;
		update_option( 'ace_wizard_data', $wizard_data );
	}

	/**
	 * Get next step
	 *
	 * @param string $current_step Current step.
	 * @return string
	 */
	private function get_next_step( $current_step ) {
		$steps = array_keys( $this->steps );
		$index = array_search( $current_step, $steps, true );

		if ( false === $index || $index >= count( $steps ) - 1 ) {
			return 'complete';
		}

		return $steps[ $index + 1 ];
	}

	/**
	 * AJAX: Test API connection
	 */
	public function ajax_test_api() {
		check_ajax_referer( 'ace_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

		if ( empty( $provider ) || empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Provider and API key are required.', 'ai-content-engine' ) ) );
		}

		// Test the API connection.
		try {
			$factory  = new Provider_Factory();
			$instance = $factory->create( $provider, $api_key );

			// Try a simple test request.
			$result = $instance->generate_content(
				'You are a helpful assistant.',
				'Say "Hello" in one word.',
				array( 'max_tokens' => 10 )
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error(
					array(
						'message' => $result->get_error_message(),
					)
				);
			}

			wp_send_json_success(
				array(
					'message' => __( 'Connection successful!', 'ai-content-engine' ),
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * AJAX: Generate sample article
	 */
	public function ajax_generate_sample() {
		check_ajax_referer( 'ace_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$topic = isset( $_POST['topic'] ) ? sanitize_text_field( $_POST['topic'] ) : '';

		if ( empty( $topic ) ) {
			wp_send_json_error( array( 'message' => __( 'Topic is required.', 'ai-content-engine' ) ) );
		}

		// Generate article.
		$generator = new Article_Generator();
		$result    = $generator->generate_article(
			$topic,
			array(
				'length'      => 'medium',
				'tone'        => 'professional',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);
		}

		// Get post data for preview.
		$post = get_post( $result );

		wp_send_json_success(
			array(
				'post_id' => $result,
				'title'   => $post->post_title,
				'excerpt' => wp_trim_words( $post->post_content, 50 ),
				'edit_url' => get_edit_post_link( $result, 'raw' ),
			)
		);
	}
}

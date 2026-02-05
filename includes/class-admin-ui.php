<?php
/**
 * Admin UI class for settings page and AJAX handlers.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Admin_UI
 *
 * Handles the admin interface, settings page, and AJAX article generation.
 */
class Admin_UI {

	/**
	 * Article generator instance.
	 *
	 * @var Article_Generator
	 */
	private $generator;

	/**
	 * Initialize the admin UI.
	 */
	public function init() {
		$this->generator = new Article_Generator();

		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Enqueue block editor assets.
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Enqueue frontend content styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_content_styles' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ace_generate_article', array( $this, 'ajax_generate_article' ) );
		add_action( 'wp_ajax_ace_test_api_key', array( $this, 'ajax_test_api_key' ) );
		add_action( 'wp_ajax_ace_bulk_upload', array( $this, 'ajax_bulk_upload' ) );
		add_action( 'wp_ajax_ace_bulk_pause', array( $this, 'ajax_bulk_pause' ) );
		add_action( 'wp_ajax_ace_bulk_resume', array( $this, 'ajax_bulk_resume' ) );
		add_action( 'wp_ajax_ace_bulk_delete', array( $this, 'ajax_bulk_delete' ) );
		
		// Chat Builder AJAX handlers.
		add_action( 'wp_ajax_ace_chat_start', array( $this, 'ajax_chat_start' ) );
		add_action( 'wp_ajax_ace_chat_message', array( $this, 'ajax_chat_message' ) );
		add_action( 'wp_ajax_ace_chat_sessions', array( $this, 'ajax_chat_sessions' ) );
		add_action( 'wp_ajax_ace_chat_load', array( $this, 'ajax_chat_load' ) );
		add_action( 'wp_ajax_ace_chat_save_draft', array( $this, 'ajax_chat_save_draft' ) );
		add_action( 'wp_ajax_ace_chat_publish', array( $this, 'ajax_chat_publish' ) );
		add_action( 'wp_ajax_ace_chat_rename', array( $this, 'ajax_chat_rename' ) );
		add_action( 'wp_ajax_ace_chat_delete', array( $this, 'ajax_chat_delete' ) );
		add_action( 'wp_ajax_ace_chat_duplicate', array( $this, 'ajax_chat_duplicate' ) );
		add_action( 'wp_ajax_ace_chat_pin', array( $this, 'ajax_chat_pin' ) );
		add_action( 'wp_ajax_ace_chat_versions', array( $this, 'ajax_chat_versions' ) );
		add_action( 'wp_ajax_ace_chat_restore', array( $this, 'ajax_chat_restore' ) );
		add_action( 'wp_ajax_ace_chat_preview', array( $this, 'ajax_chat_preview' ) );
		
		// Direct actions (non-AJAX).
		add_action( 'admin_init', array( $this, 'handle_direct_actions' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'AI Content Engine', 'ai-content-engine' ),
			__( 'AI Content Engine', 'ai-content-engine' ),
			'publish_posts',
			'ai-content-engine',
			array( $this, 'render_admin_page' )
		);
		
		// Chat Builder removed - not needed for blog generation workflow.
		/*
		add_submenu_page(
			'tools.php',
			__( 'AI Chat Builder', 'ai-content-engine' ),
			__( 'AI Chat Builder', 'ai-content-engine' ),
			'publish_posts',
			'ai-chat-builder',
			array( $this, 'render_chat_builder_page' )
		);
		*/
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// API Settings.
		register_setting(
			'ace_settings',
			'ace_openai_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			)
		);

		register_setting( 'ace_settings', 'ace_openai_model', array( 'type' => 'string', 'default' => 'gpt-3.5-turbo' ) );
		register_setting( 'ace_settings', 'ace_default_length', array( 'type' => 'string', 'default' => 'medium' ) );
		register_setting( 'ace_settings', 'ace_default_tone', array( 'type' => 'string', 'default' => 'professional' ) );
		register_setting( 'ace_settings', 'ace_enable_cache', array( 'type' => 'boolean', 'default' => true ) );
		register_setting( 'ace_settings', 'ace_enable_rate_limit', array( 'type' => 'boolean', 'default' => true ) );
		register_setting( 'ace_settings', 'ace_enable_content_styles', array( 'type' => 'boolean', 'default' => true ) );
		register_setting(
			'ace_settings',
			'ace_openai_enabled_models',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_openai_enabled_models' ),
				'default'           => array(),
			)
		);
		register_setting(
			'ace_settings',
			'ace_claude_enabled_models',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_claude_enabled_models' ),
				'default'           => array(),
			)
		);
		register_setting(
			'ace_settings',
			'ace_gemini_enabled_models',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_gemini_enabled_models' ),
				'default'           => array(),
			)
		);

		// Settings sections.
		add_settings_section(
			'ace_api_settings',
			__( 'API Configuration', 'ai-content-engine' ),
			array( $this, 'render_api_settings_section' ),
			'ai-content-engine'
		);

		add_settings_section(
			'ace_default_settings',
			__( 'Default Article Settings', 'ai-content-engine' ),
			array( $this, 'render_default_settings_section' ),
			'ai-content-engine'
		);

		add_settings_section(
			'ace_performance_settings',
			__( 'Performance & Security', 'ai-content-engine' ),
			array( $this, 'render_performance_settings_section' ),
			'ai-content-engine'
		);

		add_settings_section(
			'ace_chat_settings',
			__( 'Chat Builder Models', 'ai-content-engine' ),
			array( $this, 'render_chat_settings_section' ),
			'ai-content-engine'
		);

		// API fields.
		add_settings_field(
			'ace_ai_provider',
			__( 'AI Provider', 'ai-content-engine' ),
			array( $this, 'render_provider_field' ),
			'ai-content-engine',
			'ace_api_settings'
		);

		add_settings_field(
			'ace_openai_api_key',
			__( 'OpenAI API Key', 'ai-content-engine' ),
			array( $this, 'render_api_key_field' ),
			'ai-content-engine',
			'ace_api_settings'
		);

		add_settings_field(
			'ace_openai_model',
			__( 'OpenAI Model', 'ai-content-engine' ),
			array( $this, 'render_model_field' ),
			'ai-content-engine',
			'ace_api_settings'
		);

		add_settings_field(
			'ace_claude_api_key',
			__( 'Claude API Key', 'ai-content-engine' ),
			array( $this, 'render_claude_key_field' ),
			'ai-content-engine',
			'ace_api_settings'
		);

		add_settings_field(
			'ace_gemini_api_key',
			__( 'Gemini API Key', 'ai-content-engine' ),
			array( $this, 'render_gemini_key_field' ),
			'ai-content-engine',
			'ace_api_settings'
		);

		// Default settings fields.
		add_settings_field(
			'ace_default_length',
			__( 'Default Article Length', 'ai-content-engine' ),
			array( $this, 'render_length_field' ),
			'ai-content-engine',
			'ace_default_settings'
		);

		add_settings_field(
			'ace_default_tone',
			__( 'Default Writing Tone', 'ai-content-engine' ),
			array( $this, 'render_tone_field' ),
			'ai-content-engine',
			'ace_default_settings'
		);

		// Performance fields.
		add_settings_field(
			'ace_enable_cache',
			__( 'Enable Caching', 'ai-content-engine' ),
			array( $this, 'render_cache_field' ),
			'ai-content-engine',
			'ace_performance_settings'
		);

		add_settings_field(
			'ace_enable_rate_limit',
			__( 'Enable Rate Limiting', 'ai-content-engine' ),
			array( $this, 'render_rate_limit_field' ),
			'ai-content-engine',
			'ace_performance_settings'
		);

		add_settings_field(
			'ace_enable_content_styles',
			__( 'Content Block Styling', 'ai-content-engine' ),
			array( $this, 'render_content_styles_field' ),
			'ai-content-engine',
			'ace_performance_settings'
		);

		add_settings_field(
			'ace_openai_enabled_models',
			__( 'OpenAI Chat Models', 'ai-content-engine' ),
			array( $this, 'render_openai_enabled_models_field' ),
			'ai-content-engine',
			'ace_chat_settings'
		);

		add_settings_field(
			'ace_claude_enabled_models',
			__( 'Claude Chat Models', 'ai-content-engine' ),
			array( $this, 'render_claude_enabled_models_field' ),
			'ai-content-engine',
			'ace_chat_settings'
		);

		add_settings_field(
			'ace_gemini_enabled_models',
			__( 'Gemini Chat Models', 'ai-content-engine' ),
			array( $this, 'render_gemini_enabled_models_field' ),
			'ai-content-engine',
			'ace_chat_settings'
		);
	}

	/**
	 * Sanitize and encrypt API key.
	 *
	 * @param string $value API key value.
	 * @return string Encrypted API key.
	 */
	public function sanitize_api_key( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		// Only encrypt if it's not already encrypted (check format).
		if ( strpos( $value, 'sk-' ) === 0 ) {
			// It's a raw API key, encrypt it.
			return Security::encrypt_api_key( $value );
		}

		// Already encrypted, return as is.
		return $value;
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-content-engine' ) );
		}

		// Load the template.
		require_once ACE_PLUGIN_DIR . 'templates/admin-page.php';
	}

	/**
	 * Render API settings section.
	 */
	public function render_api_settings_section() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API credentials. Get your API key from', 'ai-content-engine' ) . ' <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.</p>';
	}

	/**
	 * Render default settings section.
	 */
	public function render_default_settings_section() {
		echo '<p>' . esc_html__( 'Set default values for article generation. These can be overridden when generating individual articles.', 'ai-content-engine' ) . '</p>';
	}

	/**
	 * Render performance settings section.
	 */
	public function render_performance_settings_section() {
		echo '<p>' . esc_html__( 'Configure caching and rate limiting to optimize API usage and costs.', 'ai-content-engine' ) . '</p>';
	}

	/**
	 * Render chat settings section.
	 */
	public function render_chat_settings_section() {
		echo '<p>' . esc_html__( 'Choose which models are available in the Chat Builder.', 'ai-content-engine' ) . '</p>';
	}

	/**
	 * Sanitize enabled OpenAI models.
	 *
	 * @param array $value Selected models.
	 * @return array Sanitized models.
	 */
	public function sanitize_openai_enabled_models( $value ) {
		return $this->sanitize_enabled_models( $value, 'openai' );
	}

	/**
	 * Sanitize enabled Claude models.
	 *
	 * @param array $value Selected models.
	 * @return array Sanitized models.
	 */
	public function sanitize_claude_enabled_models( $value ) {
		return $this->sanitize_enabled_models( $value, 'claude' );
	}

	/**
	 * Sanitize enabled Gemini models.
	 *
	 * @param array $value Selected models.
	 * @return array Sanitized models.
	 */
	public function sanitize_gemini_enabled_models( $value ) {
		return $this->sanitize_enabled_models( $value, 'gemini' );
	}

	/**
	 * Sanitize enabled model list against provider models.
	 *
	 * @param array  $value Selected models.
	 * @param string $provider Provider ID.
	 * @return array Sanitized models.
	 */
	private function sanitize_enabled_models( $value, $provider ) {
		$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : array();
		$provider_instance = Provider_Factory::get( $provider );
		if ( is_wp_error( $provider_instance ) ) {
			return array();
		}

		$available = array_keys( $provider_instance->get_available_models() );
		return array_values( array_intersect( $available, $value ) );
	}

	/**
	 * Render OpenAI enabled models field.
	 */
	public function render_openai_enabled_models_field() {
		$this->render_enabled_models_field( 'openai', 'ace_openai_enabled_models' );
	}

	/**
	 * Render Claude enabled models field.
	 */
	public function render_claude_enabled_models_field() {
		$this->render_enabled_models_field( 'claude', 'ace_claude_enabled_models' );
	}

	/**
	 * Render Gemini enabled models field.
	 */
	public function render_gemini_enabled_models_field() {
		$this->render_enabled_models_field( 'gemini', 'ace_gemini_enabled_models' );
	}

	/**
	 * Render enabled models checkbox list.
	 *
	 * @param string $provider Provider ID.
	 * @param string $option Option name.
	 */
	private function render_enabled_models_field( $provider, $option ) {
		$provider_instance = Provider_Factory::get( $provider );
		if ( is_wp_error( $provider_instance ) ) {
			echo '<p class="description">' . esc_html__( 'Provider not available.', 'ai-content-engine' ) . '</p>';
			return;
		}

		$enabled = get_option( $option, array() );
		$models  = $provider_instance->get_available_models();

		echo '<div class="ace-model-grid">';
		foreach ( $models as $model_id => $label ) {
			$checked = in_array( $model_id, $enabled, true );
			printf(
				'<label class="ace-model-item %5$s"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s><span class="ace-model-label">%4$s</span></label>',
				esc_attr( $option ),
				esc_attr( $model_id ),
				checked( $checked, true, false ),
				esc_html( $label ),
				$checked ? 'is-checked' : ''
			);
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Select models to show in Chat Builder. If none selected, all are available.', 'ai-content-engine' ) . '</p>';
	}

	/**
	 * Render API key field.
	 */
	public function render_api_key_field() {
		$encrypted_key = get_option( 'ace_openai_api_key', '' );
		$masked_key    = ! empty( $encrypted_key ) ? 'sk-************************************' : '';

		echo '<input type="password" name="ace_openai_api_key" value="' . esc_attr( $masked_key ) . '" class="regular-text" placeholder="sk-..." />';
		echo '<p class="description">' . esc_html__( 'Your API key is encrypted and stored securely.', 'ai-content-engine' ) . '</p>';
		echo '<button type="button" class="button" id="ace-test-api-key">' . esc_html__( 'Test API Key', 'ai-content-engine' ) . '</button>';
		echo '<span id="ace-api-test-result"></span>';
	}

	/**
	 * Render model field.
	 */
	public function render_model_field() {
		$model = get_option( 'ace_openai_model', 'gpt-3.5-turbo' );
		?>
		<select name="ace_openai_model">
			<option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo (Faster, Cheaper)</option>
			<option value="gpt-4" <?php selected( $model, 'gpt-4' ); ?>>GPT-4 (Higher Quality, More Expensive)</option>
			<option value="gpt-4-turbo" <?php selected( $model, 'gpt-4-turbo' ); ?>>GPT-4 Turbo (Balanced)</option>
		</select>
		<p class="description"><?php esc_html_e( 'Select the OpenAI model to use for generation.', 'ai-content-engine' ); ?></p>
		<?php
	}

	/**
	 * Render provider selector field.
	 */
	public function render_provider_field() {
		$provider = get_option( 'ace_ai_provider', 'openai' );
		?>
		<select name="ace_ai_provider" id="ace_ai_provider">
			<option value="openai" <?php selected( $provider, 'openai' ); ?>>OpenAI (GPT-3.5, GPT-4)</option>
			<option value="claude" <?php selected( $provider, 'claude' ); ?>>Anthropic Claude (Claude 3)</option>
			<option value="gemini" <?php selected( $provider, 'gemini' ); ?>>Google Gemini</option>
		</select>
		<p class="description"><?php esc_html_e( 'Select the AI provider to use for content generation.', 'ai-content-engine' ); ?></p>
		<?php
	}

	/**
	 * Render Claude API key field.
	 */
	public function render_claude_key_field() {
		$api_key = get_option( 'ace_claude_api_key', '' );
		$decrypted = ! empty( $api_key ) ? Security::decrypt_api_key( $api_key ) : '';
		$masked = ! empty( $decrypted ) ? substr( $decrypted, 0, 10 ) . '...' : '';
		?>
		<input type="password" 
			name="ace_claude_api_key" 
			id="ace_claude_api_key" 
			class="regular-text" 
			value=""
			placeholder="<?php echo esc_attr( $masked ?: 'sk-ant-...' ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Anthropic Claude API key.', 'ai-content-engine' ); ?>
			<a href="https://console.anthropic.com/" target="_blank"><?php esc_html_e( 'Get API Key', 'ai-content-engine' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Render Gemini API key field.
	 */
	public function render_gemini_key_field() {
		$api_key = get_option( 'ace_gemini_api_key', '' );
		$decrypted = ! empty( $api_key ) ? Security::decrypt_api_key( $api_key ) : '';
		$masked = ! empty( $decrypted ) ? substr( $decrypted, 0, 10 ) . '...' : '';
		?>
		<input type="password" 
			name="ace_gemini_api_key" 
			id="ace_gemini_api_key" 
			class="regular-text" 
			value=""
			placeholder="<?php echo esc_attr( $masked ?: 'AIza...' ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter your Google Gemini API key.', 'ai-content-engine' ); ?>
			<a href="https://makersuite.google.com/app/apikey" target="_blank"><?php esc_html_e( 'Get API Key', 'ai-content-engine' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Render length field.
	 */
	public function render_length_field() {
		$length = get_option( 'ace_default_length', 'medium' );
		?>
		<select name="ace_default_length">
			<option value="short" <?php selected( $length, 'short' ); ?>><?php esc_html_e( 'Short (800-1200 words)', 'ai-content-engine' ); ?></option>
			<option value="medium" <?php selected( $length, 'medium' ); ?>><?php esc_html_e( 'Medium (1200-1800 words)', 'ai-content-engine' ); ?></option>
			<option value="long" <?php selected( $length, 'long' ); ?>><?php esc_html_e( 'Long (1800-2500+ words)', 'ai-content-engine' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render tone field.
	 */
	public function render_tone_field() {
		$tone = get_option( 'ace_default_tone', 'professional' );
		?>
		<select name="ace_default_tone">
			<option value="professional" <?php selected( $tone, 'professional' ); ?>><?php esc_html_e( 'Professional', 'ai-content-engine' ); ?></option>
			<option value="conversational" <?php selected( $tone, 'conversational' ); ?>><?php esc_html_e( 'Conversational', 'ai-content-engine' ); ?></option>
			<option value="authoritative" <?php selected( $tone, 'authoritative' ); ?>><?php esc_html_e( 'Authoritative', 'ai-content-engine' ); ?></option>
			<option value="friendly" <?php selected( $tone, 'friendly' ); ?>><?php esc_html_e( 'Friendly', 'ai-content-engine' ); ?></option>
			<option value="academic" <?php selected( $tone, 'academic' ); ?>><?php esc_html_e( 'Academic', 'ai-content-engine' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render cache field.
	 */
	public function render_cache_field() {
		$enabled = get_option( 'ace_enable_cache', '1' );
		?>
		<label class="ace-toggle">
			<input type="checkbox" name="ace_enable_cache" value="1" <?php checked( $enabled, '1' ); ?> />
			<span class="ace-toggle-slider"></span>
			<span class="ace-toggle-label"><?php esc_html_e( 'Cache generated articles to reduce API costs', 'ai-content-engine' ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render rate limit field.
	 */
	public function render_rate_limit_field() {
		$enabled = get_option( 'ace_enable_rate_limit', '1' );
		?>
		<label class="ace-toggle">
			<input type="checkbox" name="ace_enable_rate_limit" value="1" <?php checked( $enabled, '1' ); ?> />
			<span class="ace-toggle-slider"></span>
			<span class="ace-toggle-label">
				<?php
				printf(
					/* translators: %d: Maximum requests per hour */
					esc_html__( 'Limit to %d generations per hour per user', 'ai-content-engine' ),
					ACE_MAX_REQUESTS_PER_HOUR
				);
				?>
			</span>
		</label>
		<?php
	}

	/**
	 * Render content styles field.
	 */
	public function render_content_styles_field() {
		$enabled = get_option( 'ace_enable_content_styles', '1' );
		?>
		<label class="ace-toggle">
			<input type="checkbox" name="ace_enable_content_styles" value="1" <?php checked( $enabled, '1' ); ?> />
			<span class="ace-toggle-slider"></span>
			<span class="ace-toggle-label"><?php esc_html_e( 'Add styling for FAQs, callouts, pros/cons, and other rich content blocks', 'ai-content-engine' ); ?></span>
		</label>
		<p class="description"><?php esc_html_e( 'Disable if your theme already provides styling for these elements.', 'ai-content-engine' ); ?></p>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_ai-content-engine' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ace-admin',
			ACE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ACE_VERSION
		);

		wp_enqueue_script(
			'ace-admin',
			ACE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ACE_VERSION,
			true
		);

		// Bulk tab script.
		wp_enqueue_script(
			'ace-bulk',
			ACE_PLUGIN_URL . 'assets/js/bulk.js',
			array( 'jquery', 'ace-admin' ),
			ACE_VERSION,
			true
		);

		wp_localize_script(
			'ace-admin',
			'aceAdmin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => Security::create_nonce( 'ace_ajax' ),
				'templates' => self::get_template_descriptions(),
				'model'    => get_option( 'ace_openai_model', 'gpt-3.5-turbo' ),
				'strings'  => array(
					'generating'    => __( 'Generating article... This may take a minute.', 'ai-content-engine' ),
					'success'       => __( 'Article generated successfully!', 'ai-content-engine' ),
					'error'         => __( 'An error occurred:', 'ai-content-engine' ),
					'testing'       => __( 'Testing...', 'ai-content-engine' ),
					'test_success'  => __( '✓ API key is valid', 'ai-content-engine' ),
					'test_failed'   => __( '✗ API key test failed', 'ai-content-engine' ),
					'empty_topic'   => __( 'Please enter an article topic.', 'ai-content-engine' ),
				),
			)
		);
	}

	/**
	 * Get template descriptions for JavaScript.
	 *
	 * @return array Template descriptions.
	 */
	private static function get_template_descriptions() {
		$templates = Template_Manager::get_templates();
		$descriptions = array();
		
		foreach ( $templates as $template_id => $template ) {
			$descriptions[ $template_id ] = $template['description'];
		}
		
		return $descriptions;
	}

	/**
	 * Enqueue frontend content styles for AI-generated content blocks.
	 * 
	 * Only loads on single posts/pages that contain ACE content classes.
	 */
	public function enqueue_frontend_content_styles() {
		// Check if styling is enabled in settings.
		$enabled = get_option( 'ace_enable_content_styles', '1' );
		if ( ! $enabled ) {
			return;
		}

		// Only load on singular content.
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		// Check if the content contains ACE classes or tables.
		$ace_classes = array( 'ace-faq', 'ace-callout', 'ace-takeaways', 'ace-toc', 'ace-proscons', 'ace-stat', 'ace-author', 'ace-image-placeholder', 'ace-comparison-table', '<table' );
		$has_ace_content = false;

		foreach ( $ace_classes as $class ) {
			if ( strpos( $post->post_content, $class ) !== false ) {
				$has_ace_content = true;
				break;
			}
		}

		// Also check if it's an ACE-generated post.
		if ( ! $has_ace_content ) {
			$is_ace_generated = get_post_meta( $post->ID, '_ace_generated', true );
			$ace_session = get_post_meta( $post->ID, '_ace_chat_session', true );
			if ( $is_ace_generated || $ace_session ) {
				$has_ace_content = true;
			}
		}

		if ( $has_ace_content ) {
			wp_enqueue_style(
				'ace-frontend',
				ACE_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				ACE_VERSION
			);
		}
	}

	/**
	 * AJAX handler for article generation.
	 */
	public function ajax_generate_article() {
		// Verify nonce.
		check_ajax_referer( 'ace_ajax', 'nonce' );

		// Check permissions.
		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to generate articles.', 'ai-content-engine' ) ) );
		}

		// Get POST data.
		$topic   = isset( $_POST['topic'] ) ? sanitize_textarea_field( wp_unslash( $_POST['topic'] ) ) : '';
		$options = array(
			// Basic options
			'length'         => isset( $_POST['length'] ) ? sanitize_text_field( wp_unslash( $_POST['length'] ) ) : 'medium',
			'tone'           => isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'professional',
			'language'       => isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English',
			'keywords'       => isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '',
			'include_tables' => ! empty( $_POST['include_tables'] ),
			'include_charts' => ! empty( $_POST['include_charts'] ),
			'generate_featured_image' => ! empty( $_POST['generate_featured_image'] ),
			'generate_inline_images'  => ! empty( $_POST['generate_inline_images'] ),
			
			// Post creation
			'post_status'    => isset( $_POST['post_status'] ) ? sanitize_text_field( wp_unslash( $_POST['post_status'] ) ) : 'draft',
			'post_date'      => isset( $_POST['post_date'] ) ? sanitize_text_field( wp_unslash( $_POST['post_date'] ) ) : null,
			
			// Quick Win: Advanced options
			'word_count'           => isset( $_POST['word_count'] ) && ! empty( $_POST['word_count'] ) ? (int) $_POST['word_count'] : null,
			'temperature'          => isset( $_POST['temperature'] ) ? (float) $_POST['temperature'] : 0.7,
			'max_tokens'           => isset( $_POST['max_tokens'] ) ? (int) $_POST['max_tokens'] : 3000,
			'post_type'            => isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : 'post',
			'categories'           => isset( $_POST['categories'] ) ? array_map( 'intval', (array) $_POST['categories'] ) : array(),
			'auto_tags'            => ! empty( $_POST['auto_tags'] ),
			'custom_instructions'  => isset( $_POST['custom_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_instructions'] ) ) : '',
			'template_id'          => isset( $_POST['template_id'] ) ? sanitize_key( wp_unslash( $_POST['template_id'] ) ) : 'default',
		);

		// Generate the article.
		$result = $this->generator->generate_article( $topic, $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Get post data.
		$post = get_post( $result );

		// Get usage data.
		$usage          = get_post_meta( $result, '_ace_token_usage', true );
		$estimated_cost = get_post_meta( $result, '_ace_estimated_cost', true );

		wp_send_json_success(
			array(
				'post_id'        => $result,
				'post_title'     => $post->post_title,
				'edit_link'      => get_edit_post_link( $result, 'raw' ),
				'view_link'      => get_permalink( $result ),
				'tokens_used'    => $usage['total_tokens'] ?? 0,
				'estimated_cost' => $estimated_cost,
			)
		);
	}

	/**
	 * AJAX handler for API key testing.
	 */
	public function ajax_test_api_key() {
		// Verify nonce.
		check_ajax_referer( 'ace_ajax', 'nonce' );

		// Check permissions.
		if ( ! Security::can_manage_settings() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to test API keys.', 'ai-content-engine' ) ) );
		}

		$provider = Provider_Factory::get_active();
		
		if ( is_wp_error( $provider ) ) {
			wp_send_json_error( array( 'message' => $provider->get_error_message() ) );
		}
		
		$result = $provider->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'API key is valid and working!', 'ai-content-engine' ) ) );
	}

	/**
	 * Handle direct actions (CSV template download, results export).
	 */
	public function handle_direct_actions() {
		if ( ! isset( $_GET['page'] ) || 'ai-content-engine' !== $_GET['page'] || ! isset( $_GET['action'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		if ( 'download_template' === $action ) {
			$csv = Bulk_Generator::get_csv_template();
			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="ace-bulk-template.csv"' );
			echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		if ( 'export_results' === $action && isset( $_GET['queue'] ) ) {
			$queue_id = sanitize_text_field( wp_unslash( $_GET['queue'] ) );
			$generator = new Bulk_Generator();
			$csv = $generator->export_results( $queue_id );

			if ( is_wp_error( $csv ) ) {
				wp_die( esc_html( $csv->get_error_message() ) );
			}

			header( 'Content-Type: text/csv' );
			header( 'Content-Disposition: attachment; filename="ace-bulk-results-' . $queue_id . '.csv"' );
			echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	/**
	 * AJAX handler for bulk CSV upload.
	 */
	public function ajax_bulk_upload() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		if ( empty( $_FILES['csv_file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'ai-content-engine' ) ) );
		}

		$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Move uploaded file.
		$upload_dir = wp_upload_dir();
		$temp_file = $upload_dir['basedir'] . '/ace-bulk-' . time() . '.csv';
		
		if ( ! move_uploaded_file( $file['tmp_name'], $temp_file ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to upload file.', 'ai-content-engine' ) ) );
		}

		// Parse CSV.
		$generator = new Bulk_Generator();
		$result = $generator->parse_csv( $temp_file );

		// Clean up temp file.
		unlink( $temp_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'    => sprintf(
					/* translators: %d: Number of articles */
					__( 'Queue created successfully! Processing %d articles in the background.', 'ai-content-engine' ),
					$result['item_count']
				),
				'queue_id'   => $result['queue_id'],
				'item_count' => $result['item_count'],
			)
		);
	}

	/**
	 * AJAX handler for pausing a queue.
	 */
	public function ajax_bulk_pause() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$queue_id = isset( $_POST['queue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['queue_id'] ) ) : '';
		
		$generator = new Bulk_Generator();
		$success = $generator->pause_queue( $queue_id );

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Queue paused.', 'ai-content-engine' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to pause queue.', 'ai-content-engine' ) ) );
		}
	}

	/**
	 * AJAX handler for resuming a queue.
	 */
	public function ajax_bulk_resume() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$queue_id = isset( $_POST['queue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['queue_id'] ) ) : '';
		
		$generator = new Bulk_Generator();
		$success = $generator->resume_queue( $queue_id );

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Queue resumed.', 'ai-content-engine' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to resume queue.', 'ai-content-engine' ) ) );
		}
	}

	/**
	 * AJAX handler for deleting a queue.
	 */
	public function ajax_bulk_delete() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$queue_id = isset( $_POST['queue_id'] ) ? sanitize_text_field( wp_unslash( $_POST['queue_id'] ) ) : '';
		
		$generator = new Bulk_Generator();
		$success = $generator->delete_queue( $queue_id );

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Queue deleted.', 'ai-content-engine' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete queue.', 'ai-content-engine' ) ) );
		}
	}

	/**
	 * Render the Chat Builder admin page.
	 */
	public function render_chat_builder_page() {
		// Enqueue chat builder assets.
		wp_enqueue_style(
			'ace-chat-builder',
			ACE_PLUGIN_URL . 'assets/css/chat-builder.css',
			array(),
			ACE_VERSION
		);
		
		wp_enqueue_script(
			'ace-chat-builder',
			ACE_PLUGIN_URL . 'assets/js/chat-builder.js',
			array( 'jquery' ),
			ACE_VERSION,
			true
		);
		
		wp_localize_script(
			'ace-chat-builder',
			'aceChat',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ace_ajax' ),
			)
		);

		include ACE_PLUGIN_DIR . 'templates/chat-builder.php';
	}

	/**
	 * AJAX handler for starting a new chat session.
	 */
	public function ajax_chat_start() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$model        = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		$keywords     = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';
		$professional = ! empty( $_POST['professional'] );

		try {
			$chat_builder = new \Chat_Builder();
			$result = $chat_builder->start_session( $message, $model, $keywords, $professional );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$wp_time = current_time( 'mysql' );
			wp_send_json_success( array(
				'session_id' => $result['session_id'],
				'title'      => $result['title'],
				'message'    => $result['initial_message'] ?: "I'm ready to help you write an article! What would you like to create?",
				'content'    => $result['content'] ?? '',
				'word_count' => str_word_count( wp_strip_all_tags( $result['content'] ?? '' ) ),
				'updated_at' => $wp_time,
				'model'      => $model,
				'keywords'   => $keywords,
				'seo_meta'   => $result['seo_meta'] ?? array(),
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		} catch ( \Error $e ) {
			wp_send_json_error( array( 'message' => 'PHP Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler for sending a chat message.
	 */
	public function ajax_chat_message() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id   = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$message      = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$model        = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		$keywords     = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';
		$professional = ! empty( $_POST['professional'] );

		if ( empty( $session_id ) || empty( $message ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID or message.', 'ai-content-engine' ) ) );
		}

		try {
			$chat_builder = new \Chat_Builder();
			$result = $chat_builder->send_message( $session_id, $message, $model, $keywords, $professional );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$session = $chat_builder->get_session( $session_id );
			$meta    = $session ? json_decode( $session->meta_data, true ) : array();
			$session_model = is_array( $meta ) ? ( $meta['model'] ?? $model ) : $model;
			$session_keywords = is_array( $meta ) ? ( $meta['keywords'] ?? $keywords ) : $keywords;
			$session_seo = is_array( $meta ) ? ( $meta['seo_meta'] ?? array() ) : array();

			wp_send_json_success( array_merge(
				$result,
				array(
					'title'      => $session ? $session->title : '',
					'updated_at' => $session ? $session->updated_at : current_time( 'mysql' ),
					'model'      => $session_model,
					'keywords'   => $session_keywords,
					'seo_meta'   => $session_seo,
				)
			) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		} catch ( \Error $e ) {
			wp_send_json_error( array( 'message' => 'PHP Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler for getting user's chat sessions.
	 */
	public function ajax_chat_sessions() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		try {
			$chat_builder = new \Chat_Builder();
			$sessions = $chat_builder->get_user_sessions( 20 );

			wp_send_json_success( array( 'sessions' => $sessions ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
		} catch ( \Error $e ) {
			wp_send_json_error( array( 'message' => 'PHP Error: ' . $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler for loading a specific chat session.
	 */
	public function ajax_chat_load() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$session = $chat_builder->get_session( $session_id );

		if ( ! $session ) {
			wp_send_json_error( array( 'message' => __( 'Session not found.', 'ai-content-engine' ) ) );
		}

		$meta  = json_decode( $session->meta_data, true );
		$model = is_array( $meta ) ? ( $meta['model'] ?? '' ) : '';
		$keywords = is_array( $meta ) ? ( $meta['keywords'] ?? '' ) : '';
		$seo_meta = is_array( $meta ) ? ( $meta['seo_meta'] ?? array() ) : array();
		$word_count = $session->current_content ? str_word_count( wp_strip_all_tags( $session->current_content ) ) : 0;

		wp_send_json_success( array(
			'session_id'   => $session->session_id,
			'title'        => $session->title,
			'content'      => $session->current_content,
			'conversation' => json_decode( $session->conversation, true ),
			'status'       => $session->status,
			'model'        => $model,
			'updated_at'   => $session->updated_at,
			'word_count'   => $word_count,
			'keywords'     => $keywords,
			'seo_meta'     => $seo_meta,
		) );
	}

	/**
	 * AJAX handler for saving chat as draft.
	 */
	public function ajax_chat_save_draft() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$post_id = $chat_builder->save_as_draft( $session_id );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'post_id' => $post_id,
			'url'     => get_edit_post_link( $post_id, 'raw' ),
		) );
	}

	/**
	 * AJAX handler for publishing chat as post.
	 */
	public function ajax_chat_publish() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$post_id = $chat_builder->publish( $session_id );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'post_id' => $post_id,
			'url'     => get_permalink( $post_id ),
		) );
	}

	/**
	 * AJAX handler for renaming a chat session.
	 */
	public function ajax_chat_rename() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( empty( $session_id ) || empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID or title.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$result       = $chat_builder->update_session_title( $session_id, $title );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'title' => $title ) );
	}

	/**
	 * AJAX handler for deleting a chat session.
	 */
	public function ajax_chat_delete() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$result       = $chat_builder->delete_session( $session_id );

		if ( is_wp_error( $result ) || ! $result ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Failed to delete session.', 'ai-content-engine' );
			wp_send_json_error( array( 'message' => $message ) );
		}

		wp_send_json_success( array( 'session_id' => $session_id ) );
	}

	/**
	 * AJAX handler for duplicating a chat session.
	 */
	public function ajax_chat_duplicate() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$result       = $chat_builder->duplicate_session( $session_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for pinning/unpinning a chat session.
	 */
	public function ajax_chat_pin() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$pinned     = isset( $_POST['pinned'] ) ? (int) $_POST['pinned'] : 0;

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$result       = $chat_builder->set_pinned( $session_id, (bool) $pinned );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'session_id' => $session_id, 'pinned' => (bool) $pinned ) );
	}

	/**
	 * AJAX handler for listing versions of a chat session.
	 */
	public function ajax_chat_versions() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$versions     = $chat_builder->get_versions( $session_id );

		if ( is_wp_error( $versions ) ) {
			wp_send_json_error( array( 'message' => $versions->get_error_message() ) );
		}

		wp_send_json_success( array( 'versions' => $versions ) );
	}

	/**
	 * AJAX handler for restoring a version.
	 */
	public function ajax_chat_restore() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$version_id = isset( $_POST['version_id'] ) ? absint( $_POST['version_id'] ) : 0;

		if ( empty( $session_id ) || empty( $version_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing session or version ID.', 'ai-content-engine' ) ) );
		}

		$chat_builder = new \Chat_Builder();
		$result       = $chat_builder->restore_version( $session_id, $version_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for rendering theme preview HTML.
	 */
	public function ajax_chat_preview() {
		check_ajax_referer( 'ace_ajax', 'nonce' );

		if ( ! Security::can_generate_content() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-content-engine' ) ) );
		}

		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : __( 'Preview', 'ai-content-engine' );
		$content = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : '';

		if ( empty( $content ) ) {
			wp_send_json_error( array( 'message' => __( 'No content to preview.', 'ai-content-engine' ) ) );
		}

		$content = Security::sanitize_ai_content( $content );
		$token   = wp_generate_uuid4();

		set_transient(
			'ace_chat_preview_' . $token,
			array(
				'user_id' => get_current_user_id(),
				'title'   => $title,
				'content' => $content,
				'created' => time(),
			),
			10 * MINUTE_IN_SECONDS
		);

		$preview_url = add_query_arg(
			array(
				'ace_chat_preview' => 1,
				'ace_preview_token' => $token,
			),
			home_url( '/' )
		);

		wp_send_json_success( array( 'url' => $preview_url ) );
	}

	/**
	 * Register REST API routes for Gutenberg blocks.
	 */
	public function register_rest_routes() {
		// Content generation endpoint for blocks.
		register_rest_route(
			'ace/v1',
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_generate_content' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Template endpoints.
		register_rest_route(
			'ace/v1',
			'/templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_templates' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'ace/v1',
			'/templates',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_save_template' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'ace/v1',
			'/templates/(?P<id>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'rest_delete_template' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}

	/**
	 * Permission check for REST API endpoints.
	 *
	 * @return bool True if user can edit posts.
	 */
	public function rest_permission_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * REST API handler for content generation.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_generate_content( $request ) {
		$topic = sanitize_textarea_field( $request->get_param( 'topic' ) );
		$keywords = $request->get_param( 'keywords' );
		
		if ( is_array( $keywords ) ) {
			$keywords = implode( ', ', array_map( 'sanitize_text_field', $keywords ) );
		} else {
			$keywords = sanitize_text_field( $keywords );
		}

		$options = array(
			'length'         => sanitize_text_field( $request->get_param( 'length' ) ?? 'medium' ),
			'tone'           => sanitize_text_field( $request->get_param( 'tone' ) ?? 'professional' ),
			'keywords'       => $keywords,
			'include_tables' => (bool) $request->get_param( 'include_tables' ),
			'include_charts' => (bool) $request->get_param( 'include_charts' ),
			'post_status'    => 'draft', // Don't create post, just return content
			'return_data_only' => true, // Flag to return data without creating post
		);

		// Generate article data without creating a post.
		$result = $this->generator->generate_article_data( $topic, $options );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * REST API handler for starting chat session.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_chat_start( $request ) {
		if ( ! class_exists( '\\Chat_Builder' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Chat Builder not available', 'ai-content-engine' ),
				),
				400
			);
		}

		$chat = new \Chat_Builder();
		$topic = sanitize_textarea_field( $request->get_param( 'topic' ) ?? '' );
		
		$result = $chat->start_session( $topic );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * REST API handler for getting all templates.
	 *
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_get_templates() {
		$templates = Content_Template::get_all();

		return new \WP_REST_Response(
			array(
				'success'   => true,
				'templates' => array_values( $templates ), // Convert to indexed array.
			),
			200
		);
	}

	/**
	 * REST API handler for saving a template.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_save_template( $request ) {
		$template = $request->get_json_params();

		if ( empty( $template['name'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Template name is required.', 'ai-content-engine' ),
				),
				400
			);
		}

		$id = Content_Template::save( $template );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'id'      => $id,
				'message' => __( 'Template saved successfully.', 'ai-content-engine' ),
			),
			200
		);
	}

	/**
	 * REST API handler for deleting a template.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_delete_template( $request ) {
		$id = $request['id'];

		$deleted = Content_Template::delete( $id );

		if ( ! $deleted ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Template not found.', 'ai-content-engine' ),
				),
				404
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Template deleted successfully.', 'ai-content-engine' ),
			),
			200
		);
	}

	/**
	 * REST API handler for chat messages.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response REST response.
	 */
	public function rest_chat_message( $request ) {
		if ( ! class_exists( '\\Chat_Builder' ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Chat Builder not available', 'ai-content-engine' ),
				),
				400
			);
		}

		$chat = new \Chat_Builder();
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		$message = sanitize_textarea_field( $request->get_param( 'message' ) );
		
		$result = $chat->send_message( $session_id, $message );

		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $result,
			),
			200
		);
	}



	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets() {
		// Check if build file exists.
		$asset_file = ACE_PLUGIN_DIR . 'build/blocks/ai-content-generator/index.asset.php';
		
		if ( ! file_exists( $asset_file ) ) {
			// Build not complete yet, skip.
			return;
		}

		$asset_info = include $asset_file;

		// Enqueue block editor JavaScript.
		wp_enqueue_script(
			'ace-block-editor',
			ACE_PLUGIN_URL . 'build/blocks/ai-content-generator/index.js',
			$asset_info['dependencies'],
			$asset_info['version'],
			true
		);

		// Enqueue block editor styles.
		wp_enqueue_style(
			'ace-block-editor',
			ACE_PLUGIN_URL . 'build/blocks/ai-content-generator/index.css',
			array(),
			$asset_info['version']
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'ace-block-editor',
			'aceEditorData',
			array(
				'apiUrl' => rest_url( 'ace/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}

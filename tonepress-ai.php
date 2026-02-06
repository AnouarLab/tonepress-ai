<?php
/**
 * Plugin Name: TonePress AI
 * Description: AI Content Generator for WordPress. Create SEO-optimized articles with custom tones, tables, and charts.
 * Version:           2.1.0
 * Author: AnouarLab
 * Author URI: https://anouarlab.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: tonepress-ai
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package AI_Content_Engine
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'ACE_VERSION', '2.1.0' );

/**
 * Plugin directory path.
 */
define( 'ACE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'ACE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'ACE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Maximum API requests per hour per user (rate limiting).
 */
define( 'ACE_MAX_REQUESTS_PER_HOUR', 10 );

/**
 * Default article cache expiration (24 hours).
 */
define( 'ACE_CACHE_EXPIRATION', DAY_IN_SECONDS );

/**
 * PSR-4 autoloader for plugin classes.
 *
 * @param string $class_name The fully-qualified class name.
 */
function ace_autoloader( $class_name ) {
	// Project-specific namespace prefix.
	$prefix = 'ACE\\';

	// Base directory for the namespace prefix.
	$base_dir = ACE_PLUGIN_DIR . 'includes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class_name, $len );

	// Handle sub-namespaces (e.g., ACE\Chat\Intent_Classifier -> Chat/class-intent-classifier.php)
	$parts = explode( '\\', $relative_class );
	
	if ( count( $parts ) > 1 ) {
		// Sub-namespace: ACE\Chat\Intent_Classifier
		$class_name_part = array_pop( $parts );
		$sub_dir = implode( '/', $parts ) . '/';
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name_part ) ) . '.php';
		$file = $base_dir . $sub_dir . $file_name;
	} else {
		// Root namespace: ACE\Admin_UI
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';
		$file = $base_dir . $file_name;
	}

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
}

spl_autoload_register( 'ace_autoloader' );

/**
 * The code that runs during plugin activation.
 */
function ace_activate() {
	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '5.8', '<' ) ) {
		deactivate_plugins( ACE_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'AI Content Engine requires WordPress 5.8 or higher.', 'tonepress-ai' ),
			esc_html__( 'Plugin Activation Error', 'tonepress-ai' ),
			array( 'back_link' => true )
		);
	}

	// Check PHP version.
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		deactivate_plugins( ACE_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'AI Content Engine requires PHP 7.4 or higher.', 'tonepress-ai' ),
			esc_html__( 'Plugin Activation Error', 'tonepress-ai' ),
			array( 'back_link' => true )
		);
	}

	// Set default options.
	add_option( 'ace_ai_provider', 'openai' );
	add_option( 'ace_openai_model', 'gpt-3.5-turbo' );
	add_option( 'ace_default_length', 'medium' ); // short, medium, long
	add_option( 'ace_default_tone', 'professional' );
	add_option( 'ace_enable_cache', '1' );
	add_option( 'ace_enable_rate_limit', '1' );

	// Set transient for wizard redirect.
	set_transient( 'ace_activation_redirect', 1, 30 );
	error_log( '[TonePress] Activated. Transient set.' );

	// Create Chat Builder database table.
	require_once ACE_PLUGIN_DIR . 'includes/class-chat-builder.php';
	\Chat_Builder::create_table();

	// Flush rewrite rules.
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'ace_activate' );

/**
 * The code that runs during plugin deactivation.
 */
function ace_deactivate() {
	// Clear all cached articles.
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'_transient_ace_article_%'
		)
	);
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			'_transient_timeout_ace_article_%'
		)
	);

	// Flush rewrite rules.
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'ace_deactivate' );

/**
 * Initialize the plugin.
 */
function ace_init() {
	// Load text domain for internationalization (automatically handled by WordPress.org).
	load_plugin_textdomain( 'tonepress-ai', false, dirname( ACE_PLUGIN_BASENAME ) . '/languages' );

	// Initialize Admin UI (handles admin pages, settings, AND REST API routes).
	// Must run on all contexts (admin, REST API, frontend) to register REST endpoints.
	$admin_ui = new ACE\Admin_UI();
	$admin_ui->init();

	// Initialize onboarding wizard.
	$wizard = new ACE\Onboarding_Wizard();
	$wizard->init();

	// Initialize chart renderer for frontend.
	$chart_renderer = new ACE\Chart_Renderer();
	$chart_renderer->init();

	// Initialize SEO integrations.
	$seo = new ACE\SEO_Integrations();
	$seo->init();

	// Initialize Reading Time display.
	ACE\Reading_Time::init();
	
	// Load Chat Builder (non-namespaced).
	require_once ACE_PLUGIN_DIR . 'includes/class-chat-builder.php';
}

add_action( 'plugins_loaded', 'ace_init' );

/**
 * Register preview query vars.
 *
 * @param array $vars Query vars.
 * @return array
 */
function ace_preview_query_vars( $vars ) {
	$vars[] = 'ace_chat_preview';
	$vars[] = 'ace_preview_token';
	return $vars;
}

add_filter( 'query_vars', 'ace_preview_query_vars' );

/**
 * Use custom template for preview.
 *
 * @param string $template Template path.
 * @return string
 */
function ace_preview_template_include( $template ) {
	if ( get_query_var( 'ace_chat_preview' ) ) {
		$preview_template = ACE_PLUGIN_DIR . 'templates/preview.php';
		if ( file_exists( $preview_template ) ) {
			return $preview_template;
		}
	}

	return $template;
}

add_filter( 'template_include', 'ace_preview_template_include' );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function ace_add_settings_link( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=tonepress-ai' ) ),
		esc_html__( 'Settings', 'tonepress-ai' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'plugin_action_links_' . ACE_PLUGIN_BASENAME, 'ace_add_settings_link' );

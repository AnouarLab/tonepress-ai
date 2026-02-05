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
 * Wizard Step: Welcome
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-step ace-step-welcome">
	<div class="welcome-hero">
		<div class="hero-icon">
			<span class="dashicons dashicons-admin-page"></span>
		</div>
		<h1><?php esc_html_e( 'Welcome to AI Content Engine!', 'ai-content-engine' ); ?></h1>
		<p class="lead">
			<?php esc_html_e( 'Create professional blog posts in minutes with the power of AI.', 'ai-content-engine' ); ?>
		</p>
	</div>

	<div class="welcome-features">
		<div class="feature-grid">
			<div class="feature-card">
				<span class="dashicons dashicons-lightbulb"></span>
				<h3><?php esc_html_e( 'AI-Powered', 'ai-content-engine' ); ?></h3>
				<p><?php esc_html_e( 'Choose from OpenAI, Claude, or Gemini', 'ai-content-engine' ); ?></p>
			</div>

			<div class="feature-card">
				<span class="dashicons dashicons-admin-customizer"></span>
				<h3><?php esc_html_e( 'Brand Voice', 'ai-content-engine' ); ?></h3>
				<p><?php esc_html_e( 'Maintain consistent tone across all content', 'ai-content-engine' ); ?></p>
			</div>

			<div class="feature-card">
				<span class="dashicons dashicons-performance"></span>
				<h3><?php esc_html_e( 'SEO Optimized', 'ai-content-engine' ); ?></h3>
				<p><?php esc_html_e( 'Built-in optimization for search engines', 'ai-content-engine' ); ?></p>
			</div>

			<div class="feature-card">
				<span class="dashicons dashicons-layout"></span>
				<h3><?php esc_html_e( 'Templates', 'ai-content-engine' ); ?></h3>
				<p><?php esc_html_e( 'Pre-built templates for common article types', 'ai-content-engine' ); ?></p>
			</div>
		</div>
	</div>

	<div class="welcome-estimate">
		<p class="time-estimate">
			<span class="dashicons dashicons-clock"></span>
			<?php esc_html_e( 'Setup takes approximately 3 minutes', 'ai-content-engine' ); ?>
		</p>
	</div>
</div>

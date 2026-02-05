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
 * Wizard Step: Content Preferences
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-step ace-step-preferences">
	<h2><?php esc_html_e( 'Set Your Content Preferences', 'tonepress-ai' ); ?></h2>
	<p class="step-description">
		<?php esc_html_e( 'Configure default settings for your article generation.', 'tonepress-ai' ); ?>
	</p>

	<form id="preferences-form" class="wizard-form">
		<div class="form-row">
			<label for="default-length">
				<strong><?php esc_html_e( 'Default Article Length', 'tonepress-ai' ); ?></strong>
			</label>
			<select id="default-length" name="default_length" class="large-text">
				<option value="short"><?php esc_html_e( 'Short (500-700 words)', 'tonepress-ai' ); ?></option>
				<option value="medium" selected><?php esc_html_e( 'Medium (1000-1200 words)', 'tonepress-ai' ); ?></option>
				<option value="long"><?php esc_html_e( 'Long (2000+ words)', 'tonepress-ai' ); ?></option>
			</select>
		</div>

		<div class="form-row">
			<label for="default-tone">
				<strong><?php esc_html_e( 'Default Tone', 'tonepress-ai' ); ?></strong>
			</label>
			<select id="default-tone" name="default_tone" class="large-text">
				<option value="professional" selected><?php esc_html_e( 'Professional', 'tonepress-ai' ); ?></option>
				<option value="casual"><?php esc_html_e( 'Casual', 'tonepress-ai' ); ?></option>
				<option value="friendly"><?php esc_html_e( 'Friendly', 'tonepress-ai' ); ?></option>
				<option value="formal"><?php esc_html_e( 'Formal', 'tonepress-ai' ); ?></option>
				<option value="conversational"><?php esc_html_e( 'Conversational', 'tonepress-ai' ); ?></option>
			</select>
		</div>

		<div class="preferences-toggles">
			<h3><?php esc_html_e( 'Automatic Features', 'tonepress-ai' ); ?></h3>
			
			<label class="toggle-row">
				<input type="checkbox" name="auto_featured_image" checked>
				<div class="toggle-content">
					<div class="toggle-icon">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<div class="toggle-text">
						<strong><?php esc_html_e( 'Generate Featured Images', 'tonepress-ai' ); ?></strong>
						<p><?php esc_html_e( 'Automatically create relevant featured images for articles', 'tonepress-ai' ); ?></p>
					</div>
				</div>
			</label>

			<label class="toggle-row">
				<input type="checkbox" name="auto_seo" checked>
				<div class="toggle-content">
					<div class="toggle-icon">
						<span class="dashicons dashicons-search"></span>
					</div>
					<div class="toggle-text">
						<strong><?php esc_html_e( 'SEO Optimization', 'tonepress-ai' ); ?></strong>
						<p><?php esc_html_e( 'Include meta descriptions, optimize titles, and add structured data', 'tonepress-ai' ); ?></p>
					</div>
				</div>
			</label>

			<label class="toggle-row">
				<input type="checkbox" name="auto_toc" checked>
				<div class="toggle-content">
					<div class="toggle-icon">
						<span class="dashicons dashicons-list-view"></span>
					</div>
					<div class="toggle-text">
						<strong><?php esc_html_e( 'Table of Contents', 'tonepress-ai' ); ?></strong>
						<p><?php esc_html_e( 'Add a table of contents for articles with 3+ headings', 'tonepress-ai' ); ?></p>
					</div>
				</div>
			</label>

			<label class="toggle-row">
				<input type="checkbox" name="auto_internal_links">
				<div class="toggle-content">
					<div class="toggle-icon">
						<span class="dashicons dashicons-admin-links"></span>
					</div>
					<div class="toggle-text">
						<strong><?php esc_html_e( 'Internal Linking', 'tonepress-ai' ); ?></strong>
						<p><?php esc_html_e( 'Suggest internal links to related articles', 'tonepress-ai' ); ?></p>
					</div>
				</div>
			</label>
		</div>
	</form>
</div>

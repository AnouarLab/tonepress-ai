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
 * Wizard Step: First Article
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-step ace-step-first-article">
	<h2><?php esc_html_e( 'Let\'s Create Your First Article!', 'tonepress-ai' ); ?></h2>
	<p class="step-description">
		<?php esc_html_e( 'Enter a topic and watch the magic happen. We\'ll create a draft article for you to review.', 'tonepress-ai' ); ?>
	</p>

	<form id="first-article-form" class="wizard-form">
		<div class="form-row">
			<label for="article-topic">
				<strong><?php esc_html_e( 'Article Topic', 'tonepress-ai' ); ?></strong>
				<span class="required">*</span>
			</label>
			<input 
				type="text" 
				id="article-topic" 
				name="topic" 
				class="large-text" 
				placeholder="<?php esc_attr_e( 'e.g., How to improve team productivity with AI tools', 'tonepress-ai' ); ?>"
				required
			>
			<p class="description">
				<?php esc_html_e( 'Tip: Be specific! Better topics lead to better articles.', 'tonepress-ai' ); ?>
			</p>
		</div>

		<div class="generate-section">
			<button type="button" id="generate-sample-btn" class="button button-primary button-hero">
				<span class="dashicons dashicons-welcome-write-blog"></span>
				<?php esc_html_e( 'Generate Article', 'tonepress-ai' ); ?>
			</button>
		</div>

		<div id="generation-progress" class="generation-progress" style="display: none;">
			<div class="progress-bar">
				<div class="progress-fill"></div>
			</div>
			<div class="progress-steps">
				<div class="progress-step active" data-step="1">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Building prompt...', 'tonepress-ai' ); ?>
				</div>
				<div class="progress-step" data-step="2">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Generating content...', 'tonepress-ai' ); ?>
				</div>
				<div class="progress-step" data-step="3">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Creating post...', 'tonepress-ai' ); ?>
				</div>
			</div>
		</div>

		<div id="article-preview" class="article-preview" style="display: none;">
			<h3><?php esc_html_e( 'Preview', 'tonepress-ai' ); ?></h3>
			<div class="preview-content">
				<div class="preview-title"></div>
				<div class="preview-excerpt"></div>
			</div>
			<div class="preview-actions">
				<a href="#" id="edit-article-link" class="button button-secondary" target="_blank">
					<span class="dashicons dashicons-edit"></span>
					<?php esc_html_e( 'Edit Draft', 'tonepress-ai' ); ?>
				</a>
				<button type="button" id="regenerate-btn" class="button">
					<span class="dashicons dashicons-image-rotate"></span>
					<?php esc_html_e( 'Try Different Topic', 'tonepress-ai' ); ?>
				</button>
			</div>
		</div>
	</form>
</div>

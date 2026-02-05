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
 * Wizard Step: Templates
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-step ace-step-templates">
	<h2><?php esc_html_e( 'Choose Starter Templates', 'ai-content-engine' ); ?></h2>
	<p class="step-description">
		<?php esc_html_e( 'Select pre-built templates to speed up your content creation.', 'ai-content-engine' ); ?>
	</p>

	<form id="templates-form" class="wizard-form">
		<div class="template-grid">
			<label class="template-card">
				<input type="checkbox" name="templates[]" value="how_to" checked>
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-editor-help"></span>
					</div>
					<h3><?php esc_html_e( 'How-To Guide', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Step-by-step tutorials and instructional content', 'ai-content-engine' ); ?></p>
					<span class="card-badge"><?php esc_html_e( 'Recommended', 'ai-content-engine' ); ?></span>
				</div>
			</label>

			<label class="template-card">
				<input type="checkbox" name="templates[]" value="listicle" checked>
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-list-view"></span>
					</div>
					<h3><?php esc_html_e( 'Listicle', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Top 10 lists, roundups, and numbered articles', 'ai-content-engine' ); ?></p>
					<span class="card-badge"><?php esc_html_e( 'Popular', 'ai-content-engine' ); ?></span>
				</div>
			</label>

			<label class="template-card">
				<input type="checkbox" name="templates[]" value="product_review" checked>
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-star-filled"></span>
					</div>
					<h3><?php esc_html_e( 'Product Review', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Detailed reviews with pros, cons, and ratings', 'ai-content-engine' ); ?></p>
				</div>
			</label>

			<label class="template-card">
				<input type="checkbox" name="templates[]" value="comparison">
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-leftright"></span>
					</div>
					<h3><?php esc_html_e( 'Comparison', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Compare products, services, or solutions', 'ai-content-engine' ); ?></p>
				</div>
			</label>

			<label class="template-card">
				<input type="checkbox" name="templates[]" value="case_study">
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-chart-line"></span>
					</div>
					<h3><?php esc_html_e( 'Case Study', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Success stories with data and outcomes', 'ai-content-engine' ); ?></p>
				</div>
			</label>

			<label class="template-card">
				<input type="checkbox" name="templates[]" value="news">
				<div class="card-content">
					<div class="card-icon">
						<span class="dashicons dashicons-megaphone"></span>
					</div>
					<h3><?php esc_html_e( 'Industry News', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'News updates and trend analysis', 'ai-content-engine' ); ?></p>
				</div>
			</label>
		</div>

		<div class="template-note">
			<p class="description">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'You can add or remove templates later from the Templates page.', 'ai-content-engine' ); ?>
			</p>
		</div>
	</form>
</div>

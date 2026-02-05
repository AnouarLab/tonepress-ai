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
 * Wizard Step: Complete
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-step ace-step-complete">
	<div class="completion-hero">
		<div class="success-icon">
			<span class="dashicons dashicons-yes-alt"></span>
		</div>
		<h1><?php esc_html_e( '🎉 You\'re All Set!', 'ai-content-engine' ); ?></h1>
		<p class="lead">
			<?php esc_html_e( 'Your AI Content Engine is configured and ready to generate amazing content.', 'ai-content-engine' ); ?>
		</p>
	</div>

	<div class="next-steps">
		<h2><?php esc_html_e( 'What\'s Next?', 'ai-content-engine' ); ?></h2>
		
		<div class="steps-grid">
			<div class="step-item">
				<div class="step-number">1</div>
				<div class="step-content">
					<h3><?php esc_html_e( 'Review Your Draft', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Check out the sample article we created during setup', 'ai-content-engine' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_status=draft&post_type=post' ) ); ?>" class="button">
						<?php esc_html_e( 'View Drafts', 'ai-content-engine' ); ?>
					</a>
				</div>
			</div>

			<div class="step-item">
				<div class="step-number">2</div>
				<div class="step-content">
					<h3><?php esc_html_e( 'Generate More Content', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Create individual articles or use bulk generation', 'ai-content-engine' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-content-engine' ) ); ?>" class="button">
						<?php esc_html_e( 'Generate Article', 'ai-content-engine' ); ?>
					</a>
				</div>
			</div>

			<div class="step-item">
				<div class="step-number">3</div>
				<div class="step-content">
					<h3><?php esc_html_e( 'Explore Templates', 'ai-content-engine' ); ?></h3>
					<p><?php esc_html_e( 'Customize templates or create your own', 'ai-content-engine' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-content-engine&tab=templates' ) ); ?>" class="button">
						<?php esc_html_e( 'View Templates', 'ai-content-engine' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<div class="resources-section">
		<h3><?php esc_html_e( 'Helpful Resources', 'ai-content-engine' ); ?></h3>
		<ul class="resources-list">
			<li>
				<span class="dashicons dashicons-book"></span>
				<a href="<?php echo esc_url( 'https://docs.example.com/getting-started' ); ?>" target="_blank">
					<?php esc_html_e( 'Read the Getting Started Guide', 'ai-content-engine' ); ?>
				</a>
			</li>
			<li>
				<span class="dashicons dashicons-video-alt3"></span>
				<a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=example' ); ?>" target="_blank">
					<?php esc_html_e( 'Watch Video Tutorials', 'ai-content-engine' ); ?>
				</a>
			</li>
			<li>
				<span class="dashicons dashicons-groups"></span>
				<a href="<?php echo esc_url( 'https://community.example.com' ); ?>" target="_blank">
					<?php esc_html_e( 'Join the Community', 'ai-content-engine' ); ?>
				</a>
			</li>
		</ul>
	</div>

	<div class="completion-actions">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-content-engine' ) ); ?>" class="button button-primary button-hero">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Go to Dashboard', 'ai-content-engine' ); ?>
		</a>
	</div>
</div>

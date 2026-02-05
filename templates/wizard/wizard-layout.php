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
 * Onboarding Wizard Layout
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="ace-wizard-container">
	<div class="ace-wizard-header">
		<div class="ace-wizard-logo">
			<h1><?php esc_html_e( 'AI Content Engine', 'ai-content-engine' ); ?></h1>
		</div>
		
		<div class="ace-wizard-progress">
			<div class="progress-bar">
				<div class="progress-fill" style="width: <?php echo esc_attr( ( $step_number / $total_steps ) * 100 ); ?>%"></div>
			</div>
			<div class="progress-text">
				<?php
				/* translators: 1: current step, 2: total steps */
				printf( esc_html__( 'Step %1$d of %2$d', 'ai-content-engine' ), (int) $step_number, (int) $total_steps );
				?>
			</div>
		</div>
	</div>

	<div class="ace-wizard-body">
		<?php
		// Load the current step template.
		$step_file = ACE_PLUGIN_DIR . 'templates/wizard/steps/step-' . $current_step . '.php';
		
		if ( file_exists( $step_file ) ) {
			include $step_file;
		} else {
			echo '<p>' . esc_html__( 'Step not found.', 'ai-content-engine' ) . '</p>';
		}
		?>
	</div>

	<div class="ace-wizard-footer">
		<?php if ( 'welcome' !== $current_step && 'complete' !== $current_step ) : ?>
			<button type="button" class="button button-secondary ace-wizard-back">
				<?php esc_html_e( '← Back', 'ai-content-engine' ); ?>
			</button>
		<?php endif; ?>
		
		<?php if ( 'complete' !== $current_step ) : ?>
			<button type="button" class="button button-primary ace-wizard-next">
				<?php echo 'welcome' === $current_step ? esc_html__( 'Get Started →', 'ai-content-engine' ) : esc_html__( 'Continue →', 'ai-content-engine' ); ?>
			</button>
		<?php else : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-content-engine' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Go to Dashboard →', 'ai-content-engine' ); ?>
			</a>
		<?php endif; ?>
		
		<?php if ( 'welcome' !== $current_step && 'complete' !== $current_step ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-content-engine' ) ); ?>" class="ace-wizard-skip">
				<?php esc_html_e( 'Skip setup', 'ai-content-engine' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>

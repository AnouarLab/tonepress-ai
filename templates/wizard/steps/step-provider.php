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
 * Wizard Step: AI Provider Setup
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_provider = get_option( 'ace_ai_provider', 'openai' );
?>

<div class="ace-wizard-step ace-step-provider">
	<h2><?php esc_html_e( 'Choose Your AI Provider', 'tonepress-ai' ); ?></h2>
	<p class="step-description">
		<?php esc_html_e( 'Select an AI provider and enter your API key to get started.', 'tonepress-ai' ); ?>
	</p>

	<form id="provider-form" class="wizard-form">
		<div class="provider-options">
			<label class="provider-card <?php echo 'openai' === $current_provider ? 'selected' : ''; ?>">
				<input type="radio" name="provider" value="openai" <?php checked( $current_provider, 'openai' ); ?>>
				<div class="card-content">
					<div class="card-header">
						<h3>OpenAI (GPT-4)</h3>
						<span class="badge badge-popular"><?php esc_html_e( 'Most Popular', 'tonepress-ai' ); ?></span>
					</div>
					<p class="card-description">
						<?php esc_html_e( 'Best for technical content and detailed explanations', 'tonepress-ai' ); ?>
					</p>
					<div class="card-meta">
						<span class="cost-indicator">
							<span class="dashicons dashicons-money-alt"></span>
							<?php esc_html_e( 'Cost: $$$', 'tonepress-ai' ); ?>
						</span>
					</div>
				</div>
			</label>

			<label class="provider-card <?php echo 'claude' === $current_provider ? 'selected' : ''; ?>">
				<input type="radio" name="provider" value="claude" <?php checked( $current_provider, 'claude' ); ?>>
				<div class="card-content">
					<div class="card-header">
						<h3>Claude (Anthropic)</h3>
					</div>
					<p class="card-description">
						<?php esc_html_e( 'Best for long-form articles and nuanced writing', 'tonepress-ai' ); ?>
					</p>
					<div class="card-meta">
						<span class="cost-indicator">
							<span class="dashicons dashicons-money-alt"></span>
							<?php esc_html_e( 'Cost: $$', 'tonepress-ai' ); ?>
						</span>
					</div>
				</div>
			</label>

			<label class="provider-card <?php echo 'gemini' === $current_provider ? 'selected' : ''; ?>">
				<input type="radio" name="provider" value="gemini" <?php checked( $current_provider, 'gemini' ); ?>>
				<div class="card-content">
					<div class="card-header">
						<h3>Gemini (Google)</h3>
						<span class="badge badge-value"><?php esc_html_e( 'Best Value', 'tonepress-ai' ); ?></span>
					</div>
					<p class="card-description">
						<?php esc_html_e( 'Best for research-heavy content and factual accuracy', 'tonepress-ai' ); ?>
					</p>
					<div class="card-meta">
						<span class="cost-indicator">
							<span class="dashicons dashicons-money-alt"></span>
							<?php esc_html_e( 'Cost: $', 'tonepress-ai' ); ?>
						</span>
					</div>
				</div>
			</label>
		</div>

		<div class="api-key-section">
			<label for="api-key">
				<strong><?php esc_html_e( 'API Key', 'tonepress-ai' ); ?></strong>
				<span class="required">*</span>
			</label>
			<div class="api-key-input-wrapper">
				<input 
					type="password" 
					id="api-key" 
					name="api_key" 
					class="large-text" 
					placeholder="sk-..." 
					required
					value="<?php echo esc_attr( get_option( 'ace_' . $current_provider . '_api_key', '' ) ); ?>"
				>
				<button type="button" id="toggle-api-key" class="button">
					<span class="dashicons dashicons-visibility"></span>
				</button>
			</div>
			<p class="description">
				<?php
				/* translators: %s: provider name */
				printf(
					esc_html__( 'Enter your %s API key. Don\'t have one?', 'tonepress-ai' ),
					'<span class="provider-name">OpenAI</span>'
				);
				?>
				<a href="#" class="get-api-key-link" target="_blank"><?php esc_html_e( 'Get your API key →', 'tonepress-ai' ); ?></a>
			</p>
		</div>

		<div class="test-connection">
			<button type="button" id="test-connection-btn" class="button button-secondary">
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'Test Connection', 'tonepress-ai' ); ?>
			</button>
			<div class="test-result"></div>
		</div>
	</form>
</div>

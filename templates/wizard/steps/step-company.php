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
 * Wizard Step: Company Profile
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$company_data = get_option( 'ace_company_context', array() );
?>

<div class="ace-wizard-step ace-step-company">
	<h2><?php esc_html_e( 'Tell Us About Your Business', 'tonepress-ai' ); ?></h2>
	<p class="step-description">
		<?php esc_html_e( 'This helps us maintain your brand voice and consistency across all generated content.', 'tonepress-ai' ); ?>
	</p>

	<form id="company-form" class="wizard-form">
		<div class="form-row">
			<label for="company-name">
				<strong><?php esc_html_e( 'Company Name', 'tonepress-ai' ); ?></strong>
				<span class="required">*</span>
			</label>
			<input 
				type="text" 
				id="company-name" 
				name="company_name" 
				class="large-text" 
				placeholder="<?php esc_attr_e( 'e.g., Acme Corp', 'tonepress-ai' ); ?>"
				required
				value="<?php echo esc_attr( $company_data['name'] ?? '' ); ?>"
			>
		</div>

		<div class="form-row">
			<label for="industry">
				<strong><?php esc_html_e( 'Industry', 'tonepress-ai' ); ?></strong>
			</label>
			<select id="industry" name="industry" class="large-text">
				<option value=""><?php esc_html_e( 'Select your industry...', 'tonepress-ai' ); ?></option>
				<option value="saas" <?php selected( $company_data['industry'] ?? '', 'saas' ); ?>><?php esc_html_e( 'SaaS / Software', 'tonepress-ai' ); ?></option>
				<option value="ecommerce" <?php selected( $company_data['industry'] ?? '', 'ecommerce' ); ?>><?php esc_html_e( 'E-commerce / Retail', 'tonepress-ai' ); ?></option>
				<option value="healthcare" <?php selected( $company_data['industry'] ?? '', 'healthcare' ); ?>><?php esc_html_e( 'Healthcare / Medical', 'tonepress-ai' ); ?></option>
				<option value="finance" <?php selected( $company_data['industry'] ?? '', 'finance' ); ?>><?php esc_html_e( 'Finance / Banking', 'tonepress-ai' ); ?></option>
				<option value="education" <?php selected( $company_data['industry'] ?? '', 'education' ); ?>><?php esc_html_e( 'Education / Training', 'tonepress-ai' ); ?></option>
				<option value="marketing" <?php selected( $company_data['industry'] ?? '', 'marketing' ); ?>><?php esc_html_e( 'Marketing / Advertising', 'tonepress-ai' ); ?></option>
				<option value="technology" <?php selected( $company_data['industry'] ?? '', 'technology' ); ?>><?php esc_html_e( 'Technology / IT', 'tonepress-ai' ); ?></option>
				<option value="consulting" <?php selected( $company_data['industry'] ?? '', 'consulting' ); ?>><?php esc_html_e( 'Consulting / Professional Services', 'tonepress-ai' ); ?></option>
				<option value="other" <?php selected( $company_data['industry'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other', 'tonepress-ai' ); ?></option>
			</select>
		</div>

		<div class="form-row">
			<label for="target-audience">
				<strong><?php esc_html_e( 'Target Audience', 'tonepress-ai' ); ?></strong>
			</label>
			<textarea 
				id="target-audience" 
				name="target_audience" 
				class="large-text" 
				rows="3"
				placeholder="<?php esc_attr_e( 'e.g., Small business owners, Marketing professionals, Tech enthusiasts...', 'tonepress-ai' ); ?>"
			><?php echo esc_textarea( $company_data['audience'] ?? '' ); ?></textarea>
		</div>

		<div class="form-row">
			<label>
				<strong><?php esc_html_e( 'Brand Voice', 'tonepress-ai' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Select the tone that best represents your brand:', 'tonepress-ai' ); ?>
			</p>
			<div class="voice-tags">
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="professional">
					<span><?php esc_html_e( 'Professional', 'tonepress-ai' ); ?></span>
				</label>
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="friendly">
					<span><?php esc_html_e( 'Friendly', 'tonepress-ai' ); ?></span>
				</label>
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="casual">
					<span><?php esc_html_e( 'Casual', 'tonepress-ai' ); ?></span>
				</label>
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="technical">
					<span><?php esc_html_e( 'Technical', 'tonepress-ai' ); ?></span>
				</label>
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="authoritative">
					<span><?php esc_html_e( 'Authoritative', 'tonepress-ai' ); ?></span>
				</label>
				<label class="voice-tag">
					<input type="checkbox" name="voice[]" value="conversational">
					<span><?php esc_html_e( 'Conversational', 'tonepress-ai' ); ?></span>
				</label>
			</div>
		</div>

		<div class="form-row">
			<label for="brand-voice-custom">
				<strong><?php esc_html_e( 'Custom Brand Description (Optional)', 'tonepress-ai' ); ?></strong>
			</label>
			<textarea 
				id="brand-voice-custom" 
				name="brand_voice" 
				class="large-text" 
				rows="4"
				placeholder="<?php esc_attr_e( 'Describe your brand voice, key messaging, or any specific guidelines...', 'tonepress-ai' ); ?>"
			><?php echo esc_textarea( $company_data['brand_voice'] ?? '' ); ?></textarea>
		</div>
	</form>
</div>

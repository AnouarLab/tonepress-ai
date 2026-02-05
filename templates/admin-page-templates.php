<?php
/**
 * Templates Tab Content for AI Content Engine Admin Page.
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Initialize default templates.
ACE\Content_Template::create_defaults();

// Get all templates.
$templates = ACE\Content_Template::get_all();
?>

<!-- Templates Tab -->
<div class="ace-templates-tab">
	<h2><?php esc_html_e( 'Content Templates', 'ai-content-engine' ); ?></h2>
	<p><?php esc_html_e( 'Create reusable templates to streamline your blog article generation. Templates save your preferred settings and instructions for quick reuse.', 'ai-content-engine' ); ?></p>

	<!-- Add New Template Form -->
	<div class="ace-form-card">
		<div class="ace-form-card-header">
			<h3><span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'Create New Template', 'ai-content-engine' ); ?></h3>
		</div>
		
		<form id="ace-template-form">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="template-name"><?php esc_html_e( 'Template Name', 'ai-content-engine' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="template-name" name="name" class="regular-text" required placeholder="<?php esc_attr_e( 'e.g., Product Launch Post', 'ai-content-engine' ); ?>" />
							<p class="description"><?php esc_html_e( 'A descriptive name for this template.', 'ai-content-engine' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-description"><?php esc_html_e( 'Description', 'ai-content-engine' ); ?></label>
						</th>
						<td>
							<input type="text" id="template-description" name="description" class="large-text" placeholder="<?php esc_attr_e( 'Brief description of when to use this template', 'ai-content-engine' ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-topic"><?php esc_html_e( 'Topic Pattern', 'ai-content-engine' ); ?> <span class="required">*</span></label>
						</th>
						<td>
							<input type="text" id="template-topic" name="topic_pattern" class="large-text" required placeholder="<?php esc_attr_e( 'How to [achieve goal] with [product/feature]', 'ai-content-engine' ); ?>" />
							<p class="description"><?php esc_html_e( 'Use [brackets] for variables. Example: "How to [achieve result]" or "Top [number] ways to [benefit]"', 'ai-content-engine' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-keywords"><?php esc_html_e( 'Keywords', 'ai-content-engine' ); ?></label>
						</th>
						<td>
							<input type="text" id="template-keywords" name="keywords" class="large-text" placeholder="<?php esc_attr_e( 'keyword1, keyword2, keyword3', 'ai-content-engine' ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-tone"><?php esc_html_e( 'Writing Tone', 'ai-content-engine' ); ?></label>
						</th>
						<td>
							<select id="template-tone" name="tone">
								<option value="professional"><?php esc_html_e( 'Professional', 'ai-content-engine' ); ?></option>
								<option value="conversational"><?php esc_html_e( 'Conversational', 'ai-content-engine' ); ?></option>
								<option value="authoritative"><?php esc_html_e( 'Authoritative', 'ai-content-engine' ); ?></option>
								<option value="friendly"><?php esc_html_e( 'Friendly', 'ai-content-engine' ); ?></option>
								<option value="helpful"><?php esc_html_e( 'Helpful', 'ai-content-engine' ); ?></option>
								<option value="engaging"><?php esc_html_e( 'Engaging', 'ai-content-engine' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-length"><?php esc_html_e( 'Article Length', 'ai-content-engine' ); ?></label>
						</th>
						<td>
							<select id="template-length" name="length">
								<option value="short"><?php esc_html_e( 'Short (800-1200 words)', 'ai-content-engine' ); ?></option>
								<option value="medium" selected><?php esc_html_e( 'Medium (1200-1800 words)', 'ai-content-engine' ); ?></option>
								<option value="long"><?php esc_html_e( 'Long (1800-2500+ words)', 'ai-content-engine' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="template-instructions"><?php esc_html_e( 'Custom Instructions', 'ai-content-engine' ); ?></label>
						</th>
						<td>
							<textarea id="template-instructions" name="custom_instructions" rows="5" class="large-text" placeholder="<?php esc_attr_e( 'Add specific instructions for this template...', 'ai-content-engine' ); ?>"></textarea>
							<p class="description"><?php esc_html_e( 'Detailed instructions for how to structure the article. Examples: required sections, formatting guidelines, specific points to cover.', 'ai-content-engine' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary button-large">
					<?php esc_html_e( 'Save Template', 'ai-content-engine' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- Templates List -->
	<div class="ace-form-card">
		<div class="ace-form-card-header">
			<h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Saved Templates', 'ai-content-engine' ); ?></h3>
		</div>

		<?php if ( empty( $templates ) ) : ?>
			<p><?php esc_html_e( 'No templates found. Create your first template above to get started!', 'ai-content-engine' ); ?></p>
		<?php  else : ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 20%;"><?php esc_html_e( 'Name', 'ai-content-engine' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Topic Pattern', 'ai-content-engine' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Tone', 'ai-content-engine' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Length', 'ai-content-engine' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Created', 'ai-content-engine' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Actions', 'ai-content-engine' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $templates as $template ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $template['name'] ); ?></strong>
								<?php if ( ! empty( $template['description'] ) ) : ?>
									<br><small style="color: #646970;"><?php echo esc_html( $template['description'] ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<code style="font-size: 12px;"><?php echo esc_html( $template['topic_pattern'] ); ?></code>
							</td>
							<td><?php echo esc_html( ucfirst( $template['tone'] ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $template['length'] ) ); ?></td>
							<td><?php echo esc_html( date( 'M j, Y', strtotime( $template['created'] ) ) ); ?></td>
							<td>
								<button class="button button-small ace-delete-template" data-template="<?php echo esc_attr( $template['id'] ); ?>">
									<?php esc_html_e( 'Delete', 'ai-content-engine' ); ?>
								</button>
							</td>
						</tr>
						<?php if ( ! empty( $template['custom_instructions'] ) ) : ?>
							<tr class="ace-template-instructions">
								<td colspan="6" style="padding-left: 40px; background: #f6f7f7;">
									<small><strong><?php esc_html_e( 'Instructions:', 'ai-content-engine' ); ?></strong> <?php echo esc_html( $template['custom_instructions'] ); ?></small>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- Info Box -->
	<div class="ace-form-card" style="background: #e7f3ff; border-left: 4px solid #2271b1;">
		<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Using Templates', 'ai-content-engine' ); ?></h3>
		<p><?php esc_html_e( 'Templates are available in:', 'ai-content-engine' ); ?></p>
		<ul style="margin-left: 20px;">
			<li><?php esc_html_e( '✓ Gutenberg Block Editor - Select from the template dropdown when adding the AI Content Generator block', 'ai-content-engine' ); ?></li>
			<li><?php esc_html_e( '✓ Bulk Generation - Reference template IDs in your CSV file', 'ai-content-engine' ); ?></li>
		</ul>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
		// Save template
		$('#ace-template-form').on('submit', function(e) {
			e.preventDefault();
			
			const formData = new FormData(this);
			const templateData = Object.fromEntries(formData.entries());

			$.ajax({
				url: '<?php echo esc_url_raw( rest_url( 'ace/v1/templates' ) ); ?>',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
				},
				contentType: 'application/json',
				data: JSON.stringify(templateData),
				success: function(response) {
					if (response.success) {
						alert('<?php esc_html_e( 'Template saved successfully!', 'ai-content-engine' ); ?>');
						location.reload();
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Error saving template.', 'ai-content-engine' ); ?>');
				}
			});
		});

		// Delete template
		$('.ace-delete-template').on('click', function() {
			if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this template?', 'ai-content-engine' ); ?>')) {
				return;
			}

			const templateId = $(this).data('template');

			$.ajax({
				url: '<?php echo esc_url_raw( rest_url( 'ace/v1/templates/' ) ); ?>' + templateId,
				method: 'DELETE',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>');
				},
				success: function(response) {
					if (response.success) {
						alert('<?php esc_html_e( 'Template deleted successfully!', 'ai-content-engine' ); ?>');
						location.reload();
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Error deleting template.', 'ai-content-engine' ); ?>');
				}
			});
		});
	});
</script>

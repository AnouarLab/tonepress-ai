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
 * Admin page template for TonePress AI.
 *
 * @package AI_Content_Engine
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'generate'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="wrap ace-admin-wrapper">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=tonepress-ai&tab=generate" class="nav-tab <?php echo 'generate' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Generate Article', 'tonepress-ai' ); ?>
		</a>
		<a href="?page=tonepress-ai&tab=bulk" class="nav-tab <?php echo 'bulk' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Bulk Generation', 'tonepress-ai' ); ?>
		</a>
		<a href="?page=tonepress-ai&tab=templates" class="nav-tab <?php echo 'templates' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Templates', 'tonepress-ai' ); ?>
		</a>
		<a href="?page=tonepress-ai&tab=history" class="nav-tab <?php echo 'history' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'History', 'tonepress-ai' ); ?>
		</a>
		<a href="?page=tonepress-ai&tab=settings" class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Settings', 'tonepress-ai' ); ?>
		</a>
		<a href="?page=tonepress-ai&tab=stats" class="nav-tab <?php echo 'stats' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Statistics', 'tonepress-ai' ); ?>
		</a>
	</nav>

	<div class="ace-tab-content">
		<?php if ( 'generate' === $current_tab ) : ?>
			<!-- Generate Article Tab -->
			<div class="ace-generate-tab">
				<h2><?php esc_html_e( 'Generate AI Article', 'tonepress-ai' ); ?></h2>

			<!-- Quick Presets -->
			<div class="ace-quick-presets">
				<button type="button" class="ace-preset-btn" data-preset="blog">
					<span class="dashicons dashicons-edit"></span>
					<?php esc_html_e( 'Blog Post', 'tonepress-ai' ); ?>
				</button>
				<button type="button" class="ace-preset-btn" data-preset="howto">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'How-To Guide', 'tonepress-ai' ); ?>
				</button>
				<button type="button" class="ace-preset-btn" data-preset="review">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Product Review', 'tonepress-ai' ); ?>
				</button>
			</div>

			<!-- Cost Estimator -->
			<div class="ace-cost-estimate">
				<span class="dashicons dashicons-money-alt"></span>
				<span class="ace-cost-estimate-label"><?php esc_html_e( 'Estimated Cost:', 'tonepress-ai' ); ?></span>
				<span class="ace-cost-estimate-value">$0.0000</span>
			</div>

			<!-- Progress Tracker -->
			<div class="ace-progress-container">
				<div class="ace-progress-bar">
					<div class="ace-progress-fill" style="width: 0%"></div>
				</div>
				<div class="ace-progress-steps">
					<div class="ace-progress-step" data-step="validating">
						<span class="dashicons dashicons-minus"></span>
						<?php esc_html_e( 'Validating inputs...', 'tonepress-ai' ); ?>
					</div>
					<div class="ace-progress-step" data-step="calling_api">
						<span class="dashicons dashicons-minus"></span>
						<?php esc_html_e( 'Calling OpenAI API...', 'tonepress-ai' ); ?>
					</div>
					<div class="ace-progress-step" data-step="processing">
						<span class="dashicons dashicons-minus"></span>
						<?php esc_html_e( 'Processing content...', 'tonepress-ai' ); ?>
					</div>
					<div class="ace-progress-step" data-step="creating_post">
						<span class="dashicons dashicons-minus"></span>
						<?php esc_html_e( 'Creating post...', 'tonepress-ai' ); ?>
					</div>
					<div class="ace-progress-step" data-step="finalizing">
						<span class="dashicons dashicons-minus"></span>
						<?php esc_html_e( 'Finalizing...', 'tonepress-ai' ); ?>
					</div>
				</div>
			</div>

			<form id="ace-generate-form">
				<table class="form-table ace-form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ace-template"><?php esc_html_e( 'Article Template', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<select id="ace-template" name="template_id">
										<?php
										$templates = ACE\Template_Manager::get_templates();
										foreach ( $templates as $id => $template ) {
											printf(
												'<option value="%s">%s</option>',
												esc_attr( $id ),
												esc_html( $template['name'] )
											);
										}
										?>
									</select>
									<p class="description" id="ace-template-description"><?php esc_html_e( 'Select a template to structure your article.', 'tonepress-ai' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="ace-topic"><?php esc_html_e( 'Article Topic', 'tonepress-ai' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<textarea id="ace-topic" name="topic" rows="4" class="large-text" required placeholder="<?php esc_attr_e( 'Enter the topic or title for your article...', 'tonepress-ai' ); ?>"></textarea>
									<p class="description"><?php esc_html_e( 'Describe the topic or subject you want the AI to write about.', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-keywords"><?php esc_html_e( 'Target Keywords', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="text" id="ace-keywords" name="keywords" class="regular-text" placeholder="<?php esc_attr_e( 'keyword1, keyword2, keyword3', 'tonepress-ai' ); ?>" />
									<p class="description"><?php esc_html_e( 'Comma-separated keywords to include in the article (optional).', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-length"><?php esc_html_e( 'Article Length', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<select id="ace-length" name="length">
										<option value="short"><?php esc_html_e( 'Short (800-1200 words)', 'tonepress-ai' ); ?></option>
										<option value="medium" selected><?php esc_html_e( 'Medium (1200-1800 words)', 'tonepress-ai' ); ?></option>
										<option value="long"><?php esc_html_e( 'Long (1800-2500+ words)', 'tonepress-ai' ); ?></option>
									</select>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-tone"><?php esc_html_e( 'Writing Tone', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<select id="ace-tone" name="tone">
										<option value="professional" selected><?php esc_html_e( 'Professional', 'tonepress-ai' ); ?></option>
										<option value="conversational"><?php esc_html_e( 'Conversational', 'tonepress-ai' ); ?></option>
										<option value="authoritative"><?php esc_html_e( 'Authoritative', 'tonepress-ai' ); ?></option>
										<option value="friendly"><?php esc_html_e( 'Friendly', 'tonepress-ai' ); ?></option>
										<option value="academic"><?php esc_html_e( 'Academic', 'tonepress-ai' ); ?></option>
									</select>
								</td>
							</tr>

						<!-- Output Language -->
						<tr>
							<th scope="row">
								<label for="ace-language"><?php esc_html_e( 'Output Language', 'tonepress-ai' ); ?></label>
							</th>
							<td>
								<select id="ace-language" name="language">
									<option value="English" selected><?php esc_html_e( 'English', 'tonepress-ai' ); ?></option>
									<option value="Spanish"><?php esc_html_e( 'Spanish (Español)', 'tonepress-ai' ); ?></option>
									<option value="French"><?php esc_html_e( 'French (Français)', 'tonepress-ai' ); ?></option>
									<option value="German"><?php esc_html_e( 'German (Deutsch)', 'tonepress-ai' ); ?></option>
									<option value="Italian"><?php esc_html_e( 'Italian (Italiano)', 'tonepress-ai' ); ?></option>
									<option value="Portuguese"><?php esc_html_e( 'Portuguese (Português)', 'tonepress-ai' ); ?></option>
									<option value="Dutch"><?php esc_html_e( 'Dutch (Nederlands)', 'tonepress-ai' ); ?></option>
									<option value="Russian"><?php esc_html_e( 'Russian (Русский)', 'tonepress-ai' ); ?></option>
									<option value="Chinese"><?php esc_html_e( 'Chinese (中文)', 'tonepress-ai' ); ?></option>
									<option value="Japanese"><?php esc_html_e( 'Japanese (日本語)', 'tonepress-ai' ); ?></option>
									<option value="Korean"><?php esc_html_e( 'Korean (한국어)', 'tonepress-ai' ); ?></option>
									<option value="Arabic"><?php esc_html_e( 'Arabic (العربية)', 'tonepress-ai' ); ?></option>
									<option value="Hindi"><?php esc_html_e( 'Hindi (हिन्दी)', 'tonepress-ai' ); ?></option>
									<option value="Turkish"><?php esc_html_e( 'Turkish (Türkçe)', 'tonepress-ai' ); ?></option>
									<option value="Polish"><?php esc_html_e( 'Polish (Polski)', 'tonepress-ai' ); ?></option>
									<option value="Swedish"><?php esc_html_e( 'Swedish (Svenska)', 'tonepress-ai' ); ?></option>
									<option value="Norwegian"><?php esc_html_e( 'Norwegian (Norsk)', 'tonepress-ai' ); ?></option>
									<option value="Indonesian"><?php esc_html_e( 'Indonesian (Bahasa)', 'tonepress-ai' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Select the language for the generated article.', 'tonepress-ai' ); ?></p>
							</td>
						</tr>

							<!-- Quick Win: Precise Word Count -->
							<tr>
								<th scope="row">
									<label for="ace-word-count"><?php esc_html_e( 'Custom Word Count', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="number" id="ace-word-count" name="word_count" class="small-text" min="100" max="5000" placeholder="Optional" />
									<p class="description"><?php esc_html_e( 'Specify exact word count (overrides length selection). Leave empty to use length preset.', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Include Features', 'tonepress-ai' ); ?></th>
								<td>
									<label>
										<input type="checkbox" id="ace-include-tables" name="include_tables" value="1" />
										<?php esc_html_e( 'Include Tables', 'tonepress-ai' ); ?>
									</label>
									<br />
									<label>
										<input type="checkbox" id="ace-include-charts" name="include_charts" value="1" />
										<?php esc_html_e( 'Include Charts', 'tonepress-ai' ); ?>
									</label>
									<br />
									<label>
										<input type="checkbox" id="ace-generate-featured-image" name="generate_featured_image" value="1" />
										<?php esc_html_e( 'Generate Featured Image (DALL-E)', 'tonepress-ai' ); ?>
									</label>
									<br />
									<label>
										<input type="checkbox" id="ace-generate-inline-images" name="generate_inline_images" value="1" />
										<?php esc_html_e( 'Generate Inline Images (DALL-E)', 'tonepress-ai' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Select what elements to include in the generated article.', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<!-- Advanced Options Accordion -->
							<tr>
								<th colspan="2">
									<h3 class="ace-accordion-toggle" style="cursor: pointer; margin: 20px 0 10px 0;">
										<span class="dashicons dashicons-arrow-right" style="transition: transform 0.2s;"></span>
										<?php esc_html_e( 'Advanced Options', 'tonepress-ai' ); ?>
									</h3>
								</th>
							</tr>
						</tbody>
						<tbody id="ace-advanced-options" style="display: none;">
							<!-- Quick Win: Temperature Slider -->
							<tr>
								<th scope="row">
									<label for="ace-temperature"><?php esc_html_e( 'Creativity (Temperature)', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="range" id="ace-temperature" name="temperature" min="0" max="1" step="0.1" value="0.7" />
									<span id="ace-temperature-value">0.7</span>
									<p class="description"><?php esc_html_e( 'Lower = more focused and deterministic, Higher = more creative and varied (0.0 - 1.0)', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<!-- Quick Win: Max Tokens Slider -->
							<tr>
								<th scope="row">
									<label for="ace-max-tokens"><?php esc_html_e( 'Max Tokens', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="range" id="ace-max-tokens" name="max_tokens" min="1000" max="4000" step="100" value="3000" />
									<span id="ace-max-tokens-value">3000</span>
									<p class="description"><?php esc_html_e( 'Maximum tokens for generation (controls cost and maximum length)', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<!-- Quick Win: Custom Post Type -->
							<tr>
								<th scope="row">
									<label for="ace-post-type"><?php esc_html_e( 'Post Type', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<select id="ace-post-type" name="post_type">
										<option value="post" selected><?php esc_html_e( 'Post', 'tonepress-ai' ); ?></option>
										<option value="page"><?php esc_html_e( 'Page', 'tonepress-ai' ); ?></option>
										<?php
										// Get custom post types
										$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
										foreach ( $post_types as $post_type ) {
											echo '<option value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . '</option>';
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Select post type for the generated content', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<!-- Quick Win: Category Assignment -->
							<tr>
								<th scope="row">
									<label for="ace-categories"><?php esc_html_e( 'Categories', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<?php
									wp_dropdown_categories(
										array(
											'name'             => 'categories[]',
											'id'               => 'ace-categories',
											'class'            => 'widefat',
											'hide_empty'       => false,
											'hierarchical'     => true,
											'show_option_all'  => __( 'Auto-assign based on keywords', 'tonepress-ai' ),
											'show_option_none' => '',
										)
									);
									?>
									<p class="description"><?php esc_html_e( 'Select category or leave at default for auto-assignment', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<!-- Quick Win: Tag Auto-generation -->
							<tr>
								<th scope="row">
									<label for="ace-auto-tags"><?php esc_html_e( 'Auto-Generate Tags', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="ace-auto-tags" name="auto_tags" value="1" checked />
										<?php esc_html_e( 'Automatically generate tags from keywords and content', 'tonepress-ai' ); ?>
									</label>
								</td>
							</tr>

							<!-- Quick Win: Custom AI Instructions -->
							<tr>
								<th scope="row">
									<label for="ace-custom-instructions"><?php esc_html_e( 'Custom Instructions', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<textarea id="ace-custom-instructions" name="custom_instructions" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Add custom instructions for this article...', 'tonepress-ai' ); ?>"></textarea>
									<p class="description"><?php esc_html_e( 'Optional additional instructions for AI (e.g., "Include examples from the healthcare industry")', 'tonepress-ai' ); ?></p>
								</td>
							</tr>
						</tbody>
						<tbody>
							<tr>
								<th scope="row">
									<label for="ace-post-status"><?php esc_html_e( 'Post Status', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<select id="ace-post-status" name="post_status">
										<option value="draft" selected><?php esc_html_e( 'Save as Draft', 'tonepress-ai' ); ?></option>
										<option value="publish"><?php esc_html_e( 'Publish Immediately', 'tonepress-ai' ); ?></option>
										<option value="future"><?php esc_html_e( 'Schedule for Later', 'tonepress-ai' ); ?></option>
									</select>
								</td>
							</tr>

							<tr id="ace-schedule-row" style="display: none;">
								<th scope="row">
									<label for="ace-post-date"><?php esc_html_e( 'Publish Date', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="datetime-local" id="ace-post-date" name="post_date" />
								</td>
							</tr>
						</tbody>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary button-large" id="ace-generate-btn">
							<?php esc_html_e( 'Generate Article', 'tonepress-ai' ); ?>
						</button>
						<span class="spinner" id="ace-spinner"></span>
					</p>
				</form>

				<div id="ace-result" class="ace-result" style="display: none;">
					<h3><?php esc_html_e( 'Generation Result', 'tonepress-ai' ); ?></h3>
					<div id="ace-result-content"></div>
				</div>
			</div>

		<?php elseif ( 'bulk' === $current_tab ) : ?>
			<!-- Bulk Generation Tab -->
			<div class="ace-bulk-tab">
				<h2><?php esc_html_e( 'Bulk Article Generation', 'tonepress-ai' ); ?></h2>
				<p><?php esc_html_e( 'Upload a CSV file to generate multiple articles automatically. The system will process articles in the background.', 'tonepress-ai' ); ?></p>

				<!-- CSV Upload Form -->
				<div class="ace-form-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload CSV File', 'tonepress-ai' ); ?></h3>
					</div>
					
					<form id="ace-bulk-upload-form" enctype="multipart/form-data">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="ace-csv-file"><?php esc_html_e( 'CSV File', 'tonepress-ai' ); ?> <span class="required">*</span></label>
									</th>
									<td>
										<input type="file" id="ace-csv-file" name="csv_file" accept=".csv" required />
										<p class="description">
											<?php esc_html_e( 'Upload a CSV file with article topics and options.', 'tonepress-ai' ); ?>
											<a href="<?php echo esc_url( admin_url( 'tools.php?page=tonepress-ai&action=download_template' ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Download Template', 'tonepress-ai' ); ?>
											</a>
										</p>
									</td>
								</tr>
							</tbody>
						</table>

						<p class="submit">
							<button type="submit" class="button button-primary button-large" id="ace-bulk-upload-btn">
								<?php esc_html_e( 'Upload and Start Generation', 'tonepress-ai' ); ?>
							</button>
						</p>
					</form>
				</div>

				<!-- Queue Dashboard -->
				<div class="ace-form-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Generation Queues', 'tonepress-ai' ); ?></h3>
					</div>

					<div id="ace-queue-list">
						<?php
						$bulk_generator = new ACE\Bulk_Generator();
						$queues = $bulk_generator->get_all_queues();

						if ( empty( $queues ) ) :
							?>
							<p><?php esc_html_e( 'No queues found. Upload a CSV file to get started.', 'tonepress-ai' ); ?></p>
						<?php else : ?>
							<table class="widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Queue ID', 'tonepress-ai' ); ?></th>
										<th><?php esc_html_e( 'Created', 'tonepress-ai' ); ?></th>
										<th><?php esc_html_e( 'Progress', 'tonepress-ai' ); ?></th>
										<th><?php esc_html_e( 'Status', 'tonepress-ai' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'tonepress-ai' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( array_reverse( $queues ) as $queue_id => $queue ) : ?>
										<tr>
											<td><code><?php echo esc_html( $queue_id ); ?></code></td>
											<td><?php echo esc_html( $queue['created'] ); ?></td>
											<td>
												<?php
												$progress = ( $queue['completed'] + $queue['failed'] ) / $queue['total'] * 100;
												printf(
													'<div class="ace-progress-bar" style="max-width: 200px;"><div class="ace-progress-fill" style="width: %d%%"></div></div>',
													(int) $progress
												);
												printf(
													'<small>%d / %d (%d succeeded, %d failed)</small>',
													$queue['completed'] + $queue['failed'],
													$queue['total'],
													$queue['completed'],
													$queue['failed']
												);
												?>
											</td>
											<td>
												<?php
												$status_colors = array(
													'pending'    => '#f0b849',
													'processing' => '#2271b1',
													'paused'     => '#646970',
													'completed'  => '#00a32a',
												);
												$color = $status_colors[ $queue['status'] ] ?? '#646970';
												printf(
													'<span style="color: %s; font-weight: 600;">%s</span>',
													esc_attr( $color ),
													esc_html( ucfirst( $queue['status'] ) )
												);
												?>
											</td>
											<td>
												<?php if ( 'processing' === $queue['status'] || 'pending' === $queue['status'] ) : ?>
													<button class="button ace-pause-queue" data-queue="<?php echo esc_attr( $queue_id ); ?>">
														<?php esc_html_e( 'Pause', 'tonepress-ai' ); ?>
													</button>
												<?php elseif ( 'paused' === $queue['status'] ) : ?>
													<button class="button ace-resume-queue" data-queue="<?php echo esc_attr( $queue_id ); ?>">
														<?php esc_html_e( 'Resume', 'tonepress-ai' ); ?>
													</button>
												<?php endif; ?>

												<?php if ( 'completed' === $queue['status'] ) : ?>
													<a href="<?php echo esc_url( admin_url( 'tools.php?page=tonepress-ai&action=export_results&queue=' . $queue_id ) ); ?>" class="button">
														<?php esc_html_e( 'Export Results', 'tonepress-ai' ); ?>
													</a>
												<?php endif; ?>

												<button class="button ace-delete-queue" data-queue="<?php echo esc_attr( $queue_id ); ?>">
													<?php esc_html_e( 'Delete', 'tonepress-ai' ); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- CSV Format Guide -->
				<div class="ace-form-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'CSV Format Guide', 'tonepress-ai' ); ?></h3>
					</div>

					<p><?php esc_html_e( 'Your CSV file should include the following columns:', 'tonepress-ai' ); ?></p>

					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Column', 'tonepress-ai' ); ?></th>
								<th><?php esc_html_e( 'Required', 'tonepress-ai' ); ?></th>
								<th><?php esc_html_e( 'Description', 'tonepress-ai' ); ?></th>
								<th><?php esc_html_e( 'Example', 'tonepress-ai' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><code>topic</code></td>
								<td><strong><?php esc_html_e( 'Yes', 'tonepress-ai' ); ?></strong></td>
								<td><?php esc_html_e( 'Article topic or title', 'tonepress-ai' ); ?></td>
								<td>How to Start a Blog</td>
							</tr>
							<tr>
								<td><code>keywords</code></td>
								<td><?php esc_html_e( 'No', 'tonepress-ai' ); ?></td>
								<td><?php esc_html_e( 'Target keywords (comma-separated)', 'tonepress-ai' ); ?></td>
								<td>blogging,WordPress,tutorial</td>
							</tr>
							<tr>
								<td><code>length</code></td>
								<td><?php esc_html_e( 'No', 'tonepress-ai' ); ?></td>
								<td><?php esc_html_e( 'short, medium, or long', 'tonepress-ai' ); ?></td>
								<td>long</td>
							</tr>
							<tr>
								<td><code>template_id</code></td>
								<td><?php esc_html_e( 'No', 'tonepress-ai' ); ?></td>
								<td><?php esc_html_e( 'Template: default, how_to, review, comparison, listicle', 'tonepress-ai' ); ?></td>
								<td>how_to</td>
							</tr>
							<tr>
								<td><code>post_status</code></td>
								<td><?php esc_html_e( 'No', 'tonepress-ai' ); ?></td>
								<td><?php esc_html_e( 'draft, publish, or future', 'tonepress-ai' ); ?></td>
								<td>draft</td>
							</tr>
						</tbody>
					</table>

					<p>
						<a href="<?php echo esc_url( admin_url( 'tools.php?page=tonepress-ai&action=download_template' ) ); ?>" class="button button-primary">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Download CSV Template', 'tonepress-ai' ); ?>
						</a>
					</p>
				</div>
			</div>
			</div>

	
	<?php elseif ( 'templates' === $current_tab ) : ?>
		<?php require_once ACE_PLUGIN_DIR . 'templates/admin-page-templates.php'; ?>

	<?php elseif ( 'history' === $current_tab ) : ?>
			<!-- History Tab -->
			<div class="ace-history-tab">
				<h2><?php esc_html_e( 'Generation History', 'tonepress-ai' ); ?></h2>

				<?php
				global $wpdb;
				
				// Get pagination
				$paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
				$per_page = 20;
				$offset = ( $paged - 1 ) * $per_page;

				// Get total count
				$total_articles = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ace_generated' AND meta_value = '1'"
				);

				// Get total cost
				$total_cost = $wpdb->get_var(
					"SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_ace_estimated_cost'"
				) ?: 0;

				// Get total tokens
				$token_data = $wpdb->get_results(
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_ace_token_usage'"
				);
				$total_tokens = 0;
				foreach ( $token_data as $row ) {
					$usage = maybe_unserialize( $row->meta_value );
					$total_tokens += $usage['total_tokens'] ?? 0;
				}

				// Get articles for current page
				$generated_posts = get_posts(
					array(
						'meta_key'       => '_ace_generated',
						'meta_value'     => '1',
						'numberposts'    => $per_page,
						'offset'         => $offset,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'post_type'      => 'any',
						'post_status'    => 'any',
					)
				);

				$total_pages = ceil( $total_articles / $per_page );
				?>

				<!-- Summary Cards -->
				<div class="ace-history-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
					<div class="ace-form-card" style="margin-bottom: 0;">
						<div style="font-size: 32px; font-weight: 700; color: #2271b1;"><?php echo esc_html( $total_articles ); ?></div>
						<div style="color: #646970;"><?php esc_html_e( 'Total Articles', 'tonepress-ai' ); ?></div>
					</div>
					<div class="ace-form-card" style="margin-bottom: 0;">
						<div style="font-size: 32px; font-weight: 700; color: #00a32a;">$<?php echo esc_html( number_format( $total_cost, 2 ) ); ?></div>
						<div style="color: #646970;"><?php esc_html_e( 'Total Cost', 'tonepress-ai' ); ?></div>
					</div>
					<div class="ace-form-card" style="margin-bottom: 0;">
						<div style="font-size: 32px; font-weight: 700; color: #8c5e15;"><?php echo esc_html( number_format( $total_tokens ) ); ?></div>
						<div style="color: #646970;"><?php esc_html_e( 'Total Tokens', 'tonepress-ai' ); ?></div>
					</div>
					<div class="ace-form-card" style="margin-bottom: 0;">
						<div style="font-size: 32px; font-weight: 700; color: #646970;">$<?php echo esc_html( $total_articles > 0 ? number_format( $total_cost / $total_articles, 3 ) : '0.00' ); ?></div>
						<div style="color: #646970;"><?php esc_html_e( 'Avg Cost/Article', 'tonepress-ai' ); ?></div>
					</div>
				</div>

				<!-- Articles Table -->
				<div class="ace-form-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Generated Articles', 'tonepress-ai' ); ?></h3>
					</div>

					<?php if ( empty( $generated_posts ) ) : ?>
						<p><?php esc_html_e( 'No AI-generated articles found.', 'tonepress-ai' ); ?></p>
					<?php else : ?>
						<table class="widefat fixed striped">
							<thead>
								<tr>
									<th style="width: 35%;"><?php esc_html_e( 'Title', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Type', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Status', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Date', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Tokens', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Cost', 'tonepress-ai' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'tonepress-ai' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $generated_posts as $post ) : 
									$usage = get_post_meta( $post->ID, '_ace_token_usage', true );
									$cost = get_post_meta( $post->ID, '_ace_estimated_cost', true );
									$model = get_post_meta( $post->ID, '_ace_model', true );
									$tokens = $usage['total_tokens'] ?? 0;
									
									$status_colors = array(
										'publish' => '#00a32a',
										'draft'   => '#646970',
										'pending' => '#f0b849',
										'future'  => '#2271b1',
									);
									$status_color = $status_colors[ $post->post_status ] ?? '#646970';
								?>
									<tr>
										<td>
											<strong><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></strong>
											<?php if ( $model ) : ?>
												<br><small style="color: #646970;"><?php echo esc_html( $model ); ?></small>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( get_post_type_object( $post->post_type )->labels->singular_name ); ?></td>
										<td><span style="color: <?php echo esc_attr( $status_color ); ?>; font-weight: 600;"><?php echo esc_html( ucfirst( $post->post_status ) ); ?></span></td>
										<td><?php echo esc_html( get_the_date( 'M j, Y', $post ) ); ?></td>
										<td><?php echo esc_html( number_format( $tokens ) ); ?></td>
										<td>$<?php echo esc_html( number_format( $cost, 4 ) ); ?></td>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'tonepress-ai' ); ?></a>
											<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="button button-small" target="_blank"><?php esc_html_e( 'View', 'tonepress-ai' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<!-- Pagination -->
						<?php if ( $total_pages > 1 ) : ?>
							<div class="tablenav bottom" style="margin-top: 16px;">
								<div class="tablenav-pages">
									<span class="displaying-num"><?php printf( esc_html__( '%s items', 'tonepress-ai' ), number_format( $total_articles ) ); ?></span>
									<span class="pagination-links">
										<?php if ( $paged > 1 ) : ?>
											<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1 ) ); ?>">‹</a>
										<?php endif; ?>
										<span class="paging-input">
											<?php printf( esc_html__( '%1$s of %2$s', 'tonepress-ai' ), $paged, $total_pages ); ?>
										</span>
										<?php if ( $paged < $total_pages ) : ?>
											<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1 ) ); ?>">›</a>
										<?php endif; ?>
									</span>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

		<?php elseif ( 'settings' === $current_tab ) : ?>
		<!-- Settings Tab - Organized Layout -->
		<div class="ace-settings-wrapper">
			<form method="post" action="options.php">
				<?php settings_fields( 'ace_settings' ); ?>

				<!-- API Configuration Section -->
				<div class="ace-form-card ace-settings-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'API Configuration', 'tonepress-ai' ); ?></h3>
						<p class="ace-card-desc"><?php esc_html_e( 'Configure your AI provider API keys and select your preferred model.', 'tonepress-ai' ); ?></p>
					</div>
					<div class="ace-settings-card-body">
						<table class="form-table" role="presentation">
							<tbody>
								<?php do_settings_fields( 'tonepress-ai', 'ace_api_settings' ); ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Default Settings Section -->
				<div class="ace-form-card ace-settings-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-edit-page"></span> <?php esc_html_e( 'Default Article Settings', 'tonepress-ai' ); ?></h3>
						<p class="ace-card-desc"><?php esc_html_e( 'Set default values for new article generation.', 'tonepress-ai' ); ?></p>
					</div>
					<div class="ace-settings-card-body">
						<table class="form-table" role="presentation">
							<tbody>
								<?php do_settings_fields( 'tonepress-ai', 'ace_default_settings' ); ?>
							</tbody>
						</table>
					</div>
				</div>


			<?php require_once ACE_PLUGIN_DIR . 'templates/company-profile-section.php'; ?>

				<!-- Performance & Security Section -->
				<div class="ace-form-card ace-settings-card">
					<div class="ace-form-card-header">
						<h3><span class="dashicons dashicons-shield-alt"></span> <?php esc_html_e( 'Performance & Security', 'tonepress-ai' ); ?></h3>
						<p class="ace-card-desc"><?php esc_html_e( 'Optimize performance and control usage limits.', 'tonepress-ai' ); ?></p>
					</div>
					<div class="ace-settings-card-body">
						<table class="form-table" role="presentation">
							<tbody>
								<?php do_settings_fields( 'tonepress-ai', 'ace_performance_settings' ); ?>
							</tbody>
						</table>
					</div>
				</div>



				<!-- Submit Button -->
				<div class="ace-settings-submit">
					<?php submit_button( __( 'Save Settings', 'tonepress-ai' ), 'primary large', 'submit', false ); ?>
					<span class="ace-submit-hint">
						<span class="dashicons dashicons-lock"></span>
						<?php esc_html_e( 'API keys are encrypted before storage', 'tonepress-ai' ); ?>
					</span>
				</div>
			</form>
		</div>

		<?php elseif ( 'stats' === $current_tab ) : ?>
			<!-- Statistics Tab -->
			<h2><?php esc_html_e( 'Usage Statistics', 'tonepress-ai' ); ?></h2>

			<?php
			// Get statistics.
			global $wpdb;

			$total_generated = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ace_generated' AND meta_value = '1'"
			);

			$rate_limit_count = ACE\Cache_Manager::get_rate_limit_count();
			$rate_limit_max   = ACE_MAX_REQUESTS_PER_HOUR;

			$recent_posts = get_posts(
				array(
					'meta_key'    => '_ace_generated',
					'meta_value'  => '1',
					'numberposts' => 10,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
			?>

			<table class="widefat fixed striped">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Total AI-Generated Articles', 'tonepress-ai' ); ?></th>
						<td><strong><?php echo esc_html( $total_generated ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Your Current Hour Usage', 'tonepress-ai' ); ?></th>
						<td><strong><?php echo esc_html( $rate_limit_count ); ?></strong> / <?php echo esc_html( $rate_limit_max ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Active SEO Plugin', 'tonepress-ai' ); ?></th>
						<td>
							<?php
							$seo = new ACE\SEO_Integrations();
							$seo->init();
							$plugin = $seo->get_active_plugin();

							if ( 'yoast' === $plugin ) {
								esc_html_e( 'Yoast SEO', 'tonepress-ai' );
							} elseif ( 'rankmath' === $plugin ) {
								esc_html_e( 'RankMath', 'tonepress-ai' );
							} else {
								esc_html_e( 'None (using fallback meta)', 'tonepress-ai' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( ! empty( $recent_posts ) ) : ?>
				<h3><?php esc_html_e( 'Recently Generated Articles', 'tonepress-ai' ); ?></h3>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'tonepress-ai' ); ?></th>
							<th><?php esc_html_e( 'Date', 'tonepress-ai' ); ?></th>
							<th><?php esc_html_e( 'Tokens Used', 'tonepress-ai' ); ?></th>
							<th><?php esc_html_e( 'Est. Cost', 'tonepress-ai' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'tonepress-ai' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_posts as $post ) : ?>
							<?php
							$usage = get_post_meta( $post->ID, '_ace_token_usage', true );
							$cost  = get_post_meta( $post->ID, '_ace_estimated_cost', true );
							?>
							<tr>
								<td><strong><?php echo esc_html( $post->post_title ); ?></strong></td>
								<td><?php echo esc_html( get_the_date( '', $post ) ); ?></td>
								<td><?php echo esc_html( $usage['total_tokens'] ?? 'N/A' ); ?></td>
								<td>$<?php echo esc_html( number_format( $cost, 4 ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php esc_html_e( 'Edit', 'tonepress-ai' ); ?></a> |
									<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'tonepress-ai' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		<?php endif; ?>
	</div>
</div>

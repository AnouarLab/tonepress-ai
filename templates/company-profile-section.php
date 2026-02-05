			</div>

			<!-- Company Profile Section -->
			<div class="ace-form-card ace-settings-card">
				<div class="ace-form-card-header">
					<h3><span class="dashicons dashicons-building"></span> <?php esc_html_e( 'Company Profile', 'tonepress-ai' ); ?></h3>
					<p class="ace-card-desc"><?php esc_html_e( 'Define your company context to ensure consistent brand voice across all generated content. This information will be automatically included in article generation prompts.', 'tonepress-ai' ); ?></p>
				</div>
				<div class="ace-settings-card-body">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="ace-enable-company-context"><?php esc_html_e( 'Enable Company Context', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="ace-enable-company-context" name="ace_enable_company_context" value="1" <?php checked( get_option( 'ace_enable_company_context', true ) ); ?> />
										<?php esc_html_e( 'Automatically include company context in all article prompts', 'tonepress-ai' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'When enabled, the information below will be added to generation prompts to maintain brand consistency.', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-company-name"><?php esc_html_e( 'Company Name', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="text" id="ace-company-name" name="ace_company_name" value="<?php echo esc_attr( get_option( 'ace_company_name', '' ) ); ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Your company or brand name', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-company-industry"><?php esc_html_e( 'Industry', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="text" id="ace-company-industry" name="ace_company_industry" value="<?php echo esc_attr( get_option( 'ace_company_industry', '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., SaaS, E-commerce, Healthcare', 'tonepress-ai' ); ?>" />
									<p class="description"><?php esc_html_e( 'Your industry or sector', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-company-description"><?php esc_html_e( 'Company Description', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<textarea id="ace-company-description" name="ace_company_description" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Brief description of what your company does...', 'tonepress-ai' ); ?>"><?php echo esc_textarea( get_option( 'ace_company_description', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'A concise description of your business (1-2 sentences)', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-target-audience"><?php esc_html_e( 'Target Audience', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="text" id="ace-target-audience" name="ace_target_audience" value="<?php echo esc_attr( get_option( 'ace_target_audience', '' ) ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g., Small business owners, Enterprise IT managers', 'tonepress-ai' ); ?>" />
									<p class="description"><?php esc_html_e( 'Who is your primary audience?', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-key-products"><?php esc_html_e( 'Key Products/Services', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<textarea id="ace-key-products" name="ace_key_products" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'List your main products or services...', 'tonepress-ai' ); ?>"><?php echo esc_textarea( get_option( 'ace_key_products', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Main products or services you want to highlight in content', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-brand-values"><?php esc_html_e( 'Brand Values', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<input type="text" id="ace-brand-values" name="ace_brand_values" value="<?php echo esc_attr( get_option( 'ace_brand_values', '' ) ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g., Innovation, Transparency, Customer-first', 'tonepress-ai' ); ?>" />
									<p class="description"><?php esc_html_e( 'Core values that guide your company (comma-separated)', 'tonepress-ai' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="ace-brand-voice"><?php esc_html_e( 'Brand Voice & Tone', 'tonepress-ai' ); ?></label>
								</th>
								<td>
									<textarea id="ace-brand-voice" name="ace_brand_voice" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Describe your brand voice and tone...', 'tonepress-ai' ); ?>"><?php echo esc_textarea( get_option( 'ace_brand_voice', '' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'How should your brand sound? (e.g., "Professional yet approachable, data-driven but human")', 'tonepress-ai' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Performance & Security Section -->

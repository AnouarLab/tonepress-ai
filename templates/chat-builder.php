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
 * Chat Builder Admin Page Template
 *
 * @package AI_Content_Engine
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get chat builder instance.
$chat_builder = new Chat_Builder();
$sessions     = $chat_builder->get_user_sessions( 20 );
$stats        = $chat_builder->get_stats();
?>

<div class="wrap ace-chat-wrap">
	<div class="ace-chat-builder ace-editor-first">
		<!-- Sidebar - Session History -->
		<div class="ace-chat-sidebar">
			<div class="ace-sidebar-header">
				<button type="button" class="ace-new-chat-btn" id="ace-new-chat-btn">
					<span class="dashicons dashicons-plus-alt2"></span>
					<span>New Chat</span>
				</button>
			</div>
			<div class="ace-sidebar-tabs">
				<button type="button" class="ace-tab-btn is-active" data-tab="sessions">Sessions</button>
			</div>
			
			<div class="ace-session-list" id="ace-session-list">
				<?php if ( empty( $sessions ) ) : ?>
					<div class="ace-no-sessions">
						No previous chats
					</div>
				<?php else : ?>
					<?php foreach ( $sessions as $session ) : ?>
						<div class="ace-session-item <?php echo ! empty( $session->pinned ) ? 'is-pinned' : ''; ?>" data-session-id="<?php echo esc_attr( $session->session_id ); ?>">
							<div class="ace-session-title"><?php echo esc_html( $session->title ); ?></div>
							<div class="ace-session-meta">
								<div class="ace-session-date">
									<?php echo esc_html( date_i18n( 'M j', strtotime( $session->updated_at ) ) ); ?>
									<span class="ace-session-status <?php echo esc_attr( $session->status ); ?>">
										<?php echo esc_html( $session->status ); ?>
									</span>
								</div>
								<div class="ace-session-actions">
									<button type="button" class="ace-session-action ace-session-pin" data-action="pin" title="<?php echo ! empty( $session->pinned ) ? esc_attr__( 'Unpin', 'tonepress-ai' ) : esc_attr__( 'Pin', 'tonepress-ai' ); ?>" data-pinned="<?php echo ! empty( $session->pinned ) ? 1 : 0; ?>">
										<span class="dashicons dashicons-star-filled"></span>
									</button>
									<button type="button" class="ace-session-action" data-action="rename" title="Rename">
										<span class="dashicons dashicons-edit"></span>
									</button>
									<button type="button" class="ace-session-action" data-action="delete" title="Delete">
										<span class="dashicons dashicons-trash"></span>
									</button>
									<button type="button" class="ace-session-action" data-action="duplicate" title="Duplicate">
										<span class="dashicons dashicons-admin-page"></span>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			
			
			<!-- Sidebar Stats -->
			<div class="ace-sidebar-stats">
				<div class="ace-sidebar-stats-row">
					<span>Total: <?php echo esc_html( $stats['total'] ); ?></span>
					<span>Active: <?php echo esc_html( $stats['active'] ); ?></span>
				</div>
			</div>
		</div>

		<!-- Main Content Area -->
		<div class="ace-chat-main">
			
			<!-- Chat Panel -->
			<div class="ace-chat-panel ace-chat-drawer is-collapsed" id="ace-chat-drawer">
				<div class="ace-chat-header">
					<button type="button" class="ace-btn ace-btn-secondary ace-toggle-sidebar" id="ace-toggle-sidebar" title="Toggle sessions sidebar">
						<span class="dashicons dashicons-menu-alt3"></span>
					</button>
					<button type="button" class="ace-btn ace-btn-secondary ace-toggle-fullscreen" id="ace-toggle-fullscreen" title="Toggle fullscreen">
						<span class="dashicons dashicons-fullscreen-alt"></span>
					</button>
					<h2 class="ace-chat-title">AI Article Builder</h2>
					<span class="ace-professional-badge is-hidden" id="ace-professional-badge">Professional</span>
					<div class="ace-chat-status" id="ace-chat-status">
						<span class="ace-status-dot"></span>
						<span class="ace-status-text">Ready</span>
					</div>
					<div class="ace-chat-actions">
						<button type="button" class="ace-btn ace-btn-icon ace-toggle-preview" id="ace-toggle-preview" title="Toggle Preview">
							<span class="dashicons dashicons-visibility"></span>
						</button>
						<?php
						$provider_id = get_option( 'ace_ai_provider', 'openai' );
						$provider    = \ACE\Provider_Factory::get( $provider_id );
						$models      = ! is_wp_error( $provider ) ? $provider->get_available_models() : array();
						$enabled     = get_option( 'ace_' . $provider_id . '_enabled_models', array() );
						if ( ! empty( $enabled ) ) {
							$models = array_intersect_key( $models, array_flip( $enabled ) );
						}
						?>
						<select id="ace-chat-model" class="ace-model-select" title="Select AI Model">
							<?php foreach ( $models as $model_id => $label ) : ?>
								<option value="<?php echo esc_attr( $model_id ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="ace-btn ace-btn-icon" id="ace-settings-open" title="Settings">
							<span class="dashicons dashicons-admin-generic"></span>
						</button>
					</div>
				</div>
				<div class="ace-session-summary is-hidden" id="ace-session-summary">
					<div class="ace-summary-item">
						<span class="ace-summary-label">Title</span>
						<span class="ace-summary-value ace-summary-title">New Chat</span>
					</div>
					<div class="ace-summary-item">
						<span class="ace-summary-label">Model</span>
						<span class="ace-summary-value ace-summary-model">Default</span>
					</div>
					<div class="ace-summary-item">
						<span class="ace-summary-label">Words</span>
						<span class="ace-summary-value ace-summary-words">0</span>
					</div>
					<div class="ace-summary-item">
						<span class="ace-summary-label">Updated</span>
						<span class="ace-summary-value ace-summary-updated">—</span>
					</div>
					<div class="ace-summary-item ace-summary-autosave">
						<span class="ace-summary-label">Autosave</span>
						<span class="ace-summary-value ace-autosave-status" id="ace-autosave-status">Ready</span>
					</div>
				</div>

				<!-- Welcome Screen (shown initially) -->
				<div class="ace-welcome-screen" id="ace-welcome-screen">
					<div class="ace-welcome-icon">✨</div>
					<h2 class="ace-welcome-title">AI Article Builder</h2>
					<p class="ace-welcome-subtitle">
						Create amazing articles through conversation. Just tell me what you want to write about!
					</p>
					
					<div class="ace-starter-prompts">
						<div class="ace-starter-prompt" data-prompt="Write a comprehensive guide about cloud security best practices for small businesses">
							<div class="ace-starter-prompt-icon">🔒</div>
							<div class="ace-starter-prompt-text">Guide about cloud security best practices</div>
						</div>
						<div class="ace-starter-prompt" data-prompt="Create an SEO-optimized article about sustainable living tips for beginners">
							<div class="ace-starter-prompt-icon">🌱</div>
							<div class="ace-starter-prompt-text">Article about sustainable living tips</div>
						</div>
						<div class="ace-starter-prompt" data-prompt="Write a how-to tutorial on setting up a WordPress website from scratch">
							<div class="ace-starter-prompt-icon">📝</div>
							<div class="ace-starter-prompt-text">Tutorial on setting up WordPress</div>
						</div>
						<div class="ace-starter-prompt" data-prompt="Create an informative article about the future of artificial intelligence in healthcare">
							<div class="ace-starter-prompt-icon">🏥</div>
							<div class="ace-starter-prompt-text">AI in healthcare article</div>
						</div>
					</div>
				</div>

				<!-- Chat Messages Area (hidden initially) -->
				<div class="ace-chat-panel-content" id="ace-chat-panel-content">
					<div class="ace-chat-messages" id="ace-chat-messages">
						<!-- Messages will be inserted here -->
					</div>

					<!-- Input Area -->
					<div class="ace-chat-input-area">
						<div class="ace-chat-tools is-hidden" id="ace-chat-tools">
							<div class="ace-quick-controls">
								<div class="ace-control">
									<label for="ace-tone-select">Tone</label>
									<select id="ace-tone-select">
										<option value="professional">Professional</option>
										<option value="conversational">Conversational</option>
										<option value="authoritative">Authoritative</option>
										<option value="friendly">Friendly</option>
										<option value="academic">Academic</option>
									</select>
									<button type="button" class="ace-control-btn" id="ace-apply-tone">Apply</button>
								</div>
								<div class="ace-control">
									<label for="ace-length-range">Length</label>
									<input type="range" id="ace-length-range" min="800" max="2500" step="100" value="1200" />
									<span class="ace-range-value" id="ace-length-value">1200</span>
									<button type="button" class="ace-control-btn" id="ace-apply-length">Apply</button>
								</div>
								<div class="ace-control">
									<label>Outline</label>
									<button type="button" class="ace-toggle-btn" id="ace-outline-toggle" data-enabled="0">Off</button>
								</div>
							</div>

							<div class="ace-prompt-builder">
								<button type="button" class="ace-builder-toggle" id="ace-builder-toggle">
									<span class="dashicons dashicons-welcome-write-blog"></span>
									Prompt Builder
								</button>
								<div class="ace-builder-panel" id="ace-builder-panel" style="display:none;">
									<div class="ace-builder-grid">
										<div class="ace-builder-field">
											<label for="ace-builder-topic">Topic</label>
											<textarea id="ace-builder-topic" rows="2" placeholder="What should the article be about?"></textarea>
										</div>
										<div class="ace-builder-field">
											<label for="ace-builder-audience">Audience</label>
											<input type="text" id="ace-builder-audience" placeholder="Who is this for?" />
										</div>
										<div class="ace-builder-field">
											<label for="ace-builder-goal">Goal</label>
											<input type="text" id="ace-builder-goal" placeholder="What should readers do/learn?" />
										</div>
										<div class="ace-builder-field">
											<label for="ace-builder-keywords">Keywords</label>
											<input type="text" id="ace-builder-keywords" placeholder="keyword1, keyword2" />
										</div>
										<div class="ace-builder-field">
											<label for="ace-builder-tone">Tone</label>
											<select id="ace-builder-tone">
												<option value="professional">Professional</option>
												<option value="conversational">Conversational</option>
												<option value="authoritative">Authoritative</option>
												<option value="friendly">Friendly</option>
												<option value="academic">Academic</option>
											</select>
										</div>
										<div class="ace-builder-field">
											<label for="ace-builder-length">Length</label>
											<select id="ace-builder-length">
												<option value="short">Short (800-1200)</option>
												<option value="medium" selected>Medium (1200-1800)</option>
												<option value="long">Long (1800-2500+)</option>
											</select>
										</div>
										<div class="ace-builder-field">
											<label class="ace-builder-checkbox">
												<input type="checkbox" id="ace-builder-faq" />
												Include FAQ
											</label>
											<label class="ace-builder-checkbox">
												<input type="checkbox" id="ace-builder-examples" />
												Include examples
											</label>
										</div>
									</div>
									<div class="ace-builder-actions">
										<button type="button" class="ace-btn ace-btn-secondary" id="ace-builder-send">
											<span class="dashicons dashicons-send"></span>
											Send Prompt
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="ace-input-actions">
							<button type="button" class="ace-btn ace-btn-secondary ace-professional-toggle" id="ace-professional-toggle">
								Professional
							</button>
							<button type="button" class="ace-btn ace-btn-secondary ace-quick-toggle" id="ace-quick-toggle">
								Quick actions
							</button>
						</div>
						<div class="ace-quick-actions is-hidden" id="ace-quick-actions">
							<button type="button" class="ace-quick-btn" data-action="Make it longer and more detailed">📝 Expand</button>
							<button type="button" class="ace-quick-btn" data-action="Add more examples and case studies">💡 Add Examples</button>
							<button type="button" class="ace-quick-btn" data-action="Make it simpler and easier to understand">✨ Simplify</button>
							<button type="button" class="ace-quick-btn" data-action="Add a FAQ section at the end">❓ Add FAQ</button>
							<button type="button" class="ace-quick-btn" data-action="Improve the SEO and add more keywords naturally">🔍 Boost SEO</button>
						</div>
						
						<div class="ace-input-container">
							<textarea 
								class="ace-chat-input" 
								id="ace-chat-input" 
								placeholder="Type your message... (Enter to send, Shift+Enter for new line)"
								rows="1"
							></textarea>
							<button type="button" class="ace-send-btn" id="ace-send-btn">
								<span class="dashicons dashicons-arrow-right-alt"></span>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Preview Panel -->
			<div class="ace-preview-panel is-desktop" id="ace-preview-panel">
				<div class="ace-preview-header">
					<h2 class="ace-preview-title">Editor Preview</h2>
					<button type="button" class="ace-btn ace-btn-icon ace-toggle-assistant" id="ace-toggle-assistant" title="Toggle assistant">
						<span class="dashicons dashicons-format-chat"></span>
					</button>
					<div class="ace-preview-modes" role="group" aria-label="Preview mode">
						<button type="button" class="ace-preview-mode is-active" data-mode="desktop">
							<span class="dashicons dashicons-desktop"></span>
							Desktop
						</button>
						<button type="button" class="ace-preview-mode" data-mode="mobile">
							<span class="dashicons dashicons-smartphone"></span>
							Mobile
						</button>
					</div>
					<div class="ace-preview-stats">
						<div class="ace-stat">
							<span class="dashicons dashicons-editor-paste-word"></span>
							<span><span class="ace-stat-value" id="ace-word-count">0</span> words</span>
						</div>
						<div class="ace-stat">
							<span class="dashicons dashicons-clock"></span>
							<span><span class="ace-stat-value" id="ace-reading-time">0</span> read</span>
						</div>
					</div>
				</div>

				<div class="ace-preview-body">
					<div class="ace-preview-content" id="ace-preview-content">
						<div class="ace-preview-viewport" id="ace-preview-viewport">
							<!-- Placeholder when no content -->
							<div class="ace-preview-placeholder" id="ace-preview-placeholder">
								<div class="ace-preview-placeholder-icon">📄</div>
								<div class="ace-preview-placeholder-text">Your article will appear here</div>
								<div class="ace-preview-placeholder-hint">Start a conversation to generate content</div>
							</div>
							
							<!-- Theme preview iframe -->
							<div class="ace-preview-frame is-hidden" id="ace-preview-frame">
								<iframe id="ace-preview-iframe" title="Theme preview"></iframe>
							</div>
						</div>
					</div>
					<aside class="ace-preview-insights">
						<div class="ace-preview-card">
							<h3>Outline</h3>
							<ul id="ace-outline-list" class="ace-context-list"></ul>
						</div>
						<div class="ace-preview-card">
							<h3>Keywords</h3>
							<div id="ace-keyword-list" class="ace-context-tags"></div>
						</div>
						<div class="ace-preview-card">
							<h3>SEO Meta</h3>
							<div class="ace-seo-row">
								<span class="ace-seo-label">Title</span>
								<span class="ace-seo-value" id="ace-seo-title">—</span>
							</div>
							<div class="ace-seo-row">
								<span class="ace-seo-label">Description</span>
								<span class="ace-seo-value" id="ace-seo-description">—</span>
							</div>
							<div class="ace-seo-row">
								<span class="ace-seo-label">Keywords</span>
								<span class="ace-seo-value" id="ace-seo-keywords">—</span>
							</div>
						</div>
					</aside>
				</div>

				<div class="ace-preview-footer">
					<button type="button" class="ace-btn ace-btn-secondary" id="ace-save-draft-btn">
						<span class="dashicons dashicons-media-text"></span>
						Save Draft
					</button>
					<button type="button" class="ace-btn ace-btn-secondary" id="ace-open-editor-btn">
						<span class="dashicons dashicons-edit"></span>
						Open Editor
					</button>
					<button type="button" class="ace-btn ace-btn-secondary" id="ace-history-btn">
						<span class="dashicons dashicons-backup"></span>
						History
					</button>
					<button type="button" class="ace-btn ace-btn-success" id="ace-publish-btn">
						<span class="dashicons dashicons-yes-alt"></span>
						Publish
					</button>
				</div>
			</div>

		</div>
	</div>
</div>

<!-- Version History Modal -->
<div class="ace-history-modal" id="ace-history-modal" style="display: none;">
	<div class="ace-history-dialog">
		<div class="ace-history-header">
			<h3>Version History</h3>
			<button type="button" class="ace-history-close" id="ace-history-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="ace-history-body">
			<div class="ace-history-list" id="ace-history-list"></div>
			<div class="ace-history-preview">
				<div class="ace-history-preview-header">
					<strong>Diff Preview</strong>
					<div class="ace-history-actions">
						<button type="button" class="button" id="ace-compare-versions-btn" disabled>Compare</button>
						<button type="button" class="button" id="ace-restore-version-btn" disabled>Restore Version</button>
					</div>
				</div>
				<div class="ace-history-preview-content" id="ace-history-preview-content">
					Select a version to compare with the current draft.
				</div>
				<div class="ace-history-compare" id="ace-history-compare" style="display:none;">
					<div class="ace-history-compare-column">
						<div class="ace-history-compare-title" id="ace-compare-left-title">Version</div>
						<div class="ace-history-compare-body" id="ace-compare-left"></div>
					</div>
					<div class="ace-history-compare-column">
						<div class="ace-history-compare-title" id="ace-compare-right-title">Version</div>
						<div class="ace-history-compare-body" id="ace-compare-right"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Settings Modal -->
<div class="ace-settings-modal" id="ace-settings-modal" style="display: none;">
	<div class="ace-settings-dialog" role="dialog" aria-modal="true" aria-labelledby="ace-settings-title">
		<div class="ace-settings-header">
			<h3 id="ace-settings-title">Settings</h3>
			<button type="button" class="ace-settings-close" id="ace-settings-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="ace-settings-body">
			<div class="ace-settings-tabs" role="tablist" aria-orientation="vertical">
				<button type="button" class="ace-settings-tab is-active" data-tab="general" role="tab" aria-selected="true">General</button>
				<button type="button" class="ace-settings-tab" data-tab="models" role="tab" aria-selected="false">Models</button>
				<button type="button" class="ace-settings-tab" data-tab="output" role="tab" aria-selected="false">Output</button>
				<button type="button" class="ace-settings-tab" data-tab="advanced" role="tab" aria-selected="false">Advanced</button>
			</div>
			<div class="ace-settings-panels">
				<div class="ace-settings-panel is-active" data-panel="general" role="tabpanel">
					<div class="ace-settings-group">
						<label class="ace-switch">
							<input type="checkbox" id="ace-setting-professional" />
							<span class="ace-switch-slider"></span>
							<span class="ace-switch-label">Professional mode</span>
						</label>
						<p class="ace-settings-hint">Polish output for clarity, tone, and structure.</p>
					</div>
					<div class="ace-settings-group">
						<label class="ace-switch">
							<input type="checkbox" id="ace-setting-quick-actions" />
							<span class="ace-switch-slider"></span>
							<span class="ace-switch-label">Show quick actions</span>
						</label>
						<p class="ace-settings-hint">Display quick actions by default in the input area.</p>
					</div>
				</div>
				<div class="ace-settings-panel" data-panel="models" role="tabpanel">
					<div class="ace-settings-group">
						<div class="ace-settings-row">
							<span>Active model</span>
							<strong id="ace-settings-current-model">—</strong>
						</div>
						<p class="ace-settings-hint">Change the model using the selector in the header.</p>
					</div>
				</div>
				<div class="ace-settings-panel" data-panel="output" role="tabpanel">
					<div class="ace-settings-group">
						<label class="ace-switch">
							<input type="checkbox" id="ace-setting-preview" />
							<span class="ace-switch-slider"></span>
							<span class="ace-switch-label">Show preview panel</span>
						</label>
						<p class="ace-settings-hint">Toggle the live preview panel visibility.</p>
					</div>
				</div>
				<div class="ace-settings-panel" data-panel="advanced" role="tabpanel">
					<p class="ace-settings-hint">Advanced settings are managed inside this modal.</p>
				</div>
			</div>
		</div>
	</div>
</div>

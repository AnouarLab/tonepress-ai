/**
 * AI Content Engine - Chat Builder JavaScript
 * Real-time chat with live preview functionality
 */

(function ($) {
    'use strict';

    // State management
    const ChatBuilder = {
        sessionId: null,
        isLoading: false,
        currentContent: '',
        messageQueue: [],
        previewTimer: null,
        previewRequest: null,
        previewRequestId: 0,
        lastPreviewHash: '',
        lastSavedAt: null,
        autosaveTimer: null,
        hasPendingChanges: false,
        lastUserMessage: '',
        lastUserModel: '',
        lastKeywords: [],
        versionsMap: {},
        selectedVersions: [],
        isSyncingScroll: false,
        professionalMode: false,
        scrollSyncEnabled: false,
        assistantVisible: false,
        lastSeoMeta: null,

        // DOM elements
        elements: {
            chatMessages: null,
            chatInput: null,
            sendBtn: null,
            previewContent: null,
            wordCount: null,
            readingTime: null,
            sessionList: null
        },

        /**
         * Initialize the chat builder
         */
        init: function () {
            this.cacheElements();
            this.bindEvents();
            this.loadSessions();
            this.autoResizeInput();
            this.restoreSidebarState();
            this.restorePreviewMode();
            this.restoreProfessionalMode();
            this.restoreQuickActions();
            this.restoreAssistantDrawer();
            this.initWpSidebar();
            this.initAutosaveIndicator();
            this.bindUnloadWarning();
            this.restorePreviewVisibility();
        },

        /**
         * Cache DOM elements
         */
        cacheElements: function () {
            this.elements = {
                chatMessages: $('#ace-chat-messages'),
                chatInput: $('#ace-chat-input'),
                sendBtn: $('#ace-send-btn'),
                previewContent: $('#ace-preview-content'),
                previewFrame: $('#ace-preview-frame'),
                previewIframe: $('#ace-preview-iframe'),
                wordCount: $('#ace-word-count'),
                readingTime: $('#ace-reading-time'),
                sessionList: $('#ace-session-list'),
                welcomeScreen: $('#ace-welcome-screen'),
                chatPanel: $('#ace-chat-panel-content')
            };
        },

        /**
         * Bind event listeners
         */
        bindEvents: function () {
            const self = this;

            // Send message on button click
            this.elements.sendBtn.on('click', function () {
                self.sendMessage();
            });

            // Send message on Enter (without Shift)
            this.elements.chatInput.on('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Model change updates session context
            $('#ace-chat-model').on('change', function () {
                const label = $(this).find('option:selected').text();
                self.updateSessionSummary({ model: label });
                $('#ace-settings-current-model').text(label || '—');
                self.showToast('Model set to ' + label, 'success');
            });

            // Auto-resize textarea
            this.elements.chatInput.on('input', function () {
                self.autoResizeInput();
            });

            // New chat button
            $('#ace-new-chat-btn').on('click', function () {
                self.startNewChat();
            });

            // Sidebar toggle
            $('#ace-toggle-sidebar').on('click', function () {
                const $builder = $('.ace-chat-builder');
                const isCollapsed = $builder.toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed');
                localStorage.setItem('ace_sidebar_collapsed', isCollapsed ? '1' : '0');
            });

            // WP Admin sidebar toggle (fullscreen mode)
            $('#ace-toggle-fullscreen').on('click', function () {
                self.toggleWpSidebar();
            });

            // Quick action buttons
            $('.ace-quick-btn').on('click', function () {
                const action = $(this).data('action');
                self.elements.chatInput.val(action);
                self.sendMessage();
            });

            // Prompt builder toggle
            $('#ace-builder-toggle').on('click', function () {
                $('#ace-builder-panel').slideToggle(150);
            });

            // Prompt builder send
            $('#ace-builder-send').on('click', function () {
                const prompt = self.buildPromptFromTemplate();
                if (prompt) {
                    self.sendMessage(false, prompt, $('#ace-chat-model').val());
                }
            });

            // Sidebar tabs
            $(document).on('click', '.ace-tab-btn', function () {
                const tab = $(this).data('tab');
                $('.ace-tab-btn').removeClass('is-active');
                $(this).addClass('is-active');
                if (tab === 'context') {
                    $('#ace-session-list').hide();
                    $('#ace-context-panel').show();
                } else {
                    $('#ace-context-panel').hide();
                    $('#ace-session-list').show();
                }
            });

            // Prompt builder toggle
            $('#ace-builder-toggle').on('click', function () {
                $('#ace-builder-panel').slideToggle(150);
            });

            // Prompt builder send
            $('#ace-builder-send').on('click', function () {
                const prompt = self.buildPromptFromTemplate();
                if (prompt) {
                    self.sendMessage(false, prompt, $('#ace-chat-model').val());
                }
            });

            // Tone apply
            $('#ace-apply-tone').on('click', function () {
                const tone = $('#ace-tone-select').val();
                if (tone) {
                    self.sendMessage(false, `Use a ${tone} tone for the article.`, $('#ace-chat-model').val());
                }
            });

            // Length slider
            $('#ace-length-range').on('input', function () {
                $('#ace-length-value').text($(this).val());
            });

            $('#ace-apply-length').on('click', function () {
                const length = $('#ace-length-range').val();
                self.sendMessage(false, `Target a length of about ${length} words.`, $('#ace-chat-model').val());
            });

            // Outline toggle
            $('#ace-outline-toggle').on('click', function () {
                const enabled = $(this).data('enabled') === 1;
                const next = enabled ? 0 : 1;
                $(this).data('enabled', next);
                $(this).toggleClass('is-active', next === 1);
                $(this).text(next === 1 ? 'On' : 'Off');
                const instruction = next === 1
                    ? 'Start with an outline before drafting the full article.'
                    : 'Proceed without an outline and draft the article directly.';
                self.sendMessage(false, instruction, $('#ace-chat-model').val());
            });

            // Starter prompts
            $('.ace-starter-prompt').on('click', function () {
                const prompt = $(this).data('prompt');
                self.startNewChat(prompt);
            });

            // Session items
            $(document).on('click', '.ace-session-item', function () {
                const sessionId = $(this).data('session-id');
                self.loadSession(sessionId);
            });

            // Session actions
            $(document).on('click', '.ace-session-action', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const action = $(this).data('action');
                const sessionId = $(this).closest('.ace-session-item').data('session-id');

                if (action === 'rename') {
                    self.renameSession(sessionId);
                } else if (action === 'delete') {
                    self.deleteSession(sessionId);
                } else if (action === 'duplicate') {
                    self.duplicateSession(sessionId);
                } else if (action === 'pin') {
                    const pinned = $(this).data('pinned') ? 0 : 1;
                    self.togglePin(sessionId, pinned);
                }
            });

            // Save draft button
            $('#ace-save-draft-btn').on('click', function () {
                self.saveDraft(false);
            });

            // Open editor button
            $('#ace-open-editor-btn').on('click', function () {
                self.saveDraft(true);
            });

            // History button
            $('#ace-history-btn').on('click', function () {
                self.openHistory();
            });

            $('#ace-history-close').on('click', function () {
                self.closeHistory();
            });

            $('#ace-history-modal').on('click', function (e) {
                if (e.target === this) {
                    self.closeHistory();
                }
            });

            // Restore version button
            $('#ace-restore-version-btn').on('click', function () {
                const versionId = $(this).data('version-id');
                if (versionId) {
                    self.restoreVersion(versionId);
                }
            });

            // Compare versions button
            $('#ace-compare-versions-btn').on('click', function () {
                self.compareSelectedVersions();
            });

            // Publish button
            $('#ace-publish-btn').on('click', function () {
                self.publish();
            });

            // Preview mode toggles
            $(document).on('click', '.ace-preview-mode', function () {
                const mode = $(this).data('mode');
                $('.ace-preview-mode').removeClass('is-active');
                $(this).addClass('is-active');
                self.setPreviewMode(mode);
            });

            // Minimalist toggles
            $('#ace-toggle-details').on('click', function () {
                const $summary = $('#ace-session-summary');
                $summary.toggleClass('is-hidden');
                $(this).toggleClass('is-active', !$summary.hasClass('is-hidden'));
            });

            $('#ace-toggle-tools').on('click', function () {
                const $tools = $('#ace-chat-tools');
                $tools.toggleClass('is-hidden');
                $(this).toggleClass('is-active', !$tools.hasClass('is-hidden'));
            });

            $('#ace-toggle-preview').on('click', function () {
                self.setPreviewVisible($('#ace-preview-panel').hasClass('is-collapsed'));
            });

            $('#ace-professional-toggle').on('click', function () {
                self.setProfessionalMode(!self.professionalMode);
            });

            $('#ace-quick-toggle').on('click', function () {
                self.setQuickActionsVisible($('#ace-quick-actions').hasClass('is-hidden'));
            });

            $('#ace-toggle-assistant').on('click', function () {
                self.setAssistantVisible($('#ace-chat-drawer').hasClass('is-collapsed'));
            });

            $('#ace-settings-open').on('click', function () {
                self.openSettingsModal();
            });

            $('#ace-settings-close').on('click', function () {
                self.closeSettingsModal();
            });

            $('#ace-settings-modal').on('click', function (e) {
                if (e.target.id === 'ace-settings-modal') {
                    self.closeSettingsModal();
                }
            });

            $(document).on('click', '.ace-settings-tab', function () {
                const tab = $(this).data('tab');
                $('.ace-settings-tab').removeClass('is-active').attr('aria-selected', 'false');
                $(this).addClass('is-active').attr('aria-selected', 'true');
                $('.ace-settings-panel').removeClass('is-active');
                $(`.ace-settings-panel[data-panel="${tab}"]`).addClass('is-active');
            });

            $('#ace-setting-professional').on('change', function () {
                self.setProfessionalMode(this.checked);
            });

            $('#ace-setting-quick-actions').on('change', function () {
                self.setQuickActionsVisible(this.checked);
            });

            $('#ace-setting-preview').on('change', function () {
                self.setPreviewVisible(this.checked);
            });

            // Copy content button
            $('#ace-copy-btn').on('click', function () {
                self.copyContent();
            });

            // Scroll sync: chat -> preview
            this.elements.chatMessages.on('scroll', function () {
                self.syncScrollFromChat();
            });

            // Inline AI actions
            $(document).on('click', '.ace-ai-action', function (e) {
                e.preventDefault();
                const action = $(this).data('action');
                self.handleAiAction(action);
            });

            // Recovery actions
            $(document).on('click', '.ace-recovery-action', function (e) {
                e.preventDefault();
                const action = $(this).data('action');
                if (action === 'retry') {
                    self.retryLastMessage(false);
                } else if (action === 'fallback') {
                    self.retryLastMessage(true);
                } else {
                    $(this).closest('.ace-recovery').remove();
                }
            });
        },

        /**
         * Start a new chat session
         */
        startNewChat: function (initialPrompt = '') {
            const self = this;

            // Show chat panel, hide welcome
            this.elements.welcomeScreen.hide();
            this.elements.chatPanel.show();

            // Clear previous messages
            this.elements.chatMessages.empty();
            this.clearPreview();
            this.updateSessionSummary({
                title: 'New Chat',
                model: $('#ace-chat-model').find('option:selected').text() || 'Default',
                wordCount: 0,
                updatedAt: null
            });
            this.setAutosaveStatus('Ready');

            // Add welcome message
            this.addMessage('assistant', "👋 Hi! I'm your AI article writing assistant. What would you like to write about today?");

            if (initialPrompt) {
                // Auto-send the initial prompt
                this.elements.chatInput.val(initialPrompt);
                this.sendMessage(true); // true = is new session
            }
        },

        /**
         * Send a message
         */
        sendMessage: function (isNewSession = false, overrideMessage = null, overrideModel = null, overrideProfessional = null) {
            const message = (overrideMessage !== null ? overrideMessage : this.elements.chatInput.val().trim());
            const model = overrideModel || $('#ace-chat-model').val();
            const keywords = this.lastKeywords.length ? this.lastKeywords.join(', ') : '';

            if (!message) {
                return;
            }

            if (this.isLoading) {
                this.messageQueue.push({
                    message,
                    isNewSession,
                    model,
                    professional: overrideProfessional !== null ? overrideProfessional : this.professionalMode
                });
                if (overrideMessage === null) {
                    this.elements.chatInput.val('');
                    this.autoResizeInput();
                }
                return;
            }

            // Clear input
            if (overrideMessage === null) {
                this.elements.chatInput.val('');
                this.autoResizeInput();
            }

            // Add user message to chat
            this.addMessage('user', message);
            this.lastUserMessage = message;
            this.lastUserModel = model;

            // Show typing indicator
            this.showTypingIndicator();

            // Disable send button
            this.setLoading(true);
            this.setStatus(this.currentContent ? 'Revising…' : 'Drafting…', 'busy');
            this.hasPendingChanges = true;
            this.setAutosaveStatus('Saving…');

            // Determine endpoint
            const endpoint = isNewSession || !this.sessionId ? 'ace_chat_start' : 'ace_chat_message';

            // Send to server
            const professionalMode = overrideProfessional !== null ? overrideProfessional : this.professionalMode;
            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: endpoint,
                    nonce: aceChat.nonce,
                    message: message,
                    model: model,
                    keywords: keywords,
                    session_id: this.sessionId,
                    professional: professionalMode ? 1 : 0
                },
                success: (response) => {
                    this.hideTypingIndicator();
                    this.setLoading(false);
                    this.processQueue();

                    if (response.success) {
                        // Update session ID if new
                        if (response.data.session_id) {
                            this.sessionId = response.data.session_id;
                            this.loadSessions(); // Refresh session list
                        }

                        // Add AI response
                        this.addMessage('assistant', response.data.message);

                        // Update preview if content provided
                        if (response.data.content) {
                            console.log('[ACE] Content received:', response.data.content.substring(0, 200));
                            this.setStatus('Finalizing…', 'busy');
                            this.updatePreview(response.data.content);
                        } else {
                            console.log('[ACE] No content in response:', response.data);
                        }

                        this.lastSeoMeta = response.data.seo_meta || null;
                        this.updateSeoMeta(this.lastSeoMeta);

                        // Update stats
                        if (response.data.word_count) {
                            this.updateStats(response.data.word_count);
                        }
                        this.updateSessionSummary({
                            title: response.data.title || $('.ace-summary-title').text(),
                            model: $('#ace-chat-model').find('option:selected').text(),
                            wordCount: response.data.word_count || this.getWordCount(this.currentContent),
                            updatedAt: response.data.updated_at || null
                        });
                        this.markAutosaved();
                        this.setStatus('Ready', 'ready');
                    } else {
                        this.addMessage('assistant', '❌ ' + (response.data?.message || 'An error occurred. Please try again.'));
                        this.setAutosaveStatus('Not saved');
                        this.setStatus('Error', 'error');
                        this.addRecoveryActions();
                    }
                },
                error: () => {
                    this.hideTypingIndicator();
                    this.setLoading(false);
                    this.processQueue();
                    this.addMessage('assistant', '❌ Connection error. Please check your internet and try again.');
                    this.setAutosaveStatus('Not saved');
                    this.setStatus('Error', 'error');
                    this.addRecoveryActions();
                }
            });
        },

        /**
         * Add a message to the chat
         */
        addMessage: function (role, content) {
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const actions = role === 'assistant' ? this.getAiActionsHtml() : '';
            const messageHtml = `
                <div class="ace-message ${role}">
                    <div class="ace-message-content">${this.escapeHtml(content)}</div>
                    ${actions}
                    <div class="ace-message-time">${time}</div>
                </div>
            `;

            this.elements.chatMessages.append(messageHtml);
            this.scrollToBottom();
        },

        /**
         * Show typing indicator
         */
        showTypingIndicator: function () {
            const indicator = `
                <div class="ace-typing-indicator" id="ace-typing">
                    <div class="ace-typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span style="color: var(--ace-text-muted); font-size: 13px;">AI is thinking...</span>
                </div>
            `;
            this.elements.chatMessages.append(indicator);
            this.scrollToBottom();
        },

        /**
         * Hide typing indicator
         */
        hideTypingIndicator: function () {
            $('#ace-typing').remove();
        },

        /**
         * Update the preview panel
         */
        updatePreview: function (content) {
            this.currentContent = content;

            // Show preview frame
            $('#ace-preview-placeholder').addClass('is-hidden');
            this.elements.previewFrame.removeClass('is-hidden');

            // Add animation
            this.elements.previewFrame.addClass('content-updated');
            setTimeout(() => {
                this.elements.previewFrame.removeClass('content-updated');
            }, 500);

            this.updateThemePreview(content);
            this.updateContext(content);
        },

        /**
         * Clear preview
         */
        clearPreview: function () {
            this.currentContent = '';
            this.elements.previewFrame.addClass('is-hidden');
            $('#ace-preview-placeholder').removeClass('is-hidden');
            this.updateStats(0);
            this.updateContext('');
        },

        /**
         * Update theme preview iframe via AJAX
         */
        updateThemePreview: function (content) {
            if (!content) {
                return;
            }

            const hash = this.simpleHash(content);
            if (hash === this.lastPreviewHash) {
                return;
            }

            if (this.previewTimer) {
                clearTimeout(this.previewTimer);
            }

            this.previewTimer = setTimeout(() => {
                if (this.previewRequest && this.previewRequest.readyState !== 4) {
                    this.previewRequest.abort();
                }
                const requestId = ++this.previewRequestId;
                const title = this.extractTitle(content);
                console.log('[ACE] Calling preview API for title:', title);
                this.elements.previewFrame.addClass('is-loading');
                this.setStatus('Rendering preview…', 'busy');
                this.previewRequest = $.ajax({
                    url: aceChat.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ace_chat_preview',
                        nonce: aceChat.nonce,
                        title: title,
                        content: content
                    },
                    success: (response) => {
                        console.log('[ACE] Preview API response:', response);
                        if (requestId !== this.previewRequestId) {
                            return;
                        }
                        if (response.success && response.data.url) {
                            console.log('[ACE] Loading preview URL:', response.data.url);
                            this.elements.previewIframe.attr('src', response.data.url);
                            this.lastPreviewHash = hash;
                            this.elements.previewIframe.off('load').on('load', () => {
                                console.log('[ACE] Preview iframe loaded');
                                this.bindPreviewIframeScroll();
                            });
                        } else {
                            console.error('[ACE] Preview failed:', response);
                            this.showToast(response.data?.message || 'Preview failed', 'error');
                        }
                        this.elements.previewFrame.removeClass('is-loading');
                        this.setStatus('Ready', 'ready');
                    },
                    error: (xhr, status, error) => {
                        console.error('[ACE] Preview AJAX error:', status, error);
                        if (requestId !== this.previewRequestId) {
                            return;
                        }
                        this.elements.previewFrame.removeClass('is-loading');
                        this.showToast('Preview failed', 'error');
                        this.setStatus('Preview failed', 'error');
                    }
                });
            }, 300);
        },

        extractTitle: function (content) {
            const match = content.match(/<h1[^>]*>(.*?)<\/h1>/i);
            if (match && match[1]) {
                const div = document.createElement('div');
                div.innerHTML = match[1];
                return div.textContent || 'Preview';
            }
            return 'Preview';
        },

        simpleHash: function (text) {
            let hash = 0;
            for (let i = 0; i < text.length; i++) {
                hash = ((hash << 5) - hash) + text.charCodeAt(i);
                hash |= 0;
            }
            return hash.toString();
        },

        /**
         * Update word count and reading time stats
         */
        updateStats: function (wordCount) {
            this.elements.wordCount.text(wordCount.toLocaleString());

            const readingTime = Math.ceil(wordCount / 200);
            this.elements.readingTime.text(readingTime + ' min');
        },

        /**
         * Load session list
         */
        loadSessions: function () {
            const self = this;

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_sessions',
                    nonce: aceChat.nonce
                },
                success: function (response) {
                    if (response.success && response.data.sessions) {
                        self.renderSessionList(response.data.sessions);
                    }
                }
            });
        },

        /**
         * Render session list
         */
        renderSessionList: function (sessions) {
            const list = this.elements.sessionList;
            list.empty();

            if (sessions.length === 0) {
                list.html('<div class="ace-no-sessions">No previous chats</div>');
                return;
            }

            sessions.forEach(session => {
                const date = new Date(session.updated_at);
                const dateStr = date.toLocaleDateString([], { month: 'short', day: 'numeric' });
                const isActive = session.session_id === this.sessionId;
                const pinned = !!session.pinned;

                const itemHtml = `
                    <div class="ace-session-item ${isActive ? 'active' : ''} ${pinned ? 'is-pinned' : ''}" data-session-id="${session.session_id}">
                        <div class="ace-session-title">${this.escapeHtml(session.title)}</div>
                        <div class="ace-session-meta">
                            <div class="ace-session-date">
                                ${dateStr}
                                <span class="ace-session-status ${session.status}">${session.status}</span>
                            </div>
                            <div class="ace-session-actions">
                                <button type="button" class="ace-session-action ace-session-pin" data-action="pin" data-pinned="${pinned ? 1 : 0}" title="${pinned ? 'Unpin' : 'Pin'}">
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
                `;
                list.append(itemHtml);
            });
        },

        /**
         * Load a specific session
         */
        loadSession: function (sessionId) {
            const self = this;

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_load',
                    nonce: aceChat.nonce,
                    session_id: sessionId
                },
                success: function (response) {
                    if (response.success) {
                        self.sessionId = sessionId;
                        self.restoreSession(response.data);
                        self.loadSessions(); // Refresh to update active state
                    } else {
                        self.showToast('Failed to load session', 'error');
                    }
                }
            });
        },

        /**
         * Restore a session's state
         */
        restoreSession: function (data) {
            // Show chat panel
            this.elements.welcomeScreen.hide();
            this.elements.chatPanel.show();

            // Clear and restore messages
            this.elements.chatMessages.empty();

            if (data.conversation) {
                data.conversation.forEach(msg => {
                    if (msg.role !== 'system') {
                        this.addMessage(msg.role, msg.content);
                    }
                });
            }

            // Restore content
            if (data.content) {
                this.updatePreview(data.content);
                const wordCount = data.content.split(/\s+/).length;
                this.updateStats(wordCount);
            }

            if (data.model) {
                const $model = $('#ace-chat-model');
                if ($model.find(`option[value="${data.model}"]`).length) {
                    $model.val(data.model);
                }
            }

            if (data.keywords) {
                this.lastKeywords = data.keywords.split(',').map((kw) => kw.trim()).filter(Boolean);
                this.updateContext(this.currentContent);
            }

            this.lastSeoMeta = data.seo_meta || null;
            this.updateSeoMeta(this.lastSeoMeta);

            this.updateSessionSummary({
                title: data.title || 'Untitled',
                model: $('#ace-chat-model').find('option:selected').text(),
                wordCount: data.word_count || this.getWordCount(data.content || ''),
                updatedAt: data.updated_at || null
            });
            this.setAutosaveStatus('Ready');
        },

        /**
         * Save as draft
         */
        saveDraft: function (openEditor) {
            if (!this.sessionId) {
                this.showToast('No active session to save', 'error');
                return;
            }

            const self = this;

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_save_draft',
                    nonce: aceChat.nonce,
                    session_id: this.sessionId
                },
                success: function (response) {
                    if (response.success) {
                        self.showToast('✅ Saved as draft! Post ID: ' + response.data.post_id, 'success');
                        if (openEditor && response.data.url) {
                            window.open(response.data.url, '_blank');
                        }
                    } else {
                        self.showToast('❌ ' + (response.data?.message || 'Failed to save'), 'error');
                    }
                }
            });
        },

        /**
         * Rename a session
         */
        renameSession: function (sessionId) {
            const currentTitle = $(this.elements.sessionList)
                .find(`.ace-session-item[data-session-id="${sessionId}"] .ace-session-title`)
                .text();
            const newTitle = prompt('Rename session', currentTitle || '');

            if (!newTitle || newTitle.trim() === '' || newTitle === currentTitle) {
                return;
            }

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_rename',
                    nonce: aceChat.nonce,
                    session_id: sessionId,
                    title: newTitle.trim()
                },
                success: (response) => {
                    if (response.success) {
                        this.loadSessions();
                        this.showToast('Session renamed', 'success');
                    } else {
                        this.showToast(response.data?.message || 'Rename failed', 'error');
                    }
                },
                error: () => {
                    this.showToast('Rename failed', 'error');
                }
            });
        },

        /**
         * Delete a session
         */
        deleteSession: function (sessionId) {
            if (!confirm('Delete this chat session? This cannot be undone.')) {
                return;
            }

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_delete',
                    nonce: aceChat.nonce,
                    session_id: sessionId
                },
                success: (response) => {
                    if (response.success) {
                        if (this.sessionId === sessionId) {
                            this.sessionId = null;
                            this.elements.chatMessages.empty();
                            this.clearPreview();
                            this.elements.chatPanel.hide();
                            this.elements.welcomeScreen.show();
                        }
                        this.loadSessions();
                        this.showToast('Session deleted', 'success');
                    } else {
                        this.showToast(response.data?.message || 'Delete failed', 'error');
                    }
                },
                error: () => {
                    this.showToast('Delete failed', 'error');
                }
            });
        },

        /**
         * Duplicate a session
         */
        duplicateSession: function (sessionId) {
            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_duplicate',
                    nonce: aceChat.nonce,
                    session_id: sessionId
                },
                success: (response) => {
                    if (response.success) {
                        this.loadSessions();
                        this.showToast('Session duplicated', 'success');
                    } else {
                        this.showToast(response.data?.message || 'Duplicate failed', 'error');
                    }
                },
                error: () => {
                    this.showToast('Duplicate failed', 'error');
                }
            });
        },

        /**
         * Toggle pinned state
         */
        togglePin: function (sessionId, pinned) {
            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_pin',
                    nonce: aceChat.nonce,
                    session_id: sessionId,
                    pinned: pinned
                },
                success: (response) => {
                    if (response.success) {
                        this.loadSessions();
                    } else {
                        this.showToast(response.data?.message || 'Pin update failed', 'error');
                    }
                },
                error: () => {
                    this.showToast('Pin update failed', 'error');
                }
            });
        },

        /**
         * Open history modal and load versions
         */
        openHistory: function () {
            if (!this.sessionId) {
                this.showToast('No active session', 'error');
                return;
            }
            $('#ace-history-modal').fadeIn(150);
            this.loadVersions();
        },

        closeHistory: function () {
            $('#ace-history-modal').fadeOut(150);
            $('#ace-history-preview-content').text('Select a version to compare with the current draft.');
            $('#ace-restore-version-btn').prop('disabled', true).removeData('version-id');
            $('#ace-compare-versions-btn').prop('disabled', true);
            $('#ace-history-compare').hide();
            $('#ace-history-preview-content').show();
        },

        /**
         * Load version history
         */
        loadVersions: function () {
            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_versions',
                    nonce: aceChat.nonce,
                    session_id: this.sessionId
                },
                success: (response) => {
                    if (response.success) {
                        this.renderVersions(response.data.versions || []);
                    } else {
                        this.showToast(response.data?.message || 'Failed to load history', 'error');
                    }
                },
                error: () => {
                    this.showToast('Failed to load history', 'error');
                }
            });
        },

        renderVersions: function (versions) {
            const list = $('#ace-history-list');
            list.empty();
            this.versionsMap = {};
            this.selectedVersions = [];
            $('#ace-compare-versions-btn').prop('disabled', true);
            $('#ace-history-compare').hide();
            $('#ace-history-preview-content').show();

            if (!versions.length) {
                list.html('<div class="ace-history-empty">No versions yet.</div>');
                return;
            }

            versions.forEach((version) => {
                const date = new Date(version.created_at);
                const dateStr = date.toLocaleString();
                this.versionsMap[version.id] = version.content || '';
                const item = $(`
                    <button type="button" class="ace-history-item" data-version-id="${version.id}">
                        <div class="ace-history-title">Version ${version.id}</div>
                        <div class="ace-history-date">${dateStr}</div>
                        <label class="ace-history-select">
                            <input type="checkbox" class="ace-version-check" value="${version.id}" />
                            Compare
                        </label>
                    </button>
                `);
                item.data('content', version.content || '');
                list.append(item);
            });

            $('.ace-history-item').on('click', (e) => {
                const $item = $(e.currentTarget);
                const content = $item.data('content') || '';
                const diff = this.diffHtml(this.currentContent || '', content);
                $('#ace-history-preview-content').html(diff || 'No differences found.');
                $('#ace-restore-version-btn').prop('disabled', false).data('version-id', $item.data('version-id'));
                $('.ace-history-item').removeClass('active');
                $item.addClass('active');
            });

            $('.ace-version-check').on('change', (e) => {
                const id = parseInt($(e.currentTarget).val(), 10);
                if (e.currentTarget.checked) {
                    if (this.selectedVersions.length >= 2) {
                        e.currentTarget.checked = false;
                        return;
                    }
                    this.selectedVersions.push(id);
                } else {
                    this.selectedVersions = this.selectedVersions.filter((item) => item !== id);
                }
                $('#ace-compare-versions-btn').prop('disabled', this.selectedVersions.length !== 2);
            });
        },

        compareSelectedVersions: function () {
            if (this.selectedVersions.length !== 2) {
                return;
            }
            const [leftId, rightId] = this.selectedVersions;
            const leftContent = this.versionsMap[leftId] || '';
            const rightContent = this.versionsMap[rightId] || '';

            $('#ace-history-preview-content').hide();
            $('#ace-history-compare').show();
            $('#ace-compare-left-title').text(`Version ${leftId}`);
            $('#ace-compare-right-title').text(`Version ${rightId}`);
            $('#ace-compare-left').html(this.escapeHtml(leftContent).replace(/\n/g, '<br>'));
            $('#ace-compare-right').html(this.escapeHtml(rightContent).replace(/\n/g, '<br>'));
        },

        /**
         * Restore a version
         */
        restoreVersion: function (versionId) {
            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_restore',
                    nonce: aceChat.nonce,
                    session_id: this.sessionId,
                    version_id: versionId
                },
                success: (response) => {
                    if (response.success) {
                        const content = response.data.content || '';
                        if (content) {
                            this.updatePreview(content);
                            const wordCount = content.split(/\s+/).length;
                            this.updateStats(wordCount);
                        }
                        this.showToast('Version restored', 'success');
                        this.closeHistory();
                    } else {
                        this.showToast(response.data?.message || 'Restore failed', 'error');
                    }
                },
                error: () => {
                    this.showToast('Restore failed', 'error');
                }
            });
        },

        /**
         * Simple inline diff for HTML content
         */
        diffHtml: function (oldText, newText) {
            if (!oldText && !newText) return '';
            if (oldText === newText) return '';

            const oldWords = oldText.replace(/\s+/g, ' ').split(' ');
            const newWords = newText.replace(/\s+/g, ' ').split(' ');
            const diff = [];
            let i = 0;
            let j = 0;

            while (i < oldWords.length || j < newWords.length) {
                const oldWord = oldWords[i];
                const newWord = newWords[j];

                if (oldWord === newWord) {
                    diff.push(this.escapeHtml(oldWord));
                    i++;
                    j++;
                } else {
                    if (oldWord) {
                        diff.push(`<del>${this.escapeHtml(oldWord)}</del>`);
                        i++;
                    }
                    if (newWord) {
                        diff.push(`<ins>${this.escapeHtml(newWord)}</ins>`);
                        j++;
                    }
                }
            }

            return diff.join(' ');
        },

        /**
         * Publish the article
         */
        publish: function () {
            if (!this.sessionId) {
                this.showToast('No active session to publish', 'error');
                return;
            }

            if (!confirm('Are you sure you want to publish this article?')) {
                return;
            }

            const self = this;

            $.ajax({
                url: aceChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ace_chat_publish',
                    nonce: aceChat.nonce,
                    session_id: this.sessionId
                },
                success: function (response) {
                    if (response.success) {
                        self.showToast('🎉 Published! Post ID: ' + response.data.post_id, 'success');
                        window.open(response.data.url, '_blank');
                    } else {
                        self.showToast('❌ ' + (response.data?.message || 'Failed to publish'), 'error');
                    }
                }
            });
        },

        /**
         * Copy content to clipboard
         */
        copyContent: function () {
            if (!this.currentContent) {
                this.showToast('No content to copy', 'error');
                return;
            }

            navigator.clipboard.writeText(this.currentContent).then(() => {
                this.showToast('📋 Content copied to clipboard!', 'success');
            }).catch(() => {
                this.showToast('Failed to copy content', 'error');
            });
        },

        /**
         * Auto-resize textarea
         */
        autoResizeInput: function () {
            const input = this.elements.chatInput[0];
            if (input) {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 150) + 'px';
            }
        },

        /**
         * Scroll chat to bottom
         */
        scrollToBottom: function () {
            const messages = this.elements.chatMessages[0];
            if (messages) {
                messages.scrollTop = messages.scrollHeight;
            }
        },

        /**
         * Set loading state
         */
        setLoading: function (loading) {
            this.isLoading = loading;
            this.elements.sendBtn.prop('disabled', loading);
            this.elements.chatInput.prop('disabled', loading);
        },

        /**
         * Process queued messages
         */
        processQueue: function () {
            if (this.isLoading || !this.messageQueue.length) {
                return;
            }
            const next = this.messageQueue.shift();
            if (next) {
                this.sendMessage(next.isNewSession, next.message, next.model, next.professional);
            }
        },

        /**
         * Restore sidebar state from localStorage
         */
        restoreSidebarState: function () {
            const collapsed = localStorage.getItem('ace_sidebar_collapsed') === '1';
            if (collapsed) {
                $('.ace-chat-builder').addClass('sidebar-collapsed');
            }
        },

        /**
         * Set preview mode (desktop/mobile)
         */
        setPreviewMode: function (mode) {
            const $panel = $('.ace-preview-panel');
            $panel.removeClass('is-mobile is-desktop');
            if (mode === 'mobile') {
                $panel.addClass('is-mobile');
                localStorage.setItem('ace_preview_mode', 'mobile');
            } else {
                $panel.addClass('is-desktop');
                localStorage.setItem('ace_preview_mode', 'desktop');
            }
        },

        /**
         * Restore preview mode from localStorage
         */
        restorePreviewMode: function () {
            const mode = localStorage.getItem('ace_preview_mode') || 'desktop';
            this.setPreviewMode(mode);
            $('.ace-preview-mode').removeClass('is-active');
            $(`.ace-preview-mode[data-mode="${mode}"]`).addClass('is-active');
        },

        restorePreviewVisibility: function () {
            const collapsed = localStorage.getItem('ace_preview_collapsed') === '1';
            this.setPreviewVisible(!collapsed);
        },

        restoreProfessionalMode: function () {
            const enabled = localStorage.getItem('ace_professional_mode') === '1';
            this.setProfessionalMode(enabled);
        },

        restoreQuickActions: function () {
            const visible = localStorage.getItem('ace_quick_actions_visible') === '1';
            this.setQuickActionsVisible(visible);
        },

        restoreAssistantDrawer: function () {
            const visible = localStorage.getItem('ace_assistant_visible') === '1';
            this.setAssistantVisible(visible);
        },

        setAssistantVisible: function (visible) {
            const isVisible = !!visible;
            $('#ace-chat-drawer').toggleClass('is-collapsed', !isVisible);
            $('#ace-toggle-assistant').toggleClass('is-active', isVisible);
            this.assistantVisible = isVisible;
            localStorage.setItem('ace_assistant_visible', isVisible ? '1' : '0');
        },

        setProfessionalMode: function (enabled) {
            this.professionalMode = !!enabled;
            $('#ace-professional-toggle').toggleClass('is-active', this.professionalMode);
            $('#ace-professional-badge').toggleClass('is-hidden', !this.professionalMode);
            $('#ace-setting-professional').prop('checked', this.professionalMode);
            localStorage.setItem('ace_professional_mode', this.professionalMode ? '1' : '0');
        },

        setQuickActionsVisible: function (visible) {
            const isVisible = !!visible;
            $('#ace-quick-actions').toggleClass('is-hidden', !isVisible);
            $('#ace-quick-toggle').toggleClass('is-active', isVisible);
            $('#ace-setting-quick-actions').prop('checked', isVisible);
            localStorage.setItem('ace_quick_actions_visible', isVisible ? '1' : '0');
        },

        setPreviewVisible: function (visible) {
            const isVisible = !!visible;
            $('#ace-preview-panel').toggleClass('is-collapsed', !isVisible);
            $('#ace-toggle-preview').toggleClass('is-active', isVisible);
            $('#ace-setting-preview').prop('checked', isVisible);
            localStorage.setItem('ace_preview_collapsed', isVisible ? '0' : '1');
        },

        openSettingsModal: function () {
            const modelLabel = $('#ace-chat-model').find('option:selected').text();
            $('#ace-settings-current-model').text(modelLabel || '—');
            $('#ace-setting-professional').prop('checked', this.professionalMode);
            $('#ace-setting-quick-actions').prop('checked', !$('#ace-quick-actions').hasClass('is-hidden'));
            $('#ace-setting-preview').prop('checked', !$('#ace-preview-panel').hasClass('is-collapsed'));
            $('#ace-settings-modal').fadeIn(120);
        },

        closeSettingsModal: function () {
            $('#ace-settings-modal').fadeOut(120);
        },

        /**
         * Initialize WP sidebar state - auto collapse on load
         */
        initWpSidebar: function () {
            // Auto-collapse WP sidebar by default, restore user preference
            const fullscreen = localStorage.getItem('ace_fullscreen');
            if (fullscreen === null || fullscreen === '1') {
                $('body').addClass('folded ace-fullscreen');
                $('#ace-toggle-fullscreen').addClass('is-active');
            }
        },

        /**
         * Toggle WordPress admin sidebar
         */
        toggleWpSidebar: function () {
            const $body = $('body');
            const $btn = $('#ace-toggle-fullscreen');

            if ($body.hasClass('ace-fullscreen')) {
                $body.removeClass('folded ace-fullscreen');
                $btn.removeClass('is-active');
                localStorage.setItem('ace_fullscreen', '0');
            } else {
                $body.addClass('folded ace-fullscreen');
                $btn.addClass('is-active');
                localStorage.setItem('ace_fullscreen', '1');
            }
        },

        /**
         * Update session summary UI
         */
        updateSessionSummary: function ({ title, model, wordCount, updatedAt }) {
            if (title !== undefined && title !== null) {
                $('.ace-summary-title').text(title);
            }
            if (model !== undefined && model !== null) {
                $('.ace-summary-model').text(model);
            }
            if (wordCount !== undefined && wordCount !== null) {
                $('.ace-summary-words').text(Number(wordCount).toLocaleString());
            }
            if (updatedAt) {
                const date = new Date(updatedAt.replace(' ', 'T'));
                $('.ace-summary-updated').text(date.toLocaleString());
            } else {
                $('.ace-summary-updated').text('—');
            }
        },

        /**
         * Autosave status helpers
         */
        setAutosaveStatus: function (text) {
            $('#ace-autosave-status').text(text);
        },

        markAutosaved: function () {
            this.hasPendingChanges = false;
            this.lastSavedAt = new Date();
            this.updateAutosaveLabel();
        },

        initAutosaveIndicator: function () {
            this.autosaveTimer = setInterval(() => {
                this.updateAutosaveLabel();
            }, 60000);
        },

        updateAutosaveLabel: function () {
            if (!this.lastSavedAt) {
                return;
            }
            const seconds = Math.floor((Date.now() - this.lastSavedAt.getTime()) / 1000);
            if (seconds < 10) {
                this.setAutosaveStatus('Saved just now');
            } else if (seconds < 60) {
                this.setAutosaveStatus(`Saved ${seconds}s ago`);
            } else if (seconds < 3600) {
                const mins = Math.floor(seconds / 60);
                this.setAutosaveStatus(`Saved ${mins}m ago`);
            } else {
                const hours = Math.floor(seconds / 3600);
                this.setAutosaveStatus(`Saved ${hours}h ago`);
            }
        },

        /**
         * Update context sidebar (outline + keywords)
         */
        updateContext: function (content) {
            const outline = [];
            const regex = /<h2[^>]*>(.*?)<\/h2>|<h3[^>]*>(.*?)<\/h3>/gi;
            let match;
            while ((match = regex.exec(content || '')) !== null) {
                const text = match[1] || match[2];
                if (text) {
                    outline.push(this.stripHtml(text));
                }
            }
            const $outline = $('#ace-outline-list');
            $outline.empty();
            if (!outline.length) {
                $outline.append('<li>No outline yet</li>');
            } else {
                outline.forEach((item) => {
                    $outline.append(`<li>${this.escapeHtml(item)}</li>`);
                });
            }

            const $keywords = $('#ace-keyword-list');
            $keywords.empty();
            if (!this.lastKeywords.length) {
                $keywords.append('<span class="ace-context-tag">No keywords</span>');
            } else {
                this.lastKeywords.forEach((kw) => {
                    $keywords.append(`<span class="ace-context-tag">${this.escapeHtml(kw)}</span>`);
                });
            }
        },

        updateSeoMeta: function (meta) {
            const title = meta?.title || '—';
            const description = meta?.description || '—';
            let keywords = meta?.keywords || '—';
            if (Array.isArray(keywords)) {
                keywords = keywords.length ? keywords.join(', ') : '—';
            }
            $('#ace-seo-title').text(title);
            $('#ace-seo-description').text(description);
            $('#ace-seo-keywords').text(keywords || '—');
        },

        stripHtml: function (html) {
            const div = document.createElement('div');
            div.innerHTML = html;
            return div.textContent || div.innerText || '';
        },

        /**
         * Scroll sync from chat to preview iframe
         */
        syncScrollFromChat: function () {
            if (!this.scrollSyncEnabled) return;
            if (this.isSyncingScroll) return;
            const iframe = this.elements.previewIframe[0];
            if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;
            const chatEl = this.elements.chatMessages[0];
            const doc = iframe.contentDocument.documentElement;
            const maxChat = chatEl.scrollHeight - chatEl.clientHeight;
            const maxIframe = doc.scrollHeight - iframe.clientHeight;
            if (maxChat <= 0 || maxIframe <= 0) return;

            this.isSyncingScroll = true;
            const ratio = chatEl.scrollTop / maxChat;
            iframe.contentWindow.scrollTo(0, ratio * maxIframe);
            setTimeout(() => { this.isSyncingScroll = false; }, 50);
        },

        bindPreviewIframeScroll: function () {
            const iframe = this.elements.previewIframe[0];
            if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;
            $(iframe.contentWindow).off('scroll.ace').on('scroll.ace', () => {
                if (!this.scrollSyncEnabled) return;
                if (this.isSyncingScroll) return;
                const chatEl = this.elements.chatMessages[0];
                const doc = iframe.contentDocument.documentElement;
                const maxChat = chatEl.scrollHeight - chatEl.clientHeight;
                const maxIframe = doc.scrollHeight - iframe.clientHeight;
                if (maxChat <= 0 || maxIframe <= 0) return;

                this.isSyncingScroll = true;
                const ratio = iframe.contentWindow.scrollY / maxIframe;
                chatEl.scrollTop = ratio * maxChat;
                setTimeout(() => { this.isSyncingScroll = false; }, 50);
            });
        },

        getWordCount: function (content) {
            if (!content) {
                return 0;
            }
            return content.trim().split(/\s+/).length;
        },

        bindUnloadWarning: function () {
            window.addEventListener('beforeunload', (e) => {
                if (this.isLoading || this.messageQueue.length || this.hasPendingChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        /**
         * AI inline actions UI
         */
        getAiActionsHtml: function () {
            return `
                <div class="ace-ai-actions">
                    <button type="button" class="ace-ai-action" data-action="regenerate">Regenerate</button>
                    <button type="button" class="ace-ai-action" data-action="shorten">Shorten</button>
                    <button type="button" class="ace-ai-action" data-action="expand">Expand</button>
                    <button type="button" class="ace-ai-action" data-action="tone">Rewrite tone</button>
                </div>
            `;
        },

        handleAiAction: function (action) {
            if (action === 'regenerate') {
                this.retryLastMessage(false);
                return;
            }

            if (action === 'shorten') {
                this.sendMessage(false, 'Shorten the article and make it more concise.', this.lastUserModel || null);
                return;
            }

            if (action === 'expand') {
                this.sendMessage(false, 'Expand the article with more detail and concrete examples.', this.lastUserModel || null);
                return;
            }

            if (action === 'tone') {
                const tone = prompt('Enter tone (e.g., professional, conversational, friendly)');
                if (tone) {
                    this.sendMessage(false, `Rewrite the article in a ${tone} tone.`, this.lastUserModel || null);
                }
            }
        },

        addRecoveryActions: function () {
            const html = `
                <div class="ace-message assistant ace-recovery">
                    <div class="ace-message-content">Want to retry or switch models?</div>
                    <div class="ace-recovery-actions">
                        <button type="button" class="ace-recovery-action" data-action="retry">Retry</button>
                        <button type="button" class="ace-recovery-action" data-action="fallback">Try fallback model</button>
                        <button type="button" class="ace-recovery-action" data-action="dismiss">Keep draft</button>
                    </div>
                </div>
            `;
            this.elements.chatMessages.append(html);
            this.scrollToBottom();
        },

        retryLastMessage: function (useFallback) {
            if (!this.lastUserMessage) {
                this.showToast('No previous message to retry', 'error');
                return;
            }
            const model = useFallback ? this.getFallbackModel() : (this.lastUserModel || $('#ace-chat-model').val());
            if (!model) {
                this.showToast('No fallback model available', 'error');
                return;
            }
            this.sendMessage(false, this.lastUserMessage, model);
        },

        getFallbackModel: function () {
            const current = $('#ace-chat-model').val();
            const options = $('#ace-chat-model option').map(function () { return $(this).val(); }).get();
            return options.find((opt) => opt !== current) || current;
        },

        /**
         * Build structured prompt from template fields
         */
        buildPromptFromTemplate: function () {
            const topic = $('#ace-builder-topic').val().trim();
            if (!topic) {
                this.showToast('Please enter a topic', 'error');
                return '';
            }

            const audience = $('#ace-builder-audience').val().trim();
            const goal = $('#ace-builder-goal').val().trim();
            const keywords = $('#ace-builder-keywords').val().trim();
            const tone = $('#ace-builder-tone').val();
            const length = $('#ace-builder-length').val();
            const includeFaq = $('#ace-builder-faq').is(':checked');
            const includeExamples = $('#ace-builder-examples').is(':checked');
            const outline = $('#ace-outline-toggle').data('enabled') === 1 ? 'Yes' : 'No';

            const lines = [
                `TOPIC: ${topic}`,
                audience ? `AUDIENCE: ${audience}` : null,
                goal ? `GOAL: ${goal}` : null,
                keywords ? `KEYWORDS: ${keywords}` : null,
                `TONE: ${tone}`,
                `LENGTH: ${length}`,
                `OUTLINE FIRST: ${outline}`,
                includeFaq ? 'INCLUDE FAQ: Yes' : null,
                includeExamples ? 'INCLUDE EXAMPLES: Yes' : null,
                'Please generate a full article based on the above.'
            ].filter(Boolean);

            this.lastKeywords = keywords ? keywords.split(',').map((kw) => kw.trim()).filter(Boolean) : [];
            this.updateContext(this.currentContent);

            return lines.join('\n');
        },

        /**
         * Update chat status
         */
        setStatus: function (text, state) {
            const $status = $('#ace-chat-status');
            if (!$status.length) {
                return;
            }
            $status.removeClass('is-busy is-error is-ready');
            if (state === 'busy') {
                $status.addClass('is-busy');
            } else if (state === 'error') {
                $status.addClass('is-error');
            } else {
                $status.addClass('is-ready');
            }
            $status.find('.ace-status-text').text(text);
        },

        /**
         * Show toast notification
         */
        showToast: function (message, type = 'success') {
            const toast = $(`
                <div class="ace-toast ${type}">
                    ${message}
                </div>
            `);

            $('body').append(toast);

            setTimeout(() => {
                toast.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 4000);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when ready
    $(document).ready(function () {
        if ($('.ace-chat-builder').length) {
            ChatBuilder.init();
        }
    });

    // Expose globally for debugging
    window.ACEChatBuilder = ChatBuilder;

})(jQuery);

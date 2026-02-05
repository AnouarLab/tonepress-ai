(function ($) {
    'use strict';

    $(document).ready(function () {

        // ===== Toast Notification System =====
        const showToast = function (title, message, type = 'success') {
            const icon = type === 'success' ? 'yes-alt' : 'dismiss';
            const toast = $(`
                <div class="ace-toast ${type}">
                    <span class="dashicons dashicons-${icon}"></span>
                    <div class="ace-toast-content">
                        <div class="ace-toast-title">${title}</div>
                        <div class="ace-toast-message">${message}</div>
                    </div>
                    <span class="dashicons dashicons-no-alt ace-toast-close"></span>
                </div>
            `);

            $('body').append(toast);

            toast.find('.ace-toast-close').on('click', function () {
                toast.fadeOut(300, function () { $(this).remove(); });
            });

            setTimeout(function () {
                toast.fadeOut(300, function () { $(this).remove(); });
            }, 5000);
        };

        // ===== Progress Tracker =====
        const progressSteps = [
            { id: 'validating', label: 'Validating inputs...', progress: 10 },
            { id: 'calling_api', label: 'Calling OpenAI API...', progress: 30 },
            { id: 'processing', label: 'Processing content...', progress: 60 },
            { id: 'creating_post', label: 'Creating post...', progress: 80 },
            { id: 'finalizing', label: 'Finalizing...', progress: 100 }
        ];

        const updateProgress = function (stepId) {
            const step = progressSteps.find(s => s.id === stepId);
            if (!step) return;

            $('.ace-progress-fill').css('width', step.progress + '%');
            $('.ace-progress-step').removeClass('active complete');

            progressSteps.forEach(s => {
                const $step = $(`.ace-progress-step[data-step="${s.id}"]`);
                if (s.progress < step.progress) {
                    $step.addClass('complete');
                    $step.find('.dashicons').removeClass('dashicons-minus').addClass('dashicons-yes-alt');
                } else if (s.id === stepId) {
                    $step.addClass('active');
                    $step.find('.dashicons').removeClass('dashicons-minus dashicons-yes-alt').addClass('dashicons-update spin');
                }
            });
        };

        // ===== Character Counters =====
        const updateCharCounter = function ($input, $counter, max) {
            const length = $input.val().length;
            $counter.text(`${length} / ${max} characters`);

            $counter.removeClass('warning error');
            if (length > max * 0.9) {
                $counter.addClass('error');
            } else if (length > max * 0.75) {
                $counter.addClass('warning');
            }
        };

        // Topic character counter
        const $topic = $('#ace-topic');
        const $topicCounter = $('<span class="ace-char-counter"></span>');
        $topic.after($topicCounter);
        $topic.on('input', function () {
            updateCharCounter($(this), $topicCounter, 500);
        });
        updateCharCounter($topic, $topicCounter, 500);

        // Custom instructions character counter
        const $customInstructions = $('#ace-custom-instructions');
        const $instructionsCounter = $('<span class="ace-char-counter"></span>');
        $customInstructions.after($instructionsCounter);
        $customInstructions.on('input', function () {
            updateCharCounter($(this), $instructionsCounter, 1000);
        });
        updateCharCounter($customInstructions, $instructionsCounter, 1000);

        // ===== Cost Estimator =====
        const estimateCost = function () {
            const model = aceAdmin.model || 'gpt-3.5-turbo';
            const maxTokens = parseInt($('#ace-max-tokens').val()) || 3000;
            const includeTables = $('#ace-include-tables').is(':checked');
            const includeCharts = $('#ace-include-charts').is(':checked');
            const featuredImage = $('#ace-generate-featured-image').is(':checked');
            const inlineImages = $('#ace-generate-inline-images').is(':checked');

            const modelRates = {
                'gpt-3.5-turbo': 0.002 / 1000,
                'gpt-4': 0.03 / 1000,
                'gpt-4-turbo': 0.01 / 1000
            };

            const rate = modelRates[model] || modelRates['gpt-3.5-turbo'];
            let cost = maxTokens * rate;

            // Add image costs
            if (featuredImage) cost += 0.04; // DALL-E standard
            if (inlineImages) cost += 0.12; // 3 inline images

            return cost.toFixed(4);
        };

        const updateCostEstimate = function () {
            const cost = estimateCost();
            $('.ace-cost-estimate-value').text('$' + cost);
        };

        // Update cost on any change
        $('#ace-max-tokens, #ace-include-tables, #ace-include-charts, #ace-generate-featured-image, #ace-generate-inline-images').on('change input', updateCostEstimate);
        updateCostEstimate();

        // ===== Smart Defaults (localStorage) =====
        const saveDefaults = function () {
            const defaults = {
                length: $('#ace-length').val(),
                tone: $('#ace-tone').val(),
                language: $('#ace-language').val(),
                temperature: $('#ace-temperature').val(),
                maxTokens: $('#ace-max-tokens').val(),
                postType: $('#ace-post-type').val(),
                templateId: $('#ace-template').val()
            };
            localStorage.setItem('ace_defaults', JSON.stringify(defaults));
        };

        const loadDefaults = function () {
            const saved = localStorage.getItem('ace_defaults');
            if (saved) {
                const defaults = JSON.parse(saved);
                $('#ace-length').val(defaults.length);
                $('#ace-tone').val(defaults.tone);
                $('#ace-temperature').val(defaults.temperature).trigger('input');
                $('#ace-max-tokens').val(defaults.maxTokens).trigger('input');
                $('#ace-post-type').val(defaults.postType);
                $('#ace-template').val(defaults.templateId).trigger('change');
            }
        };

        loadDefaults();
        $('#ace-length, #ace-tone, #ace-temperature, #ace-max-tokens, #ace-post-type, #ace-template').on('change', saveDefaults);

        // ===== Quick Presets =====
        $('.ace-preset-btn').on('click', function () {
            const preset = $(this).data('preset');

            switch (preset) {
                case 'blog':
                    $('#ace-template').val('default');
                    $('#ace-length').val('medium');
                    $('#ace-tone').val('conversational');
                    $('#ace-include-tables').prop('checked', false);
                    $('#ace-include-charts').prop('checked', false);
                    break;
                case 'howto':
                    $('#ace-template').val('how_to');
                    $('#ace-length').val('long');
                    $('#ace-tone').val('friendly');
                    $('#ace-include-tables').prop('checked', true);
                    break;
                case 'review':
                    $('#ace-template').val('review');
                    $('#ace-length').val('long');
                    $('#ace-tone').val('authoritative');
                    $('#ace-include-tables').prop('checked', true);
                    $('#ace-include-charts').prop('checked', true);
                    break;
            }

            $('#ace-template').trigger('change');
            updateCostEstimate();
            showToast('Preset Applied', `${$(this).text()} settings loaded`, 'success');
        });

        // ===== Advanced Options Accordion =====
        $('.ace-accordion-toggle').on('click', function () {
            const $icon = $(this).find('.dashicons');
            const $options = $('#ace-advanced-options');

            $options.slideToggle(300);

            if ($icon.hasClass('dashicons-arrow-right')) {
                $icon.removeClass('dashicons-arrow-right').addClass('dashicons-arrow-down');
            } else {
                $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-right');
            }
        });

        // ===== Slider Value Updates =====
        $('#ace-temperature').on('input', function () {
            $('#ace-temperature-value').text($(this).val());
        });

        $('#ace-max-tokens').on('input', function () {
            $('#ace-max-tokens-value').text($(this).val());
        });

        // ===== Template Description Update =====
        $('#ace-template').on('change', function () {
            const templateDescriptions = aceAdmin.templates || {};
            const selectedTemplate = $(this).val();
            $('#ace-template-description').text(templateDescriptions[selectedTemplate] || '');
        });

        // ===== Schedule Date Visibility =====
        $('#ace-post-status').on('change', function () {
            if ($(this).val() === 'future') {
                $('#ace-schedule-row').slideDown();
            } else {
                $('#ace-schedule-row').slideUp();
            }
        });

        // ===== Article Generation =====
        $('#ace-generate-form').on('submit', function (e) {
            e.preventDefault();

            const topic = $('#ace-topic').val().trim();

            if (!topic) {
                showToast('Error', aceAdmin.strings.empty_topic, 'error');
                return;
            }

            // Disable button and show progress
            $('#ace-generate-btn').prop('disabled', true).text('Generating...');
            $('.ace-progress-container').addClass('active');
            updateProgress('validating');

            // Collect form data
            const formData = {
                action: 'ace_generate_article',
                nonce: aceAdmin.nonce,
                topic: topic,
                keywords: $('#ace-keywords').val(),
                length: $('#ace-length').val(),
                word_count: $('#ace-word-count').val(),
                tone: $('#ace-tone').val(),
                include_tables: $('#ace-include-tables').is(':checked') ? 1 : 0,
                include_charts: $('#ace-include-charts').is(':checked') ? 1 : 0,
                generate_featured_image: $('#ace-generate-featured-image').is(':checked') ? 1 : 0,
                generate_inline_images: $('#ace-generate-inline-images').is(':checked') ? 1 : 0,
                temperature: $('#ace-temperature').val(),
                max_tokens: $('#ace-max-tokens').val(),
                post_type: $('#ace-post-type').val(),
                categories: $('#ace-categories').val(),
                auto_tags: $('#ace-auto-tags').is(':checked') ? 1 : 0,
                custom_instructions: $('#ace-custom-instructions').val(),
                template_id: $('#ace-template').val(),
                post_status: $('#ace-post-status').val(),
                post_date: $('#ace-post-date').val()
            };

            updateProgress('calling_api');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: formData,
                success: function (response) {
                    updateProgress('finalizing');

                    setTimeout(function () {
                        $('.ace-progress-container').removeClass('active');
                        $('#ace-generate-btn').prop('disabled', false).text('Generate Article');

                        if (response.success) {
                            showToast('Success!', 'Article generated successfully', 'success');
                            displayResult(response.data);
                        } else {
                            showToast('Error', response.data.message || 'Generation failed', 'error');
                        }
                    }, 500);
                },
                error: function () {
                    $('.ace-progress-container').removeClass('active');
                    $('#ace-generate-btn').prop('disabled', false).text('Generate Article');
                    showToast('Error', 'Request failed. Please try again.', 'error');
                }
            }).progress(function () {
                updateProgress('processing');
            });
        });

        function displayResult(data) {
            const html = `
                <div class="ace-result-header">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3>Article Created Successfully</h3>
                </div>
                <div class="ace-result-body">
                    <div class="ace-result-meta">
                        <div class="ace-result-meta-item">
                            <div class="ace-result-meta-label">Post Title</div>
                            <div class="ace-result-meta-value">${data.post_title}</div>
                        </div>
                        <div class="ace-result-meta-item">
                            <div class="ace-result-meta-label">Tokens Used</div>
                            <div class="ace-result-meta-value">${data.tokens_used}</div>
                        </div>
                        <div class="ace-result-meta-item">
                            <div class="ace-result-meta-label">Est. Cost</div>
                            <div class="ace-result-meta-value">$${data.estimated_cost}</div>
                        </div>
                    </div>
                    <p>
                        <a href="${data.edit_link}" class="button button-primary">Edit Post</a>
                        <a href="${data.view_link}" class="button" target="_blank">View Post</a>
                    </p>
                </div>
            `;

            $('#ace-result').html(html).slideDown();
        }

        // ===== API Key Testing =====
        $('#ace-test-api-key').on('click', function (e) {
            e.preventDefault();

            const $btn = $(this);
            const $result = $('#ace-api-test-result');

            $btn.prop('disabled', true).text('Testing...');
            $result.text('');

            $.ajax({
                url: aceAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_test_api_key',
                    nonce: aceAdmin.nonce
                },
                success: function (response) {
                    $btn.prop('disabled', false).text('Test API Key');

                    if (response.success) {
                        $result.html('<span class="success">✓ API key is valid</span>');
                        showToast('Success', 'API key is working correctly', 'success');
                    } else {
                        $result.html('<span class="error">✗ ' + response.data.message + '</span>');
                        showToast('Error', response.data.message, 'error');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Test API Key');
                    $result.html('<span class="error">✗ Test failed</span>');
                    showToast('Error', 'Could not test API key', 'error');
                }
            });
        });
    });

})(jQuery);

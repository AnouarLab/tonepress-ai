/**
 * Onboarding Wizard JavaScript
 *
 * @package AI_Content_Engine
 */

(function ($) {
    'use strict';

    const WizardApp = {
        currentStep: '',
        formData: {},

        init() {
            this.bindEvents();
            this.initCurrentStep();
        },

        bindEvents() {
            // Next button.
            $(document).on('click', '.ace-wizard-next', this.handleNext.bind(this));

            // Back button.
            $(document).on('click', '.ace-wizard-back', this.handleBack.bind(this));

            // Provider selection.
            $(document).on('change', 'input[name="provider"]', this.handleProviderChange.bind(this));

            // Toggle API key visibility.
            $(document).on('click', '#toggle-api-key', this.toggleApiKey.bind(this));

            // Test connection.
            $(document).on('click', '#test-connection-btn', this.testConnection.bind(this));

            // Generate sample article.
            $(document).on('click', '#generate-sample-btn', this.generateSample.bind(this));

            // Regenerate article.
            $(document).on('click', '#regenerate-btn', this.resetFirstArticle.bind(this));

            // Voice tag selection.
            $(document).on('change', '.voice-tags input[type="checkbox"]', this.updateVoiceDescription.bind(this));
        },

        initCurrentStep() {
            this.currentStep = aceWizard.current_step;
        },

        handleNext() {
            const currentForm = this.getCurrentForm();

            if (!this.validateForm(currentForm)) {
                return;
            }

            const formData = this.getFormData(currentForm);
            this.saveStepData(this.currentStep, formData);
        },

        handleBack() {
            const steps = Object.keys(aceWizard.steps);
            const currentIndex = steps.indexOf(this.currentStep);

            if (currentIndex > 0) {
                const prevStep = steps[currentIndex - 1];
                window.location.href = `?page=ace-wizard&step=${prevStep}`;
            }
        },

        getCurrentForm() {
            return $('.ace-wizard-step form');
        },

        validateForm(form) {
            const requiredFields = form.find('[required]');
            let isValid = true;

            requiredFields.each(function () {
                const field = $(this);
                if (!field.val() || (field.is(':checkbox') && !field.is(':checked'))) {
                    field.addClass('error');
                    isValid = false;
                } else {
                    field.removeClass('error');
                }
            });

            if (!isValid) {
                alert(aceWizard.strings?.validation_error || 'Please fill in all required fields.');
            }

            return isValid;
        },

        getFormData(form) {
            const data = {};

            form.find('[name]').each(function () {
                const field = $(this);
                const name = field.attr('name');

                if (field.is(':checkbox')) {
                    if (name.includes('[]')) {
                        const baseName = name.replace('[]', '');
                        if (!data[baseName]) {
                            data[baseName] = [];
                        }
                        if (field.is(':checked')) {
                            data[baseName].push(field.val());
                        }
                    } else {
                        data[name] = field.is(':checked');
                    }
                } else if (field.is(':radio')) {
                    if (field.is(':checked')) {
                        data[name] = field.val();
                    }
                } else {
                    data[name] = field.val();
                }
            });

            return data;
        },

        saveStepData(step, data) {
            $.ajax({
                url: aceWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_wizard_save_step',
                    nonce: aceWizard.nonce,
                    step: step,
                    data: data
                },
                success: (response) => {
                    if (response.success) {
                        window.location.href = response.data.redirect;
                    } else {
                        alert(response.data.message || 'An error occurred.');
                    }
                },
                error: () => {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        handleProviderChange(e) {
            const provider = $(e.currentTarget).val();
            const providerNames = {
                'openai': 'OpenAI',
                'claude': 'Claude',
                'gemini': 'Gemini'
            };

            $('.provider-name').text(providerNames[provider] || provider);

            // Update "Get API key" link.
            const links = {
                'openai': 'https://platform.openai.com/api-keys',
                'claude': 'https://console.anthropic.com/account/keys',
                'gemini': 'https://aistudio.google.com/app/apikey'
            };

            $('.get-api-key-link').attr('href', links[provider] || '#');

            // Show/hide provider-specific fields if any.
        },

        toggleApiKey() {
            const field = $('#api-key');
            const type = field.attr('type');

            if (type === 'password') {
                field.attr('type', 'text');
                $('#toggle-api-key .dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                field.attr('type', 'password');
                $('#toggle-api-key .dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        },

        testConnection() {
            const provider = $('input[name="provider"]:checked').val();
            const apiKey = $('#api-key').val();
            const button = $('#test-connection-btn');
            const resultDiv = $('.test-result');

            if (!provider || !apiKey) {
                resultDiv.html('<span class="error">Please select a provider and enter an API key.</span>');
                return;
            }

            button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
            resultDiv.html('');

            $.ajax({
                url: aceWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_wizard_test_api',
                    nonce: aceWizard.nonce,
                    provider: provider,
                    api_key: apiKey
                },
                success: (response) => {
                    if (response.success) {
                        resultDiv.html('<span class="success"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>');
                    } else {
                        resultDiv.html('<span class="error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.message + '</span>');
                    }
                },
                error: () => {
                    resultDiv.html('<span class="error">Connection failed. Please check your API key.</span>');
                },
                complete() {
                    button.prop('disabled', false).html('<span class="dashicons dashicons-admin-network"></span> Test Connection');
                }
            });
        },

        generateSample() {
            const topic = $('#article-topic').val();
            const button = $('#generate-sample-btn');
            const progressDiv = $('#generation-progress');
            const previewDiv = $('#article-preview');

            if (!topic) {
                alert('Please enter a topic.');
                return;
            }

            button.prop('disabled', true);
            progressDiv.show();
            previewDiv.hide();

            // Simulate progress steps.
            this.animateProgress(0, 100, 15000);

            $.ajax({
                url: aceWizard.ajax_url,
                type: 'POST',
                data: {
                    action: 'ace_wizard_generate_sample',
                    nonce: aceWizard.nonce,
                    topic: topic
                },
                success: (response) => {
                    if (response.success) {
                        this.showPreview(response.data);
                        progressDiv.hide();
                    } else {
                        alert(response.data.message || 'Generation failed.');
                        progressDiv.hide();
                        button.prop('disabled', false);
                    }
                },
                error: () => {
                    alert('An error occurred. Please try again.');
                    progressDiv.hide();
                    button.prop('disabled', false);
                }
            });
        },

        animateProgress(current, target, duration) {
            const steps = $('.progress-step');
            const fill = $('.progress-fill');
            const increment = (target - current) / (duration / 100);

            const interval = setInterval(() => {
                current += increment;
                fill.css('width', current + '%');

                // Update step states.
                if (current > 33) {
                    steps.eq(1).addClass('active');
                }
                if (current > 66) {
                    steps.eq(2).addClass('active');
                }

                if (current >= target) {
                    clearInterval(interval);
                }
            }, 100);
        },

        showPreview(data) {
            $('.preview-title').text(data.title);
            $('.preview-excerpt').html(data.excerpt);
            $('#edit-article-link').attr('href', data.edit_url);
            $('#article-preview').fadeIn();
        },

        resetFirstArticle() {
            $('#article-topic').val('');
            $('#generation-progress').hide();
            $('#article-preview').hide();
            $('#generate-sample-btn').prop('disabled', false);
        },

        updateVoiceDescription() {
            const selectedVoices = [];
            $('.voice-tags input:checked').each(function () {
                selectedVoices.push($(this).val());
            });

            // You could update a hidden field or preview here.
        }
    };

    // Initialize when document is ready.
    $(document).ready(() => {
        WizardApp.init();
    });

})(jQuery);

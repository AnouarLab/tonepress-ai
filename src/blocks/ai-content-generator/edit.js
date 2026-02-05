/**
 * AI Content Generator Block - Edit Component
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
    useBlockProps,
    InspectorControls,
    RichText,
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    SelectControl,
    Button,
    ToggleControl,
    Spinner,
    Notice,
    Placeholder,
} from '@wordpress/components';
import { starFilled } from '@wordpress/icons';

import './editor.css';

export default function Edit({ attributes, setAttributes }) {
    const {
        topic,
        keywords,
        length,
        tone,
        includeTables,
        includeCharts,
        generatedContent,
        generatedTitle,
    } = attributes;

    const [isGenerating, setIsGenerating] = useState(false);
    const [error, setError] = useState(null);
    const [templates, setTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState('');
    const blockProps = useBlockProps();

    /**
     * Fetch templates on component mount
     */
    useEffect(() => {
        apiFetch({ path: '/wp-json/ace/v1/templates' })
            .then(response => {
                if (response.success && response.templates) {
                    setTemplates(response.templates);
                }
            })
            .catch(err => {
                console.error('Failed to load templates:', err);
            });
    }, []);

    /**
     * Apply template to form fields
     */
    const applyTemplate = (templateId) => {
        const template = templates.find(t => t.id === templateId);
        if (template) {
            setAttributes({
                topic: template.topic_pattern || '',
                keywords: template.keywords || '',
                tone: template.tone || 'professional',
                length: template.length || 'medium',
            });
        }
    };

    /**
     * Generate AI content via REST API
     */
    const handleGenerate = async () => {
        if (!topic.trim()) {
            setError(__('Please enter a topic', 'ai-content-engine'));
            return;
        }

        setIsGenerating(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: '/ace/v1/generate',
                method: 'POST',
                data: {
                    topic,
                    keywords: keywords.split(',').map(k => k.trim()).filter(Boolean),
                    length,
                    tone,
                    include_tables: includeTables,
                    include_charts: includeCharts,
                },
            });

            if (response.success && response.data) {
                setAttributes({
                    generatedContent: response.data.content_html || '',
                    generatedTitle: response.data.title || '',
                });
            } else {
                throw new Error(response.message || __('Generation failed', 'ai-content-engine'));
            }
        } catch (err) {
            setError(err.message || __('An error occurred', 'ai-content-engine'));
        } finally {
            setIsGenerating(false);
        }
    };

    /**
     * Insert generated content into post
     */
    const handleInsert = () => {
        // This will be handled by converting the block content
        // The generated content is already in the block attributes
        // WordPress will handle the insertion when the block is saved
    };

    /**
     * Clear generated content
     */
    const handleClear = () => {
        setAttributes({
            generatedContent: '',
            generatedTitle: '',
        });
        setError(null);
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Content Settings', 'ai-content-engine')}>
                    <SelectControl
                        label={__('Article Length', 'ai-content-engine')}
                        value={length}
                        options={[
                            { label: __('Short (800-1200 words)', 'ai-content-engine'), value: 'short' },
                            { label: __('Medium (1200-1800 words)', 'ai-content-engine'), value: 'medium' },
                            { label: __('Long (1800-2500+ words)', 'ai-content-engine'), value: 'long' },
                        ]}
                        onChange={(value) => setAttributes({ length: value })}
                    />

                    <SelectControl
                        label={__('Writing Tone', 'ai-content-engine')}
                        value={tone}
                        options={[
                            { label: __('Professional', 'ai-content-engine'), value: 'professional' },
                            { label: __('Conversational', 'ai-content-engine'), value: 'conversational' },
                            { label: __('Authoritative', 'ai-content-engine'), value: 'authoritative' },
                            { label: __('Friendly', 'ai-content-engine'), value: 'friendly' },
                            { label: __('Academic', 'ai-content-engine'), value: 'academic' },
                        ]}
                        onChange={(value) => setAttributes({ tone: value })}
                    />

                    <ToggleControl
                        label={__('Include Data Tables', 'ai-content-engine')}
                        checked={includeTables}
                        onChange={(value) => setAttributes({ includeTables: value })}
                    />

                    <ToggleControl
                        label={__('Include Charts', 'ai-content-engine')}
                        checked={includeCharts}
                        onChange={(value) => setAttributes({ includeCharts: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!generatedContent ? (
                    <Placeholder
                        icon={starFilled}
                        label={__('AI Content Generator', 'ai-content-engine')}
                        instructions={__('Generate high-quality, SEO-optimized content with AI', 'ai-content-engine')}
                    >
                        <div className="ace-generator-form">
                            <TextControl
                                label={__('Topic or Title', 'ai-content-engine')}
                                value={topic}
                                onChange={(value) => setAttributes({ topic: value })}
                                placeholder={__('Enter your article topic...', 'ai-content-engine')}
                                disabled={isGenerating}
                            />

                            <TextControl
                                label={__('Keywords (comma-separated)', 'ai-content-engine')}
                                value={keywords}
                                onChange={(value) => setAttributes({ keywords: value })}
                                placeholder={__('AI, content, WordPress...', 'ai-content-engine')}
                                disabled={isGenerating}
                            />

                            {error && (
                                <Notice status="error" isDismissible={false}>
                                    {error}
                                </Notice>
                            )}

                            <Button
                                variant="primary"
                                onClick={handleGenerate}
                                disabled={isGenerating || !topic.trim()}
                                className="ace-generate-button"
                            >
                                {isGenerating ? (
                                    <>
                                        <Spinner /> {__('Generating...', 'ai-content-engine')}
                                    </>
                                ) : (
                                    __('Generate Content', 'ai-content-engine')
                                )}
                            </Button>
                        </div>
                    </Placeholder>
                ) : (
                    <div className="ace-generated-content">
                        <div className="ace-content-header">
                            <h3>{__('Generated Content', 'ai-content-engine')}</h3>
                            <div className="ace-content-actions">
                                <Button
                                    variant="secondary"
                                    onClick={handleClear}
                                    disabled={isGenerating}
                                >
                                    {__('Clear', 'ai-content-engine')}
                                </Button>
                                <Button
                                    variant="primary"
                                    onClick={handleGenerate}
                                    disabled={isGenerating}
                                >
                                    {__('Regenerate', 'ai-content-engine')}
                                </Button>
                            </div>
                        </div>

                        {generatedTitle && (
                            <div className="ace-generated-title">
                                <strong>{__('Suggested Title:', 'ai-content-engine')}</strong> {generatedTitle}
                            </div>
                        )}

                        <div
                            className="ace-content-preview"
                            dangerouslySetInnerHTML={{ __html: generatedContent }}
                        />

                        <Notice status="info" isDismissible={false}>
                            {__('This content will be inserted when you save the block. You can edit it further in the editor.', 'ai-content-engine')}
                        </Notice>
                    </div>
                )}
            </div>
        </>
    );
}

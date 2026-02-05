/**
 * AI Content Generator Block
 * 
 * Gutenberg block for generating AI content directly in the editor.
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { starFilled as icon } from '@wordpress/icons';

import edit from './edit';
import save from './save';
import metadata from './block.json';

/**
 * Register the AI Content Generator block.
 */
registerBlockType(metadata.name, {
    ...metadata,
    icon,
    edit,
    save,
});

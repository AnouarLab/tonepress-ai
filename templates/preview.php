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
 * Theme-accurate preview template for Chat Builder.
 *
 * @package AI_Content_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token = get_query_var( 'ace_preview_token' );
$token = $token ? sanitize_text_field( wp_unslash( $token ) ) : '';

if ( empty( $token ) ) {
	wp_die( esc_html__( 'Missing preview token.', 'ai-content-engine' ) );
}

$data = get_transient( 'ace_chat_preview_' . $token );
if ( empty( $data ) || ! is_array( $data ) ) {
	wp_die( esc_html__( 'Preview expired or invalid.', 'ai-content-engine' ) );
}

if ( (int) ( $data['user_id'] ?? 0 ) !== get_current_user_id() ) {
	wp_die( esc_html__( 'Preview not authorized.', 'ai-content-engine' ) );
}

nocache_headers();
header( 'X-Robots-Tag: noindex, nofollow', true );

$title   = $data['title'] ?? __( 'Preview', 'ai-content-engine' );
$content = $data['content'] ?? '';

// Output a simple, standalone preview page that doesn't rely on theme templates.
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> - Preview</title>
	<?php wp_head(); ?>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			line-height: 1.7;
			color: #1a1a1a;
			background: #fff;
			margin: 0;
			padding: 0;
		}
		.ace-preview-container {
			max-width: 760px;
			margin: 0 auto;
			padding: 40px 24px;
		}
		.ace-preview-container h1 {
			font-size: 2.25rem;
			font-weight: 700;
			line-height: 1.2;
			margin: 0 0 24px;
			color: #0f172a;
		}
		.ace-preview-container h2 {
			font-size: 1.5rem;
			font-weight: 600;
			margin: 32px 0 16px;
			color: #1e293b;
		}
		.ace-preview-container h3 {
			font-size: 1.25rem;
			font-weight: 600;
			margin: 24px 0 12px;
			color: #334155;
		}
		.ace-preview-container p {
			margin: 0 0 16px;
			color: #374151;
		}
		.ace-preview-container ul, .ace-preview-container ol {
			margin: 0 0 16px;
			padding-left: 24px;
		}
		.ace-preview-container li {
			margin-bottom: 8px;
		}
		.ace-preview-container strong {
			font-weight: 600;
		}
		.ace-preview-container blockquote {
			border-left: 4px solid #e5e7eb;
			margin: 24px 0;
			padding: 12px 20px;
			background: #f9fafb;
			color: #4b5563;
		}
		.ace-preview-container table {
			width: 100%;
			border-collapse: collapse;
			margin: 24px 0;
		}
		.ace-preview-container th, .ace-preview-container td {
			border: 1px solid #e5e7eb;
			padding: 12px;
			text-align: left;
		}
		.ace-preview-container th {
			background: #f3f4f6;
			font-weight: 600;
		}
		/* FAQ Accordion */
		.ace-faq { margin: 24px 0; }
		.ace-faq details {
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			margin-bottom: 8px;
			background: #fff;
		}
		.ace-faq summary {
			padding: 14px 16px;
			font-weight: 600;
			cursor: pointer;
			list-style: none;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.ace-faq summary::after { content: '+'; font-size: 18px; color: #6b7280; }
		.ace-faq details[open] summary::after { content: '−'; }
		.ace-faq details > *:not(summary) { padding: 0 16px 14px; margin: 0; color: #4b5563; }
		/* Callout Boxes */
		.ace-callout {
			padding: 16px 20px;
			border-radius: 8px;
			margin: 20px 0;
			border-left: 4px solid;
		}
		.ace-callout-info { background: #eff6ff; border-color: #3b82f6; }
		.ace-callout-warning { background: #fffbeb; border-color: #f59e0b; }
		.ace-callout-tip { background: #ecfdf5; border-color: #10b981; }
		.ace-callout-note { background: #f5f5f5; border-color: #6b7280; }
		/* Key Takeaways */
		.ace-takeaways {
			background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
			border: 1px solid #86efac;
			border-radius: 12px;
			padding: 20px 24px;
			margin: 28px 0;
		}
		.ace-takeaways > strong:first-child {
			display: block;
			font-size: 14px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			color: #15803d;
			margin-bottom: 12px;
		}
		.ace-takeaways ul { margin: 0; padding-left: 20px; }
		.ace-takeaways li { margin-bottom: 6px; color: #166534; }
		/* Table of Contents */
		.ace-toc {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 20px;
			margin: 24px 0;
		}
		.ace-toc > strong:first-child { display: block; margin-bottom: 10px; color: #1e293b; }
		.ace-toc ul { margin: 0; padding-left: 18px; }
		.ace-toc li { margin-bottom: 6px; }
		.ace-toc a { color: #3b82f6; text-decoration: none; }
		.ace-toc a:hover { text-decoration: underline; }
		/* Pros/Cons */
		.ace-proscons {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
			margin: 24px 0;
		}
		.ace-pros, .ace-cons {
			padding: 16px;
			border-radius: 10px;
		}
		.ace-pros { background: #f0fdf4; border: 1px solid #86efac; }
		.ace-cons { background: #fef2f2; border: 1px solid #fca5a5; }
		.ace-pros strong { color: #15803d; }
		.ace-cons strong { color: #dc2626; }
		.ace-pros ul, .ace-cons ul { margin: 8px 0 0; padding-left: 18px; }
		/* Stat Highlight */
		.ace-stat {
			text-align: center;
			padding: 24px;
			background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
			border-radius: 12px;
			margin: 24px 0;
			color: white;
		}
		.ace-stat-number { display: block; font-size: 3rem; font-weight: 700; line-height: 1; }
		.ace-stat-label { font-size: 14px; opacity: 0.9; }
		/* Author Bio */
		.ace-author {
			display: flex;
			gap: 16px;
			align-items: flex-start;
			padding: 20px;
			background: #f8fafc;
			border-radius: 12px;
			margin: 32px 0;
			border: 1px solid #e2e8f0;
		}
		.ace-author-avatar { width: 56px; height: 56px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
		.ace-author-name { font-weight: 600; margin-bottom: 4px; }
		.ace-author-bio { margin: 0; font-size: 14px; color: #64748b; }
		/* Image Placeholder */
		.ace-image-placeholder {
			background: #f1f5f9;
			border: 2px dashed #cbd5e1;
			border-radius: 10px;
			padding: 40px 20px;
			text-align: center;
			margin: 24px 0;
		}
		.ace-placeholder-icon { font-size: 32px; margin-bottom: 8px; }
		.ace-image-placeholder figcaption { font-size: 13px; color: #64748b; font-style: italic; margin: 0; }
		/* Badge */
		.ace-preview-badge {
			display: inline-block;
			background: #dbeafe;
			color: #1e40af;
			font-size: 12px;
			font-weight: 500;
			padding: 4px 10px;
			border-radius: 999px;
			margin-bottom: 16px;
		}
	</style>
</head>
<body>
	<div class="ace-preview-container">
		<span class="ace-preview-badge">AI Generated Preview</span>
		<?php echo wp_kses_post( $content ); ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
<?php

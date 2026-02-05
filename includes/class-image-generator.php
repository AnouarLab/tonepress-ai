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
 * Image Generator class for DALL-E image generation.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Image_Generator
 *
 * Handles AI-powered image generation using OpenAI's DALL-E.
 */
class Image_Generator {

	/**
	 * OpenAI Images API endpoint.
	 */
	const API_ENDPOINT = 'https://api.openai.com/v1/images/generations';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Constructor.
	 *
	 * @param string $api_key API key (encrypted).
	 */
	public function __construct( $api_key = null ) {
		if ( null === $api_key ) {
			$api_key = get_option( 'ace_openai_api_key', '' );
		}

		$this->api_key = Security::decrypt_api_key( $api_key );
	}

	/**
	 * Generate an image using DALL-E.
	 *
	 * @param string $prompt Image generation prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Array with image URL or WP_Error on failure.
	 */
	public function generate_image( $prompt, $options = array() ) {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'missing_api_key',
				__( 'OpenAI API key is not configured.', 'tonepress-ai' )
			);
		}

		// Build request payload.
		$payload = array(
			'model'   => $options['model'] ?? 'dall-e-3',
			'prompt'  => $this->refine_prompt( $prompt ),
			'n'       => 1,
			'size'    => $options['size'] ?? '1024x1024',
			'quality' => $options['quality'] ?? 'standard',
			'style'   => $options['style'] ?? 'vivid',
		);

		// Make the API request.
		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'api_request_failed',
				sprintf(
					/* translators: %s: Error message */
					__( 'Image generation request failed: %s', 'tonepress-ai' ),
					$response->get_error_message()
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			$error_data = json_decode( $response_body, true );
			$error_message = $error_data['error']['message'] ?? __( 'Unknown API error', 'tonepress-ai' );

			return new \WP_Error( 'api_error', $error_message, array( 'status' => $response_code ) );
		}

		// Parse the response.
		$data = json_decode( $response_body, true );

		if ( ! isset( $data['data'][0]['url'] ) ) {
			return new \WP_Error(
				'invalid_response',
				__( 'Invalid response from OpenAI Images API.', 'tonepress-ai' )
			);
		}

		return array(
			'url'             => $data['data'][0]['url'],
			'revised_prompt'  => $data['data'][0]['revised_prompt'] ?? $prompt,
		);
	}

	/**
	 * Generate featured image for an article.
	 *
	 * @param string $title Article title.
	 * @param string $topic Article topic/description.
	 * @param array  $options Image options.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public function generate_featured_image( $title, $topic, $options = array() ) {
		// Build prompt for featured image.
		$prompt = $this->build_featured_image_prompt( $title, $topic, $options );

		// Generate the image.
		$result = $this->generate_image( $prompt, $options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Download and save to media library.
		$attachment_id = $this->save_to_media_library(
			$result['url'],
			$title,
			$result['revised_prompt']
		);

		return $attachment_id;
	}

	/**
	 * Generate inline images for article content.
	 *
	 * @param string $content Article content (HTML).
	 * @param array  $sections Content sections for context.
	 * @param array  $options Image options.
	 * @return array Array of generated image data.
	 */
	public function generate_inline_images( $content, $sections = array(), $options = array() ) {
		$images = array();
		$max_images = $options['max_inline_images'] ?? 3;

		// Extract logical insertion points (after H2/H3 sections).
		$insertion_points = $this->find_image_insertion_points( $content, $sections );

		// Limit to max images.
		$insertion_points = array_slice( $insertion_points, 0, $max_images );

		foreach ( $insertion_points as $point ) {
			// Build contextual prompt.
			$prompt = $this->build_inline_image_prompt( $point['context'], $options );

			// Generate image.
			$result = $this->generate_image( $prompt, $options );

			if ( ! is_wp_error( $result ) ) {
				// Save to media library.
				$attachment_id = $this->save_to_media_library(
					$result['url'],
					$point['heading'] ?? 'Article Image',
					$result['revised_prompt']
				);

				if ( ! is_wp_error( $attachment_id ) ) {
					$images[] = array(
						'attachment_id' => $attachment_id,
						'url'           => wp_get_attachment_url( $attachment_id ),
						'position'      => $point['position'],
						'context'       => $point['heading'],
						'alt_text'      => $this->generate_alt_text( $point['context'] ),
					);
				}
			}

			// Add delay to respect rate limits.
			sleep( 2 );
		}

		return $images;
	}

	/**
	 * Save image from URL to WordPress media library.
	 *
	 * @param string $image_url Image URL.
	 * @param string $title Image title.
	 * @param string $description Image description.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	private function save_to_media_library( $image_url, $title, $description = '' ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download image to temp file.
		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Prepare file array.
		$file_array = array(
			'name'     => sanitize_file_name( $title ) . '-' . time() . '.png',
			'tmp_name' => $temp_file,
		);

		// Upload to media library.
		$attachment_id = media_handle_sideload( $file_array, 0 );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			@unlink( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Update image metadata.
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
		
		wp_update_post(
			array(
				'ID'           => $attachment_id,
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => sanitize_textarea_field( $description ),
			)
		);

		// Mark as AI-generated.
		update_post_meta( $attachment_id, '_ace_generated_image', true );
		update_post_meta( $attachment_id, '_ace_image_prompt', $description );

		return $attachment_id;
	}

	/**
	 * Build prompt for featured image.
	 *
	 * @param string $title Article title.
	 * @param string $topic Article topic.
	 * @param array  $options Options.
	 * @return string Image prompt.
	 */
	private function build_featured_image_prompt( $title, $topic, $options = array() ) {
		$style = $options['image_style'] ?? 'modern professional';

		$prompt = "Create a {$style} featured image for an article titled: '{$title}'. ";
		$prompt .= "The image should be visually appealing, relevant to the topic, and suitable as a blog header. ";
		$prompt .= "Do not include any text or typography in the image.";

		return apply_filters( 'ace_featured_image_prompt', $prompt, $title, $topic );
	}

	/**
	 * Build prompt for inline image.
	 *
	 * @param string $context Surrounding content context.
	 * @param array  $options Options.
	 * @return string Image prompt.
	 */
	private function build_inline_image_prompt( $context, $options = array() ) {
		$style = $options['image_style'] ?? 'clean professional';

		$prompt = "Create a {$style} image to illustrate: {$context}. ";
		$prompt .= "The image should be clear, relevant, and enhance understanding of the content. ";
		$prompt .= "Avoid text in the image.";

		return apply_filters( 'ace_inline_image_prompt', $prompt, $context );
	}

	/**
	 * Find logical insertion points for inline images.
	 *
	 * @param string $content HTML content.
	 * @param array  $sections Content sections.
	 * @return array Insertion points with context.
	 */
	private function find_image_insertion_points( $content, $sections = array() ) {
		$points = array();

		// Extract H2 and H3 headings as insertion points.
		preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>/i', $content, $matches, PREG_OFFSET_CAPTURE );

		foreach ( $matches[1] as $index => $match ) {
			$heading = wp_strip_all_tags( $match[0] );
			$position = $matches[0][ $index ][1];

			// Get context (next paragraph or two).
			$after_heading = substr( $content, $position + strlen( $matches[0][ $index ][0] ) );
			preg_match( '/<p[^>]*>(.*?)<\/p>/is', $after_heading, $context_match );

			$context = isset( $context_match[1] ) ? wp_strip_all_tags( $context_match[1] ) : $heading;
			$context = wp_trim_words( $context, 30 );

			$points[] = array(
				'heading'  => $heading,
				'position' => $position,
				'context'  => $heading . '. ' . $context,
			);
		}

		return $points;
	}

	/**
	 * Generate SEO-friendly alt text for an image.
	 *
	 * @param string $context Image context.
	 * @return string Alt text.
	 */
	private function generate_alt_text( $context ) {
		// Use first sentence or truncated context.
		$alt_text = wp_trim_words( $context, 12 );
		return sanitize_text_field( $alt_text );
	}

	/**
	 * Refine and optimize the image prompt.
	 *
	 * @param string $prompt Original prompt.
	 * @return string Refined prompt.
	 */
	private function refine_prompt( $prompt ) {
		// Ensure prompt is descriptive and within limits (max 4000 chars for DALL-E 3).
		$prompt = substr( $prompt, 0, 4000 );

		// Add quality modifiers if not present.
		if ( strpos( $prompt, 'high quality' ) === false && 
			 strpos( $prompt, 'professional' ) === false ) {
			$prompt .= ' High quality, professional image.';
		}

		return $prompt;
	}

	/**
	 * Inject inline images into content.
	 *
	 * @param string $content Original content.
	 * @param array  $images Generated images array.
	 * @return string Content with images injected.
	 */
	public function inject_images_into_content( $content, $images ) {
		if ( empty( $images ) ) {
			return $content;
		}

		// Sort images by position (descending to avoid offset issues).
		usort( $images, function( $a, $b ) {
			return $b['position'] - $a['position'];
		});

		foreach ( $images as $image ) {
			$img_html = sprintf(
				'<figure class="ace-inline-image"><img src="%s" alt="%s" /><figcaption>%s</figcaption></figure>',
				esc_url( $image['url'] ),
				esc_attr( $image['alt_text'] ),
				esc_html( $image['context'] )
			);

			// Insert after the heading at this position.
			$offset = $image['position'];
			$content = substr_replace( $content, $img_html, $offset, 0 );
		}

		return $content;
	}
}

<?php
/**
 * Security class for input/output sanitization and API key encryption.
 *
 * @package AI_Content_Engine
 */

namespace ACE;

/**
 * Class Security
 *
 * Handles all security-related functionality including nonce verification,
 * input sanitization, output escaping, and API key encryption.
 */
class Security {

	/**
	 * Generate a nonce for a given action.
	 *
	 * @param string $action Action name.
	 * @return string The nonce.
	 */
	public static function create_nonce( $action = 'ace_admin_action' ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify a nonce for a given action.
	 *
	 * @param string $nonce  The nonce to verify.
	 * @param string $action Action name.
	 * @return bool Whether the nonce is valid.
	 */
	public static function verify_nonce( $nonce, $action = 'ace_admin_action' ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Verify nonce from POST request and die if invalid.
	 *
	 * @param string $action Action name.
	 */
	public static function verify_admin_nonce( $action = 'ace_admin_action' ) {
		if ( ! isset( $_POST['ace_nonce'] ) || ! self::verify_nonce( sanitize_text_field( wp_unslash( $_POST['ace_nonce'] ) ), $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ai-content-engine' ) );
		}
	}

	/**
	 * Encrypt an API key using OpenSSL (or fallback to base64).
	 *
	 * @param string $api_key The API key to encrypt.
	 * @return string The encrypted API key.
	 */
	public static function encrypt_api_key( $api_key ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// Fallback to base64 encoding (less secure, but better than plaintext).
			return base64_encode( $api_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}

		$cipher = 'AES-256-CBC';
		$key    = self::get_encryption_key();
		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $cipher ) );

		$encrypted = openssl_encrypt( $api_key, $cipher, $key, 0, $iv );

		// Return encrypted string with IV prepended.
		return base64_encode( $encrypted . '::' . base64_encode( $iv ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt an API key using OpenSSL (or fallback from base64).
	 *
	 * @param string $encrypted The encrypted API key.
	 * @return string|false The decrypted API key, or false on failure.
	 */
	public static function decrypt_api_key( $encrypted ) {
		if ( empty( $encrypted ) ) {
			return false;
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			// Fallback from base64 encoding.
			return base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		$encrypted = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( strpos( $encrypted, '::' ) === false ) {
			return false;
		}

		list( $encrypted_data, $iv ) = explode( '::', $encrypted, 2 );
		$iv                          = base64_decode( $iv ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$cipher = 'AES-256-CBC';
		$key    = self::get_encryption_key();

		return openssl_decrypt( $encrypted_data, $cipher, $key, 0, $iv );
	}

	/**
	 * Get the encryption key from WordPress salts.
	 *
	 * @return string The encryption key.
	 */
	private static function get_encryption_key() {
		if ( ! defined( 'ACE_ENCRYPTION_KEY' ) ) {
			define( 'ACE_ENCRYPTION_KEY', wp_salt( 'auth' ) );
		}
		return hash( 'sha256', ACE_ENCRYPTION_KEY, true );
	}

	/**
	 * Sanitize user prompt input to prevent prompt injection attacks.
	 *
	 * @param string $input The user input to sanitize.
	 * @return string Sanitized input.
	 */
	public static function sanitize_user_prompt( $input ) {
		// Remove any attempts to inject system instructions.
		$patterns = array(
			'/\bignore\s+previous\s+instructions\b/i',
			'/\bsystem\s*:\s*/i',
			'/\bassistant\s*:\s*/i',
			'/\buser\s*:\s*/i',
			'/\b<\s*script\b/i',
			'/\bjavascript\s*:/i',
			'/\bon\w+\s*=/i', // Event handlers like onclick=
		);

		$input = preg_replace( $patterns, '', $input );

		// Sanitize as textarea field.
		return sanitize_textarea_field( $input );
	}

	/**
	 * Sanitize keywords array.
	 *
	 * @param array|string $keywords Keywords to sanitize.
	 * @return array Sanitized keywords array.
	 */
	public static function sanitize_keywords( $keywords ) {
		if ( is_string( $keywords ) ) {
			// Split by comma if string.
			$keywords = explode( ',', $keywords );
		}

		if ( ! is_array( $keywords ) ) {
			return array();
		}

		return array_filter( array_map( 'sanitize_text_field', $keywords ) );
	}

	/**
	 * Get allowed HTML tags for AI-generated content.
	 *
	 * @return array Allowed HTML tags and attributes.
	 */
	public static function get_allowed_html() {
		return array(
			// Headings.
			'h1'         => array( 'id' => true, 'class' => true ),
			'h2'         => array( 'id' => true, 'class' => true ),
			'h3'         => array( 'id' => true, 'class' => true ),
			'h4'         => array( 'id' => true, 'class' => true ),
			'h5'         => array( 'id' => true, 'class' => true ),
			'h6'         => array( 'id' => true, 'class' => true ),
			// Text.
			'p'          => array( 'class' => true ),
			'br'         => array(),
			'hr'         => array( 'class' => true ),
			'strong'     => array( 'class' => true ),
			'em'         => array( 'class' => true ),
			'b'          => array(),
			'i'          => array( 'class' => true ),
			'u'          => array(),
			// Lists.
			'ul'         => array( 'class' => true ),
			'ol'         => array( 'class' => true, 'start' => true ),
			'li'         => array( 'class' => true ),
			// Links.
			'a'          => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true ),
			// Tables.
			'table'      => array( 'class' => true, 'border' => true, 'cellpadding' => true, 'cellspacing' => true ),
			'thead'      => array(),
			'tbody'      => array(),
			'tfoot'      => array(),
			'tr'         => array( 'class' => true ),
			'th'         => array( 'class' => true, 'scope' => true, 'colspan' => true, 'rowspan' => true ),
			'td'         => array( 'class' => true, 'colspan' => true, 'rowspan' => true ),
			// Quotes and code.
			'blockquote' => array( 'class' => true, 'cite' => true ),
			'code'       => array( 'class' => true ),
			'pre'        => array( 'class' => true ),
			// Containers.
			'div'        => array( 'class' => true, 'id' => true, 'data-chart-id' => true ),
			'span'       => array( 'class' => true ),
			'section'    => array( 'class' => true, 'id' => true ),
			'aside'      => array( 'class' => true ),
			'nav'        => array( 'class' => true, 'aria-label' => true ),
			// FAQ accordion.
			'details'    => array( 'class' => true, 'open' => true ),
			'summary'    => array( 'class' => true ),
			// Figures and images.
			'figure'     => array( 'class' => true ),
			'figcaption' => array( 'class' => true ),
			'img'        => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true, 'loading' => true ),
			// Charts.
			'canvas'     => array( 'class' => true, 'id' => true, 'data-chart-id' => true, 'width' => true, 'height' => true ),
		);
	}

	/**
	 * Validate and sanitize AI-generated HTML content.
	 *
	 * @param string $html The HTML content to sanitize.
	 * @return string Sanitized HTML.
	 */
	public static function sanitize_ai_content( $html ) {
		return wp_kses( $html, self::get_allowed_html() );
	}

	/**
	 * Check if the current user has permission to generate content.
	 *
	 * @return bool Whether the user can generate content.
	 */
	public static function can_generate_content() {
		return current_user_can( 'publish_posts' );
	}

	/**
	 * Check if the current user has permission to manage settings.
	 *
	 * @return bool Whether the user can manage settings.
	 */
	public static function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}
}

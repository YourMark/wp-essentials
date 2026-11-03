<?php
/**
 * Emoji polyfill.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Drops the emoji detection script, its styles and its TinyMCE plugin.
 *
 * The inline script alone can be a quarter of a logged-out page, to polyfill
 * what every current browser renders natively.
 */
final class Emoji {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', [ self::class, 'remove' ] );
		add_filter( 'tiny_mce_plugins', [ self::class, 'remove_tinymce_plugin' ] );
	}

	/**
	 * Unhook the script, the styles and the image swaps in feeds and mail.
	 *
	 * @return void
	 */
	public static function remove(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	/**
	 * Remove the TinyMCE emoji plugin, which the actions above leave behind.
	 *
	 * @param mixed $plugins TinyMCE plugin names.
	 * @return string[]
	 */
	public static function remove_tinymce_plugin( $plugins ): array {
		return is_array( $plugins ) ? array_values( array_diff( $plugins, [ 'wpemoji' ] ) ) : [];
	}
}

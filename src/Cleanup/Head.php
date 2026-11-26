<?php
/**
 * Discovery links and URLs that only lead crawlers on.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Drops the REST, oEmbed and shortlink discovery links, in the head and as headers,
 * and WordPress 7.2's redirect to random content.
 *
 * They only point crawlers at duplicate URLs of the same page. The REST API and
 * oEmbed themselves keep working; the block editor and embeds need them.
 */
final class Head {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', [ self::class, 'remove_links' ] );
		add_filter( 'wp_enable_random_content_redirect', '__return_false' );
	}

	/**
	 * Unhook the links and the Link headers.
	 *
	 * @return void
	 */
	public static function remove_links(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	}
}

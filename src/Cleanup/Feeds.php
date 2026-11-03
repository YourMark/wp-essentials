<?php
/**
 * Feeds, for a site without a blog.
 *
 * @package YourMark\Essentials
 */

namespace YourMark\Essentials\Cleanup;

/**
 * Sends every feed home and drops the feed links, once a site says it has no blog.
 *
 * Off by default: a site with posts wants its feed. Without posts every feed
 * is an empty page that crawlers and feed readers still find.
 */
final class Feeds {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'redirect' ], 1 );
		add_action( 'init', [ self::class, 'remove_links' ] );
	}

	/**
	 * Whether the site turned its feeds off.
	 *
	 * @return bool
	 */
	private static function disabled(): bool {
		/**
		 * Filters whether the feeds are off.
		 *
		 * @param bool $disabled Default false.
		 */
		return (bool) apply_filters( 'yourmark/cleanup/disable_feeds', false );
	}

	/**
	 * Send a feed request to the home page, before WordPress builds the feed.
	 *
	 * @return void
	 */
	public static function redirect(): void {
		if ( ! is_feed() || ! self::disabled() ) {
			return;
		}

		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}

	/**
	 * Drop the feed links from the head.
	 *
	 * @return void
	 */
	public static function remove_links(): void {
		if ( ! self::disabled() ) {
			return;
		}

		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
}
